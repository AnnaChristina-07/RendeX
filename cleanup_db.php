<?php
require_once 'config/database.php';
$pdo = getDBConnection();
// Delete duplicates for 'calculator' ensuring we keep the lowest ID (e.g. 1)
// Using a safe delete where we delete items with title 'calculator' and ID NOT IN (SELECT MIN(id) ...)
// But simplified: Just delete 9 and 17 explicitly if they are calculators.

$stmt = $pdo->prepare("DELETE FROM items WHERE id IN (9, 17) AND title='calculator'");
$stmt->execute();
echo "Deleted " . $stmt->rowCount() . " duplicate calculators.";
?>
