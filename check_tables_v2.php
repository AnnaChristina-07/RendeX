<?php
require_once 'config/database.php';

try {
    $pdo = getDBConnection();
    if (!$pdo) {
        die("Could not connect to database.\n");
    }

    echo "--- EXISTING TABLES ---\n";
    $stmt = $pdo->query("SHOW TABLES");
    $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    if (empty($tables)) {
        echo "No tables found.\n";
    } else {
        foreach ($tables as $table) {
            echo $table . "\n";
        }
    }
    echo "-----------------------\n";

} catch (PDOException $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?>
