<?php
require 'c:\xamppp\htdocs\RendeX\config\database.php';
$pdo = getDBConnection();
// Unlink item 1 (calculator)
$pdo->exec("UPDATE items SET fulfilled_request_id = NULL WHERE id = 1");

// Let's see all items that have fulfilled_request_id set
print_r($pdo->query("SELECT id, title, fulfilled_request_id FROM items WHERE fulfilled_request_id IS NOT NULL")->fetchAll(PDO::FETCH_ASSOC));
?>
