<?php
require 'c:\xamppp\htdocs\RendeX\config\database.php';
$pdo = getDBConnection();
$pdo->exec("UPDATE item_requests SET status = 'active' WHERE status = 'fulfilled' AND id NOT IN (SELECT fulfilled_request_id FROM items WHERE fulfilled_request_id IS NOT NULL)");
echo "Done";
?>
