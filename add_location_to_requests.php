<?php
require_once 'config/database.php';

try {
    $pdo = getDBConnection();
    
    // Add location column to item_requests table if it doesn't exist
    // MySQL doesn't have IF NOT EXISTS for ADD COLUMN in a simple way in older versions, 
    // but we can try-catch or query information_schema.
    
    $stmt = $pdo->prepare("SELECT count(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = :db AND TABLE_NAME = 'item_requests' AND COLUMN_NAME = 'location'");
    $stmt->execute(['db' => 'rendex_db']); // Assuming db name, but connection handles it usually.
    // Actually, getting DB name from config might be hard if not exposed.
    // Easier way: just try to add it.
    
    try {
        $sql = "ALTER TABLE item_requests ADD COLUMN location VARCHAR(255) DEFAULT NULL AFTER category";
        $pdo->exec($sql);
        echo "Column 'location' added to 'item_requests' table successfully.";
    } catch (PDOException $e) {
        if (strpos($e->getMessage(), 'Duplicate column') !== false) {
            echo "Column 'location' already exists.";
        } else {
            echo "Error adding column: " . $e->getMessage();
        }
    }

} catch (PDOException $e) {
    die("DB Error: " . $e->getMessage());
}
?>
