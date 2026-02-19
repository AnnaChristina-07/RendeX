<?php
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['status' => 'error', 'message' => 'Unauthorized']);
    exit();
}

require_once 'config/database.php';

$input = json_decode(file_get_contents('php://input'), true);
$item_id = $input['item_id'] ?? null;

if (!$item_id) {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => 'Missing item_id']);
    exit();
}

$user_id = $_SESSION['user_id'];
$pdo = getDBConnection();

if (!$pdo) {
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => 'Database error']);
    exit();
}

try {
    // Check if item exists in wishlist
    $stmt = $pdo->prepare("SELECT id FROM wishlist WHERE user_id = ? AND item_id = ?");
    $stmt->execute([$user_id, $item_id]);
    $exists = $stmt->fetch();

    if ($exists) {
        // Remove it
        $del = $pdo->prepare("DELETE FROM wishlist WHERE id = ?");
        $del->execute([$exists['id']]);
        echo json_encode(['status' => 'success', 'action' => 'removed']);
    } else {
        // Add it
        $add = $pdo->prepare("INSERT INTO wishlist (user_id, item_id) VALUES (?, ?)");
        $add->execute([$user_id, $item_id]);
        echo json_encode(['status' => 'success', 'action' => 'added']);
    }

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}
?>
