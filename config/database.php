<?php
// ============================================================
// Database Configuration
// Credentials are read from environment variables so the same
// file works under Docker (docker-compose.yml) and locally.
// ============================================================

define('DB_HOST', getenv('DB_HOST') ?: 'localhost');
define('DB_USER', getenv('DB_USER') ?: 'root');
define('DB_PASS', getenv('DB_PASSWORD') ?: '');
define('DB_NAME', getenv('DB_NAME') ?: 'oems_db');
define('DB_PORT', (int)(getenv('DB_PORT') ?: 3306));

/**
 * Returns a new MySQLi connection.
 * Terminates the script if the connection fails.
 */
function getDBConnection(): mysqli
{
    $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME, DB_PORT);

    if ($conn->connect_error) {
        // In production, log the error rather than exposing it
        error_log('DB connection failed: ' . $conn->connect_error);
        die(json_encode(['error' => 'Database connection failed. Please contact the administrator.']));
    }

    $conn->set_charset('utf8mb4');
    return $conn;
}
