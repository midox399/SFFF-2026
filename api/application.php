<?php
/**
 * POST /api/application.php
 * Body (JSON): { fullName, email, phone, applicationTypeIndex, message }
 *
 * Stores an "Espace Professionnels" application. `applicationTypeIndex` is
 * the 0-based tab index from index.html's `pro.tabs` arrays (identical
 * order in FR/EN/AR — see APPLICATION_TYPES below), sent instead of the
 * displayed label so validation doesn't depend on which language the
 * visitor had selected.
 */

declare(strict_types=1);

require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/http.php';

require_post_method();

// Canonical (English) labels stored in the DB, indexed to match the tab
// order in index.html's pro.tabs arrays: ["Investisseur","Sponsor",
// "Exposant / Kiosque","Ambassade","Média & Presse","Visiteur"].
const APPLICATION_TYPES = [
    'Investor',
    'Sponsor',
    'Exhibitor / Kiosk',
    'Embassy',
    'Media & Press',
    'Visitor',
];

$body = read_json_body();

$fullName = trim((string)($body['fullName'] ?? ''));
$email = trim((string)($body['email'] ?? ''));
$phone = trim((string)($body['phone'] ?? ''));
$typeIndexRaw = $body['applicationTypeIndex'] ?? null;
$message = trim((string)($body['message'] ?? ''));

$errors = [];

if ($fullName === '' || mb_strlen($fullName) > 200) {
    $errors[] = 'full name is required';
}
if ($email === '' || !is_valid_email($email) || mb_strlen($email) > 255) {
    $errors[] = 'a valid email is required';
}
if (mb_strlen($phone) > 30) {
    $errors[] = 'phone is too long';
}
if (!is_numeric($typeIndexRaw) || (int)$typeIndexRaw != $typeIndexRaw
    || (int)$typeIndexRaw < 0 || (int)$typeIndexRaw >= count(APPLICATION_TYPES)) {
    $errors[] = 'application type is required';
}
if (mb_strlen($message) > 5000) {
    $errors[] = 'message is too long';
}

if ($errors) {
    json_error(implode('; ', $errors), 422);
}

$applicationType = APPLICATION_TYPES[(int)$typeIndexRaw];

try {
    $pdo = get_db();
    $stmt = $pdo->prepare(
        'INSERT INTO applications
            (full_name, email, phone, application_type, message, status)
         VALUES (?, ?, ?, ?, ?, \'pending\')'
    );
    $stmt->execute([$fullName, $email, $phone ?: null, $applicationType, $message ?: null]);

    json_success(['message' => 'Application received successfully.'], 201);
} catch (Throwable $e) {
    error_log('[application] ' . $e->getMessage());
    json_error('Something went wrong while saving your application. Please try again.', 500);
}
