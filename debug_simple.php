<?php
require_once 'config/database.php';
$pdo = getDBConnection();
// simple dump
$stmt = $pdo->query("SELECT name, email, role FROM users WHERE role='delivery_partner'");
$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
foreach($rows as $r) { echo "USER: " . $r['name'] . " (" . $r['role'] . ")\n"; }
?>
