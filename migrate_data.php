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
        // Updated to match new schema: password_hash, profile_picture
        $stmt = $conn->prepare("INSERT INTO users (id, name, email, phone, password_hash, role, profile_picture, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?) ON DUPLICATE KEY UPDATE name=VALUES(name), phone=VALUES(phone), role=VALUES(role)");
        
        foreach ($users as $user) {
            $pass_hash = $user['password_hash'] ?? $user['password'] ?? ''; 
            $stmt->bind_param("ssssssss", 
                $user['id'], 
                $user['name'], 
                $user['email'], 
                $user['phone'], 
                $pass_hash, 
                $user['role'], 
                $user['profile_pic'], // JSON uses profile_pic, DB uses profile_picture
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
        // Updated to match new schema: owner_id, price_per_day, location, etc.
        // items table schema: id, owner_id, title, description, category, price_per_day, security_deposit, condition_status, availability_status, location, city, state, pincode, images, is_active, views_count, created_at
        
        $stmt = $conn->prepare("INSERT INTO items (id, owner_id, title, category, price_per_day, description, location, images, availability_status, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?) ON DUPLICATE KEY UPDATE title=VALUES(title), price_per_day=VALUES(price_per_day)");
        
        foreach ($items as $item) {
            $images_enc = json_encode($item['images'] ?? []);
            $status = ($item['status'] == 'available') ? 'available' : 'unavailable';
            
            $stmt->bind_param("ssssssssss", 
                $item['id'], 
                $item['user_id'], // JSON has user_id, DB maps to owner_id
                $item['title'], 
                $item['category'], 
                $item['price'], // JSON has price, DB maps to price_per_day
                $item['description'], 
                $item['address'], // JSON has address, DB maps to location
                $images_enc, 
                $status,
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
        // Schema: id, item_id, renter_id, owner_id, start_date, end_date, total_days, daily_rate, total_amount, status
        // JSON likely missing owner_id, total_days, daily_rate - we might need to fetch or approximate
        // For simple restore, we will try to map available fields.
        // Important: Schema requires owner_id. JSON might not have it directly on the rental object if it was normalized differently.
        // Assuming JSON has user_id as renter_id.
        
        $stmt = $conn->prepare("INSERT INTO rentals (id, renter_id, item_id, owner_id, start_date, end_date, total_amount, daily_rate, total_days, status, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?) ON DUPLICATE KEY UPDATE status=VALUES(status)");
        
        foreach ($rentals as $rental) {
            // We need owner_id. If missing in JSON, we might fail or need to look it up.
            // For now, let's assume item owner or use a placeholder if not found.
            $owner_id = $rental['owner_id'] ?? 'unknown'; 
            $daily_rate = $rental['price_per_day'] ?? 0;
            $total_days = 1; // Default
            
            // Calculate days if dates exist
            if (!empty($rental['start_date']) && !empty($rental['end_date'])) {
                $start = new DateTime($rental['start_date']);
                $end = new DateTime($rental['end_date']);
                $diff = $start->diff($end);
                $total_days = $diff->days + 1;
            }

            $stmt->bind_param("ssssssddiss", 
                $rental['id'], 
                $rental['user_id'], // renter
                $rental['item_id'], 
                $owner_id,
                $rental['start_date'], 
                $rental['end_date'], 
                $rental['total_price'], 
                $daily_rate,
                $total_days,
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
