<?php
/**
 * =============================================================
 *  EXAMPLE 2 — Quote / Order Request Form
 * =============================================================
 *  Demonstrates:
 *   - Multi-field form with selects and date pickers
 *   - Structured notification email to owner
 *   - Personalised auto-reply to customer
 *   - reply_to so owner can reply directly to customer
 * =============================================================
 */
require_once __DIR__ . '/../includes/config.php';

$success  = '';
$error    = '';
$formData = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    if (!verifyCsrf()) {
        $error = 'Security check failed.';
    } else {
        $formData = [
            'name'     => clean($_POST['name']     ?? ''),
            'email'    => filter_var(trim($_POST['email'] ?? ''), FILTER_SANITIZE_EMAIL),
            'phone'    => clean($_POST['phone']    ?? ''),
            'service'  => clean($_POST['service']  ?? ''),
            'budget'   => clean($_POST['budget']   ?? ''),
            'deadline' => clean($_POST['deadline'] ?? ''),
            'details'  => clean($_POST['details']  ?? ''),
        ];

        $errs = [];
        if (strlen($formData['name']) < 2)    $errs[] = 'Please enter your name.';
        if (!isValidEmail($formData['email'])) $errs[] = 'Invalid email address.';
        if (empty($formData['service']))       $errs[] = 'Please select a service.';
        if (empty($formData['deadline']))      $errs[] = 'Please enter a deadline.';

        if ($errs) {
            $error = implode('<br>', $errs);
        } else {
            $mailer = new Mailer();

            // Structured table for owner
            $ownerBody = "
                <h2 style='color:#333;'>New Quote Request</h2>
                <table style='width:100%;border-collapse:collapse;'>
                  <tr style='border-bottom:1px solid #eee;'>
                    <td style='padding:8px;font-weight:bold;width:35%;'>Name</td>
                    <td style='padding:8px;'>{$formData['name']}</td>
                  </tr>
                  <tr style='border-bottom:1px solid #eee;'>
                    <td style='padding:8px;font-weight:bold;'>Email</td>
                    <td style='padding:8px;'><a href='mailto:{$formData['email']}'>{$formData['email']}</a></td>
                  </tr>
                  <tr style='border-bottom:1px solid #eee;'>
                    <td style='padding:8px;font-weight:bold;'>Phone</td>
                    <td style='padding:8px;'>{$formData['phone']}</td>
                  </tr>
                  <tr style='border-bottom:1px solid #eee;'>
                    <td style='padding:8px;font-weight:bold;'>Service</td>
                    <td style='padding:8px;'>{$formData['service']}</td>
                  </tr>
                  <tr style='border-bottom:1px solid #eee;'>
                    <td style='padding:8px;font-weight:bold;'>Budget</td>
                    <td style='padding:8px;'>{$formData['budget']}</td>
                  </tr>
                  <tr style='border-bottom:1px solid #eee;'>
                    <td style='padding:8px;font-weight:bold;'>Deadline</td>
                    <td style='padding:8px;'>{$formData['deadline']}</td>
                  </tr>
                  <tr>
                    <td style='padding:8px;font-weight:bold;'>Details</td>
                    <td style='padding:8px;'>" . nl2br($formData['details']) . "</td>
                  </tr>
                </table>
            ";

            // Notify owner — with reply_to set to customer so owner can just hit Reply
            $mailer->send([
                'to'       => $_ENV['BUSINESS_EMAIL'] ?? '',
                'subject'  => 'New Quote Request: ' . $formData['service'],
                'body'     => $ownerBody,
                'reply_to' => ['email' => $formData['email'], 'name' => $formData['name']],
            ]);

            // Auto-reply to customer
            $userBody = "
                <h2>Hi {$formData['name']},</h2>
                <p>Thank you for your quote request!</p>
                <p>We've received your enquiry for <strong>{$formData['service']}</strong>
                   and will send you a personalised quote within <strong>24–48 hours</strong>.</p>
                <p>If you have any questions in the meantime, feel free to reply to this email.</p>
                <p>— The Team</p>
            ";

            $result = $mailer->send([
                'to'      => $formData['email'],
                'to_name' => $formData['name'],
                'subject' => 'Your Quote Request — We\'ll Be In Touch!',
                'body'    => $userBody,
            ]);

            if ($result['success']) {
                $success  = "Quote request received! We'll contact you within 24–48 hours.";
                $formData = [];
            } else {
                $error = 'Something went wrong. Please try again.';
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Get a Quote</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
<div class="container py-5" style="max-width:640px;">
  <h2 class="mb-4">Get a Free Quote</h2>

  <?php if ($success): ?>
    <div class="alert alert-success"><?= $success ?></div>
  <?php endif; ?>
  <?php if ($error): ?>
    <div class="alert alert-danger"><?= $error ?></div>
  <?php endif; ?>

  <form method="POST" class="bg-white p-4 rounded shadow-sm">
    <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>">

    <div class="row g-3">
      <div class="col-md-6">
        <label class="form-label">Full Name *</label>
        <input type="text" name="name" class="form-control"
               value="<?= htmlspecialchars($formData['name'] ?? '') ?>" required>
      </div>
      <div class="col-md-6">
        <label class="form-label">Email Address *</label>
        <input type="email" name="email" class="form-control"
               value="<?= htmlspecialchars($formData['email'] ?? '') ?>" required>
      </div>
      <div class="col-md-6">
        <label class="form-label">Phone Number</label>
        <input type="tel" name="phone" class="form-control"
               value="<?= htmlspecialchars($formData['phone'] ?? '') ?>">
      </div>
      <div class="col-md-6">
        <label class="form-label">Service Required *</label>
        <select name="service" class="form-select" required>
          <option value="">-- Select --</option>
          <option value="Basic Website">Basic Website</option>
          <option value="E-Commerce Store">E-Commerce Store</option>
          <option value="Logo Design">Logo Design</option>
          <option value="Social Media Pack">Social Media Pack</option>
        </select>
      </div>
      <div class="col-md-6">
        <label class="form-label">Budget Range</label>
        <select name="budget" class="form-select">
          <option value="">-- Select --</option>
          <option value="Under ₹15,000">Under ₹15,000</option>
          <option value="₹15,000 – ₹30,000">₹15,000 – ₹30,000</option>
          <option value="₹30,000 – ₹75,000">₹30,000 – ₹75,000</option>
          <option value="₹75,000+">₹75,000+</option>
        </select>
      </div>
      <div class="col-md-6">
        <label class="form-label">Deadline *</label>
        <input type="date" name="deadline" class="form-control"
               value="<?= htmlspecialchars($formData['deadline'] ?? '') ?>"
               min="<?= date('Y-m-d', strtotime('+1 day')) ?>" required>
      </div>
      <div class="col-12">
        <label class="form-label">Project Details</label>
        <textarea name="details" class="form-control" rows="4"
                  placeholder="Describe what you need..."><?= htmlspecialchars($formData['details'] ?? '') ?></textarea>
      </div>
    </div>

    <button type="submit" class="btn btn-primary w-100 mt-4">Submit Quote Request</button>
  </form>
</div>
</body>
</html>
