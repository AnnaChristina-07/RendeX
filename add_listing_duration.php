<?php
require_once 'config/database.php';

try {
    $pdo = getDBConnection();
    
    // Check if column exists
    $stmt = $pdo->prepare("SHOW COLUMNS FROM items LIKE 'active_until'");
    $stmt->execute();
    $exists = $stmt->fetch();
    
    if (!$exists) {
        // Add active_until column
        $sql = "ALTER TABLE items ADD COLUMN active_until DATETIME NULL DEFAULT NULL AFTER availability_status";
        $pdo->exec($sql);
        echo "Column 'active_until' added successfully.<br>";
    } else {
        echo "Column 'active_until' already exists.<br>";
    }

    // Also check for 'listing_duration' just in case we want to store the original duration
    $stmt = $pdo->prepare("SHOW COLUMNS FROM items LIKE 'listing_duration'");
    $stmt->execute();
    $exists = $stmt->fetch();
    
    if (!$exists) {
        // Add listing_duration column (in days)
        $sql = "ALTER TABLE items ADD COLUMN listing_duration INT NULL DEFAULT NULL AFTER active_until";
        $pdo->exec($sql);
        echo "Column 'listing_duration' added successfully.<br>";
    } else {
        echo "Column 'listing_duration' already exists.<br>";
    }
    
    echo "Database schema updated.";
    
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage();
}
?>
