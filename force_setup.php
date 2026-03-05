<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
require_once 'config/database.php';

try {
    $pdo = getDBConnection();
    if (!$pdo) die("DB Connection Failed");

    $pdo->exec("
        CREATE TABLE IF NOT EXISTS pre_bookings (
            id INT AUTO_INCREMENT PRIMARY KEY,
            booking_ref VARCHAR(30) NOT NULL UNIQUE,
            item_id VARCHAR(100) NOT NULL,
            user_id VARCHAR(50) NOT NULL,
            owner_id VARCHAR(50) NOT NULL,
            start_date DATE NOT NULL,
            end_date DATE NOT NULL,
            total_days INT NOT NULL,
            daily_rate DECIMAL(10,2) NOT NULL,
            total_amount DECIMAL(10,2) NOT NULL,
            status ENUM('pending','confirmed','active','completed','cancelled','expired') DEFAULT 'pending',
            payment_status ENUM('none','paid','refunded') DEFAULT 'none',
            payment_id VARCHAR(100) DEFAULT NULL,
            delivery_method ENUM('pickup','delivery') DEFAULT 'pickup',
            delivery_address TEXT DEFAULT NULL,
            owner_approved TINYINT DEFAULT 0,
            reminder_sent TINYINT DEFAULT 0,
            notes TEXT DEFAULT NULL,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            expires_at DATETIME DEFAULT NULL,
            INDEX idx_item_dates (item_id, start_date, end_date),
            INDEX idx_user (user_id),
            INDEX idx_status (status)
        ) ENGINE=InnoDB
    ");
    echo "Table pre_bookings created.\n";

    // Try adding the columns if they don't exist
    try {
        $pdo->exec("ALTER TABLE items ADD COLUMN allow_prebooking TINYINT DEFAULT 1 AFTER sold_at");
        echo "Added allow_prebooking.\n";
    } catch (Exception $e) {}

    try {
        $pdo->exec("ALTER TABLE items ADD COLUMN max_advance_days INT DEFAULT 60 AFTER allow_prebooking");
        echo "Added max_advance_days.\n";
    } catch (Exception $e) {}

    echo "ALL DONE";
} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage();
}
?>
