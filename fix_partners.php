<?php
require_once 'config/database.php';
$pdo = getDBConnection();

// Check for discrepancy: Approved Application but User Role not 'delivery_partner'
$stmt = $pdo->query("
    SELECT u.id, u.name, u.role, da.status as app_status 
    FROM users u 
    JOIN driver_applications da ON u.id = da.user_id 
    WHERE da.status = 'approved' AND u.role != 'delivery_partner'
");
$discrepancies = $stmt->fetchAll(PDO::FETCH_ASSOC);

if (count($discrepancies) > 0) {
    echo "Found " . count($discrepancies) . " discrepancies where application is approved but user role is not updated.\n";
    foreach ($discrepancies as $d) {
        echo "Fixing User: " . $d['name'] . " (ID: " . $d['id'] . ") - Current Role: " . $d['role'] . "\n";
        
        // Fix it
        $update = $pdo->prepare("UPDATE users SET role = 'delivery_partner' WHERE id = ?");
        $update->execute([$d['id']]);
    }
    echo "All fixed.\n";
} else {
    echo "No discrepancies found between driver applications and user roles.\n";
    
    // Debug: List all approved applications
    echo "All Approved Applications:\n";
    $stmt = $pdo->query("SELECT user_id, full_name FROM driver_applications WHERE status='approved'");
    $rows = $stmt->fetchAll();
    foreach($rows as $r) echo " - " . $r['full_name'] . " (" . $r['user_id'] . ")\n";
}
?>
