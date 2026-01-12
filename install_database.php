<?php
/**
 * RendeX Database Installer
 * This script will create the database, tables, and migrate existing JSON data
 * Run this once to set up your MySQL database in XAMPP
 */

// Prevent timeout for large migrations
set_time_limit(300);

// Database credentials
$host = 'localhost';
$user = 'root';
$pass = ''; // Default XAMPP has no password

echo "<!DOCTYPE html>
<html lang='en'>
<head>
    <meta charset='UTF-8'>
    <meta name='viewport' content='width=device-width, initial-scale=1.0'>
    <title>RendeX Database Installer</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #1a1a2e 0%, #16213e 50%, #0f3460 100%);
            min-height: 100vh;
            padding: 40px;
            color: #fff;
        }
        .container {
            max-width: 800px;
            margin: 0 auto;
            background: rgba(255, 255, 255, 0.1);
            backdrop-filter: blur(10px);
            border-radius: 20px;
            padding: 40px;
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.3);
        }
        h1 {
            text-align: center;
            margin-bottom: 30px;
            font-size: 2.5em;
            background: linear-gradient(90deg, #00d9ff, #00ff88);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }
        .step {
            display: flex;
            align-items: center;
            padding: 15px;
            margin: 10px 0;
            background: rgba(255, 255, 255, 0.05);
            border-radius: 10px;
            border-left: 4px solid #666;
        }
        .step.success { border-left-color: #00ff88; }
        .step.error { border-left-color: #ff4444; }
        .step.info { border-left-color: #00d9ff; }
        .icon {
            width: 30px;
            height: 30px;
            margin-right: 15px;
            font-size: 20px;
        }
        .message { flex: 1; }
        .count { 
            background: #00ff88; 
            color: #000; 
            padding: 5px 15px; 
            border-radius: 20px; 
            font-weight: bold;
        }
        .summary {
            margin-top: 30px;
            padding: 20px;
            background: linear-gradient(135deg, rgba(0, 255, 136, 0.2), rgba(0, 217, 255, 0.2));
            border-radius: 15px;
            text-align: center;
        }
        .btn {
            display: inline-block;
            margin-top: 20px;
            padding: 15px 30px;
            background: linear-gradient(90deg, #00d9ff, #00ff88);
            color: #000;
            text-decoration: none;
            border-radius: 30px;
            font-weight: bold;
            transition: transform 0.3s;
        }
        .btn:hover { transform: scale(1.05); }
    </style>
</head>
<body>
    <div class='container'>
        <h1>🚀 RendeX Database Installer</h1>";

$errors = [];
$success = [];

// Step 1: Connect to MySQL
echo "<div class='step info'><span class='icon'>🔌</span><span class='message'>Connecting to MySQL server...</span></div>";

try {
    $pdo = new PDO("mysql:host=$host", $user, $pass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
    ]);
    echo "<div class='step success'><span class='icon'>✅</span><span class='message'>Connected to MySQL server successfully!</span></div>";
} catch (PDOException $e) {
    echo "<div class='step error'><span class='icon'>❌</span><span class='message'>Failed to connect: " . $e->getMessage() . "</span></div>";
    die("</div></body></html>");
}

// Step 2: Create database
echo "<div class='step info'><span class='icon'>📦</span><span class='message'>Creating database 'rendex_db'...</span></div>";

try {
    $pdo->exec("CREATE DATABASE IF NOT EXISTS rendex_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
    $pdo->exec("USE rendex_db");
    echo "<div class='step success'><span class='icon'>✅</span><span class='message'>Database 'rendex_db' created/selected!</span></div>";
} catch (PDOException $e) {
    echo "<div class='step error'><span class='icon'>❌</span><span class='message'>Failed to create database: " . $e->getMessage() . "</span></div>";
}

// Step 3: Create tables
echo "<div class='step info'><span class='icon'>🏗️</span><span class='message'>Creating database tables...</span></div>";

$tables = [
    'users' => "CREATE TABLE IF NOT EXISTS users (
        id VARCHAR(20) PRIMARY KEY,
        name VARCHAR(100) NOT NULL,
        email VARCHAR(255) NOT NULL UNIQUE,
        phone VARCHAR(20),
        role ENUM('user', 'owner', 'delivery_partner', 'admin') DEFAULT 'user',
        password_hash VARCHAR(255) NOT NULL,
        profile_picture VARCHAR(255) DEFAULT NULL,
        address TEXT DEFAULT NULL,
        city VARCHAR(100) DEFAULT NULL,
        state VARCHAR(100) DEFAULT NULL,
        pincode VARCHAR(10) DEFAULT NULL,
        is_verified TINYINT(1) DEFAULT 0,
        verification_token VARCHAR(255) DEFAULT NULL,
        reset_token VARCHAR(255) DEFAULT NULL,
        reset_token_expiry DATETIME DEFAULT NULL,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        INDEX idx_email (email),
        INDEX idx_role (role)
    ) ENGINE=InnoDB",

    'driver_applications' => "CREATE TABLE IF NOT EXISTS driver_applications (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id VARCHAR(20) NOT NULL,
        full_name VARCHAR(100) NOT NULL,
        email VARCHAR(255) DEFAULT NULL,
        date_of_birth DATE,
        gender ENUM('male', 'female', 'other'),
        phone VARCHAR(20),
        address TEXT,
        city VARCHAR(100),
        state VARCHAR(100),
        pincode VARCHAR(10),
        vehicle_type ENUM('bicycle', 'bike', 'scooter', 'car', 'van', 'truck') DEFAULT 'bike',
        vehicle_number VARCHAR(20),
        driving_license VARCHAR(50),
        license_expiry DATE DEFAULT NULL,
        license_document VARCHAR(255),
        id_proof_type VARCHAR(50),
        id_proof_document VARCHAR(255),
        vehicle_document VARCHAR(255),
        profile_photo VARCHAR(255),
        service_areas TEXT DEFAULT NULL,
        availability_hours VARCHAR(50) DEFAULT NULL,
        experience VARCHAR(50) DEFAULT NULL,
        has_smartphone TINYINT(1) DEFAULT 1,
        status ENUM('pending', 'approved', 'rejected') DEFAULT 'pending',
        rejection_reason TEXT DEFAULT NULL,
        applied_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        reviewed_at DATETIME DEFAULT NULL,
        reviewed_by VARCHAR(20) DEFAULT NULL,
        INDEX idx_status (status),
        INDEX idx_user_id (user_id),
        INDEX idx_applied_at (applied_at)
    ) ENGINE=InnoDB",

    'admin_notifications' => "CREATE TABLE IF NOT EXISTS admin_notifications (
        id INT AUTO_INCREMENT PRIMARY KEY,
        type ENUM('driver_application', 'owner_application', 'report', 'system') DEFAULT 'system',
        reference_id INT DEFAULT NULL,
        title VARCHAR(255) NOT NULL,
        message TEXT,
        is_read TINYINT(1) DEFAULT 0,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        INDEX idx_type (type),
        INDEX idx_read (is_read)
    ) ENGINE=InnoDB",

    'items' => "CREATE TABLE IF NOT EXISTS items (
        id INT AUTO_INCREMENT PRIMARY KEY,
        owner_id VARCHAR(20) NOT NULL,
        title VARCHAR(255) NOT NULL,
        description TEXT,
        category VARCHAR(100),
        price_per_day DECIMAL(10, 2) NOT NULL,
        security_deposit DECIMAL(10, 2) DEFAULT 0,
        condition_status ENUM('new', 'like_new', 'good', 'fair', 'poor') DEFAULT 'good',
        availability_status ENUM('available', 'rented', 'unavailable') DEFAULT 'available',
        location VARCHAR(255),
        city VARCHAR(100),
        state VARCHAR(100),
        pincode VARCHAR(10),
        images JSON DEFAULT NULL,
        is_active TINYINT(1) DEFAULT 1,
        views_count INT DEFAULT 0,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        INDEX idx_category (category),
        INDEX idx_availability (availability_status),
        INDEX idx_city (city)
    ) ENGINE=InnoDB",

    'rentals' => "CREATE TABLE IF NOT EXISTS rentals (
        id INT AUTO_INCREMENT PRIMARY KEY,
        item_id INT NOT NULL,
        renter_id VARCHAR(20) NOT NULL,
        owner_id VARCHAR(20) NOT NULL,
        start_date DATE NOT NULL,
        end_date DATE NOT NULL,
        total_days INT NOT NULL,
        daily_rate DECIMAL(10, 2) NOT NULL,
        total_amount DECIMAL(10, 2) NOT NULL,
        security_deposit DECIMAL(10, 2) DEFAULT 0,
        status ENUM('pending', 'confirmed', 'in_progress', 'completed', 'cancelled', 'disputed') DEFAULT 'pending',
        payment_status ENUM('pending', 'paid', 'refunded', 'failed') DEFAULT 'pending',
        payment_method VARCHAR(50),
        pickup_address TEXT,
        delivery_address TEXT,
        notes TEXT,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        INDEX idx_status (status),
        INDEX idx_renter (renter_id),
        INDEX idx_owner (owner_id)
    ) ENGINE=InnoDB",

    'deliveries' => "CREATE TABLE IF NOT EXISTS deliveries (
        id INT AUTO_INCREMENT PRIMARY KEY,
        rental_id INT NOT NULL,
        driver_id VARCHAR(20) DEFAULT NULL,
        pickup_address TEXT NOT NULL,
        delivery_address TEXT NOT NULL,
        pickup_contact VARCHAR(100),
        delivery_contact VARCHAR(100),
        pickup_phone VARCHAR(20),
        delivery_phone VARCHAR(20),
        scheduled_pickup DATETIME,
        scheduled_delivery DATETIME,
        actual_pickup DATETIME DEFAULT NULL,
        actual_delivery DATETIME DEFAULT NULL,
        distance_km DECIMAL(10, 2) DEFAULT NULL,
        delivery_fee DECIMAL(10, 2) DEFAULT 0,
        status ENUM('pending', 'assigned', 'picked_up', 'in_transit', 'delivered', 'cancelled', 'failed') DEFAULT 'pending',
        tracking_notes TEXT,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        INDEX idx_status (status),
        INDEX idx_driver (driver_id)
    ) ENGINE=InnoDB",

    'notifications' => "CREATE TABLE IF NOT EXISTS notifications (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id VARCHAR(20) NOT NULL,
        title VARCHAR(255) NOT NULL,
        message TEXT,
        type ENUM('info', 'success', 'warning', 'error', 'rental', 'delivery', 'payment', 'approval') DEFAULT 'info',
        is_read TINYINT(1) DEFAULT 0,
        link VARCHAR(255) DEFAULT NULL,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        INDEX idx_user_read (user_id, is_read)
    ) ENGINE=InnoDB",

    'reviews' => "CREATE TABLE IF NOT EXISTS reviews (
        id INT AUTO_INCREMENT PRIMARY KEY,
        rental_id INT NOT NULL,
        reviewer_id VARCHAR(20) NOT NULL,
        reviewee_id VARCHAR(20) NOT NULL,
        item_id INT DEFAULT NULL,
        rating TINYINT NOT NULL,
        review_text TEXT,
        review_type ENUM('renter_to_owner', 'owner_to_renter', 'item_review') DEFAULT 'item_review',
        is_visible TINYINT(1) DEFAULT 1,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        INDEX idx_reviewee (reviewee_id),
        INDEX idx_item (item_id)
    ) ENGINE=InnoDB",

    'password_resets' => "CREATE TABLE IF NOT EXISTS password_resets (
        id INT AUTO_INCREMENT PRIMARY KEY,
        email VARCHAR(255) NOT NULL,
        token VARCHAR(255) NOT NULL,
        expires_at DATETIME NOT NULL,
        used TINYINT(1) DEFAULT 0,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        INDEX idx_email_token (email, token)
    ) ENGINE=InnoDB"
];

$tableCount = 0;
foreach ($tables as $name => $sql) {
    try {
        $pdo->exec($sql);
        $tableCount++;
        echo "<div class='step success'><span class='icon'>✅</span><span class='message'>Table '$name' created!</span></div>";
    } catch (PDOException $e) {
        echo "<div class='step error'><span class='icon'>❌</span><span class='message'>Failed to create '$name': " . $e->getMessage() . "</span></div>";
    }
}

// Step 4: Migrate users from users.json
echo "<div class='step info'><span class='icon'>📤</span><span class='message'>Migrating users from users.json...</span></div>";

$usersFile = __DIR__ . '/users.json';
$usersMigrated = 0;

if (file_exists($usersFile)) {
    $users = json_decode(file_get_contents($usersFile), true);
    
    if ($users && is_array($users)) {
        $stmt = $pdo->prepare("INSERT INTO users (id, name, email, phone, role, password_hash, created_at) 
                               VALUES (?, ?, ?, ?, ?, ?, ?)
                               ON DUPLICATE KEY UPDATE name = VALUES(name), phone = VALUES(phone), role = VALUES(role)");
        
        foreach ($users as $user) {
            try {
                // Map role
                $role = 'user';
                if (isset($user['role'])) {
                    if ($user['role'] === 'owner') $role = 'owner';
                    elseif ($user['role'] === 'delivery_partner') $role = 'delivery_partner';
                    elseif ($user['role'] === 'admin') $role = 'admin';
                }
                
                $stmt->execute([
                    $user['id'],
                    $user['name'],
                    $user['email'],
                    $user['phone'] ?? null,
                    $role,
                    $user['password_hash'],
                    $user['created_at'] ?? date('Y-m-d H:i:s')
                ]);
                $usersMigrated++;
            } catch (PDOException $e) {
                echo "<div class='step error'><span class='icon'>⚠️</span><span class='message'>Failed to migrate user: " . $user['email'] . " - " . $e->getMessage() . "</span></div>";
            }
        }
        
        echo "<div class='step success'><span class='icon'>✅</span><span class='message'>Users migrated!</span><span class='count'>$usersMigrated users</span></div>";
    }
} else {
    echo "<div class='step info'><span class='icon'>ℹ️</span><span class='message'>No users.json found - starting fresh</span></div>";
}

// Step 5: Migrate items from items.json
echo "<div class='step info'><span class='icon'>📤</span><span class='message'>Migrating items from items.json...</span></div>";

$itemsFile = __DIR__ . '/items.json';
$itemsMigrated = 0;

if (file_exists($itemsFile)) {
    $items = json_decode(file_get_contents($itemsFile), true);
    
    if ($items && is_array($items)) {
        foreach ($items as $item) {
            try {
                // Check if owner exists
                $checkOwner = $pdo->prepare("SELECT id FROM users WHERE id = ?");
                $checkOwner->execute([$item['owner_id'] ?? '']);
                
                if ($checkOwner->fetch()) {
                    $stmt = $pdo->prepare("INSERT INTO items (owner_id, title, description, category, price_per_day, location, images, created_at) 
                                           VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
                    $stmt->execute([
                        $item['owner_id'],
                        $item['title'] ?? 'Untitled Item',
                        $item['description'] ?? '',
                        $item['category'] ?? 'Other',
                        $item['price_per_day'] ?? 0,
                        $item['location'] ?? '',
                        json_encode($item['images'] ?? []),
                        $item['created_at'] ?? date('Y-m-d H:i:s')
                    ]);
                    $itemsMigrated++;
                }
            } catch (PDOException $e) {
                // Skip duplicates or errors
            }
        }
        
        echo "<div class='step success'><span class='icon'>✅</span><span class='message'>Items migrated!</span><span class='count'>$itemsMigrated items</span></div>";
    }
} else {
    echo "<div class='step info'><span class='icon'>ℹ️</span><span class='message'>No items.json found - starting fresh</span></div>";
}

// Step 6: Create admin user if not exists
echo "<div class='step info'><span class='icon'>👤</span><span class='message'>Creating admin user...</span></div>";

try {
    $adminHash = password_hash('admin123', PASSWORD_DEFAULT);
    $stmt = $pdo->prepare("INSERT INTO users (id, name, email, phone, role, password_hash, created_at) 
                           VALUES ('admin001', 'Administrator', 'admin@rendex.com', '0000000000', 'admin', ?, NOW())
                           ON DUPLICATE KEY UPDATE id = id");
    $stmt->execute([$adminHash]);
    echo "<div class='step success'><span class='icon'>✅</span><span class='message'>Admin user ready! (admin@rendex.com / admin123)</span></div>";
} catch (PDOException $e) {
    echo "<div class='step info'><span class='icon'>ℹ️</span><span class='message'>Admin user already exists</span></div>";
}

// Summary
echo "
        <div class='summary'>
            <h2>🎉 Installation Complete!</h2>
            <p style='margin: 15px 0;'>Your RendeX database has been set up successfully.</p>
            <p><strong>Tables Created:</strong> $tableCount</p>
            <p><strong>Users Migrated:</strong> $usersMigrated</p>
            <p><strong>Items Migrated:</strong> $itemsMigrated</p>
            <br>
            <p style='color: #ff9900;'>⚠️ Important: Update your PHP files to use the database instead of JSON files.</p>
            <a href='index.php' class='btn'>Go to Homepage</a>
            <a href='http://localhost/phpmyadmin' class='btn' target='_blank'>Open phpMyAdmin</a>
        </div>
    </div>
</body>
</html>";
?>
