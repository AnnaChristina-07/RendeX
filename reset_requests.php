<?php
require 'c:\xamppp\htdocs\RendeX\config\database.php';
$pdo = getDBConnection();
$pdo->exec("UPDATE item_requests SET status = 'active' WHERE status = 'fulfilled'");
echo "All requests are active again.";
?>
