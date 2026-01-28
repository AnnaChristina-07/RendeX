<?php
require_once 'config/database.php';
$pdo = getDBConnection();

$emails = [
    'junael samathew2028@mca.ajce.in', // Typo in image? 'junaelsamathew2028@mca.ajce.in'
    'junaelsamathew2028@mca.ajce.in',
    'annachristinajohny@gmail.com',
    'jeweltreasaraphel2028@mca.ajce.in',
    'elisreji@gmail.com'
];

echo "--- Checking Specific Users ---\n";
foreach ($emails as $email) {
    echo "Checking Email: $email\n";
    
    // Check Users Table
    $stmt = $pdo->prepare("SELECT id, name, role FROM users WHERE email = ?");
    $stmt->execute([$email]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($user) {
        echo "  [USERS TABLE] Found. ID: {$user['id']}, Name: {$user['name']}, Role: {$user['role']}\n";
        
        // Check Applications Table by User ID
        $stmt_app = $pdo->prepare("SELECT id, status FROM driver_applications WHERE user_id = ?");
        $stmt_app->execute([$user['id']]);
        $app = $stmt_app->fetch(PDO::FETCH_ASSOC);
        
        if ($app) {
            echo "  [APPS TABLE] Found Application. ID: {$app['id']}, Status: {$app['status']}\n";
        } else {
            echo "  [APPS TABLE] No application found for this User ID.\n";
        }
    } else {
        echo "  [USERS TABLE] Not found.\n";
    }
    echo "--------------------------------\n";
}

// Also check by Name just in case email doesn't match exactly
$names = ['Agnus Sabu', 'Jewel Treasa Raphel'];
foreach ($names as $name) {
    echo "Checking Name: $name\n";
    $stmt = $pdo->prepare("SELECT id, name, role FROM users WHERE name LIKE ?");
    $stmt->execute(["%$name%"]);
    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
    foreach($users as $u) {
        echo "  Found User: {$u['name']} (Role: {$u['role']})\n";
    }
}
?>
