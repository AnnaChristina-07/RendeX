<?php
require_once 'config/database.php';

try {
    $pdo = getDBConnection();
    
    // 1. Create item_requests table
    $sql1 = "CREATE TABLE IF NOT EXISTS item_requests (
        id INT AUTO_INCREMENT PRIMARY KEY,
        renter_id VARCHAR(20) NOT NULL,
        item_name VARCHAR(255) NOT NULL,
        category VARCHAR(100) NOT NULL,
        description TEXT,
        min_price DECIMAL(10, 2) DEFAULT NULL,
        max_price DECIMAL(10, 2) DEFAULT NULL,
        needed_by DATE DEFAULT NULL,
        status ENUM('active', 'fulfilled', 'cancelled', 'expired') DEFAULT 'active',
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        FOREIGN KEY (renter_id) REFERENCES users(id) ON DELETE CASCADE,
        INDEX idx_status (status),
        INDEX idx_category (category)
    ) ENGINE=InnoDB;";
    
    $pdo->exec($sql1);
    echo "Table 'item_requests' created or already exists.<br>";
    
    // 2. Create request_offers table
    $sql2 = "CREATE TABLE IF NOT EXISTS request_offers (
        id INT AUTO_INCREMENT PRIMARY KEY,
        request_id INT NOT NULL,
        owner_id VARCHAR(20) NOT NULL,
        item_id INT NOT NULL, 
        message TEXT,
        offer_price DECIMAL(10, 2) DEFAULT NULL,
        status ENUM('pending', 'accepted', 'rejected', 'withdrawn') DEFAULT 'pending',
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        FOREIGN KEY (request_id) REFERENCES item_requests(id) ON DELETE CASCADE,
        FOREIGN KEY (owner_id) REFERENCES users(id) ON DELETE CASCADE,
        FOREIGN KEY (item_id) REFERENCES items(id) ON DELETE CASCADE,
        INDEX idx_request (request_id),
        INDEX idx_owner (owner_id)
    ) ENGINE=InnoDB;";

    $pdo->exec($sql2);
    echo "Table 'request_offers' created or already exists.<br>";

} catch (PDOException $e) {
    die("DB Error: " . $e->getMessage());
}
?>
