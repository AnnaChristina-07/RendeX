<?php
require_once 'config/database.php';

try {
    $pdo = getDBConnection();
    if (!$pdo) {
        die("Could not connect to database.");
    }

    // Add security_deposit if it doesn't exist
    $pdo->exec("ALTER TABLE items ADD COLUMN IF NOT EXISTS security_deposit DECIMAL(10, 2) DEFAULT 0 AFTER price_per_day");
    
    // Add handover_methods (JSON)
    $pdo->exec("ALTER TABLE items ADD COLUMN IF NOT EXISTS handover_methods JSON DEFAULT NULL AFTER security_deposit");
    
    // Add availability dates
    $pdo->exec("ALTER TABLE items ADD COLUMN IF NOT EXISTS available_from DATE DEFAULT NULL AFTER description");
    $pdo->exec("ALTER TABLE items ADD COLUMN IF NOT EXISTS available_to DATE DEFAULT NULL AFTER available_from");

    echo "Successfully updated items table schema.";
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage();
}
?>
