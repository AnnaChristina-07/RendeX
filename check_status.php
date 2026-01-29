<?php
require_once 'config/database.php';

try {
    $pdo = getDBConnection();
    if ($pdo) {
        $stmt = $pdo->query("SELECT id, title, availability_status, admin_status FROM items WHERE title LIKE '%Raincoat%' OR title LIKE '%umberlla%' OR title LIKE '%umbrella%'");
        $items = $stmt->fetchAll(PDO::FETCH_ASSOC);
        echo json_encode($items, JSON_PRETTY_PRINT);
    }
} catch (Exception $e) {
    echo $e->getMessage();
}
?>
