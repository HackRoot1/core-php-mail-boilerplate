<?php
/**
 * =============================================================
 *  EXAMPLE 4 — Send Email with PDF Attachment
 * =============================================================
 *  Demonstrates:
 *   - Sending an email with a file attachment
 *   - CC and BCC
 *   - Multiple recipients in one call
 * =============================================================
 */
require_once __DIR__ . '/../includes/config.php';

// ---- Send with Attachment ----
$mailer = new Mailer();

$result = $mailer->send([
    // Required
    'to'      => 'client@example.com',
    'to_name' => 'Rahul Sharma',
    'subject' => 'Your Invoice #INV-2024-001',
    'body'    => '
        <h2>Invoice Attached</h2>
        <p>Dear Rahul,</p>
        <p>Please find your invoice attached to this email.</p>
        <p>Payment is due within 7 days.</p>
        <p>Thank you for your business!</p>
    ',

    // Optional: Reply-To
    'reply_to' => [
        'email' => 'accounts@yourcompany.com',
        'name'  => 'Accounts Team',
    ],

    // Optional: CC
    'cc' => [
        ['email' => 'manager@yourcompany.com', 'name' => 'Manager'],
    ],

    // Optional: BCC (silent copy)
    'bcc' => [
        ['email' => 'archive@yourcompany.com', 'name' => 'Archive'],
    ],

    // Optional: Attachments
    'attachments' => [
        [
            'path' => '/path/to/invoice.pdf',  // Absolute server path
            'name' => 'Invoice-INV-2024-001.pdf', // Display name in email
        ],
        // You can add multiple files:
        // ['path' => '/path/to/receipt.pdf', 'name' => 'Receipt.pdf'],
    ],
]);

if ($result['success']) {
    echo 'Invoice sent successfully!';
} else {
    echo 'Failed: ' . $result['error'];
}
echo '<hr>';

// ---- Send to Multiple Recipients ----
$result2 = $mailer->sendToMany(
    recipients: [
        ['email' => 'alice@example.com', 'name' => 'Alice'],
        ['email' => 'bob@example.com',   'name' => 'Bob'],
        ['email' => 'carol@example.com', 'name' => 'Carol'],
    ],
    subject: 'Our Monthly Newsletter — April 2024',
    body: '
        <h2>Hello {name}! 👋</h2>
        <p>Here\'s what\'s new this month...</p>
        <p>Thank you for being with us!</p>
    '
    // {name} is automatically replaced per recipient
);

echo "Bulk send: {$result2['sent']} sent, {$result2['failed']} failed.";
if ($result2['errors']) {
    echo '<br>Errors: ' . implode(', ', $result2['errors']);
}
