<?php
require_once 'config/database.php';

try {
    $pdo = getDBConnection();
    if ($pdo) {
        $stmt = $pdo->query("SELECT * FROM items");
        $db_items = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        $json_items = [];
        foreach ($db_items as $db_item) {
            $item = $db_item;
            // Map fields for compatibility with JSON-based frontend logic
            $item['price'] = $db_item['price_per_day'] ?? 0;
            $item['address'] = $db_item['location'] ?? '';
            
            // Map status
            // Standardize status for frontend (which expects 'Active')
            $status = 'Active';
            if (isset($db_item['admin_status']) && $db_item['admin_status'] !== 'approved') {
                $status = 'Pending';
            }
            if (isset($db_item['is_active']) && $db_item['is_active'] == 0) {
                $status = 'Inactive';
            }
            $item['status'] = $status;
            
            // Parse images
            if (!empty($db_item['images'])) {
                $decoded = json_decode($db_item['images'], true);
                if (is_array($decoded)) {
                    $item['images'] = $decoded;
                } else {
                    // Handle case where it might be a simple string or single file
                    $item['images'] = [$db_item['images']];
                    // If it looked like JSON but failed decode, it might be weirdly formatted, 
                    // but usually it's array string
                }
            } else {
                $item['images'] = [];
            }
            
            $json_items[] = $item;
        }
        
        file_put_contents('items.json', json_encode($json_items, JSON_PRETTY_PRINT));
        echo "Successfully synced " . count($json_items) . " items from Database to items.json";
    } else {
        echo "Failed to connect to DB";
    }
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
?>
