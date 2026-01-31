<?php
require_once 'config/database.php';

try {
    $pdo = getDBConnection();
    if (!$pdo) {
        die("Could not connect to database.\n");
    }

    echo "=== Creating Missing Tables ===\n";

    // 1. MESSAGES TABLE
    $sql_messages = "CREATE TABLE IF NOT EXISTS messages (
        id INT AUTO_INCREMENT PRIMARY KEY,
        sender_id VARCHAR(20) NOT NULL,
        receiver_id VARCHAR(20) NOT NULL,
        rental_id INT DEFAULT NULL,
        message TEXT NOT NULL,
        is_read TINYINT(1) DEFAULT 0,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (sender_id) REFERENCES users(id) ON DELETE CASCADE,
        FOREIGN KEY (receiver_id) REFERENCES users(id) ON DELETE CASCADE,
        INDEX idx_sender (sender_id),
        INDEX idx_receiver (receiver_id)
    ) ENGINE=InnoDB;";
    
    $pdo->exec($sql_messages);
    echo "✅ Table 'messages' created or already exists.\n";

    // 2. WISHLIST TABLE
    $sql_wishlist = "CREATE TABLE IF NOT EXISTS wishlist (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id VARCHAR(20) NOT NULL,
        item_id INT NOT NULL,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
        FOREIGN KEY (item_id) REFERENCES items(id) ON DELETE CASCADE,
        UNIQUE KEY unique_wishlist (user_id, item_id)
    ) ENGINE=InnoDB;";

    $pdo->exec($sql_wishlist);
    echo "✅ Table 'wishlist' created or already exists.\n";

    // 3. TRANSACTIONS TABLE
    $sql_transactions = "CREATE TABLE IF NOT EXISTS transactions (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id VARCHAR(20) NOT NULL,
        rental_id INT DEFAULT NULL,
        amount DECIMAL(10, 2) NOT NULL,
        type ENUM('credit', 'debit', 'refund') NOT NULL,
        description VARCHAR(255),
        status ENUM('pending', 'success', 'failed') DEFAULT 'pending',
        gateway_ref VARCHAR(100) DEFAULT NULL,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
        FOREIGN KEY (rental_id) REFERENCES rentals(id) ON DELETE SET NULL
    ) ENGINE=InnoDB;";

    $pdo->exec($sql_transactions);
    echo "✅ Table 'transactions' created or already exists.\n";

    // 4. CATEGORIES TABLE
    $sql_categories = "CREATE TABLE IF NOT EXISTS categories (
        id INT AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(100) NOT NULL UNIQUE,
        slug VARCHAR(100) NOT NULL UNIQUE,
        icon VARCHAR(50) DEFAULT 'category',
        parent_id INT DEFAULT NULL,
        is_active TINYINT(1) DEFAULT 1,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (parent_id) REFERENCES categories(id) ON DELETE SET NULL
    ) ENGINE=InnoDB;";

    $pdo->exec($sql_categories);
    echo "✅ Table 'categories' created or already exists.\n";

    // 5. AVAILABILITY TABLE (Optional but good for scalability)
    $sql_availability = "CREATE TABLE IF NOT EXISTS availability (
        id INT AUTO_INCREMENT PRIMARY KEY,
        item_id INT NOT NULL,
        start_date DATE NOT NULL,
        end_date DATE NOT NULL,
        reason VARCHAR(255) DEFAULT 'manual_block',
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (item_id) REFERENCES items(id) ON DELETE CASCADE,
        INDEX idx_item_date (item_id, start_date)
    ) ENGINE=InnoDB;";

    $pdo->exec($sql_availability);
    echo "✅ Table 'availability' created or already exists.\n";


    echo "\n=== All missing tables created successfully ===\n";

} catch (PDOException $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}
?>
