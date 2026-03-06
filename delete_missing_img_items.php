<?php
require_once 'config/database.php';
$pdo = getDBConnection();
if ($pdo) {
    $stmt = $pdo->query("SELECT id, title, images FROM items");
    $items = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $deleted_count = 0;
    
    foreach ($items as $item) {
        $has_valid_image = false;
        $images = [];
        if (!empty($item['images'])) {
            $decoded = json_decode($item['images'], true);
            if (is_array($decoded)) {
                $images = $decoded;
            } elseif (is_string($decoded)) {
                $images = [$decoded];
            } else {
                $images = [$item['images']];
            }
        }
        
        foreach ($images as $img) {
            $path = 'uploads/' . $img;
            if (file_exists($path) && is_file($path)) {
                $has_valid_image = true;
                break;
            }
        }
        
        if (!$has_valid_image) {
            $del = $pdo->prepare("DELETE FROM items WHERE id = ?");
            $del->execute([$item['id']]);
            echo "Deleted item ID {$item['id']} ({$item['title']}) because it has no physical image.\n";
            $deleted_count++;
        }
    }
    echo "Total deleted: " . $deleted_count . "\n";
} else {
    echo "DB connection failed";
}
?>
