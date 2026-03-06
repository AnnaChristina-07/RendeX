<?php
require_once 'config/database.php';

try {
    $pdo = getDBConnection();
    if ($pdo) {
        $sql = "CREATE TABLE IF NOT EXISTS reviews (
            id INT AUTO_INCREMENT PRIMARY KEY,
            rental_id VARCHAR(255) NOT NULL,
            item_id VARCHAR(255) NOT NULL,
            reviewer_id VARCHAR(255) NOT NULL,
            owner_id VARCHAR(255) NOT NULL,
            rating INT NOT NULL CHECK (rating >= 1 AND rating <= 5),
            comment TEXT,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            UNIQUE KEY unique_rental_review (rental_id)
        )";
        $pdo->exec($sql);
        echo "Reviews table created successfully.";
    } else {
        echo "Failed to connect to database.";
    }
} catch (PDOException $e) {
    echo "Error creating table: " . $e->getMessage();
}
?>
