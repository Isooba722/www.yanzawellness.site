<?php
/**
 * Database Connection Setup using PDO
 */

// Import passwords configuration
require_once __DIR__ . '/passwords.php';

/**
 * Returns a secure PDO database connection instance.
 * @return PDO
 */
function getDBConnection() {
    static $pdo = null;
    if ($pdo === null) {
        try {
            $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4";
            $options = [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES   => false,
            ];
            $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
        } catch (PDOException $e) {
            // Log database errors silently for security
            error_log("Database connection error: " . $e->getMessage());
            die("Unable to connect to the database. Please verify your credentials in passwords.php.");
        }
    }
    return $pdo;
}
