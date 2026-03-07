<?php
require 'c:\xamppp\htdocs\RendeX\config\database.php';
$pdo = getDBConnection();
print_r($pdo->query("DESCRIBE items")->fetchAll(PDO::FETCH_ASSOC));
?>
