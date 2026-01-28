<?php
require_once 'config/database.php';
$pdo = getDBConnection();

echo "ALL USERS:\n";
$stmt = $pdo->query("SELECT id, name, role FROM users");
$users = $stmt->fetchAll(PDO::FETCH_ASSOC);
foreach ($users as $u) {
    echo str_pad($u['name'], 20) . " | " . str_pad($u['role'] ?? 'NULL', 20) . " | " . $u['id'] . "\n";
}

echo "\nALL DRIVER APPLICATIONS:\n";
$stmt = $pdo->query("SELECT id, full_name, status, user_id FROM driver_applications");
$apps = $stmt->fetchAll(PDO::FETCH_ASSOC);
foreach ($apps as $a) {
    echo str_pad($a['full_name'], 20) . " | " . str_pad($a['status'], 10) . " | " . $a['user_id'] . "\n";
}
?>
