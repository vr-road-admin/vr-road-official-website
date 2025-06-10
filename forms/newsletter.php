<?php
  // Ensure we use the correct namespaces if PHPMailer is integrated, though the existing library might handle this internally.
  // For now, we'll assume the PHP Email Form library handles its own dependencies.

  // --- Configuration ---
  // Your Outlook email address that will SEND the emails (and receive subscription notifications if you want)
  $sending_email_address = 'vr-road@outlook.com';
  $sending_email_password = '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'; // YOUR APP PASSWORD HERE!
  $sending_email_display_name = 'VR-Road Newsletter'; // How the sender will appear

  // Email to receive subscription notifications (can be the same as sending_email_address)
  $receiving_notification_email_address = 'vr-road@outlook.com'; // Change this to your preferred notification email

  // Path to your subscribers file
  $subscribers_file = __DIR__ . '/../subscribers.txt';

  // --- Load PHP Email Form Library ---
  if( file_exists($php_email_form = '../assets/vendor/php-email-form/php-email-form.php' )) {
    include( $php_email_form );
  } else {
    die( 'Unable to load the "PHP Email Form" Library!');
  }

  // --- Main Logic ---
  $response = ""; // To store the response for the frontend

  if ($_SERVER["REQUEST_METHOD"] == "POST") {
      if (isset($_POST['email']) && !empty($_POST['email'])) {
          $subscriber_email = filter_var($_POST['email'], FILTER_SANITIZE_EMAIL);
          if (filter_var($subscriber_email, FILTER_VALIDATE_EMAIL)) {
              // Email is valid

              // 1. Save the Email to file
              $existing_emails = [];
              if (file_exists($subscribers_file)) {
                  $existing_emails = file($subscribers_file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
              }

              if (!in_array($subscriber_email, $existing_emails)) {
                  // Append the email to the file
                  if (file_put_contents($subscribers_file, $subscriber_email . PHP_EOL, FILE_APPEND | LOCK_EX)) {

                      // 2. Send Confirmation Email to Subscriber using PHP Email Form's SMTP via Outlook
                      $contact_subscriber = new PHP_Email_Form;
                      $contact_subscriber->ajax = true; // Or false if you don't need immediate AJAX response for this specific email

                      $contact_subscriber->to = $subscriber_email; // Send to the subscriber
                      $contact_subscriber->from_name = $sending_email_display_name;
                      $contact_subscriber->from_email = $sending_email_address;
                      $contact_subscriber->subject = 'Welcome to VR-Road Newsletter!';

                      // SMTP configuration for sending to subscriber
                      $contact_subscriber->smtp = array(
                          'host' => 'smtp.office365.com',
                          'username' => $sending_email_address,
                          'password' => $sending_email_password,
                          'port' => '587',
                          'encryption' => 'tls' // Use 'tls' or 'ssl', 587 usually uses 'tls'
                      );

                      $contact_subscriber->add_message( 'Thank you for subscribing to the VR-Road newsletter! We\'re excited to share updates on simulator releases, VR driving events, training tips, and more.', 'Body', 10);
                      $contact_subscriber->add_message( 'Stay tuned for exciting news!', 'Body');
                      $contact_subscriber->add_message( 'Best regards, The VR-Road Team', 'Body');
                      $contact_subscriber->add_message( 'If you did not subscribe to this newsletter, please ignore this email or contact us at ' . $sending_email_address . '.', 'Footer');

                      if ($contact_subscriber->send()) {
                          // 3. Optional: Send Notification Email to yourself (if different from sending email)
                          if ($receiving_notification_email_address !== $sending_email_address) {
                            $contact_admin_notification = new PHP_Email_Form;
                            $contact_admin_notification->ajax = false; // This is an internal notification, not an AJAX response

                            $contact_admin_notification->to = $receiving_notification_email_address;
                            $contact_admin_notification->from_name = $sending_email_display_name;
                            $contact_admin_notification->from_email = $sending_email_address;
                            $contact_admin_notification->subject = "New Newsletter Subscription: " . $subscriber_email;

                            // SMTP configuration for sending notification (can be the same or different)
                            $contact_admin_notification->smtp = array(
                                'host' => 'smtp.office365.com',
                                'username' => $sending_email_address,
                                'password' => $sending_email_password,
                                'port' => '587',
                                'encryption' => 'tls'
                            );

                            $contact_admin_notification->add_message( $subscriber_email, 'New Subscriber Email');
                            $contact_admin_notification->send(); // Send without checking success here, as primary goal (subscriber email) is met
                          }
                          $response = "OK"; // Success for the frontend
                      } else {
                          // If confirmation email fails, still acknowledge subscription if saved
                          $response = "Error: Subscription saved, but confirmation email could not be sent. Mailer Error: " . $contact_subscriber->error;
                          error_log("Newsletter confirmation email failed for: " . $subscriber_email . " Error: " . $contact_subscriber->error);
                      }

                  } else {
                      $response = "Error: Unable to save your subscription. Please try again.";
                  }
              } else {
                  $response = "You are already subscribed!";
              }

          } else {
              $response = "Error: Invalid email format.";
          }
      } else {
          $response = "Error: Email address is required.";
      }
  } else {
      $response = "Error: Invalid request method.";
  }

  echo $response;
?>