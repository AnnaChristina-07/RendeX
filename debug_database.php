<?php
/**
 * Database Debug Tool for RendeX
 * This will help identify issues with the database connection and tables
 */

require_once 'config/database.php';

echo "<!DOCTYPE html>
<html lang='en'>
<head>
    <meta charset='UTF-8'>
    <meta name='viewport' content='width=device-width, initial-scale=1.0'>
    <title>RendeX Database Debug</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: #1a1a2e;
            min-height: 100vh;
            padding: 40px;
            color: #fff;
        }
        .container {
            max-width: 900px;
            margin: 0 auto;
            background: rgba(255, 255, 255, 0.1);
            border-radius: 20px;
            padding: 30px;
        }
        h1 { color: #00ff88; margin-bottom: 20px; }
        h2 { color: #00d9ff; margin: 20px 0 10px; border-bottom: 1px solid #333; padding-bottom: 5px; }
        .success { color: #00ff88; }
        .error { color: #ff4444; }
        .warning { color: #ffaa00; }
        .info { color: #00d9ff; }
        pre {
            background: #000;
            padding: 15px;
            border-radius: 10px;
            overflow-x: auto;
            font-size: 12px;
            margin: 10px 0;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin: 10px 0;
        }
        th, td {
            padding: 10px;
            text-align: left;
            border: 1px solid #333;
        }
        th { background: #333; }
        .btn {
            display: inline-block;
            padding: 10px 20px;
            background: #00ff88;
            color: #000;
            text-decoration: none;
            border-radius: 5px;
            margin: 5px;
            font-weight: bold;
        }
    </style>
</head>
<body>
<div class='container'>
    <h1>🔧 RendeX Database Debug Tool</h1>";

// Step 1: Test Connection
echo "<h2>1. Database Connection</h2>";
try {
    $pdo = getDBConnection();
    if ($pdo) {
        echo "<p class='success'>✅ Connected to database successfully!</p>";
    } else {
        echo "<p class='error'>❌ Failed to connect - getDBConnection() returned null</p>";
        die("</div></body></html>");
    }
} catch (Exception $e) {
    echo "<p class='error'>❌ Connection Error: " . $e->getMessage() . "</p>";
    die("</div></body></html>");
}

// Step 2: Check Tables
echo "<h2>2. Database Tables</h2>";
try {
    $stmt = $pdo->query("SHOW TABLES");
    $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    if (count($tables) > 0) {
        echo "<p class='success'>✅ Found " . count($tables) . " tables:</p>";
        echo "<ul>";
        foreach ($tables as $table) {
            echo "<li>$table</li>";
        }
        echo "</ul>";
    } else {
        echo "<p class='error'>❌ No tables found! Run the installer: <a href='install_database.php' class='btn'>Install Database</a></p>";
    }
} catch (Exception $e) {
    echo "<p class='error'>❌ Error: " . $e->getMessage() . "</p>";
}

// Step 3: Check driver_applications table structure
echo "<h2>3. driver_applications Table Structure</h2>";
try {
    $stmt = $pdo->query("DESCRIBE driver_applications");
    $columns = $stmt->fetchAll();
    
    echo "<table>";
    echo "<tr><th>Column</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th></tr>";
    foreach ($columns as $col) {
        echo "<tr>";
        echo "<td>" . $col['Field'] . "</td>";
        echo "<td>" . $col['Type'] . "</td>";
        echo "<td>" . $col['Null'] . "</td>";
        echo "<td>" . $col['Key'] . "</td>";
        echo "<td>" . ($col['Default'] ?? 'NULL') . "</td>";
        echo "</tr>";
    }
    echo "</table>";
} catch (Exception $e) {
    echo "<p class='error'>❌ Table doesn't exist or error: " . $e->getMessage() . "</p>";
    echo "<p class='warning'>⚠️ Run the installer to create tables: <a href='install_database.php' class='btn'>Install Database</a></p>";
}

// Step 4: Check driver_applications data
echo "<h2>4. Data in driver_applications Table</h2>";
try {
    $stmt = $pdo->query("SELECT * FROM driver_applications ORDER BY applied_at DESC LIMIT 10");
    $applications = $stmt->fetchAll();
    
    if (count($applications) > 0) {
        echo "<p class='success'>✅ Found " . count($applications) . " application(s):</p>";
        echo "<table>";
        echo "<tr><th>ID</th><th>User ID</th><th>Full Name</th><th>Email</th><th>Phone</th><th>Vehicle</th><th>Status</th><th>Applied At</th></tr>";
        foreach ($applications as $app) {
            echo "<tr>";
            echo "<td>" . $app['id'] . "</td>";
            echo "<td>" . $app['user_id'] . "</td>";
            echo "<td>" . htmlspecialchars($app['full_name']) . "</td>";
            echo "<td>" . htmlspecialchars($app['email'] ?? 'N/A') . "</td>";
            echo "<td>" . htmlspecialchars($app['phone'] ?? 'N/A') . "</td>";
            echo "<td>" . $app['vehicle_type'] . " - " . $app['vehicle_number'] . "</td>";
            echo "<td>" . $app['status'] . "</td>";
            echo "<td>" . $app['applied_at'] . "</td>";
            echo "</tr>";
        }
        echo "</table>";
    } else {
        echo "<p class='warning'>⚠️ No applications found in the database.</p>";
        echo "<p class='info'>ℹ️ Try submitting a new driver application to test.</p>";
    }
} catch (Exception $e) {
    echo "<p class='error'>❌ Error reading data: " . $e->getMessage() . "</p>";
}

// Step 5: Check admin_notifications
echo "<h2>5. Admin Notifications</h2>";
try {
    $stmt = $pdo->query("SELECT * FROM admin_notifications ORDER BY created_at DESC LIMIT 10");
    $notifications = $stmt->fetchAll();
    
    if (count($notifications) > 0) {
        echo "<p class='success'>✅ Found " . count($notifications) . " notification(s):</p>";
        echo "<table>";
        echo "<tr><th>ID</th><th>Type</th><th>Title</th><th>Message</th><th>Read</th><th>Created</th></tr>";
        foreach ($notifications as $notif) {
            echo "<tr>";
            echo "<td>" . $notif['id'] . "</td>";
            echo "<td>" . $notif['type'] . "</td>";
            echo "<td>" . htmlspecialchars($notif['title']) . "</td>";
            echo "<td>" . htmlspecialchars(substr($notif['message'], 0, 50)) . "...</td>";
            echo "<td>" . ($notif['is_read'] ? 'Yes' : 'No') . "</td>";
            echo "<td>" . $notif['created_at'] . "</td>";
            echo "</tr>";
        }
        echo "</table>";
    } else {
        echo "<p class='warning'>⚠️ No admin notifications found.</p>";
    }
} catch (Exception $e) {
    echo "<p class='error'>❌ Error or table doesn't exist: " . $e->getMessage() . "</p>";
}

// Step 6: Check users in database
echo "<h2>6. Users in Database</h2>";
try {
    $stmt = $pdo->query("SELECT id, name, email, role, created_at FROM users ORDER BY created_at DESC LIMIT 10");
    $users = $stmt->fetchAll();
    
    if (count($users) > 0) {
        echo "<p class='success'>✅ Found " . count($users) . " user(s):</p>";
        echo "<table>";
        echo "<tr><th>ID</th><th>Name</th><th>Email</th><th>Role</th><th>Created</th></tr>";
        foreach ($users as $user) {
            echo "<tr>";
            echo "<td>" . substr($user['id'], -8) . "</td>";
            echo "<td>" . htmlspecialchars($user['name']) . "</td>";
            echo "<td>" . htmlspecialchars($user['email']) . "</td>";
            echo "<td>" . $user['role'] . "</td>";
            echo "<td>" . $user['created_at'] . "</td>";
            echo "</tr>";
        }
        echo "</table>";
    } else {
        echo "<p class='warning'>⚠️ No users in database. Run installer to migrate from JSON.</p>";
    }
} catch (Exception $e) {
    echo "<p class='error'>❌ Error: " . $e->getMessage() . "</p>";
}

// Actions
echo "<h2>7. Quick Actions</h2>";
echo "<p>";
echo "<a href='install_database.php' class='btn'>🔧 Run Database Installer</a>";
echo "<a href='http://localhost/phpmyadmin' class='btn' target='_blank'>📊 Open phpMyAdmin</a>";
echo "<a href='admin_dashboard.php' class='btn'>👤 Admin Dashboard</a>";
echo "<a href='driver-registration.php' class='btn'>🚗 Driver Registration</a>";
echo "</p>";

echo "</div></body></html>";
?>
