<?php
/**
 * Quick fix: Create missing admin_notifications table
 */
require_once 'config/database.php';

echo "<h1>Creating Missing Table...</h1>";

try {
    $pdo = getDBConnection();
    
    // Create admin_notifications table
    $sql = "CREATE TABLE IF NOT EXISTS admin_notifications (
        id INT AUTO_INCREMENT PRIMARY KEY,
        type ENUM('driver_application', 'owner_application', 'report', 'system') DEFAULT 'system',
        reference_id INT DEFAULT NULL,
        title VARCHAR(255) NOT NULL,
        message TEXT,
        is_read TINYINT(1) DEFAULT 0,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        INDEX idx_type (type),
        INDEX idx_read (is_read)
    ) ENGINE=InnoDB";
    
    $pdo->exec($sql);
    echo "<p style='color:green'>✅ admin_notifications table created successfully!</p>";
    
    // Also update driver_applications table to add missing columns
    $alterQueries = [
        "ALTER TABLE driver_applications ADD COLUMN IF NOT EXISTS email VARCHAR(255) DEFAULT NULL AFTER full_name",
        "ALTER TABLE driver_applications ADD COLUMN IF NOT EXISTS license_expiry DATE DEFAULT NULL AFTER driving_license",
        "ALTER TABLE driver_applications ADD COLUMN IF NOT EXISTS service_areas TEXT DEFAULT NULL AFTER profile_photo",
        "ALTER TABLE driver_applications ADD COLUMN IF NOT EXISTS availability_hours VARCHAR(50) DEFAULT NULL AFTER service_areas",
        "ALTER TABLE driver_applications ADD COLUMN IF NOT EXISTS experience VARCHAR(50) DEFAULT NULL AFTER availability_hours",
        "ALTER TABLE driver_applications ADD COLUMN IF NOT EXISTS has_smartphone TINYINT(1) DEFAULT 1 AFTER experience"
    ];
    
    foreach ($alterQueries as $query) {
        try {
            $pdo->exec($query);
        } catch (PDOException $e) {
            // Column might already exist, ignore error
        }
    }
    echo "<p style='color:green'>✅ driver_applications table updated!</p>";
    
    echo "<h2>Done! You can now:</h2>";
    echo "<ul>";
    echo "<li><a href='driver-registration.php'>Submit a Driver Application</a></li>";
    echo "<li><a href='debug_database.php'>Check Database Status</a></li>";
    echo "<li><a href='admin_dashboard.php'>Go to Admin Dashboard</a></li>";
    echo "</ul>";
    
} catch (PDOException $e) {
    echo "<p style='color:red'>❌ Error: " . $e->getMessage() . "</p>";
}
?>
