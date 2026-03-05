<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
require_once 'config/database.php';
$pdo = getDBConnection();
try {
    $stmt = $pdo->prepare("INSERT INTO pre_bookings (booking_ref, item_id, user_id, owner_id, start_date, end_date, total_days, daily_rate, total_amount, status) VALUES ('PB_ABC123', 'item_123', 'u1', 'o1', '2026-03-06', '2026-03-08', 3, 100, 300, 'pending')");
    $stmt->execute();
    echo "Insert OK";
    $pdo->exec("DELETE FROM pre_bookings WHERE booking_ref = 'PB_ABC123'");
} catch(Exception $e) {
    echo "Fail: " . $e->getMessage();
}
?>
