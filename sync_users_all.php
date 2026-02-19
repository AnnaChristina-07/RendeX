<?php
require_once 'config/database.php';
$pdo = getDBConnection();

$json_file = 'users.json';
if (!file_exists($json_file)) {
    die("users.json not found.");
}

$json_users = json_decode(file_get_contents($json_file), true);
if (!$json_users) die("Invalid JSON.");

echo "Syncing ALL JSON users to Database...\n";

foreach ($json_users as $j_user) {
    if (empty($j_user['email'])) continue;

    $email = $j_user['email'];
    $name = $j_user['name'];
    $id = $j_user['id'] ?? uniqid();
    $role = $j_user['role'] ?? 'user';
    $phone = $j_user['phone'] ?? '';
    // Use existing password hash or default
    $password_hash = $j_user['password'] ?? password_hash('password123', PASSWORD_DEFAULT);

    echo "Processing User: $name ($email, ID: $id)\n";

    // Check if user exists in DB
    $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
    $stmt->execute([$email]);
    $existing = $stmt->fetch();

    if ($existing) {
        // Update if needed (e.g. ensure ID matches if possible, but usually we trust email)
        // If ID is different, we have a conflict. For now, assume email is unique key.
        // We might need to update other fields
        $upd = $pdo->prepare("UPDATE users SET name = ?, phone = ?, role = ? WHERE email = ?");
        $upd->execute([$name, $phone, $role, $email]);
        echo " - Updated existing user.\n";
    } else {
        // Insert
        try {
            $ins = $pdo->prepare("INSERT INTO users (id, name, email, phone, role, password_hash, created_at) VALUES (?, ?, ?, ?, ?, ?, NOW())");
            $ins->execute([$id, $name, $email, $phone, $role, $password_hash]);
            echo " - Inserted new user.\n";
        } catch (PDOException $e) {
            // Handle duplicate ID potential
            echo " - Error inserting: " . $e->getMessage() . "\n";
            // Try updating by ID?
            $upd_id = $pdo->prepare("UPDATE users SET name = ?, email = ?, phone = ?, role = ? WHERE id = ?");
            $upd_id->execute([$name, $email, $phone, $role, $id]);
            echo " - Tried updating by ID instead.\n";
        }
    }
}

echo "Sync complete.\n";
?>
