<?php
require_once 'config/database.php';
$pdo = getDBConnection();

$stmt = $pdo->query("SELECT count(*) FROM driver_applications WHERE status = 'pending'");
echo "Pending Applications: " . $stmt->fetchColumn() . "\n";

$stmt = $pdo->query("SELECT count(*) FROM driver_applications WHERE status = 'approved'");
echo "Approved Applications: " . $stmt->fetchColumn() . "\n";

$stmt = $pdo->query("SELECT count(*) FROM users WHERE role = 'delivery_partner'");
echo "Users with delivery_partner role: " . $stmt->fetchColumn() . "\n";

echo "\n--- LIST OF ACTUAL DELIVERY PARTNERS IN DB ---\n";
$stmt = $pdo->query("SELECT name FROM users WHERE role = 'delivery_partner'");
while($r = $stmt->fetch()) {
    echo $r['name'] . "\n";
}

echo "\n--- LIST OF JSON USERS (Check for sync issues) ---\n";
$users = json_decode(file_get_contents('users.json'), true);
foreach($users as $u) {
    if(isset($u['role']) && $u['role'] == 'delivery_partner') {
        echo $u['name'] . " (JSON)\n";
    }
}
?>
