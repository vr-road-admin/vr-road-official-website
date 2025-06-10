<?php
  // --- Configuration ---
  // Your Outlook email address that will RECEIVE the contact messages
  $receiving_email_address = 'vr-road@outlook.com'; // IMPORTANT: This is where contact form submissions will go!

  // Your Outlook email address that will SEND the emails (same as for newsletter)
  $sending_email_address = 'vr-road@outlook.com';
  $sending_email_password = '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'; // YOUR APP PASSWORD HERE!
  $sending_email_display_name = 'VR-Road Website Contact Form'; // How the sender will appear

  // --- Load PHP Email Form Library ---
  if( file_exists($php_email_form = '../assets/vendor/php-email-form/php-email-form.php' )) {
    include( $php_email_form );
  } else {
    die( 'Unable to load the "PHP Email Form" Library!');
  }

  // --- Main Logic ---
  $contact = new PHP_Email_Form;
  $contact->ajax = true;

  $contact->to = $receiving_email_address;
  $contact->from_name = $_POST['name']; // Visitor's name
  $contact->from_email = $_POST['email']; // Visitor's email
  $contact->subject = "VR-Road Contact Form: " . $_POST['subject']; // Subject from visitor

  // SMTP configuration for sending the contact message
  $contact->smtp = array(
    'host' => 'smtp.office365.com',
    'username' => $sending_email_address,
    'password' => $sending_email_password,
    'port' => '587',
    'encryption' => 'tls' // Use 'tls' or 'ssl', 587 usually uses 'tls'
  );

  $contact->add_message( $_POST['name'], 'From');
  $contact->add_message( $_POST['email'], 'Email');
  if(isset($_POST['phone']) && !empty($_POST['phone'])) { // Added check for empty phone
    $contact->add_message( $_POST['phone'], 'Phone');
  }
  $contact->add_message( $_POST['message'], 'Message', 10);

  // Send the email and echo the response back to the frontend
  echo $contact->send();
?>