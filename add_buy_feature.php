<?php
/**
 * RendeX - Buy Feature Database Migration
 * Run this ONCE in the browser: http://localhost/RendeX/add_buy_feature.php
 */
require_once 'config/database.php';
$pdo = getDBConnection();
if (!$pdo) {
    die('<h2 style="color:red">❌ Database connection failed.</h2>');
}

$steps = [];

// 1. Add listing_type column to items
try {
    $cols = $pdo->query("SHOW COLUMNS FROM items LIKE 'listing_type'")->fetchAll();
    if (empty($cols)) {
        $pdo->exec("ALTER TABLE items ADD COLUMN listing_type ENUM('rent','sell','both') DEFAULT 'rent' AFTER category");
        $steps[] = "✅ Added <b>listing_type</b> column to <code>items</code>";
    } else {
        $steps[] = "⚠️ <b>listing_type</b> already exists — skipped";
    }
} catch (Exception $e) {
    $steps[] = "❌ listing_type error: " . $e->getMessage();
}

// 2. Add selling_price column
try {
    $cols = $pdo->query("SHOW COLUMNS FROM items LIKE 'selling_price'")->fetchAll();
    if (empty($cols)) {
        $pdo->exec("ALTER TABLE items ADD COLUMN selling_price DECIMAL(10,2) DEFAULT NULL AFTER listing_type");
        $steps[] = "✅ Added <b>selling_price</b> column to <code>items</code>";
    } else {
        $steps[] = "⚠️ <b>selling_price</b> already exists — skipped";
    }
} catch (Exception $e) {
    $steps[] = "❌ selling_price error: " . $e->getMessage();
}

// 3. Add sold_to + sold_at columns
try {
    $cols = $pdo->query("SHOW COLUMNS FROM items LIKE 'sold_to'")->fetchAll();
    if (empty($cols)) {
        $pdo->exec("ALTER TABLE items ADD COLUMN sold_to VARCHAR(20) DEFAULT NULL AFTER selling_price");
        $pdo->exec("ALTER TABLE items ADD COLUMN sold_at DATETIME DEFAULT NULL AFTER sold_to");
        $steps[] = "✅ Added <b>sold_to</b> and <b>sold_at</b> columns to <code>items</code>";
    } else {
        $steps[] = "⚠️ <b>sold_to</b> already exists — skipped";
    }
} catch (Exception $e) {
    $steps[] = "❌ sold_to/sold_at error: " . $e->getMessage();
}

// 4. Create purchases table
try {
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS purchases (
            id INT AUTO_INCREMENT PRIMARY KEY,
            purchase_ref VARCHAR(30) NOT NULL UNIQUE,
            item_id INT NOT NULL,
            buyer_id VARCHAR(20) NOT NULL,
            seller_id VARCHAR(20) NOT NULL,
            amount DECIMAL(10,2) NOT NULL,
            platform_fee DECIMAL(10,2) DEFAULT 50.00,
            payment_status ENUM('pending','paid','failed','refunded') DEFAULT 'pending',
            payment_id VARCHAR(100) DEFAULT NULL,
            delivery_address TEXT DEFAULT NULL,
            delivery_city VARCHAR(100) DEFAULT NULL,
            delivery_state VARCHAR(100) DEFAULT NULL,
            delivery_zip VARCHAR(10) DEFAULT NULL,
            delivery_phone VARCHAR(20) DEFAULT NULL,
            status ENUM('pending','confirmed','shipped','delivered','cancelled') DEFAULT 'pending',
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            FOREIGN KEY (item_id) REFERENCES items(id) ON DELETE CASCADE,
            INDEX idx_buyer (buyer_id),
            INDEX idx_seller (seller_id),
            INDEX idx_status (status)
        ) ENGINE=InnoDB
    ");
    $steps[] = "✅ Created <b>purchases</b> table";
} catch (Exception $e) {
    $steps[] = "⚠️ purchases table: " . $e->getMessage();
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>RendeX - Buy Feature Migration</title>
    <style>
        body { font-family: 'Segoe UI', sans-serif; max-width: 700px; margin: 60px auto; background: #f8f8f5; color: #1c1c0d; }
        h1 { font-size: 28px; font-weight: 900; margin-bottom: 24px; }
        .card { background: #fff; border-radius: 16px; padding: 32px; border: 1px solid #e9e8ce; margin-bottom: 24px; }
        .step { padding: 12px 16px; border-radius: 10px; margin-bottom: 10px; background: #f4f4e6; font-size: 15px; }
        .action { display: inline-block; margin-top: 24px; background: #f9f506; color: #000; font-weight: 900; padding: 14px 32px; border-radius: 50px; text-decoration: none; font-size: 15px; }
        .action-secondary { display: inline-block; margin-top: 24px; margin-left: 12px; background: #000; color: #fff; font-weight: 900; padding: 14px 32px; border-radius: 50px; text-decoration: none; font-size: 15px; }
    </style>
</head>
<body>
    <div class="card">
        <h1>🛒 Buy Feature — DB Migration</h1>
        <?php foreach ($steps as $s): ?>
            <div class="step"><?= $s ?></div>
        <?php endforeach; ?>
        <br>
        <strong>✅ Migration complete!</strong> You can now use the Buy feature.
        <br>
        <a href="lend-item.php" class="action">→ List an Item (with Sell option)</a>
        <a href="dashboard.php" class="action-secondary">Back to Dashboard</a>
    </div>
</body>
</html>
