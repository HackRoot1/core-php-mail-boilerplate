<?php
/**
 * ================================================================
 *  Mailer.php  —  Reusable SMTP Email Class
 * ================================================================
 *  Author  : Your Name / Company
 *  Version : 1.0
 *  Requires: PHP 8.1+, PHPMailer (via Composer)
 *
 *  COPY THIS FILE TO ANY PROJECT. Zero changes needed.
 *  Just make sure:
 *    1. composer.json has "phpmailer/phpmailer": "^6.9"
 *    2. .env file has your SMTP credentials
 *    3. config.php is loaded before using this class
 * ================================================================
 *
 *  QUICK USAGE:
 * ----------------------------------------------------------------
 *  $mailer = new Mailer();
 *
 *  // Simple send
 *  $result = $mailer->send([
 *      'to'      => 'john@example.com',
 *      'to_name' => 'John Doe',
 *      'subject' => 'Welcome!',
 *      'body'    => '<h1>Hello John</h1><p>Welcome aboard!</p>',
 *  ]);
 *
 *  if ($result['success']) {
 *      echo 'Email sent!';
 *  } else {
 *      echo 'Failed: ' . $result['error'];
 *  }
 *
 *  // Notify business owner (uses BUSINESS_EMAIL from .env)
 *  $mailer->notify('New Order Received', '<p>Order #123 placed.</p>');
 * ================================================================
 */

// PHPMailer is loaded via Composer autoload in config.php
// If you're not using config.php, uncomment the line below:
// require_once __DIR__ . '/../vendor/autoload.php';

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

    public function __construct()
    {
        // All credentials come from .env — never hardcoded here
        $this->host      = $_ENV['SMTP_HOST']       ?? 'smtp.gmail.com';
        $this->port      = (int)($_ENV['SMTP_PORT'] ?? 587);
        $this->username  = $_ENV['SMTP_USERNAME']   ?? '';
        $this->password  = $_ENV['SMTP_PASSWORD']   ?? '';
        $this->fromEmail = $_ENV['SMTP_FROM_EMAIL'] ?? '';
        $this->fromName  = $_ENV['SMTP_FROM_NAME']  ?? 'Website';
    }

    // ================================================================
    //  send()  —  Main method. Handles all email scenarios.
    // ================================================================
    /**
     * @param array $params {
     *
     *   REQUIRED:
     *   string   to          Recipient email address
     *   string   subject     Email subject line
     *   string   body        HTML email body
     *
     *   OPTIONAL:
     *   string   to_name     Recipient display name
     *   string   alt         Plain-text fallback (auto-generated if omitted)
     *   array    reply_to    ['email' => '...', 'name' => '...']
     *   array    cc          [['email' => '...', 'name' => '...'], ...]
     *   array    bcc         [['email' => '...', 'name' => '...'], ...]
     *   array    attachments [['path' => '/full/path/file.pdf', 'name' => 'Invoice.pdf'], ...]
     * }
     *
     * @return array { bool success, string|null error }
     */
    public function send(array $params): array
    {
        // Guard: required fields
        if (empty($params['to']) || empty($params['subject']) || empty($params['body'])) {
            return [
                'success' => false,
                'error'   => 'Missing required fields: to, subject, body.',
            ];
        }

        $mail = new PHPMailer(true); // true = throw exceptions

        try {
            // ---- SMTP Server Config ----
            $mail->isSMTP();
            $mail->Host       = $this->host;
            $mail->SMTPAuth   = true;
            $mail->Username   = $this->username;
            $mail->Password   = $this->password;
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS; // Port 587
            $mail->Port       = $this->port;
            $mail->CharSet    = 'UTF-8';

            // ---- From (Sender) ----
            $mail->setFrom($this->fromEmail, $this->fromName);

            // ---- To (Primary Recipient) ----
            $mail->addAddress($params['to'], $params['to_name'] ?? '');

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

            // ---- Email Body ----
            $mail->isHTML(true);
            $mail->Subject = $params['subject'];
            $mail->Body    = $params['body'];
            $mail->AltBody = $params['alt'] ?? strip_tags($params['body']);

            // ---- Attachments (optional) ----
            foreach ($params['attachments'] ?? [] as $file) {
                if (!empty($file['path']) && file_exists($file['path'])) {
                    $mail->addAttachment($file['path'], $file['name'] ?? basename($file['path']));
                }
            }

            $mail->send();
            return ['success' => true, 'error' => null];

        } catch (Exception $e) {
            // Log error silently — never show SMTP details to the user
            error_log('[Mailer] Send failed: ' . $mail->ErrorInfo);
            return ['success' => false, 'error' => $mail->ErrorInfo];
        }
    }

    // ================================================================
    //  notify()  —  Shortcut: send email to the business/site owner
    // ================================================================
    /**
     * Sends an internal notification email to the business owner.
     * Uses BUSINESS_EMAIL from .env as the recipient.
     *
     * @param string $subject
     * @param string $body     HTML body
     * @return array           { bool success, string|null error }
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

    // ================================================================
    //  sendToMany()  —  Send same email to multiple recipients
    // ================================================================
    /**
     * Sends the same email individually to an array of recipients.
     * (Each person gets a separate email — not a group email.)
     *
     * @param array  $recipients  [['email' => '...', 'name' => '...'], ...]
     * @param string $subject
     * @param string $body        HTML. Use {name} as a placeholder for personalisation.
     * @return array              ['sent' => int, 'failed' => int, 'errors' => []]
     */
    public function sendToMany(array $recipients, string $subject, string $body): array
    {
        $sent   = 0;
        $failed = 0;
        $errors = [];

        foreach ($recipients as $recipient) {
            // Personalise body — replace {name} with actual name
            $personalBody = str_replace(
                '{name}',
                htmlspecialchars($recipient['name'] ?? 'there'),
                $body
            );

            $result = $this->send([
                'to'      => $recipient['email'],
                'to_name' => $recipient['name'] ?? '',
                'subject' => $subject,
                'body'    => $personalBody,
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
