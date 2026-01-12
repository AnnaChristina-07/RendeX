<?php
require_once 'config/database.php';

try {
    $pdo = getDBConnection();
    if (!$pdo) {
        die("Database connection failed.\n");
    }

    // Add admin_status column to items table
    $sql = "ALTER TABLE items ADD COLUMN admin_status ENUM('pending', 'approved', 'rejected') DEFAULT 'pending' AFTER availability_status";
    $pdo->exec($sql);
    echo "Column 'admin_status' added successfully to 'items' table.\n";

    // Add notification type 'item_listing' to admin_notifications table
    $sql = "ALTER TABLE admin_notifications MODIFY COLUMN type ENUM('driver_application', 'owner_application', 'report', 'system', 'item_listing') DEFAULT 'system'";
    $pdo->exec($sql);
    echo "Notification type 'item_listing' added successfully to 'admin_notifications' table.\n";

} catch (PDOException $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?>
