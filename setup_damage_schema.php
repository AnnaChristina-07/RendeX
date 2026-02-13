<?php
require_once 'config/database.php';

try {
    $pdo = getDBConnection();
    if ($pdo) {
        echo "Connected to database.\n";
        
        // Add columns to rentals table if they don't exist
        $columns = [
            "damage_reported_at" => "DATETIME NULL",
            "damage_description" => "TEXT NULL",
            "damage_cost" => "DECIMAL(10,2) NULL",
            "damage_evidence_photos" => "TEXT NULL", // JSON string
             // 'dispute_status' enum might be tricky if not supported/strict, let's use VARCHAR or modify active status
            "dispute_status" => "VARCHAR(20) DEFAULT 'none'" 
        ];

        foreach ($columns as $col => $def) {
            try {
                $pdo->exec("ALTER TABLE rentals ADD COLUMN $col $def");
                echo "Added column: $col\n";
            } catch (PDOException $e) {
                // Column likely exists
                echo "Column $col might already exist or error: " . $e->getMessage() . "\n";
            }
        }
        
        echo "Database schema updated successfully.";
    } else {
        echo "No database connection available.";
    }
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
?>
