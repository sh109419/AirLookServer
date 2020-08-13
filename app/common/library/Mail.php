<?php
declare(strict_types=1);

namespace AirLook\Library;

use Phalcon\Di\Injectable;
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;


/**
 * Sends e-mails
 */
class Mail extends Injectable
{

    /**
     * Sends warning e-mails
     *
     * @param string $subject
     * @param string $body
     *
     * @return bool
     */

    function send($subject,$body) {

        // Settings
        $mailSettings = $this->config->mail;

        // Create the mail
        $mail = new PHPMailer(true);

        try {
            //Server settings
            $mail->SMTPDebug = SMTP::DEBUG_OFF;                      // Enable verbose debug output
            $mail->isSMTP();                                            // Send using SMTP
            $mail->Host       = $mailSettings->smtp->server;                    // Set the SMTP server to send through
            $mail->SMTPAuth   = true;                                   // Enable SMTP authentication
            $mail->Username   = $mailSettings->smtp->username;                     // SMTP username
            $mail->Password   = $mailSettings->smtp->password;                               // SMTP password
            //$mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;         // Enable TLS encryption; `PHPMailer::ENCRYPTION_SMTPS` encouraged
            $mail->SMTPSecure = $mailSettings->smtp->security;          // 163不支持starttls
            $mail->Port       = $mailSettings->smtp->port;                                    // TCP port to connect to, use 465 for `PHPMailer::ENCRYPTION_SMTPS` above

            //Recipients
            $mail->setFrom($mailSettings->fromEmail, $mailSettings->fromName); //Mail from must equal authorized user
            $mail->addAddress('sh109419@163.com', 'Admin');     // Add a recipient

            // Attachments
            //$mail->addAttachment('/var/tmp/file.tar.gz');         // Add attachments

            // Content
            $mail->isHTML(true);                                  // Set email format to HTML
            $mail->Subject = $subject;
            $mail->Body    = $body;
            //$mail->AltBody = 'This is the body in plain text for non-HTML mail clients';

            return $mail->send();
            //echo 'Message has been sent';
        } catch (Exception $e) {
            //echo "Message could not be sent. Mailer Error: {$mail->ErrorInfo}";
        }
    }

}
