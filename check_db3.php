<?php
require 'c:\xamppp\htdocs\RendeX\config\database.php';
$pdo = getDBConnection();
print_r($pdo->query("SELECT id, title, fulfilled_request_id FROM items ORDER BY id DESC LIMIT 5")->fetchAll(PDO::FETCH_ASSOC));
print_r($pdo->query("SELECT id, status FROM item_requests ORDER BY id DESC LIMIT 5")->fetchAll(PDO::FETCH_ASSOC));
?>
