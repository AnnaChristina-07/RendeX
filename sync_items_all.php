<?php
require_once 'config/database.php';
$pdo = getDBConnection();

$json_file = 'items.json';
if (!file_exists($json_file)) {
    die("items.json not found.");
}

$json_items = json_decode(file_get_contents($json_file), true);
if (!$json_items) die("Invalid JSON.");

echo "Syncing JSON items to Database...\n";

foreach ($json_items as $item) {
    echo "Processing Item ID: {$item['id']} ({$item['title']})\n";

    // 1. Check if owner exists in users table (FK constraint)
    $owner_id = $item['owner_id'] ?? $item['user_id'] ?? null;
    if (!$owner_id) {
        echo " - Skipped: No owner ID.\n";
        continue;
    }
    
    // Check owner existence
    $stmt = $pdo->prepare("SELECT id FROM users WHERE id = ?");
    $stmt->execute([$owner_id]);
    if (!$stmt->fetch()) {
        echo " - Warning: Owner $owner_id not found in DB. Creating dummy/syncing might be needed.\n";
        // Attempt to insert dummy user if missing to satisfy FK
        try {
             $u_stmt = $pdo->prepare("INSERT INTO users (id, name, email, password_hash) VALUES (?, 'Unknown Owner', ?, 'dummy')");
             $u_stmt->execute([$owner_id, "owner_{$owner_id}@rendex.com"]);
             echo " - Created dummy owner.\n";
        } catch (Exception $e) {
             echo " - Failed to create owner: " . $e->getMessage() . "\n";
             continue; // Skip item if no owner
        }
    }

    // 2. Prepare Data
    $id = $item['id'];
    $title = $item['title'];
    $desc = $item['description'] ?? '';
    $cat = $item['category'] ?? 'others';
    $price = $item['price_per_day'] ?? $item['price'] ?? 0;
    $deposit = $item['security_deposit'] ?? 0;
    $condition = $item['condition_status'] ?? 'good';
    $status = $item['availability_status'] ?? 'available';
    $location = $item['location'] ?? $item['address'] ?? '';
    $images = isset($item['images']) ? json_encode($item['images']) : '[]';
    $active = $item['is_active'] ?? 1;

    // 3. Upsert into Items Table
    $check = $pdo->prepare("SELECT id FROM items WHERE id = ?");
    $check->execute([$id]);
    
    if ($check->fetch()) {
        // Update
        $upd = $pdo->prepare("
            UPDATE items SET 
                owner_id = ?, title = ?, description = ?, category = ?, 
                price_per_day = ?, security_deposit = ?, condition_status = ?, 
                availability_status = ?, location = ?, images = ?, is_active = ? 
            WHERE id = ?
        ");
        $upd->execute([
            $owner_id, $title, $desc, $cat, 
            $price, $deposit, $condition, 
            $status, $location, $images, $active, 
            $id
        ]);
        echo " - Updated.\n";
    } else {
        // Insert
        $ins = $pdo->prepare("
            INSERT INTO items (
                id, owner_id, title, description, category, 
                price_per_day, security_deposit, condition_status, 
                availability_status, location, images, is_active, created_at
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())
        ");
        $ins->execute([
            $id, $owner_id, $title, $desc, $cat, 
            $price, $deposit, $condition, 
            $status, $location, $images, $active
        ]);
        echo " - Inserted.\n";
    }
}

echo "Sync complete.\n";
?>
