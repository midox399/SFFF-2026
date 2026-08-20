<?php
/**
 * Small helpers for JSON API endpoints: consistent success/error output,
 * generic error messages for anything unexpected (never leak SQL errors or
 * stack traces to the client), and a couple of shared validation checks.
 */

declare(strict_types=1);

// Never let a misconfigured host leak PHP errors/warnings/stack traces
// (path disclosure, SQL details, etc.) to API clients. Errors are still
// logged server-side via error_log() in each endpoint's catch block.
ini_set('display_errors', '0');
error_reporting(E_ALL);

function json_success(array $data, int $status = 200): void
{
    http_response_code($status);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(array_merge(['success' => true], $data), JSON_UNESCAPED_UNICODE);
    exit;
}

function json_error(string $message, int $status = 400): void
{
    http_response_code($status);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(['success' => false, 'error' => $message], JSON_UNESCAPED_UNICODE);
    exit;
}

/**
 * Reads and decodes a JSON request body. Returns [] if the body is empty
 * or not valid JSON (callers should then fail their own required-field
 * checks rather than trust an empty array).
 */
function read_json_body(): array
{
    $raw = file_get_contents('php://input');
    if ($raw === false || $raw === '') {
        return [];
    }
    $decoded = json_decode($raw, true);
    return is_array($decoded) ? $decoded : [];
}

function require_post_method(): void
{
    if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
        json_error('Method not allowed.', 405);
    }
}

function is_valid_email(string $email): bool
{
    return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
}

/**
 * Simple file-based rate limiter shared by every public API endpoint and
 * the admin login form (see includes/auth.php). Not a substitute for a
 * WAF, but stops trivial brute-forcing/enumeration without needing a
 * separate DB table — same convention as login_rate_limit_check(), just
 * generalized so callers pick their own key/limit/window.
 *
 * Uses flock() around the read-modify-write so two near-simultaneous
 * requests can't both read "under the limit" and both be let through.
 * Responds with a 429 JSON error (via json_error(), which exits) if the
 * key has already hit $maxAttempts within $windowSeconds; otherwise
 * records this attempt and returns.
 */
function api_rate_limit_check(string $key, int $maxAttempts, int $windowSeconds): void
{
    $dir = sys_get_temp_dir() . '/sfff_api_rate_limits';
    if (!is_dir($dir)) {
        @mkdir($dir, 0700, true);
    }
    $file = $dir . '/' . md5($key);

    $handle = @fopen($file, 'c+');
    if ($handle === false) {
        // Can't open the lock file — fail open rather than block every
        // request; this only happens if the temp dir is misconfigured.
        return;
    }

    flock($handle, LOCK_EX);

    $raw = stream_get_contents($handle);
    $attempts = $raw ? (json_decode($raw, true) ?: []) : [];

    $windowStart = time() - $windowSeconds;
    $attempts = array_values(array_filter($attempts, fn($ts) => $ts > $windowStart));

    if (count($attempts) >= $maxAttempts) {
        flock($handle, LOCK_UN);
        fclose($handle);
        json_error('Too many requests. Please wait a few minutes and try again.', 429);
    }

    $attempts[] = time();
    ftruncate($handle, 0);
    rewind($handle);
    fwrite($handle, json_encode($attempts));
    fflush($handle);
    flock($handle, LOCK_UN);
    fclose($handle);
}

/**
 * Cheap abuse signal for state-changing public endpoints: the Fetch
 * Metadata `Sec-Fetch-Site` header tells us whether a request's declared
 * origin is same-site. This is NOT a CSRF defense (no session exists on
 * these public endpoints to hijack, and the header isn't sent by every
 * client) — it's a low-cost filter stacked on top of rate limiting to
 * reject the cheapest cross-site scripted abuse. Absent header = allowed
 * through, since older browsers and non-browser HTTP clients don't send
 * it and treating "absent" as "blocked" would break legitimate use.
 */
function check_sec_fetch_site(): void
{
    $secFetchSite = $_SERVER['HTTP_SEC_FETCH_SITE'] ?? '';
    if ($secFetchSite === 'cross-site') {
        json_error('Request not allowed.', 403);
    }
}

/**
 * Generates a short, non-sequential, human-readable reference like
 * GV-2026-8F42KQR9. Uses random_bytes (CSPRNG), not uniqid()/rand(), so
 * references can't be guessed/enumerated from one known reference.
 * 8 characters over a 32-symbol alphabet ≈ 1.1×10^12 combinations —
 * long enough that brute-forcing the space is impractical even without
 * rate limiting, though rate limiting (see api_rate_limit_check()) is
 * still the primary defense.
 */
function generate_reference(PDO $pdo, string $table, string $prefix = 'GV-2026-'): string
{
    $alphabet = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789'; // no 0/O/1/I to avoid confusion
    $length = 8;
    for ($attempt = 0; $attempt < 10; $attempt++) {
        $code = '';
        $bytes = random_bytes($length);
        for ($i = 0; $i < $length; $i++) {
            $code .= $alphabet[ord($bytes[$i]) % strlen($alphabet)];
        }
        $reference = $prefix . $code;

        $stmt = $pdo->prepare("SELECT 1 FROM `$table` WHERE reference = ? LIMIT 1");
        $stmt->execute([$reference]);
        if ($stmt->fetchColumn() === false) {
            return $reference;
        }
    }
    // Astronomically unlikely with an 8-char space over 32 chars, but never
    // loop forever — surface a clear error instead of an infinite loop.
    throw new RuntimeException('Could not generate a unique reference after 10 attempts.');
}
