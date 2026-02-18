<?php
/**
 * Email Utilities for RendeX
 */

// SMTP Credentials
if (!defined('SMTP_HOST')) define('SMTP_HOST', 'ssl://smtp.gmail.com');
if (!defined('SMTP_PORT')) define('SMTP_PORT', 465);
if (!defined('SMTP_USER')) define('SMTP_USER', 'rendex857@gmail.com');
if (!defined('SMTP_PASS')) define('SMTP_PASS', 'dhljrxkzvctpnzan'); // App Password

// Include PHPMailer
require_once __DIR__ . '/../phpmailserver/PHPMailer-master/PHPMailer-master/src/Exception.php';
require_once __DIR__ . '/../phpmailserver/PHPMailer-master/PHPMailer-master/src/PHPMailer.php';
require_once __DIR__ . '/../phpmailserver/PHPMailer-master/PHPMailer-master/src/SMTP.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
use PHPMailer\PHPMailer\SMTP;

/**
 * Send an email via SMTP using PHPMailer
 * @param string $to Recipient email
 * @param string $subject Email subject
 * @param string $body Email HTML body
 * @return bool|string True on success, error message on failure
 */
function send_smtp_email($to, $subject, $body) {
    $mail = new PHPMailer(true);

    try {
        // Server settings
        // $mail->SMTPDebug = 2;                      // Debugging
        // $mail->Debugoutput = function($str, $level) { file_put_contents(__DIR__ . '/../mail_debug.log', date('Y-m-d H:i:s'). "\t" . $str . "\n", FILE_APPEND | LOCK_EX); };
        
        $mail->isSMTP();
        $mail->Host       = 'smtp.gmail.com';
        $mail->SMTPAuth   = true;
        $mail->Username   = SMTP_USER;
        $mail->Password   = SMTP_PASS;
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS; // Enable implicit TLS encryption
        $mail->Port       = 465;

        // Recipients
        $mail->setFrom(SMTP_USER, 'RendeX');
        $mail->addAddress($to);

        // Content
        $mail->isHTML(true);
        $mail->Subject = $subject;
        $mail->Body    = $body;
        $mail->AltBody = strip_tags($body);

        $mail->send();
        file_put_contents(__DIR__ . '/../mail_debug.log', date('Y-m-d H:i:s') . "\tEmail sent successfully to $to\n", FILE_APPEND | LOCK_EX);
        return true;
    } catch (Exception $e) {
        // Return error message for debugging if needed, or log it
        $errorMsg = "Message could not be sent. Mailer Error: {$mail->ErrorInfo}";
        file_put_contents(__DIR__ . '/../mail_debug.log', date('Y-m-d H:i:s') . "\t" . $errorMsg . "\n", FILE_APPEND | LOCK_EX);
        error_log($errorMsg);
        return $errorMsg;
    }
}
?>
