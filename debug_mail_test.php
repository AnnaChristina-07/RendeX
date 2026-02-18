<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Adjust path as we are in root
require_once __DIR__ . '/phpmailserver/PHPMailer-master/PHPMailer-master/src/Exception.php';
require_once __DIR__ . '/phpmailserver/PHPMailer-master/PHPMailer-master/src/PHPMailer.php';
require_once __DIR__ . '/phpmailserver/PHPMailer-master/PHPMailer-master/src/SMTP.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
use PHPMailer\PHPMailer\SMTP;

$mail = new PHPMailer(true);

try {
    echo "Initializing PHPMailer...\n";
    // Server settings
    $mail->SMTPDebug = 3;                      // Enable verbose debug output (3 = client + server)
    $mail->Debugoutput = 'echo';
    $mail->isSMTP();                                            // Send using SMTP
    $mail->Host       = 'smtp.gmail.com';                     // Set the SMTP server to send through
    $mail->SMTPAuth   = true;                                   // Enable SMTP authentication
    $mail->Username   = 'rendex857@gmail.com';                     // SMTP username
    $mail->Password   = 'dhljrxkzvctpnzan';                               // SMTP password
    $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;            // Enable implicit TLS encryption
    $mail->Port       = 465;                                    // TCP port to connect to

    // Recipients
    $mail->setFrom('rendex857@gmail.com', 'RendeX Debug');
    $mail->addAddress('rendex857@gmail.com');     // Send to self to test

    // Content
    $mail->isHTML(true);
    $mail->Subject = 'Debug Test Email';
    $mail->Body    = 'This is a test email to verify SMTP configuration.';

    echo "Attempting to send...\n";
    $mail->send();
    echo "Message has been sent successfully.\n";
} catch (Exception $e) {
    echo "Message could not be sent. Mailer Error: {$mail->ErrorInfo}\n";
}
?>
