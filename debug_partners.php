<?php
require_once 'config/database.php';
$pdo = getDBConnection();

echo "--- Users with role 'delivery_partner' ---\n";
$stmt = $pdo->query("SELECT id, name, email, role FROM users WHERE role = 'delivery_partner'");
$users = $stmt->fetchAll(PDO::FETCH_ASSOC);
foreach ($users as $u) {
    echo "ID: {$u['id']} | Name: {$u['name']} | Role: {$u['role']}\n";
}

echo "\n--- Driver Applications (Approved) ---\n";
$stmt = $pdo->query("SELECT id, user_id, full_name, status FROM driver_applications WHERE status = 'approved'");
$apps = $stmt->fetchAll(PDO::FETCH_ASSOC);
foreach ($apps as $a) {
    echo "AppID: {$a['id']} | UserID: {$a['user_id']} | Name: {$a['full_name']} | Status: {$a['status']}\n";
}

echo "\n--- All Users ---\n";
$stmt = $pdo->query("SELECT id, name, role FROM users");
$all = $stmt->fetchAll(PDO::FETCH_ASSOC);
foreach($all as $u) {
    echo "ID: {$u['id']} | Name: {$u['name']} | Role: {$u['role']}\n";
}
?>
