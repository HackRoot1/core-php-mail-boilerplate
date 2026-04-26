<?php
/**
 * ================================================================
 *  config.php  —  Project Bootstrap File
 * ================================================================
 *  COPY THIS FILE TO ANY PROJECT.
 *
 *  This file does 4 things:
 *    1. Loads your .env credentials into $_ENV
 *    2. Sets error reporting (on in dev, off in production)
 *    3. Starts PHP session
 *    4. Provides helper functions: csrfToken(), verifyCsrf(), clean()
 *
 *  HOW TO USE:
 *    Add this ONE LINE at the top of every PHP page:
 *    require_once __DIR__ . '/includes/config.php';
 *
 *  FOLDER ASSUMPTION:
 *    config.php lives in:  your-project/includes/config.php
 *    .env lives in:        your-project/.env
 *    Mailer.php lives in:  your-project/includes/Mailer.php
 *    vendor/ lives in:     your-project/vendor/
 * ================================================================
 */

// ================================================================
//  STEP 1: Load .env file
// ================================================================
function loadEnv(string $path): void
{
    if (!file_exists($path)) {
        // Silently skip if .env missing — won't crash the site
        return;
    }

    $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);

    foreach ($lines as $line) {
        $line = trim($line);

        // Skip comments and blank lines
        if ($line === '' || str_starts_with($line, '#')) continue;

        // Split on first = only
        if (!str_contains($line, '=')) continue;
        [$key, $value] = array_map('trim', explode('=', $line, 2));

        // Strip surrounding quotes: "value" or 'value' → value
        $value = trim($value, "\"'");

        // Don't overwrite values already set by the server environment
        if (!array_key_exists($key, $_SERVER) && !array_key_exists($key, $_ENV)) {
            putenv("$key=$value");
            $_ENV[$key]    = $value;
            $_SERVER[$key] = $value;
        }
    }
}

// .env is always in project root (one level above includes/)
loadEnv(__DIR__ . '/../.env');

// ================================================================
//  STEP 2: Site constants (from .env or safe defaults)
// ================================================================
define('SITE_NAME', $_ENV['SITE_NAME'] ?? 'My Website');
define('SITE_URL',  $_ENV['SITE_URL']  ?? 'http://localhost');

// ================================================================
//  STEP 3: Error reporting
//  In .env: set ENVIRONMENT=production  → errors hidden
//           set ENVIRONMENT=development → errors shown
// ================================================================
$_appEnv = $_ENV['ENVIRONMENT'] ?? 'development';

if ($_appEnv === 'production') {
    error_reporting(0);
    ini_set('display_errors', '0');
    ini_set('log_errors', '1');                // Still log to server error log
    ini_set('error_log', __DIR__ . '/../logs/error.log');
} else {
    error_reporting(E_ALL);
    ini_set('display_errors', '1');
}

// ================================================================
//  STEP 4: Timezone (change for your country)
// ================================================================
date_default_timezone_set($_ENV['TIMEZONE'] ?? 'Asia/Kolkata');

// ================================================================
//  STEP 5: Load Composer autoloader (loads PHPMailer etc.)
// ================================================================
$_composerAutoload = __DIR__ . '/../vendor/autoload.php';
if (file_exists($_composerAutoload)) {
    require_once $_composerAutoload;
} else {
    die('<b>Setup Error:</b> vendor/ folder missing. Run <code>composer install</code>.');
}

// ================================================================
//  STEP 6: Load Mailer class
// ================================================================
require_once __DIR__ . '/Mailer.php';

// ================================================================
//  STEP 7: Start Session
// ================================================================
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// ================================================================
//  HELPER FUNCTIONS
//  These are available everywhere after including config.php
// ================================================================

/**
 * Generate or retrieve CSRF token for the current session.
 * Use in forms: <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>">
 */
function csrfToken(): string
{
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

/**
 * Verify CSRF token submitted with a form.
 * Use at the top of any POST handler:
 *   if (!verifyCsrf()) { die('Invalid request.'); }
 */
function verifyCsrf(): bool
{
    $submitted = $_POST['csrf_token'] ?? '';
    $stored    = $_SESSION['csrf_token'] ?? '';
    return $submitted !== '' && hash_equals($stored, $submitted);
}

/**
 * Sanitise any user input before using it.
 * Removes HTML tags, trims whitespace, encodes special characters.
 * Use on every $_POST/$_GET value before storing or displaying.
 */
function clean(string $input): string
{
    return htmlspecialchars(strip_tags(trim($input)), ENT_QUOTES, 'UTF-8');
}

/**
 * Validate an email address.
 * Returns the clean email or false.
 */
function cleanEmail(string $input): string|false
{
    $email = filter_var(trim($input), FILTER_SANITIZE_EMAIL);
    return filter_var($email, FILTER_VALIDATE_EMAIL) ? $email : false;
}

/**
 * Redirect to a URL and stop execution.
 */
function redirect(string $url): void
{
    header('Location: ' . $url);
    exit;
}
