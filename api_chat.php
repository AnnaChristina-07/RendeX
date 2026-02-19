<?php
session_start();
header('Content-Type: application/json');

require_once 'config/database.php';

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['status' => 'error', 'message' => 'Unauthorized']);
    exit();
}

$user_id = $_SESSION['user_id'];
$pdo = getDBConnection();

if (!$pdo) {
    echo json_encode(['status' => 'error', 'message' => 'Database error']);
    exit();
}

$action = $_GET['action'] ?? '';

try {
    // 1. Get List of Conversations
    if ($action === 'list') {
        // Find distinct users we have chatted with
        // We want the last message for each conversation
        $sql = "
            SELECT 
                u.id AS partner_id, 
                u.name AS partner_name, 
                u.profile_picture,
                m.message_text AS last_message,
                m.created_at AS last_time,
                m.sender_id,
                m.is_read
            FROM messages m
            JOIN users u ON (u.id = CASE WHEN m.sender_id = :uid THEN m.receiver_id ELSE m.sender_id END)
            WHERE (m.sender_id = :uid OR m.receiver_id = :uid)
            AND m.id IN (
                SELECT MAX(id) 
                FROM messages 
                WHERE sender_id = :uid OR receiver_id = :uid 
                GROUP BY CASE WHEN sender_id = :uid THEN receiver_id ELSE sender_id END
            )
            ORDER BY m.created_at DESC
        ";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute(['uid' => $user_id]);
        $conversations = $stmt->fetchAll();
        
        echo json_encode(['status' => 'success', 'data' => $conversations]);
    }

    // 2. Get Messages for a specific conversation
    elseif ($action === 'get_messages') {
        $partner_id = $_GET['partner_id'] ?? null;
        if (!$partner_id) throw new Exception("Missing partner_id");

        // Mark as read
        $upd = $pdo->prepare("UPDATE messages SET is_read = 1 WHERE sender_id = ? AND receiver_id = ?");
        $upd->execute([$partner_id, $user_id]);

        $sql = "
            SELECT * FROM messages 
            WHERE (sender_id = :uid AND receiver_id = :pid) 
               OR (sender_id = :pid AND receiver_id = :uid)
            ORDER BY created_at ASC
        ";
        $stmt = $pdo->prepare($sql);
        $stmt->execute(['uid' => $user_id, 'pid' => $partner_id]);
        $msgs = $stmt->fetchAll();

        echo json_encode(['status' => 'success', 'data' => $msgs]);
    }

    // 3. Send a Message
    elseif ($action === 'send') {
        $input = json_decode(file_get_contents('php://input'), true);
        $receiver_id = $input['receiver_id'] ?? null;
        $message = trim($input['message'] ?? '');
        
        if (!$receiver_id || !$message) throw new Exception("Invalid input");

        $sql = "INSERT INTO messages (sender_id, receiver_id, message_text, created_at) VALUES (?, ?, ?, NOW())";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$user_id, $receiver_id, $message]);
        
        echo json_encode(['status' => 'success', 'message_id' => $pdo->lastInsertId()]);
    }

    else {
        throw new Exception("Invalid action");
    }

} catch (Exception $e) {
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}
?>
