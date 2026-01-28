<?php
require_once 'config/database.php';
$pdo = getDBConnection();
$stmt = $pdo->query("DESCRIBE items");
$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
foreach ($rows as $r) {
    echo $r['Field'] . "\n";
}
?>
