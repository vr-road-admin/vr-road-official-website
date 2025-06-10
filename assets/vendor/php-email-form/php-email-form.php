<?php

// Custom PHP Email Form Library Wrapper
// This file replaces the proprietary 'php-email-form.php' from BootstrapMade
// It uses the PHPMailer library for sending emails.

// IMPORTANT: Ensure PHPMailer is correctly installed at vr-road-official-website/PHPMailer/src/

// Include PHPMailer classes
// These paths are relative to THIS php-email-form.php file.
// If your PHPMailer installation is at /vr-road-official-website/PHPMailer/src/,
// then from /vr-road-official-website/assets/vendor/php-email-form/,
// the path is ../../../PHPMailer/src/
require __DIR__ . '/../../../PHPMailer/src/Exception.php';
require __DIR__ . '/../../../PHPMailer/src/PHPMailer.php';
require __DIR__ . '/../../../PHPMailer/src/SMTP.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
use PHPMailer\PHPMailer\SMTP;

class PHP_Email_Form {
    public $to;
    public $from_name;
    public $from_email;
    public $subject;
    public $ajax = false; // Not directly used by this wrapper for sending, but for original template's JS
    public $smtp = [];
    public $error = ''; // To store PHPMailer errors
    private $messages = []; // Stores the message content

    public function __construct() {
        // Constructor, can be empty or initialize properties
    }

    // This method is called by the original contact/newsletter scripts
    public function add_message($content, $label = '', $col_width = 100) {
        if (!empty($label)) {
            $this->messages[] = "<strong>" . htmlspecialchars($label) . ":</strong> " . htmlspecialchars($content);
        } else {
            $this->messages[] = htmlspecialchars($content);
        }
    }

    public function send() {
        $mail = new PHPMailer(true); // Enable exceptions

        try {
            // Server settings
            if (!empty($this->smtp)) {
                $mail->isSMTP();
                $mail->Host       = 'smtp.sendgrid.net'; // **UPDATED FOR SENDGRID**
                $mail->SMTPAuth   = true;
                $mail->Username   = 'apikey'; // **UPDATED FOR SENDGRID: Username is 'apikey'**
                $mail->Password   = $this->smtp['password'] ?? ''; // This gets the SendGrid API Key from newsletter.php/contact.php
                $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS; // **UPDATED FOR SENDGRID: Use TLS**
                $mail->Port       = 587; // **UPDATED FOR SENDGRID: Port is 587 for TLS**

                // Debug output (optional, uncomment for troubleshooting)
                $mail->SMTPDebug = SMTP::DEBUG_SERVER; // Keeping debug on for now
            } else {
                // If no SMTP settings, try to send via local mail() function (less reliable)
                $mail->isMail();
            }

            // Recipients
            $mail->setFrom($this->from_email, $this->from_name);
            $mail->addAddress($this->to);

            // Content
            $mail->isHTML(true); // Set email format to HTML
            $mail->Subject = $this->subject;
            $mail->Body    = implode('<br>', $this->messages); // Join messages with line breaks for HTML email
            $mail->AltBody = implode("\n", $this->messages); // Plain text for non-HTML mail clients

            $mail->send();
            return 'OK'; // Success message expected by the original template scripts
        } catch (Exception $e) {
            $this->error = $mail->ErrorInfo;
            // You might want to log this error more formally in a real application
            // error_log("Mailer Error: {$mail->ErrorInfo}");
            return "Error: {$mail->ErrorInfo}"; // Return error message
        }
    }
}
?>