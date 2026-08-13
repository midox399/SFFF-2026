<?php
/**
 * Copy this file to config/database.php and fill in real values.
 * config/database.php is gitignored — never commit real credentials.
 */

define('DB_HOST', 'localhost');
define('DB_NAME', 'your_database_name');
define('DB_USER', 'your_database_user');
define('DB_PASSWORD', 'your_database_password');
define('DB_CHARSET', 'utf8mb4');

/**
 * Shared secret for verifying incoming payment webhooks (see
 * includes/payment_provider.php). Generate a real random value for
 * production, e.g. by running once in a PHP shell:
 *   php -r "echo bin2hex(random_bytes(32));"
 * Once a real gateway (Konnect/Paymee) is wired up, replace the check in
 * payment_provider_verify_webhook() with that provider's own signature
 * scheme instead — this constant is the interim/stub protection.
 */
define('PAYMENT_WEBHOOK_SECRET', 'change-me-to-a-random-64-char-hex-value');
