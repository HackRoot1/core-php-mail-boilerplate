# PHP Mailer Kit — Reusable SMTP Email System
### Use in every PHP project. Set up once. Copy 3 files. Done.

---

## 📁 Files in This Kit

```
php-mailer-kit/
│
├── includes/
│   ├── Mailer.php          ← The email class (copy to every project)
│   └── config.php          ← .env loader + helpers (copy to every project)
│
├── examples/
│   ├── 01_contact_form.php     ← Contact us form
│   ├── 02_quote_form.php       ← Get a quote form
│   ├── 03_otp_verification.php ← OTP / email verification
│   └── 04_attachment_and_bulk.php ← Attachments + bulk send
│
├── .env.example            ← Template — copy to .env and fill values
├── .htaccess               ← Protects .env from public access
├── .gitignore              ← Prevents .env from being committed to Git
└── composer.json           ← Declares PHPMailer dependency
```

---

## 🚀 SETUP GUIDE — New Project in 5 Steps

---

### STEP 1 — Get a Gmail App Password

> This is a special password just for apps. Never use your real Gmail password.

1. Open **myaccount.google.com**
2. Go to **Security** tab
3. Enable **2-Step Verification** (required)
4. Search for **"App passwords"** in the search bar
5. Click **App passwords** → Choose app: `Mail` → Device: `Other` → name it `Website`
6. Copy the **16-character password** shown (example: `abcd efgh ijkl mnop`)
7. Save it — you'll need it in Step 3

---

### STEP 2 — Copy These 3 Files to Your Project

From this kit, copy:

| File | Where to put it |
|------|----------------|
| `includes/Mailer.php`   | `your-project/includes/Mailer.php` |
| `includes/config.php`   | `your-project/includes/config.php` |
| `.env.example`          | `your-project/.env`  (rename it) |
| `.htaccess`             | `your-project/.htaccess` |
| `.gitignore`            | `your-project/.gitignore` |
| `composer.json`         | `your-project/composer.json` |

---

### STEP 3 — Fill in Your .env File

Open your project's `.env` file and update these values:

```env
SMTP_HOST=smtp.gmail.com
SMTP_PORT=587
SMTP_USERNAME=your_real_gmail@gmail.com
SMTP_PASSWORD=abcdefghijklmnop        ← App Password (no spaces)
SMTP_FROM_EMAIL=your_real_gmail@gmail.com
SMTP_FROM_NAME=My Business Name

BUSINESS_EMAIL=owner@yourdomain.com   ← Where YOU receive form emails

SITE_NAME=My Business
SITE_URL=https://www.yourdomain.com
ENVIRONMENT=development               ← Change to: production on live server
TIMEZONE=Asia/Kolkata
```

**Never share this file. Never commit it to Git.**

---

### STEP 4 — Install PHPMailer via Composer

Open terminal in your project folder and run:

```bash
composer install
```

This creates a `vendor/` folder with PHPMailer inside it.

> **Don't have Composer?**
> Download it from **https://getcomposer.org/download/**
> Or on Linux/Mac: `curl -sS https://getcomposer.org/installer | php`

---

### STEP 5 — Include config.php at the Top of Every Page

Add this ONE line at the very top of every PHP page that uses email:

```php
<?php
require_once __DIR__ . '/includes/config.php';
// Now Mailer class + all helpers are available
```

> **Adjust the path** if your file is inside a subfolder:
> - Page in root: `require_once __DIR__ . '/includes/config.php';`
> - Page in `/pages/`: `require_once __DIR__ . '/../includes/config.php';`

---

## 📧 HOW TO USE — Quick Reference

---

### Minimum Usage (3 lines)

```php
$mailer = new Mailer();
$result = $mailer->send([
    'to'      => 'client@example.com',
    'subject' => 'Hello!',
    'body'    => '<p>Your message here</p>',
]);
```

---

### Full send() Parameters

```php
$mailer = new Mailer();
$result = $mailer->send([

    // ---- REQUIRED ----
    'to'      => 'client@example.com',   // Recipient email
    'subject' => 'Your subject line',    // Email subject
    'body'    => '<h1>HTML content</h1>',// HTML email body

    // ---- OPTIONAL ----
    'to_name' => 'Rahul Sharma',         // Recipient display name
    'alt'     => 'Plain text version',   // Auto-generated if omitted

    'reply_to' => [                      // Who replies go to
        'email' => 'support@you.com',
        'name'  => 'Support Team',
    ],

    'cc' => [                            // Carbon copy
        ['email' => 'manager@you.com', 'name' => 'Manager'],
    ],

    'bcc' => [                           // Blind carbon copy (silent)
        ['email' => 'archive@you.com', 'name' => 'Archive'],
    ],

    'attachments' => [                   // File attachments
        ['path' => '/srv/invoice.pdf', 'name' => 'Invoice.pdf'],
    ],
]);

// Check result
if ($result['success']) {
    echo 'Sent!';
} else {
    echo 'Failed: ' . $result['error'];
}
```

---

### Shortcut: Notify Business Owner

```php
// Sends to BUSINESS_EMAIL from your .env automatically
$mailer->notify(
    'New Order Received',
    '<p>Order #123 has been placed.</p>'
);
```

---

### Bulk / Multiple Recipients

```php
// Sends individually to each person (not CC)
// {name} is auto-replaced per recipient
$result = $mailer->sendToMany(
    recipients: [
        ['email' => 'alice@example.com', 'name' => 'Alice'],
        ['email' => 'bob@example.com',   'name' => 'Bob'],
    ],
    subject: 'Hello from us!',
    body: '<p>Hi {name}, thanks for joining!</p>'
);

echo "{$result['sent']} sent, {$result['failed']} failed.";
```

---

### CSRF Protection on Forms

Every form in your project should use CSRF tokens to prevent attacks:

**In your HTML form:**
```html
<form method="POST">
    <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>">
    <!-- rest of your form fields -->
</form>
```

**In your PHP handler:**
```php
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCsrf()) {
        die('Security check failed. Please refresh and try again.');
    }
    // Process form safely...
}
```

---

### Input Sanitisation

Always clean user input before using it:

```php
$name    = clean($_POST['name']    ?? '');   // Strips tags, trims, encodes
$email   = filter_var(trim($_POST['email'] ?? ''), FILTER_SANITIZE_EMAIL);
$message = clean($_POST['message'] ?? '');

// Validate email
if (!isValidEmail($email)) {
    $error = 'Invalid email address.';
}
```

---

## 🏗️ FOLDER STRUCTURE — Recommended for Every Project

```
your-project/
│
├── .env                    ← Your private credentials
├── .htaccess               ← Security rules
├── .gitignore              ← Don't commit .env or vendor/
├── composer.json
├── vendor/                 ← Auto-created by composer install
│
├── includes/
│   ├── config.php          ← Include this in every PHP page
│   ├── Mailer.php          ← Email class
│   └── header.php          ← (optional) shared HTML header
│   └── footer.php          ← (optional) shared HTML footer
│
├── assets/
│   ├── css/style.css
│   └── js/main.js
│
├── index.php               ← Homepage
└── pages/
    ├── about.php
    ├── contact.php         ← Uses Mailer
    └── quote.php           ← Uses Mailer
```

---

## ☁️ HOSTINGER DEPLOYMENT CHECKLIST

```
[ ] 1. Run  composer install  locally (creates vendor/ folder)
[ ] 2. Rename .env.example to .env and fill real credentials
[ ] 3. Set ENVIRONMENT=production in .env
[ ] 4. Upload ALL files to public_html/ via File Manager or FTP
         Important: upload vendor/ folder too
[ ] 5. In hPanel → PHP Configuration → set PHP 8.1 or higher
[ ] 6. Verify openssl extension is enabled (for SMTP TLS)
[ ] 7. Enable Free SSL in hPanel → SSL section
[ ] 8. Uncomment the HTTPS redirect lines in .htaccess
[ ] 9. Test contact form — check owner inbox and customer inbox
[ ]10. Check spam folder if email not received
```

---

## 🔐 SECURITY CHECKLIST

```
[ ] .env is in .gitignore                → never in Git
[ ] .htaccess blocks /includes/ folder   → no direct URL access
[ ] .htaccess blocks .env file           → not accessible via browser
[ ] CSRF token on every form             → use csrfToken() + verifyCsrf()
[ ] All inputs sanitised                 → use clean() on all $_POST
[ ] ENVIRONMENT=production on live       → no PHP errors exposed
[ ] Gmail App Password used              → not real Gmail password
[ ] vendor/ not accessible via browser  → protected by .htaccess
```

---

## ❓ TROUBLESHOOTING

| Problem | Solution |
|---------|----------|
| **Emails not sending** | Check App Password in `.env`. Make sure there are no spaces. |
| **"openssl" error** | In hPanel → PHP Config → enable `openssl` extension |
| **vendor/ missing** | Run `composer install` and re-upload the `vendor/` folder |
| **500 Server Error** | Check PHP version is 8.1+. Check file permissions (755 dirs, 644 files) |
| **Form submits but no email** | Check BUSINESS_EMAIL in `.env`. Check spam folder. |
| **Can access .env in browser** | Confirm `.htaccess` is uploaded to project root |
| **CSRF error on submit** | Make sure `csrfToken()` is in the form and `config.php` is included |
| **OTP not received** | Check spam. Verify SMTP credentials. Check Gmail daily send limits (500/day) |

---

## 📦 Using in a New Project — Quickstart Checklist

```
[ ] Copy includes/Mailer.php  to new-project/includes/
[ ] Copy includes/config.php  to new-project/includes/
[ ] Copy .env.example         to new-project/.env  (rename, fill values)
[ ] Copy .htaccess            to new-project/
[ ] Copy .gitignore           to new-project/
[ ] Copy composer.json        to new-project/
[ ] Run:  composer install
[ ] Add at top of each PHP file:
        require_once __DIR__ . '/includes/config.php';
[ ] Use Mailer as shown above
[ ] Done!
```

---

*PHP Mailer Kit — Reusable SMTP email system for all your PHP projects.*
#
