<?php
require_once 'c:\xamppp\htdocs\RendeX\config\database.php';
try {
    $pdo = getDBConnection();
    // Check if column exists
    $stmt = $pdo->query("SHOW COLUMNS FROM items LIKE 'fulfilled_request_id'");
    if ($stmt->rowCount() == 0) {
        $pdo->exec("ALTER TABLE items ADD COLUMN fulfilled_request_id INT DEFAULT NULL");
        echo "Column fulfilled_request_id added successfully.\n";
    } else {
        echo "Column already exists.\n";
    }
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?>
