<?php
/**
 * CLI-only script to create (or reset the password of) an admin account.
 *
 * Usage:
 *   php scripts/create_admin.php you@example.com "a-strong-password"
 *
 * Run this from a terminal with PHP CLI access on the server (SSH, or
 * Hostinger's "Run PHP script" if offered). It is deliberately NOT web-
 * accessible logic — there is no admin/register.php — so the only way to
 * create an admin is by running this script directly.
 */

declare(strict_types=1);

if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    exit('This script can only be run from the command line.');
}

require_once __DIR__ . '/../includes/db.php';

$email = $argv[1] ?? null;
$password = $argv[2] ?? null;

if (!$email || !$password) {
    fwrite(STDERR, "Usage: php scripts/create_admin.php <email> <password>\n");
    exit(1);
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    fwrite(STDERR, "Error: '$email' is not a valid email address.\n");
    exit(1);
}

if (strlen($password) < 10) {
    fwrite(STDERR, "Error: password must be at least 10 characters.\n");
    exit(1);
}

try {
    $pdo = get_db();
    $hash = password_hash($password, PASSWORD_DEFAULT);

    $existing = $pdo->prepare('SELECT id FROM admins WHERE email = ? LIMIT 1');
    $existing->execute([$email]);
    $existingId = $existing->fetchColumn();

    if ($existingId) {
        $stmt = $pdo->prepare('UPDATE admins SET password_hash = ? WHERE id = ?');
        $stmt->execute([$hash, $existingId]);
        echo "Password updated for existing admin '$email' (id=$existingId).\n";
    } else {
        $stmt = $pdo->prepare('INSERT INTO admins (email, password_hash) VALUES (?, ?)');
        $stmt->execute([$email, $hash]);
        echo "Admin created: $email (id=" . $pdo->lastInsertId() . ")\n";
    }
} catch (Throwable $e) {
    fwrite(STDERR, 'Error: ' . $e->getMessage() . "\n");
    exit(1);
}
