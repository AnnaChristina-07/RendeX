<?php
require_once 'config/database.php';
$pdo = getDBConnection();
if ($pdo) {
    // Let's see what Laundry baskets exist
    $stmt = $pdo->query("SELECT id, title, images FROM items WHERE title LIKE '%Laundry basket%'");
    $items = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    foreach ($items as $item) {
        $has_valid = false;
        $decoded = json_decode($item['images'], true);
        if (is_array($decoded)) {
            foreach ($decoded as $img) {
                if (file_exists(__DIR__ . '/uploads/' . $img)) {
                    $has_valid = true;
                    break;
                }
            }
        }
        
        if (!$has_valid) {
            $pdo->exec("DELETE FROM items WHERE id = " . $item['id']);
            echo "Deleted ID " . $item['id'] . "\n";
        } else {
            echo "Kept ID " . $item['id'] . " because it has valid image.\n";
        }
    }
}
?>
