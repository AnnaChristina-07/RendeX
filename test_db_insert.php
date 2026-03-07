<?php
require 'c:\xamppp\htdocs\RendeX\config\database.php';
$pdo = getDBConnection();
try {
    $stmt = $pdo->prepare("INSERT INTO items (
                        owner_id, title, description, category, 
                        price_per_day, security_deposit, handover_methods, 
                        location, images, listing_type, admin_status, created_at, fulfilled_request_id
                    ) VALUES (2, 'Test Printer Link', 'Desc', 'electronics', 
                        100, 0, '[\"pickup\"]', 
                        'Loc', '[]', 'rent', 'pending', NOW(), 4)");
    $stmt->execute();
    echo "Inserted ID: " . $pdo->lastInsertId() . "\n";
    print_r($pdo->query("SELECT id, title, fulfilled_request_id FROM items WHERE id = " . $pdo->lastInsertId())->fetch(PDO::FETCH_ASSOC));
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
?>
