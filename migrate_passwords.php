<?php
require_once 'config/database.php';
$users_file = 'users.json';

if (file_exists($users_file)) {
    $users = json_decode(file_get_contents($users_file), true) ?: [];
    $pdo = getDBConnection();
    
    if ($pdo) {
        $synced = 0;
        foreach ($users as $user) {
            $stmt = $pdo->prepare("UPDATE users SET password_hash = ? WHERE email = ? AND (password_hash = 'google_oauth_no_password' OR password_hash IS NULL OR password_hash = '')");
            $stmt->execute([$user['password_hash'], $user['email']]);
            if ($stmt->rowCount() > 0) {
                $synced++;
            }
        }
        echo "Successfully synced $synced users' passwords from JSON to Database.\n";
    } else {
        echo "Database connection failed.\n";
    }
} else {
    echo "users.json not found.\n";
}
?>
