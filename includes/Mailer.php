<?php
/**
 * =============================================================
 *  Mailer.php  —  Reusable SMTP Email Class
 * =============================================================
 *  Author  : Your Name
 *  Version : 1.0
 *  Requires: PHPMailer (installed via Composer)
 *            composer require phpmailer/phpmailer
 *
 *  HOW TO USE IN ANY PROJECT:
 *  1. Copy  includes/Mailer.php   → your project's includes/
 *  2. Copy  includes/config.php   → your project's includes/
 *  3. Copy  .env                  → your project root
 *  4. Run   composer require phpmailer/phpmailer
 *  5. Edit  .env  with your Gmail App Password
 *  6. Done! See examples/ folder for usage.
 * =============================================================
 */

// PHPMailer autoload (installed via Composer)
require_once __DIR__ . '/../vendor/autoload.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

class Mailer
{
    private string $host;
    private int    $port;
    private string $username;
    private string $password;
    private string $fromEmail;
    private string $fromName;

    /**
     * Constructor: reads credentials from $_ENV (loaded by config.php)
     */
    public function __construct()
    {
        $this->host      = $_ENV['SMTP_HOST']       ?? 'smtp.gmail.com';
        $this->port      = (int)($_ENV['SMTP_PORT'] ?? 587);
        $this->username  = $_ENV['SMTP_USERNAME']   ?? '';
        $this->password  = $_ENV['SMTP_PASSWORD']   ?? '';
        $this->fromEmail = $_ENV['SMTP_FROM_EMAIL'] ?? '';
        $this->fromName  = $_ENV['SMTP_FROM_NAME']  ?? 'Website';
    }

    // =============================================================
    //  PUBLIC METHOD: send()
    // =============================================================
    /**
     * Send an HTML email.
     *
     * @param array $params {
     *   REQUIRED:
     *     string  to          Recipient email address
     *     string  subject     Email subject line
     *     string  body        HTML email body
     *
     *   OPTIONAL:
     *     string  to_name     Recipient display name
     *     string  alt         Plain-text fallback (auto-generated if omitted)
     *     array   reply_to    ['email' => '', 'name' => '']
     *     array   cc          [['email' => '', 'name' => ''], ...]
     *     array   bcc         [['email' => '', 'name' => ''], ...]
     *     array   attachments [['path' => '/srv/file.pdf', 'name' => 'Invoice.pdf'], ...]
     * }
     *
     * @return array { bool success, string|null error }
     *
     * EXAMPLE:
     *   $mailer = new Mailer();
     *   $result = $mailer->send([
     *       'to'      => 'client@example.com',
     *       'to_name' => 'Rahul Sharma',
     *       'subject' => 'Your order is confirmed!',
     *       'body'    => '<h1>Thank you for your order</h1>',
     *   ]);
     *   if ($result['success']) {
     *       echo 'Sent!';
     *   } else {
     *       echo 'Error: ' . $result['error'];
     *   }
     */
    public function send(array $params): array
    {
        // ---- Validate required fields ----
        if (empty($params['to'])) {
            return ['success' => false, 'error' => 'Missing required field: to'];
        }
        if (empty($params['subject'])) {
            return ['success' => false, 'error' => 'Missing required field: subject'];
        }
        if (empty($params['body'])) {
            return ['success' => false, 'error' => 'Missing required field: body'];
        }

        $mail = new PHPMailer(true); // true = throw exceptions

        try {
            // ---- SMTP Server Settings ----
            $mail->isSMTP();
            $mail->Host       = $this->host;
            $mail->SMTPAuth   = true;
            $mail->Username   = $this->username;
            $mail->Password   = $this->password;
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS; // TLS on port 587
            $mail->Port       = $this->port;
            $mail->CharSet    = 'UTF-8';

            // ---- Sender ----
            $mail->setFrom($this->fromEmail, $this->fromName);

            // ---- Primary Recipient ----
            $mail->addAddress(
                $params['to'],
                $params['to_name'] ?? ''
            );

            // ---- Reply-To (optional) ----
            if (!empty($params['reply_to']['email'])) {
                $mail->addReplyTo(
                    $params['reply_to']['email'],
                    $params['reply_to']['name'] ?? ''
                );
            }

            // ---- CC (optional) ----
            foreach ($params['cc'] ?? [] as $cc) {
                if (!empty($cc['email'])) {
                    $mail->addCC($cc['email'], $cc['name'] ?? '');
                }
            }

            // ---- BCC (optional) ----
            foreach ($params['bcc'] ?? [] as $bcc) {
                if (!empty($bcc['email'])) {
                    $mail->addBCC($bcc['email'], $bcc['name'] ?? '');
                }
            }

            // ---- Attachments (optional) ----
            foreach ($params['attachments'] ?? [] as $file) {
                if (!empty($file['path']) && file_exists($file['path'])) {
                    $mail->addAttachment($file['path'], $file['name'] ?? '');
                }
            }

            // ---- Email Content ----
            $mail->isHTML(true);
            $mail->Subject = $params['subject'];
            $mail->Body    = $params['body'];
            // Auto plain-text fallback if not provided
            $mail->AltBody = $params['alt'] ?? strip_tags($params['body']);

            // ---- Send ----
            $mail->send();

            return ['success' => true, 'error' => null];

        } catch (Exception $e) {
            // Log to server error log silently
            error_log('[Mailer] Send failed: ' . $mail->ErrorInfo);
            return ['success' => false, 'error' => $mail->ErrorInfo];
        }
    }

    // =============================================================
    //  PUBLIC METHOD: notify()
    //  Shortcut: send an email to the business/admin owner
    // =============================================================
    /**
     * Quick internal notification to business owner.
     * Reads BUSINESS_EMAIL from .env automatically.
     *
     * EXAMPLE:
     *   $mailer->notify('New Order Received', '<p>Order #123 placed</p>');
     */
    public function notify(string $subject, string $body): array
    {
        $ownerEmail = $_ENV['BUSINESS_EMAIL'] ?? $this->fromEmail;

        return $this->send([
            'to'      => $ownerEmail,
            'subject' => $subject,
            'body'    => $body,
        ]);
    }

    // =============================================================
    //  PUBLIC METHOD: sendToMany()
    //  Send same email to multiple recipients (newsletter, bulk)
    // =============================================================
    /**
     * Send same email to a list of recipients (individually, not CC).
     *
     * @param array  $recipients [['email'=>'', 'name'=>''], ...]
     * @param string $subject
     * @param string $body       HTML body — use {name} to personalise
     * @return array             ['sent'=>N, 'failed'=>N, 'errors'=>[]]
     *
     * EXAMPLE:
     *   $mailer->sendToMany([
     *       ['email' => 'a@example.com', 'name' => 'Alice'],
     *       ['email' => 'b@example.com', 'name' => 'Bob'],
     *   ], 'Hello!', '<p>Hi {name}, welcome!</p>');
     */
    public function sendToMany(array $recipients, string $subject, string $body): array
    {
        $sent   = 0;
        $failed = 0;
        $errors = [];

        foreach ($recipients as $recipient) {
            if (empty($recipient['email'])) continue;

            // Replace {name} placeholder with recipient name
            $personalised = str_replace(
                '{name}',
                htmlspecialchars($recipient['name'] ?? 'there'),
                $body
            );

            $result = $this->send([
                'to'      => $recipient['email'],
                'to_name' => $recipient['name'] ?? '',
                'subject' => $subject,
                'body'    => $personalised,
            ]);

            if ($result['success']) {
                $sent++;
            } else {
                $failed++;
                $errors[] = $recipient['email'] . ': ' . $result['error'];
            }
        }

        return ['sent' => $sent, 'failed' => $failed, 'errors' => $errors];
    }
}
