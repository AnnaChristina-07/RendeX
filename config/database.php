<?php
/**
 * Database Configuration for RendeX
 * This file handles the MySQL database connection using PDO
 */

// Database credentials
define('DB_HOST', 'localhost');
define('DB_NAME', 'rendex_db');
define('DB_USER', 'root');
define('DB_PASS', ''); // Default XAMPP MySQL has no password

/**
 * Get PDO database connection
 * @return PDO|null
 */
function getDBConnection() {
    try {
        $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4";
        $options = [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false
        ];
        
        $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
        return $pdo;
    } catch (PDOException $e) {
        error_log("Database connection failed: " . $e->getMessage());
        return null;
    }
}

/**
 * Get MySQLi connection (for legacy compatibility)
 * @return mysqli|null
 */
function getMySQLiConnection() {
    $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
    
    if ($conn->connect_error) {
        error_log("MySQLi connection failed: " . $conn->connect_error);
        return null;
    }
    
    $conn->set_charset("utf8mb4");
    return $conn;
}

// Create a global connection instance
$pdo = getDBConnection();
?>
