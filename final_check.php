<?php
require_once 'config/database.php';

try {
    $pdo = getDBConnection();
    if (!$pdo) {
        throw new Exception("Connection failed");
    }
    echo "Database Config: " . DB_HOST . "\n";
    echo "Connection: OK\n";
    
    // Check Users
    $stmt = $pdo->query("SELECT COUNT(*) FROM users");
    echo "User Count: " . $stmt->fetchColumn() . "\n";
    
    // Check Admin
    $stmt = $pdo->query("SELECT id, email, role FROM users WHERE role = 'admin'");
    $admin = $stmt->fetch();
    if ($admin) {
        echo "Admin Found: " . $admin['email'] . "\n";
    } else {
        echo "Admin NOT Found\n";
    }
    
    // Check Tables
    $stmt = $pdo->query("SHOW TABLES");
    $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);
    echo "Tables: " . implode(", ", $tables) . "\n";
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?>
