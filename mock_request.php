<?php
require 'c:\xamppp\htdocs\RendeX\config\database.php';
$pdo = getDBConnection();
// Give some item to request 4 so the user immediately sees it
$pdo->exec("UPDATE item_requests SET status = 'fulfilled' WHERE id = 4");
// Find an existing item or create dummy
$stmt = $pdo->query("SELECT id FROM items LIMIT 1");
$itemId = $stmt->fetchColumn();

if ($itemId) {
    $pdo->exec("UPDATE items SET fulfilled_request_id = 4 WHERE id = " . $itemId);
    echo "Linked item $itemId to request 4.";
} else {
    echo "No items found.";
}
?>
