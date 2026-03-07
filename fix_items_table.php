<?php
require 'c:\xamppp\htdocs\RendeX\config\database.php';
$pdo = getDBConnection();
try {
    $pdo->exec("ALTER TABLE items ADD COLUMN listing_type ENUM('rent', 'sell', 'both') DEFAULT 'rent'");
    echo "Added listing_type.\n";
} catch (Exception $e) {}

try {
    $pdo->exec("ALTER TABLE items ADD COLUMN selling_price DECIMAL(10,2) DEFAULT NULL");
    echo "Added selling_price.\n";
} catch (Exception $e) {}
?>
