<?php
require_once 'config/database.php';
$pdo = getDBConnection();
$stmt = $pdo->query("SELECT id, title, availability_status, admin_status FROM items WHERE title LIKE '%calculator%'");
$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo "--- Items Log ---\n";
foreach ($rows as $r) {
    echo "ID: " . $r['id'] . " | Title: " . $r['title'] . " | Status: " . ($r['availability_status'] ?? 'NULL') . " | Admin: " . $r['admin_status'] . "\n";
}
?>
