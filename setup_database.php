<?php
$host = 'localhost';
$db_name = 'rendex_db';
$username = 'root';
$password = '';

// Create connection
$conn = new mysqli($host, $username, $password);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Create database if it doesn't exist
$sql_db = "CREATE DATABASE IF NOT EXISTS $db_name";
if ($conn->query($sql_db) === TRUE) {
    echo "✅ Database 'RendeX' created successfully or already exists.<br><br>";
} else {
    die("❌ Error creating database: " . $conn->error);
}

// Select database
$conn->select_db($db_name);

echo "<h2>Creating Tables for RendeX Database...</h2>";

// 1. Users Table
$sql_users = "CREATE TABLE IF NOT EXISTS users (
    id VARCHAR(255) PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    email VARCHAR(255) NOT NULL UNIQUE,
    phone VARCHAR(20),
    password_hash VARCHAR(255),
    role VARCHAR(50) DEFAULT 'renter',
    profile_pic VARCHAR(255),
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
)";
if ($conn->query($sql_users) === TRUE) {
    echo "✅ Users table created successfully.<br>";
} else {
    echo "❌ Error creating users table: " . $conn->error . "<br>";
}

// 2. Items Table
$sql_items = "CREATE TABLE IF NOT EXISTS items (
    id VARCHAR(255) PRIMARY KEY,
    user_id VARCHAR(255) NOT NULL,
    title VARCHAR(255) NOT NULL,
    category VARCHAR(100),
    price DECIMAL(10, 2),
    description TEXT,
    address TEXT,
    available_from DATE,
    available_to DATE,
    images TEXT,
    status VARCHAR(50) DEFAULT 'Active',
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
)";
if ($conn->query($sql_items) === TRUE) {
    echo "✅ Items table created successfully.<br>";
} else {
    echo "❌ Error creating items table: " . $conn->error . "<br>";
}

// 3. Rentals Table
$sql_rentals = "CREATE TABLE IF NOT EXISTS rentals (
    id VARCHAR(255) PRIMARY KEY,
    user_id VARCHAR(255) NOT NULL,
    item_id VARCHAR(255) NOT NULL,
    start_date DATE,
    end_date DATE,
    total_price DECIMAL(10, 2),
    action VARCHAR(50),
    status VARCHAR(50) DEFAULT 'Pending',
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (item_id) REFERENCES items(id) ON DELETE CASCADE
)";
if ($conn->query($sql_rentals) === TRUE) {
    echo "✅ Rentals table created successfully.<br>";
} else {
    echo "❌ Error creating rentals table: " . $conn->error . "<br>";
}

// 4. Deliveries Table
$sql_deliveries = "CREATE TABLE IF NOT EXISTS deliveries (
    id VARCHAR(255) PRIMARY KEY,
    rental_id VARCHAR(255) NOT NULL,
    partner_id VARCHAR(255),
    pickup_address TEXT,
    dropoff_address TEXT,
    status VARCHAR(50) DEFAULT 'Pending',
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (rental_id) REFERENCES rentals(id) ON DELETE CASCADE,
    FOREIGN KEY (partner_id) REFERENCES users(id) ON DELETE SET NULL
)";
if ($conn->query($sql_deliveries) === TRUE) {
    echo "✅ Deliveries table created successfully.<br>";
} else {
    echo "❌ Error creating deliveries table: " . $conn->error . "<br>";
}

echo "<br><h3 style='color: green;'>🎉 Database Setup Complete!</h3>";
echo "<p>Database 'rendex' has been created with all required tables:</p>";
echo "<ul>";
echo "<li>✓ users</li>";
echo "<li>✓ items</li>";
echo "<li>✓ rentals</li>";
echo "<li>✓ deliveries</li>";
echo "</ul>";
echo "<br><a href='index.php' style='padding: 10px 20px; background: #f9f506; color: black; text-decoration: none; border-radius: 5px; font-weight: bold;'>Go to Homepage</a>";

$conn->close();
?>
