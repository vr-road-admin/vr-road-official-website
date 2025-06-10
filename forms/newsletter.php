<?php
  // Ensure we use the correct namespaces if PHPMailer is integrated, though the existing library might handle this internally.
  // For now, we'll assume the PHP Email Form library handles its own dependencies.

  // --- Configuration ---
  // Load sensitive configurations from config.php (which is ignored by Git)
  require_once '../config.php';

  // Email sending settings from config.php
  $sending_email_address = SENDER_EMAIL_ADDRESS;
  $sending_email_password = SENDGRID_API_KEY;
  $sending_email_display_name = 'VR-Road Newsletter'; // How the sender will appear

  // Email to receive subscription notifications (from config.php, or define separately here if needed)
  $receiving_notification_email_address = RECEIVING_EMAIL_ADDRESS; // Uses the RECEIVING_EMAIL_ADDRESS defined in config.php

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

                      // 2. Send Confirmation Email to Subscriber using PHP Email Form's SMTP via SendGrid
                      $contact_subscriber = new PHP_Email_Form;
                      $contact_subscriber->ajax = true;

                      $contact_subscriber->to = $subscriber_email; // Send to the subscriber
                      $contact_subscriber->from_name = $sending_email_display_name;
                      $contact_subscriber->from_email = $sending_email_address;
                      $contact_subscriber->subject = 'Welcome to VR-Road Newsletter!';

                      // SMTP configuration for sending to subscriber (using SendGrid)
                      $contact_subscriber->smtp = array(
                          'host' => 'smtp.sendgrid.net', // Updated to SendGrid host
                          'username' => 'apikey',          // Updated to SendGrid username
                          'password' => $sending_email_password, // Uses API key from config.php
                          'port' => '587',
                          'encryption' => 'tls'
                      );

                      $contact_subscriber->add_message( 'Thank you for subscribing to the VR-Road newsletter! We\'re excited to share updates on simulator releases, VR driving events, training tips, and more.', 'Body', 10);
                      $contact_subscriber->add_message( 'Stay tuned for exciting news!', 'Body');
                      $contact_subscriber->add_message( 'Best regards, The VR-Road Team', 'Body');
                      $contact_subscriber->add_message( 'If you did not subscribe to this newsletter, please ignore this email or contact us at ' . $sending_email_address . '.', 'Footer');

                      if ($contact_subscriber->send()) {
                          // 3. Optional: Send Notification Email to yourself (if different from sending email)
                          if ($receiving_notification_email_address !== $sending_email_address) {
                            $contact_admin_notification = new PHP_Email_Form;
                            $contact_admin_notification->ajax = false;

                            $contact_admin_notification->to = $receiving_notification_email_address;
                            $contact_admin_notification->from_name = $sending_email_display_name;
                            $contact_admin_notification->from_email = $sending_email_address;
                            $contact_admin_notification->subject = "New Newsletter Subscription: " . $subscriber_email;

                            // SMTP configuration for sending notification (using SendGrid)
                            $contact_admin_notification->smtp = array(
                                'host' => 'smtp.sendgrid.net', // Updated to SendGrid host
                                'username' => 'apikey',          // Updated to SendGrid username
                                'password' => $sending_email_password, // Uses API key from config.php
                                'port' => '587',
                                'encryption' => 'tls'
                            );

                            $contact_admin_notification->add_message( $subscriber_email, 'New Subscriber Email');
                            $contact_admin_notification->send();
                          }
                          $response = "OK";
                      } else {
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