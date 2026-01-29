<?php
require_once 'config/database.php';

try {
    $pdo = getDBConnection();
    if ($pdo) {
        $stmt = $pdo->query("SELECT * FROM items");
        $items = $stmt->fetchAll(PDO::FETCH_ASSOC);
        echo "Found " . count($items) . " items in database.\n";
        if (count($items) > 0) {
            echo json_encode($items, JSON_PRETTY_PRINT);
        }
    } else {
        echo "Could not connect to database.\n";
    }
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?>
