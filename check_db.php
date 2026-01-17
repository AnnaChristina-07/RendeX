<?php
require_once 'config/database.php';
$email = 'annachristina2005@gmail.com';
try {
    $pdo = getDBConnection();
    if ($pdo) {
        $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ?");
        $stmt->execute([$email]);
        $user = $stmt->fetch();
        if ($user) {
            echo "User found in DB!\n";
            echo "Email: " . $user['email'] . "\n";
            echo "Hash: " . $user['password_hash'] . "\n";
        } else {
            echo "User NOT found in DB.\n";
        }
    } else {
        echo "Could not connect to DB.\n";
    }
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?>
