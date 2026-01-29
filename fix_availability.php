<?php
require_once 'config/database.php';

try {
    $pdo = getDBConnection();
    if ($pdo) {
        // Update all items to be available
        $stmt = $pdo->prepare("UPDATE items SET availability_status = 'available' WHERE availability_status = 'unavailable' OR availability_status = 'rented'");
        $stmt->execute();
        
        echo "Updated " . $stmt->rowCount() . " items to 'available'.";
    }
} catch (Exception $e) {
    echo $e->getMessage();
}
?>
