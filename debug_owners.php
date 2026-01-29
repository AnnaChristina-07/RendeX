<?php
require_once 'config/database.php';

echo "--- CHECKING DATABASE USERS ---\n";
try {
    $pdo = getDBConnection();
    if ($pdo) {
        $stmt = $pdo->query("SELECT id, name, email, role FROM users");
        $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
        echo "Found " . count($users) . " users in DB.\n";
        foreach ($users as $u) {
            echo "ID: {$u['id']} | Name: {$u['name']} | Role: {$u['role']} | Email: {$u['email']}\n";
        }
    } else {
        echo "DB Connection Failed.\n";
    }
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}

echo "\n--- CHECKING JSON USERS ---\n";
$json_users = [];
if (file_exists('users.json')) {
    $json_users = json_decode(file_get_contents('users.json'), true);
    echo "Found " . count($json_users) . " users in JSON.\n";
     foreach ($json_users as $u) {
            $role = $u['role'] ?? 'undefined';
            echo "ID: {$u['id']} | Name: {$u['name']} | Role: {$role} | Email: {$u['email']}\n";
        }
} else {
    echo "users.json not found.\n";
}

echo "\n--- CHECKING ITEMS FOR OWNER IDS ---\n";
if (file_exists('items.json')) {
    $items = json_decode(file_get_contents('items.json'), true);
    $owner_ids = array_unique(array_column($items, 'owner_id'));
    echo "Owner IDs found in items.json: " . implode(', ', $owner_ids) . "\n";
}
?>
