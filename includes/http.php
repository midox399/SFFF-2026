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
 * Generates a short, non-sequential, human-readable reference like
 * GV-2026-8F42K. Uses random_bytes (CSPRNG), not uniqid()/rand(), so
 * references can't be guessed/enumerated from one known reference.
 */
function generate_reference(PDO $pdo, string $table, string $prefix = 'GV-2026-'): string
{
    $alphabet = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789'; // no 0/O/1/I to avoid confusion
    for ($attempt = 0; $attempt < 10; $attempt++) {
        $code = '';
        $bytes = random_bytes(5);
        for ($i = 0; $i < 5; $i++) {
            $code .= $alphabet[ord($bytes[$i]) % strlen($alphabet)];
        }
        $reference = $prefix . $code;

        $stmt = $pdo->prepare("SELECT 1 FROM `$table` WHERE reference = ? LIMIT 1");
        $stmt->execute([$reference]);
        if ($stmt->fetchColumn() === false) {
            return $reference;
        }
    }
    // Astronomically unlikely with a 5-char space over 33 chars, but never
    // loop forever — surface a clear error instead of an infinite loop.
    throw new RuntimeException('Could not generate a unique reference after 10 attempts.');
}
