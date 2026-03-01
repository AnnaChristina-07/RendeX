<?php
/**
 * RendeX - Pre-Booking Schedule Migration
 * Run ONCE: http://localhost/RendeX/setup_prebooking.php
 */
require_once 'config/database.php';
$pdo = getDBConnection();
if (!$pdo) die('<h2 style="color:red">❌ DB connection failed.</h2>');

$steps = [];

// 1. Create pre_bookings table
try {
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS pre_bookings (
            id INT AUTO_INCREMENT PRIMARY KEY,
            booking_ref VARCHAR(30) NOT NULL UNIQUE,
            item_id INT NOT NULL,
            user_id VARCHAR(20) NOT NULL,
            owner_id VARCHAR(20) NOT NULL,
            start_date DATE NOT NULL,
            end_date DATE NOT NULL,
            total_days INT NOT NULL,
            daily_rate DECIMAL(10,2) NOT NULL,
            total_amount DECIMAL(10,2) NOT NULL,
            status ENUM('pending','confirmed','active','completed','cancelled','expired') DEFAULT 'pending',
            payment_status ENUM('none','paid','refunded') DEFAULT 'none',
            payment_id VARCHAR(100) DEFAULT NULL,
            delivery_method ENUM('pickup','delivery') DEFAULT 'pickup',
            delivery_address TEXT DEFAULT NULL,
            owner_approved TINYINT DEFAULT 0,
            reminder_sent TINYINT DEFAULT 0,
            notes TEXT DEFAULT NULL,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            expires_at DATETIME DEFAULT NULL,
            INDEX idx_item_dates (item_id, start_date, end_date),
            INDEX idx_user (user_id),
            INDEX idx_status (status)
        ) ENGINE=InnoDB
    ");
    $steps[] = "✅ Created <b>pre_bookings</b> table";
} catch (Exception $e) {
    $steps[] = "⚠️ pre_bookings: " . $e->getMessage();
}

// 2. Add allow_prebooking to items
try {
    $cols = $pdo->query("SHOW COLUMNS FROM items LIKE 'allow_prebooking'")->fetchAll();
    if (empty($cols)) {
        $pdo->exec("ALTER TABLE items ADD COLUMN allow_prebooking TINYINT DEFAULT 1 AFTER sold_at");
        $pdo->exec("ALTER TABLE items ADD COLUMN max_advance_days INT DEFAULT 60 AFTER allow_prebooking");
        $steps[] = "✅ Added <b>allow_prebooking</b> &amp; <b>max_advance_days</b> to <code>items</code>";
    } else {
        $steps[] = "⚠️ allow_prebooking already exists — skipped";
    }
} catch (Exception $e) {
    $steps[] = "❌ items alter: " . $e->getMessage();
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>RendeX — Pre-Booking Migration</title>
    <style>
        body{font-family:'Segoe UI',sans-serif;max-width:680px;margin:60px auto;background:#f8f8f5;color:#1c1c0d}
        h1{font-size:26px;font-weight:900;margin-bottom:20px}
        .card{background:#fff;border-radius:16px;padding:30px;border:1px solid #e9e8ce;margin-bottom:20px}
        .step{padding:12px 16px;border-radius:10px;margin-bottom:10px;background:#f4f4e6;font-size:14px}
        .btn{display:inline-block;margin-top:20px;background:#f9f506;color:#000;font-weight:900;padding:14px 30px;border-radius:50px;text-decoration:none;font-size:15px}
        .btn2{background:#000;color:#fff;margin-left:10px}
    </style>
</head>
<body>
<div class="card">
    <h1>📅 Pre-Booking Migration</h1>
    <?php foreach($steps as $s): ?>
        <div class="step"><?= $s ?></div>
    <?php endforeach; ?>
    <p><strong>✅ Migration complete!</strong></p>
    <a href="dashboard.php" class="btn">Go to Dashboard</a>
    <a href="item-details.php" class="btn btn2">Test on Item Page</a>
</div>
</body>
</html>
