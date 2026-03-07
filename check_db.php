<?php
require 'c:\xamppp\htdocs\RendeX\config\database.php';
$pdo = getDBConnection();
print_r($pdo->query('SELECT id, title, fulfilled_request_id FROM items WHERE fulfilled_request_id IS NOT NULL')->fetchAll(PDO::FETCH_ASSOC));
print_r($pdo->query('SELECT id, status, item_name FROM item_requests WHERE status=\'fulfilled\'')->fetchAll(PDO::FETCH_ASSOC));
?>
