<?php
  // --- Configuration ---
  // Load sensitive configurations from config.php (which is ignored by Git)
  require_once '../config.php';

  // Your Outlook email address that will RECEIVE the contact messages (from config.php)
  $receiving_email_address = RECEIVING_EMAIL_ADDRESS;

  // Your Outlook email address that will SEND the emails (from config.php)
  $sending_email_address = SENDER_EMAIL_ADDRESS;
  $sending_email_password = SENDGRID_API_KEY;
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

  // SMTP configuration for sending the contact message (using SendGrid)
  $contact->smtp = array(
    'host' => 'smtp.sendgrid.net', // Updated to SendGrid host
    'username' => 'apikey',          // Updated to SendGrid username
    'password' => $sending_email_password, // Uses API key from config.php
    'port' => '587',
    'encryption' => 'tls'
  );

  $contact->add_message( $_POST['name'], 'From');
  $contact->add_message( $_POST['email'], 'Email');
  if(isset($_POST['phone']) && !empty($_POST['phone'])) {
    $contact->add_message( $_POST['phone'], 'Phone');
  }
  $contact->add_message( $_POST['message'], 'Message', 10);

  // Send the email and echo the response back to the frontend
  echo $contact->send();
?>