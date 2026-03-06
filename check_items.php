<?php
require_once 'config/database.php';
$pdo = getDBConnection();
if ($pdo) {
    $stmt = $pdo->query("SELECT * FROM items ORDER BY created_at DESC LIMIT 10");
    $items = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo json_encode($items);
} else {
    echo "DB connection failed";
}
?>
