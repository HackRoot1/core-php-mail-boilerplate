<?php
/**
 * =============================================================
 *  config.php  —  Project Bootstrap / Config Loader
 * =============================================================
 *  Loads .env, sets error mode, starts session, provides helpers.
 *  Include this ONE line at the top of EVERY PHP page:
 *
 *      require_once __DIR__ . '/includes/config.php';
 *
 *  Adjust the loadEnv() path if your folder structure differs.
 * =============================================================
 */

// ================================================================
//  STEP 1 — Load .env file  (no third-party library needed)
// ================================================================
function loadEnv(string $filePath): void
{
    if (!file_exists($filePath)) {
        // Silently skip — production servers may use real env vars
        return;
    }

    $lines = file($filePath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);

    foreach ($lines as $line) {
        $line = trim($line);

        // Skip comments and blank lines
        if ($line === '' || str_starts_with($line, '#')) {
            continue;
        }

        // Split on first = only
        if (!str_contains($line, '=')) {
            continue;
        }

        [$key, $value] = array_map('trim', explode('=', $line, 2));

        // Strip optional surrounding quotes: KEY="value" or KEY='value'
        $value = trim($value, "\"'");

        // Don't override real server environment variables
        if (!array_key_exists($key, $_SERVER) && !array_key_exists($key, $_ENV)) {
            putenv("$key=$value");
            $_ENV[$key]    = $value;
            $_SERVER[$key] = $value;
        }
    }
}

// ---- Load from project root (.env sits next to public_html on live) ----
// Adjust '../' levels to match where .env lives relative to this file.
// Common setups:
//   includes/config.php  →  ../.env         (one level up = project root)
//   public/includes/config.php → ../../.env (two levels up)
loadEnv(__DIR__ . '/../.env');

// ================================================================
//  STEP 2 — Site-wide Constants
// ================================================================
define('SITE_NAME', $_ENV['SITE_NAME'] ?? 'My Website');
define('SITE_URL',  $_ENV['SITE_URL']  ?? 'http://localhost');

// ================================================================
//  STEP 3 — Environment / Error Reporting
// ================================================================
// Set ENVIRONMENT=production in .env for live server.
// Set ENVIRONMENT=development for local (shows errors).
$appEnv = $_ENV['ENVIRONMENT'] ?? 'development';

if ($appEnv === 'production') {
    error_reporting(0);
    ini_set('display_errors', '0');
    ini_set('log_errors', '1');
} else {
    error_reporting(E_ALL);
    ini_set('display_errors', '1');
}

// ================================================================
//  STEP 4 — Timezone (change to your region)
// ================================================================
// Common values:
//   Asia/Kolkata       IST (+5:30)
//   Asia/Dubai         GST (+4:00)
//   Europe/London      GMT/BST
//   America/New_York   EST/EDT
//   America/Los_Angeles PST/PDT
date_default_timezone_set($_ENV['TIMEZONE'] ?? 'Asia/Kolkata');

// ================================================================
//  STEP 5 — Load Mailer class
// ================================================================
require_once __DIR__ . '/Mailer.php';

// ================================================================
//  STEP 6 — Session Start (needed for CSRF)
// ================================================================
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// ================================================================
//  STEP 7 — Helper Functions (available everywhere after include)
// ================================================================

/**
 * Generate or return the current CSRF token.
 * Use in forms:  <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>">
 */
function csrfToken(): string
{
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

/**
 * Verify CSRF token submitted via POST.
 * Call at the top of every POST handler:  if (!verifyCsrf()) { die('Invalid token'); }
 */
function verifyCsrf(): bool
{
    $submitted = $_POST['csrf_token'] ?? '';
    $stored    = $_SESSION['csrf_token'] ?? '';
    return $submitted !== '' && hash_equals($stored, $submitted);
}

/**
 * Sanitise user input — use on ALL $_POST / $_GET values before using them.
 * Removes HTML tags, trims whitespace, encodes special characters.
 */
function clean(string $input): string
{
    return htmlspecialchars(strip_tags(trim($input)), ENT_QUOTES, 'UTF-8');
}

/**
 * Validate an email address.
 */
function isValidEmail(string $email): bool
{
    return (bool) filter_var($email, FILTER_VALIDATE_EMAIL);
}

/**
 * Redirect to a URL and stop execution.
 */
function redirect(string $url): void
{
    header('Location: ' . $url);
    exit;
}
