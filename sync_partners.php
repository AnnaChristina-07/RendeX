<?php
require_once 'config/database.php';
$pdo = getDBConnection();

$json_file = 'users.json';
if (!file_exists($json_file)) {
    die("users.json not found.");
}

$json_users = json_decode(file_get_contents($json_file), true);
if (!$json_users) die("Invalid JSON.");

echo "Syncing JSON users to Database...\n";

foreach ($json_users as $j_user) {
    if (isset($j_user['role']) && $j_user['role'] === 'delivery_partner') {
        $email = $j_user['email'];
        $name = $j_user['name'];
        echo "Processing JSON Partner: $name ($email)\n";

        // 1. Check if user exists in DB
        $stmt = $pdo->prepare("SELECT id, role FROM users WHERE email = ?");
        $stmt->execute([$email]);
        $db_user = $stmt->fetch();

        if ($db_user) {
            // User exists
            echo " - Found in DB (ID: {$db_user['id']}, Role: {$db_user['role']})\n";
            if ($db_user['role'] !== 'delivery_partner') {
                echo " - Updating role to delivery_partner...\n";
                $pdo->prepare("UPDATE users SET role = 'delivery_partner' WHERE id = ?")->execute([$db_user['id']]);
            }
        } else {
            // User does not exist, insert them
            echo " - Not in DB. Inserting...\n";
            $new_id = $j_user['id'] ?? uniqid();
            $stmt = $pdo->prepare("INSERT INTO users (id, name, email, password, role, phone, created_at) VALUES (?, ?, ?, ?, ?, ?, NOW())");
            // Use dummy password if not in JSON (should be in JSON though)
            $pwd = $j_user['password'] ?? password_hash('password', PASSWORD_DEFAULT);
            $phone = $j_user['phone'] ?? '';
            $stmt->execute([$new_id, $name, $email, $pwd, 'delivery_partner', $phone]);
            echo " - Inserted.\n";
            $db_user = ['id' => $new_id];
        }

        // 2. Ensure they have an approved driver application
        $stmt = $pdo->prepare("SELECT id FROM driver_applications WHERE user_id = ?");
        $stmt->execute([$db_user['id'] ?? $new_id]); // use the ID we have
        if (!$stmt->fetch()) {
             echo " - Creating dummy approved application record...\n";
             $app_stmt = $pdo->prepare("INSERT INTO driver_applications (user_id, full_name, email, phone, status, applied_at, reviewed_at) VALUES (?, ?, ?, ?, 'approved', NOW(), NOW())");
             $app_stmt->execute([
                 $db_user['id'] ?? $new_id, 
                 $name, 
                 $email, 
                 $j_user['phone'] ?? ''
             ]);
        }
    }
}

echo "Sync complete.\n";
?>
