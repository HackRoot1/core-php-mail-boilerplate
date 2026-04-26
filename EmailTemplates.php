<?php
/**
 * ================================================================
 *  EmailTemplates.php  —  Reusable HTML Email Template Engine
 * ================================================================
 *  COPY THIS FILE TO ANY PROJECT.
 *
 *  HOW TO USE:
 *    1. Change the $brand* properties at the top for each project
 *    2. Add new template methods as needed (copy an existing one)
 *    3. Use with Mailer::send() — pass the output as 'body'
 *
 *  EXAMPLE:
 *    $mailer = new Mailer();
 *    $mailer->send([
 *        'to'      => 'user@example.com',
 *        'subject' => 'Welcome!',
 *        'body'    => EmailTemplates::welcome('John'),
 *    ]);
 * ================================================================
 */

class EmailTemplates
{
    // ================================================================
    //  PROJECT BRANDING — change these for each project
    // ================================================================
    private static string $brandName    = '';   // Falls back to SITE_NAME constant
    private static string $brandTagline = 'Your Tagline Here';
    private static string $primaryColor = '#c8874a';
    private static string $darkColor    = '#2d1a0e';
    private static string $lightColor   = '#fff8f2';
    private static string $address      = '123 Example Street, Mumbai, MH 400001';
    private static string $phone        = '+91 98765 43210';
    private static string $email        = 'hello@yourdomain.com';
    private static string $website      = '';   // Falls back to SITE_URL constant

    // ================================================================
    //  PRIVATE: HTML Wrapper used by ALL templates
    //  You never need to call this directly.
    // ================================================================
    private static function wrap(string $content): string
    {
        $name    = self::$brandName    ?: (defined('SITE_NAME') ? SITE_NAME : 'Website');
        $site    = self::$website      ?: (defined('SITE_URL')  ? SITE_URL  : '#');
        $tagline = self::$brandTagline;
        $primary = self::$primaryColor;
        $dark    = self::$darkColor;
        $light   = self::$lightColor;
        $address = self::$address;
        $phone   = self::$phone;
        $emailId = self::$email;
        $year    = date('Y');

        return <<<HTML
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width,initial-scale=1.0">
  <title>{$name}</title>
  <style>
    body  { margin:0; padding:0; background:#f0f0f0; font-family:'Segoe UI',Arial,sans-serif; color:#333; }
    table { border-collapse:collapse; }
    a     { color:{$primary}; }

    .wrapper   { max-width:600px; margin:30px auto; background:#fff; border-radius:8px; overflow:hidden; box-shadow:0 2px 12px rgba(0,0,0,.1); }
    .header    { background:{$dark}; padding:28px 36px; text-align:center; }
    .brand     { margin:0; font-size:26px; font-weight:700; color:{$primary}; letter-spacing:1px; }
    .tagline   { margin:4px 0 0; font-size:12px; color:rgba(255,255,255,.5); letter-spacing:3px; text-transform:uppercase; }
    .body      { padding:36px; line-height:1.75; }
    .body h2   { margin-top:0; color:{$dark}; }
    .info-row  { display:flex; padding:10px 0; border-bottom:1px solid {$light}; font-size:14px; }
    .info-label{ font-weight:700; color:{$dark}; min-width:130px; }
    .btn       { display:inline-block; background:{$primary}; color:#fff!important; padding:12px 30px; border-radius:6px; text-decoration:none; font-weight:700; margin:12px 0; }
    .divider   { border:none; border-top:2px solid {$light}; margin:24px 0; }
    .footer    { background:{$light}; padding:20px 36px; text-align:center; font-size:12px; color:#999; }
    .footer a  { color:{$primary}; text-decoration:none; }

    /* Responsive */
    @media(max-width:600px){ .body{ padding:24px; } }
  </style>
</head>
<body>
<div class="wrapper">
  <div class="header">
    <p class="brand">{$name}</p>
    <p class="tagline">{$tagline}</p>
  </div>
  <div class="body">{$content}</div>
  <div class="footer">
    &copy; {$year} {$name}. All rights reserved.<br>
    {$address}<br>
    <a href="tel:{$phone}">{$phone}</a> &nbsp;|&nbsp;
    <a href="mailto:{$emailId}">{$emailId}</a> &nbsp;|&nbsp;
    <a href="{$site}">{$site}</a>
  </div>
</div>
</body>
</html>
HTML;
    }

    // ================================================================
    //  PRIVATE: Renders a key-value data table for notifications
    // ================================================================
    private static function dataTable(array $rows): string
    {
        $html = '<table width="100%" cellpadding="0" cellspacing="0" style="margin:20px 0;font-size:14px;">';
        foreach ($rows as [$label, $value]) {
            $light = self::$lightColor;
            $dark  = self::$darkColor;
            $html .= <<<ROW
            <tr>
              <td style="padding:10px 14px;border-bottom:1px solid {$light};font-weight:700;color:{$dark};width:38%;vertical-align:top;">{$label}</td>
              <td style="padding:10px 14px;border-bottom:1px solid {$light};vertical-align:top;">{$value}</td>
            </tr>
ROW;
        }
        $html .= '</table>';
        return $html;
    }

    // ================================================================
    //  PUBLIC TEMPLATES
    //  Add new methods below. Each returns an HTML string.
    // ================================================================

    /**
     * Generic welcome email.
     * Use for: registration, first login, onboarding.
     */
    public static function welcome(string $name, string $ctaUrl = '', string $ctaLabel = 'Get Started'): string
    {
        $name    = htmlspecialchars($name);
        $cta     = $ctaUrl ? "<a href=\"{$ctaUrl}\" class=\"btn\">{$ctaLabel}</a>" : '';
        $siteName= defined('SITE_NAME') ? SITE_NAME : 'us';

        $content = <<<HTML
<h2>Welcome, {$name}! 👋</h2>
<p>Thank you for joining <strong>{$siteName}</strong>. We're glad you're here!</p>
<p>You're all set up. Here's what you can do next:</p>
<ul style="line-height:2;">
  <li>Explore our products and services</li>
  <li>Reach out if you have any questions</li>
  <li>Check out the latest updates</li>
</ul>
{$cta}
<hr class="divider">
<p style="font-size:13px;color:#999;">If you didn't sign up for this, please ignore this email.</p>
HTML;
        return self::wrap($content);
    }

    /**
     * Contact form notification — sent to the business owner.
     * $data keys: name, email, phone, message
     */
    public static function contactNotification(array $data): string
    {
        $name    = htmlspecialchars($data['name']    ?? '');
        $email   = htmlspecialchars($data['email']   ?? '');
        $phone   = htmlspecialchars($data['phone']   ?? 'N/A');
        $message = nl2br(htmlspecialchars($data['message'] ?? ''));

        $table = self::dataTable([
            ['Name',    $name],
            ['Email',   "<a href=\"mailto:{$email}\">{$email}</a>"],
            ['Phone',   $phone],
            ['Message', $message],
        ]);

        $content = "<h2>📩 New Contact Message</h2><p>You have a new message from your website contact form.</p>{$table}";
        return self::wrap($content);
    }

    /**
     * Auto-reply sent to user after contact form submission.
     */
    public static function contactAutoReply(string $name): string
    {
        $name = htmlspecialchars($name);
        $content = <<<HTML
<h2>Hi {$name}, thanks for reaching out! ✉️</h2>
<p>We've received your message and will get back to you within <strong>24 hours</strong>.</p>
<p>In the meantime, feel free to browse our website or reach out via phone if it's urgent.</p>
<hr class="divider">
<p style="font-size:13px;color:#999;">This is an automated reply. Please do not respond directly to this email.</p>
HTML;
        return self::wrap($content);
    }

    /**
     * Generic quote / enquiry notification — sent to business owner.
     * $data: associative array of any key-value pairs to display.
     * Example: ['name'=>'John', 'product'=>'Widget', 'budget'=>'₹5000']
     */
    public static function quoteNotification(array $data, string $title = '🎉 New Quote Request'): string
    {
        $rows = [];
        foreach ($data as $key => $value) {
            $label  = ucwords(str_replace('_', ' ', $key));
            $rows[] = [$label, nl2br(htmlspecialchars((string)$value))];
        }
        $table   = self::dataTable($rows);
        $content = "<h2>{$title}</h2><p>A new quote request has been submitted via your website.</p>{$table}";
        return self::wrap($content);
    }

    /**
     * Auto-reply sent to user after quote / order form submission.
     */
    public static function quoteAutoReply(string $name, array $nextSteps = []): string
    {
        $name = htmlspecialchars($name);

        if (empty($nextSteps)) {
            $nextSteps = [
                'We review your requirements',
                'Our team prepares a personalised quote',
                'We contact you to confirm details',
                'Your order is confirmed!',
            ];
        }

        $steps = '';
        foreach ($nextSteps as $i => $step) {
            $num    = $i + 1;
            $step   = htmlspecialchars($step);
            $steps .= "<li style=\"line-height:2;\">{$step}</li>";
        }

        $content = <<<HTML
<h2>Your Request is Received, {$name}! 🎉</h2>
<p>Thank you! We'll prepare your personalised quote and get back to you within <strong>24–48 hours</strong>.</p>
<p><strong>What happens next:</strong></p>
<ol>{$steps}</ol>
<hr class="divider">
<p style="font-size:13px;color:#999;">Questions? Just reply to this email or call us directly.</p>
HTML;
        return self::wrap($content);
    }

    /**
     * Order confirmation email — sent to customer.
     * $orderDetails: associative array of order fields.
     */
    public static function orderConfirmation(string $name, string $orderId, array $orderDetails, string $ctaUrl = ''): string
    {
        $name    = htmlspecialchars($name);
        $orderId = htmlspecialchars($orderId);
        $rows    = [];
        foreach ($orderDetails as $key => $value) {
            $label  = ucwords(str_replace('_', ' ', $key));
            $rows[] = [$label, htmlspecialchars((string)$value)];
        }
        $table = self::dataTable($rows);
        $cta   = $ctaUrl ? "<a href=\"{$ctaUrl}\" class=\"btn\">View Order</a>" : '';

        $content = <<<HTML
<h2>Order Confirmed! ✅</h2>
<p>Hi <strong>{$name}</strong>, your order <strong>#{$orderId}</strong> has been confirmed.</p>
{$table}
{$cta}
<hr class="divider">
<p style="font-size:13px;color:#999;">Keep this email as your order receipt.</p>
HTML;
        return self::wrap($content);
    }

    /**
     * Password reset email.
     */
    public static function passwordReset(string $name, string $resetUrl, int $expiryMinutes = 30): string
    {
        $name    = htmlspecialchars($name);
        $primary = self::$primaryColor;

        $content = <<<HTML
<h2>Password Reset Request 🔒</h2>
<p>Hi <strong>{$name}</strong>, we received a request to reset your password.</p>
<p>Click the button below. This link expires in <strong>{$expiryMinutes} minutes</strong>.</p>
<a href="{$resetUrl}" class="btn">Reset My Password</a>
<hr class="divider">
<p style="font-size:13px;color:#999;">
  If you didn't request this, please ignore this email. Your password will remain unchanged.<br><br>
  Or copy this URL into your browser:<br>
  <a href="{$resetUrl}" style="color:{$primary};word-break:break-all;">{$resetUrl}</a>
</p>
HTML;
        return self::wrap($content);
    }

    /**
     * Generic notification / alert email.
     * For anything that doesn't fit the above templates.
     */
    public static function generic(string $heading, string $bodyHtml, string $ctaUrl = '', string $ctaLabel = 'Learn More'): string
    {
        $cta     = $ctaUrl ? "<a href=\"{$ctaUrl}\" class=\"btn\">{$ctaLabel}</a>" : '';
        $content = "<h2>{$heading}</h2>{$bodyHtml}{$cta}";
        return self::wrap($content);
    }
}
