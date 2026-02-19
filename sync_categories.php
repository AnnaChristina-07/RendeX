<?php
require_once 'config/database.php';
$pdo = getDBConnection();

$json_file = 'items.json';
if (!file_exists($json_file)) {
    die("items.json not found.");
}

$json_items = json_decode(file_get_contents($json_file), true);
if (!$json_items) die("Invalid JSON.");

echo "Syncing Categories from items.json to Database...\n";

$categories = [];
foreach ($json_items as $item) {
    if (!empty($item['category'])) {
        $categories[$item['category']] = true;
    }
}

foreach (array_keys($categories) as $cat_slug) {
    echo "Processing Category: $cat_slug\n";
    
    // Check if exists
    $stmt = $pdo->prepare("SELECT id FROM categories WHERE slug = ?");
    $stmt->execute([$cat_slug]);
    
    if (!$stmt->fetch()) {
        // Create nice name (e.g. "student-essentials" -> "Student Essentials")
        $name = ucwords(str_replace('-', ' ', $cat_slug));
        
        $ins = $pdo->prepare("INSERT INTO categories (name, slug, created_at) VALUES (?, ?, NOW())");
        $ins->execute([$name, $cat_slug]);
        echo " - Created.\n";
    } else {
        echo " - Exists.\n";
    }
}

echo "Sync complete.\n";
?>
