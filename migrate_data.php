<?php
// Function to migrate JSON data to MySQL
function migrate_json_to_mysql() {
    $host = 'localhost';
    $db_name = 'rendex_db';
    $username = 'root';
    $password = '';

    $conn = new mysqli($host, $username, $password, $db_name);

    if ($conn->connect_error) {
        die("Connection failed: " . $conn->connect_error);
    }

    // 1. Migrate Users
    $users_file = 'users.json';
    if (file_exists($users_file)) {
        $users = json_decode(file_get_contents($users_file), true) ?: [];
        $stmt = $conn->prepare("INSERT INTO users (id, name, email, phone, password, role, profile_pic, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?) ON DUPLICATE KEY UPDATE name=VALUES(name), phone=VALUES(phone), role=VALUES(role)");
        
        foreach ($users as $user) {
            $pass_hash = $user['password_hash'] ?? ''; // Handle diverse naming if any
            $stmt->bind_param("ssssssss", 
                $user['id'], 
                $user['name'], 
                $user['email'], 
                $user['phone'], 
                $pass_hash, 
                $user['role'], 
                $user['profile_pic'], 
                $user['created_at'] 
            );
            $stmt->execute();
        }
        echo "Users migrated: " . count($users) . "<br>";
        $stmt->close();
    }

    // 2. Migrate Items
    $items_file = 'items.json';
    if (file_exists($items_file)) {
        $items = json_decode(file_get_contents($items_file), true) ?: [];
        $stmt = $conn->prepare("INSERT INTO items (id, user_id, title, category, price, description, address, available_from, available_to, images, status, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?) ON DUPLICATE KEY UPDATE title=VALUES(title), price=VALUES(price)");
        
        foreach ($items as $item) {
            $images_enc = json_encode($item['images'] ?? []);
            $af = $item['available_from'] ?? NULL;
            $at = $item['available_to'] ?? NULL;
            // Handle optional dates
            if (empty($af)) $af = NULL;
            if (empty($at)) $at = NULL;
            
            $stmt->bind_param("ssssssssssss", 
                $item['id'], 
                $item['user_id'], 
                $item['title'], 
                $item['category'], 
                $item['price'], 
                $item['description'], 
                $item['address'], 
                $af, 
                $at, 
                $images_enc, 
                $item['status'], 
                $item['created_at']
            );
            $stmt->execute();
        }
        echo "Items migrated: " . count($items) . "<br>";
        $stmt->close();
    }

    // 3. Migrate Rentals
    $rentals_file = 'rentals.json';
    if (file_exists($rentals_file)) {
        $rentals = json_decode(file_get_contents($rentals_file), true) ?: [];
        $stmt = $conn->prepare("INSERT INTO rentals (id, user_id, item_id, start_date, end_date, total_price, action, status, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?) ON DUPLICATE KEY UPDATE status=VALUES(status)");
        
        foreach ($rentals as $rental) {
            $stmt->bind_param("sssssdsss", 
                $rental['id'], 
                $rental['user_id'], 
                $rental['item_id'], 
                $rental['start_date'], 
                $rental['end_date'], 
                $rental['total_price'], 
                $rental['action'], 
                $rental['status'], 
                $rental['created_at']
            );
            $stmt->execute();
        }
        echo "Rentals migrated: " . count($rentals) . "<br>";
        $stmt->close();
    }

    // 4. Migrate Deliveries
    $deliveries_file = 'deliveries.json';
    if (file_exists($deliveries_file)) {
        $deliveries = json_decode(file_get_contents($deliveries_file), true) ?: [];
        $stmt = $conn->prepare("INSERT INTO deliveries (id, rental_id, partner_id, pickup_address, dropoff_address, status, created_at) VALUES (?, ?, ?, ?, ?, ?, ?) ON DUPLICATE KEY UPDATE status=VALUES(status)");
        
        foreach ($deliveries as $del) {
            $pid = $del['partner_id'] ?? NULL; // partner_id can be null
            $stmt->bind_param("sssssss", 
                $del['id'], 
                $del['rental_id'], 
                $pid, 
                $del['pickup_address'], 
                $del['dropoff_address'], 
                $del['status'], 
                $del['created_at']
            );
            $stmt->execute();
        }
        echo "Deliveries migrated: " . count($deliveries) . "<br>";
        $stmt->close();
    }

    $conn->close();
}

// Run Migration
migrate_json_to_mysql();
?>
