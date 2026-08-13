<?php
/**
 * Session bootstrap, CSRF tokens, and login rate limiting for the admin
 * area. Include this at the top of every /admin/*.php page before any
 * output.
 */

declare(strict_types=1);

// Same rationale as includes/http.php: never leak PHP errors to the browser
// in this admin area, even if the host's php.ini has display_errors on.
ini_set('display_errors', '0');
error_reporting(E_ALL);

function start_secure_session(): void
{
    if (session_status() === PHP_SESSION_ACTIVE) {
        return;
    }

    $isHttps = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
        || (($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https');

    session_set_cookie_params([
        'lifetime' => 0,
        'path' => '/',
        'domain' => '',
        'secure' => $isHttps,
        'httponly' => true,
        'samesite' => 'Lax',
    ]);
    session_name('sfff_admin_session');
    session_start();
}

function current_admin_id(): ?int
{
    start_secure_session();
    return isset($_SESSION['admin_id']) ? (int)$_SESSION['admin_id'] : null;
}

/** Redirects to the login page if not authenticated. Call at the top of every protected admin page. */
function require_admin_auth(): void
{
    if (current_admin_id() === null) {
        header('Location: login.php');
        exit;
    }
}

/** For admin API-style POST handlers that should return JSON instead of redirecting. */
function require_admin_auth_json(): void
{
    if (current_admin_id() === null) {
        http_response_code(401);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(['success' => false, 'error' => 'Not authenticated.']);
        exit;
    }
}

function csrf_token(): string
{
    start_secure_session();
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function csrf_field(): string
{
    $token = htmlspecialchars(csrf_token(), ENT_QUOTES, 'UTF-8');
    return '<input type="hidden" name="csrf_token" value="' . $token . '">';
}

/** Verifies the csrf_token POST field. Call at the top of every admin POST handler. */
function require_valid_csrf(): void
{
    start_secure_session();
    $submitted = $_POST['csrf_token'] ?? '';
    $expected = $_SESSION['csrf_token'] ?? '';
    if ($submitted === '' || $expected === '' || !hash_equals($expected, $submitted)) {
        http_response_code(403);
        exit('Invalid or expired security token. Please go back and try again.');
    }
}

/**
 * Simple file-based login rate limiter: max 5 attempts per email+IP per
 * 15 minutes. Not a substitute for a WAF, but stops trivial brute-forcing
 * without needing a separate table or extra infra.
 */
function login_rate_limit_check(string $email): void
{
    $key = md5(strtolower($email) . '|' . ($_SERVER['REMOTE_ADDR'] ?? ''));
    $dir = sys_get_temp_dir() . '/sfff_login_attempts';
    if (!is_dir($dir)) {
        @mkdir($dir, 0700, true);
    }
    $file = $dir . '/' . $key;

    $attempts = [];
    if (is_file($file)) {
        $raw = @file_get_contents($file);
        $attempts = $raw ? (json_decode($raw, true) ?: []) : [];
    }

    $windowStart = time() - 900; // 15 minutes
    $attempts = array_values(array_filter($attempts, fn($ts) => $ts > $windowStart));

    if (count($attempts) >= 5) {
        http_response_code(429);
        exit('Too many login attempts. Please wait 15 minutes and try again.');
    }

    $attempts[] = time();
    @file_put_contents($file, json_encode($attempts));
}

function login_rate_limit_reset(string $email): void
{
    $key = md5(strtolower($email) . '|' . ($_SERVER['REMOTE_ADDR'] ?? ''));
    $file = sys_get_temp_dir() . '/sfff_login_attempts/' . $key;
    if (is_file($file)) {
        @unlink($file);
    }
}

function h(string $value): string
{
    return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
}
