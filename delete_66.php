<?php
require_once 'config/database.php';
$pdo = getDBConnection();
if ($pdo) {
    if (isset($_GET['delete'])) {
        $pdo->exec("DELETE FROM items WHERE id = 66");
        echo "Deleted item 66";
    }
}
?>
