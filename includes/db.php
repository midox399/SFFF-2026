<?php
/**
 * Shared PDO connection. Include this, then use get_db() to get the
 * singleton PDO instance. Never echo raw exception messages to the client —
 * see json_error() in includes/http.php for how errors should surface.
 */

declare(strict_types=1);

function get_db(): PDO
{
    static $pdo = null;
    if ($pdo !== null) {
        return $pdo;
    }

    $configPath = __DIR__ . '/../config/database.php';
    if (!file_exists($configPath)) {
        error_log('config/database.php is missing — copy config/database.example.php and fill in real credentials.');
        throw new RuntimeException('Database is not configured.');
    }
    require_once $configPath;

    $dsn = sprintf(
        'mysql:host=%s;dbname=%s;charset=%s',
        DB_HOST,
        DB_NAME,
        defined('DB_CHARSET') ? DB_CHARSET : 'utf8mb4'
    );

    $pdo = new PDO($dsn, DB_USER, DB_PASSWORD, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ]);

    return $pdo;
}
