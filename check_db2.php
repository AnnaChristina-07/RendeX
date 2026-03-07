<?php
require 'c:\xamppp\htdocs\RendeX\config\database.php';
$pdo = getDBConnection();
print_r($pdo->query("SELECT id, title, fulfilled_request_id FROM items WHERE title LIKE '%Printer%'")->fetchAll(PDO::FETCH_ASSOC));
?>
