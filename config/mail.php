<?php
/**
 * Email Utilities for RendeX
 */

// SMTP Credentials
if (!defined('SMTP_HOST')) define('SMTP_HOST', 'ssl://smtp.gmail.com');
if (!defined('SMTP_PORT')) define('SMTP_PORT', 465);
if (!defined('SMTP_USER')) define('SMTP_USER', 'annachristinajohny2028@mca.ajce.in');
if (!defined('SMTP_PASS')) define('SMTP_PASS', 'lnjqcasvvxewzyeh'); // App Password

/**
 * Send an email via SMTP
 * @param string $to Recipient email
 * @param string $subject Email subject
 * @param string $body Email HTML body
 * @return bool|string True on success, error message on failure
 */
function send_smtp_email($to, $subject, $body) {
    try {
        $context = stream_context_create([
            'ssl' => [
                'verify_peer' => false,
                'verify_peer_name' => false,
                'allow_self_signed' => true
            ]
        ]);

        $socket = stream_socket_client(SMTP_HOST . ':' . SMTP_PORT, $errno, $errstr, 30, STREAM_CLIENT_CONNECT, $context);
        if (!$socket) throw new Exception("Could not connect to SMTP host: $errstr ($errno)");

        $response = fgets($socket, 515);
        if (empty($response) || substr($response, 0, 3) != '220') throw new Exception("SMTP connection failure: $response");

        fputs($socket, "EHLO " . ($_SERVER['SERVER_NAME'] ?? 'localhost') . "\r\n");
        while ($line = fgets($socket, 515)) {
            if (substr($line, 3, 1) == ' ') break;
        }

        fputs($socket, "AUTH LOGIN\r\n");
        fgets($socket, 515);
        fputs($socket, base64_encode(SMTP_USER) . "\r\n");
        fgets($socket, 515);
        fputs($socket, base64_encode(SMTP_PASS) . "\r\n");
        $response = fgets($socket, 515);
        if (substr($response, 0, 3) != '235') throw new Exception("Authentication failed: $response");

        fputs($socket, "MAIL FROM: <" . SMTP_USER . ">\r\n");
        fgets($socket, 515);
        fputs($socket, "RCPT TO: <$to>\r\n");
        $response = fgets($socket, 515);
        if (substr($response, 0, 3) != '250') throw new Exception("Recipient rejected: $response");

        fputs($socket, "DATA\r\n");
        fgets($socket, 515);

        $headers  = "MIME-Version: 1.0\r\n";
        $headers .= "Content-type: text/html; charset=utf-8\r\n";
        $headers .= "From: RendeX <" . SMTP_USER . ">\r\n";
        $headers .= "To: $to\r\n";
        $headers .= "Subject: $subject\r\n";

        fputs($socket, "$headers\r\n$body\r\n.\r\n");
        $response = fgets($socket, 515);
        if (substr($response, 0, 3) != '250') throw new Exception("Message send failed: $response");

        fputs($socket, "QUIT\r\n");
        fclose($socket);

        return true;
    } catch (Exception $e) {
        return "Error: " . $e->getMessage();
    }
}
?>
