<?php
/**
 * =============================================================
 *  EXAMPLE 3 — OTP / Email Verification
 * =============================================================
 *  Demonstrates:
 *   - Generating a 6-digit OTP
 *   - Sending OTP via email
 *   - Storing OTP in session with expiry
 *   - Verifying the OTP on submission
 * =============================================================
 */
require_once __DIR__ . '/../includes/config.php';

$step    = $_SESSION['otp_step'] ?? 'enter_email';
$message = '';
$error   = '';

// ---- STEP 1: User submits email, we send OTP ----
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['send_otp'])) {

    if (!verifyCsrf()) { $error = 'Security check failed.'; }
    else {
        $email = filter_var(trim($_POST['email'] ?? ''), FILTER_SANITIZE_EMAIL);

        if (!isValidEmail($email)) {
            $error = 'Please enter a valid email address.';
        } else {
            // Generate 6-digit OTP
            $otp = str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT);

            // Store in session with 10-minute expiry
            $_SESSION['otp_code']    = $otp;
            $_SESSION['otp_email']   = $email;
            $_SESSION['otp_expires'] = time() + 600; // 10 minutes

            // Send email
            $mailer  = new Mailer();
            $body    = "
                <div style='font-family:sans-serif;text-align:center;padding:40px;'>
                    <h2>Your Verification Code</h2>
                    <p style='font-size:14px;color:#666;'>Use this code to verify your email. Valid for 10 minutes.</p>
                    <div style='font-size:48px;font-weight:bold;letter-spacing:12px;color:#333;
                                background:#f5f5f5;padding:24px;border-radius:8px;margin:24px auto;
                                display:inline-block;'>
                        {$otp}
                    </div>
                    <p style='font-size:12px;color:#999;margin-top:24px;'>
                        If you didn't request this, please ignore this email.
                    </p>
                </div>
            ";

            $result = $mailer->send([
                'to'      => $email,
                'subject' => 'Your OTP Code: ' . $otp,
                'body'    => $body,
            ]);

            if ($result['success']) {
                $_SESSION['otp_step'] = 'verify_otp';
                $step    = 'verify_otp';
                $message = "OTP sent to $email. Check your inbox (also check spam).";
            } else {
                $error = 'Could not send OTP. Please try again.';
            }
        }
    }
}

// ---- STEP 2: User submits OTP ----
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['verify_otp'])) {

    if (!verifyCsrf()) { $error = 'Security check failed.'; }
    else {
        $enteredOtp = clean($_POST['otp'] ?? '');
        $storedOtp  = $_SESSION['otp_code']    ?? '';
        $expires    = $_SESSION['otp_expires'] ?? 0;

        if (time() > $expires) {
            $error = 'OTP has expired. Please request a new one.';
            unset($_SESSION['otp_code'], $_SESSION['otp_step'], $_SESSION['otp_expires']);
            $step = 'enter_email';

        } elseif ($enteredOtp !== $storedOtp) {
            $error = 'Incorrect OTP. Please try again.';

        } else {
            // SUCCESS — email verified
            $_SESSION['email_verified'] = $_SESSION['otp_email'];
            unset($_SESSION['otp_code'], $_SESSION['otp_step'], $_SESSION['otp_expires'], $_SESSION['otp_email']);
            $step    = 'verified';
            $message = 'Email verified successfully!';
        }
    }
}

// Resend OTP
if (isset($_GET['resend'])) {
    unset($_SESSION['otp_code'], $_SESSION['otp_step'], $_SESSION['otp_expires']);
    $step = 'enter_email';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Email Verification</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
<div class="container py-5" style="max-width:440px;">
  <h3 class="mb-4 text-center">Email Verification</h3>

  <?php if ($message): ?>
    <div class="alert alert-success"><?= $message ?></div>
  <?php endif; ?>
  <?php if ($error): ?>
    <div class="alert alert-danger"><?= $error ?></div>
  <?php endif; ?>

  <?php if ($step === 'enter_email'): ?>
    <form method="POST" class="bg-white p-4 rounded shadow-sm">
      <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>">
      <div class="mb-3">
        <label class="form-label">Your Email Address</label>
        <input type="email" name="email" class="form-control" placeholder="you@example.com" required>
      </div>
      <button type="submit" name="send_otp" class="btn btn-primary w-100">Send OTP</button>
    </form>

  <?php elseif ($step === 'verify_otp'): ?>
    <form method="POST" class="bg-white p-4 rounded shadow-sm">
      <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>">
      <p class="text-muted text-center small">Sent to: <strong><?= htmlspecialchars($_SESSION['otp_email'] ?? '') ?></strong></p>
      <div class="mb-3">
        <label class="form-label">Enter 6-digit OTP</label>
        <input type="text" name="otp" class="form-control text-center fs-4 letter-spacing-wide"
               maxlength="6" placeholder="000000" required autocomplete="one-time-code"
               style="letter-spacing:8px;font-size:1.6rem;">
      </div>
      <button type="submit" name="verify_otp" class="btn btn-success w-100">Verify OTP</button>
      <div class="text-center mt-3">
        <a href="?resend=1" class="text-muted small">Resend OTP</a>
      </div>
    </form>

  <?php elseif ($step === 'verified'): ?>
    <div class="bg-white p-4 rounded shadow-sm text-center">
      <div style="font-size:3rem;">✅</div>
      <h5 class="mt-3">Email Verified!</h5>
      <p class="text-muted"><?= htmlspecialchars($_SESSION['email_verified'] ?? '') ?> is now verified.</p>
    </div>
  <?php endif; ?>
</div>
</body>
</html>
