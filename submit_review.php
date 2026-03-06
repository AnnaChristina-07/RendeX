<?php
session_start();
require_once 'config/database.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit();
}

$rental_id = $_POST['rental_id'] ?? '';
$item_id = $_POST['item_id'] ?? '';
$reviewer_id = $_SESSION['user_id'];
$rating = (int)($_POST['rating'] ?? 0);
$comment = trim($_POST['comment'] ?? '');

if (empty($rental_id) || empty($item_id) || $rating < 1 || $rating > 5) {
    echo json_encode(['success' => false, 'message' => 'Invalid input data']);
    exit();
}

try {
    $pdo = getDBConnection();
    if (!$pdo) {
        throw new Exception("Database connection failed");
    }

    // 1. Validate the rental: check if this user actually rented this item and the rental is completed
    $stmt = $pdo->prepare("SELECT id, owner_id FROM rentals WHERE id = ? AND item_id = ? AND renter_id = ? AND status = 'completed'");
    $stmt->execute([$rental_id, $item_id, $reviewer_id]);
    $rental = $stmt->fetch();

    if (!$rental) {
        // Also allow 'confirmed' status if the item is returned
        $stmt2 = $pdo->prepare("SELECT id, owner_id FROM rentals WHERE id = ? AND item_id = ? AND renter_id = ? AND return_status = 'completed'");
        $stmt2->execute([$rental_id, $item_id, $reviewer_id]);
        $rental = $stmt2->fetch();
        
        if (!$rental) {
            echo json_encode(['success' => false, 'message' => 'You can only review items you have successfully rented and returned.']);
            exit();
        }
    }

    $owner_id = $rental['owner_id'];

    // 2. Check if already reviewed
    $stmt = $pdo->prepare("SELECT id FROM reviews WHERE rental_id = ? AND reviewer_id = ?");
    $stmt->execute([$rental_id, $reviewer_id]);
    if ($stmt->fetch()) {
        echo json_encode(['success' => false, 'message' => 'You have already reviewed this rental.']);
        exit();
    }

    // 3. Insert review
    $stmt = $pdo->prepare("INSERT INTO reviews (rental_id, item_id, reviewer_id, owner_id, rating, comment) VALUES (?, ?, ?, ?, ?, ?)");
    $stmt->execute([$rental_id, $item_id, $reviewer_id, $owner_id, $rating, $comment]);

    echo json_encode(['success' => true, 'message' => 'Review submitted successfully!']);

} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Server error: ' . $e->getMessage()]);
}
?>
