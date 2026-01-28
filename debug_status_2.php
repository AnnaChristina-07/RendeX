<?php
require_once 'config/database.php';
$pdo = getDBConnection();
$stmt = $pdo->query("SELECT id, title, availability_status FROM items WHERE title = 'calculator'");
$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

foreach ($rows as $r) {
    echo "ID:" . $r['id'] . " Avail:" . ($r['availability_status']??'N/A') . "\n";
}
?>
