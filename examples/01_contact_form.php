<?php
/**
 * =============================================================
 *  EXAMPLE 1 — Simple Contact Form
 * =============================================================
 *  Demonstrates:
 *   - Basic send() usage
 *   - CSRF protection
 *   - Input sanitisation
 *   - Sending notification to owner + auto-reply to user
 * =============================================================
 */
require_once __DIR__ . '/../includes/config.php';

$success  = '';
$error    = '';
$formData = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // 1. Verify CSRF token
    if (!verifyCsrf()) {
        $error = 'Security check failed. Please refresh and try again.';
    } else {

        // 2. Sanitise inputs
        $formData = [
            'name'    => clean($_POST['name']    ?? ''),
            'email'   => filter_var(trim($_POST['email'] ?? ''), FILTER_SANITIZE_EMAIL),
            'message' => clean($_POST['message'] ?? ''),
        ];

        // 3. Validate
        $errs = [];
        if (strlen($formData['name']) < 2)               $errs[] = 'Name is too short.';
        if (!isValidEmail($formData['email']))            $errs[] = 'Invalid email address.';
        if (strlen($formData['message']) < 10)           $errs[] = 'Message must be at least 10 characters.';

        if ($errs) {
            $error = implode('<br>', $errs);
        } else {

            $mailer = new Mailer();

            // 4a. Notify business owner
            $ownerBody = "
                <h2>New Contact Message</h2>
                <p><strong>Name:</strong> {$formData['name']}</p>
                <p><strong>Email:</strong> {$formData['email']}</p>
                <p><strong>Message:</strong><br>" . nl2br($formData['message']) . "</p>
            ";
            $mailer->notify('New Contact: ' . $formData['name'], $ownerBody);

            // 4b. Auto-reply to user
            $userBody = "
                <h2>Hi {$formData['name']}, thanks for contacting us!</h2>
                <p>We received your message and will reply within 24 hours.</p>
            ";
            $result = $mailer->send([
                'to'      => $formData['email'],
                'to_name' => $formData['name'],
                'subject' => 'We received your message!',
                'body'    => $userBody,
            ]);

            if ($result['success']) {
                $success  = "Thank you, {$formData['name']}! We'll be in touch soon.";
                $formData = [];
            } else {
                $error = 'Email could not be sent. Please try again later.';
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Contact Us</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
<div class="container py-5" style="max-width:560px;">
  <h2 class="mb-4">Contact Us</h2>

  <?php if ($success): ?>
    <div class="alert alert-success"><?= $success ?></div>
  <?php endif; ?>
  <?php if ($error): ?>
    <div class="alert alert-danger"><?= $error ?></div>
  <?php endif; ?>

  <form method="POST" class="bg-white p-4 rounded shadow-sm">
    <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>">

    <div class="mb-3">
      <label class="form-label">Your Name</label>
      <input type="text" name="name" class="form-control"
             value="<?= htmlspecialchars($formData['name'] ?? '') ?>" required>
    </div>

    <div class="mb-3">
      <label class="form-label">Email Address</label>
      <input type="email" name="email" class="form-control"
             value="<?= htmlspecialchars($formData['email'] ?? '') ?>" required>
    </div>

    <div class="mb-3">
      <label class="form-label">Message</label>
      <textarea name="message" class="form-control" rows="5" required><?= htmlspecialchars($formData['message'] ?? '') ?></textarea>
    </div>

    <button type="submit" class="btn btn-primary w-100">Send Message</button>
  </form>
</div>
</body>
</html>
