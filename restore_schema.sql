-- RendeX Database Schema
-- Run this script in phpMyAdmin or MySQL CLI to create the database

-- Create database
CREATE DATABASE IF NOT EXISTS rendex_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

USE rendex_db;

-- ======================
-- USERS TABLE
-- Stores all registered users (renters, owners, delivery partners, admins)
-- Note: When a user applies as driver, their data stays HERE
-- Their role only changes from 'user' to 'delivery_partner' AFTER admin approval
-- ======================
CREATE TABLE IF NOT EXISTS users (
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
) ENGINE=InnoDB;

-- ======================
-- DRIVER APPLICATIONS TABLE
-- Stores ALL driver applications SEPARATELY from users table
-- This is where pending applications go BEFORE admin approval
-- Only after admin approves, the user's role in users table is updated
-- ======================
CREATE TABLE IF NOT EXISTS driver_applications (
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
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_status (status),
    INDEX idx_user_id (user_id),
    INDEX idx_applied_at (applied_at)
) ENGINE=InnoDB;

-- ======================
-- ADMIN NOTIFICATIONS TABLE
-- Stores notifications for admin about pending approvals
-- ======================
CREATE TABLE IF NOT EXISTS admin_notifications (
    id INT AUTO_INCREMENT PRIMARY KEY,
    type ENUM('driver_application', 'owner_application', 'report', 'system') DEFAULT 'system',
    reference_id INT DEFAULT NULL,
    title VARCHAR(255) NOT NULL,
    message TEXT,
    is_read TINYINT(1) DEFAULT 0,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_type (type),
    INDEX idx_read (is_read)
) ENGINE=InnoDB;

-- ======================
-- ITEMS TABLE
-- ======================
CREATE TABLE IF NOT EXISTS items (
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
    FOREIGN KEY (owner_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_category (category),
    INDEX idx_availability (availability_status),
    INDEX idx_city (city)
) ENGINE=InnoDB;

-- ======================
-- RENTALS TABLE
-- ======================
CREATE TABLE IF NOT EXISTS rentals (
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
    FOREIGN KEY (item_id) REFERENCES items(id) ON DELETE CASCADE,
    FOREIGN KEY (renter_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (owner_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_status (status),
    INDEX idx_renter (renter_id),
    INDEX idx_owner (owner_id)
) ENGINE=InnoDB;

-- ======================
-- DELIVERIES TABLE
-- ======================
CREATE TABLE IF NOT EXISTS deliveries (
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
    FOREIGN KEY (rental_id) REFERENCES rentals(id) ON DELETE CASCADE,
    FOREIGN KEY (driver_id) REFERENCES users(id) ON DELETE SET NULL,
    INDEX idx_status (status),
    INDEX idx_driver (driver_id)
) ENGINE=InnoDB;

-- ======================
-- NOTIFICATIONS TABLE (User notifications)
-- ======================
CREATE TABLE IF NOT EXISTS notifications (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id VARCHAR(20) NOT NULL,
    title VARCHAR(255) NOT NULL,
    message TEXT,
    type ENUM('info', 'success', 'warning', 'error', 'rental', 'delivery', 'payment', 'approval') DEFAULT 'info',
    is_read TINYINT(1) DEFAULT 0,
    link VARCHAR(255) DEFAULT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_user_read (user_id, is_read)
) ENGINE=InnoDB;

-- ======================
-- REVIEWS TABLE
-- ======================
CREATE TABLE IF NOT EXISTS reviews (
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
    FOREIGN KEY (rental_id) REFERENCES rentals(id) ON DELETE CASCADE,
    FOREIGN KEY (reviewer_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (reviewee_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (item_id) REFERENCES items(id) ON DELETE SET NULL,
    INDEX idx_reviewee (reviewee_id),
    INDEX idx_item (item_id)
) ENGINE=InnoDB;

-- ======================
-- PASSWORD RESET TOKENS TABLE
-- ======================
CREATE TABLE IF NOT EXISTS password_resets (
    id INT AUTO_INCREMENT PRIMARY KEY,
    email VARCHAR(255) NOT NULL,
    token VARCHAR(255) NOT NULL,
    expires_at DATETIME NOT NULL,
    used TINYINT(1) DEFAULT 0,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_email_token (email, token)
) ENGINE=InnoDB;

-- ======================
-- INSERT ADMIN USER
-- ======================
INSERT INTO users (id, name, email, phone, role, password_hash, created_at) 
VALUES ('admin001', 'Administrator', 'admin@rendex.com', '0000000000', 'admin', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', NOW())
ON DUPLICATE KEY UPDATE name = name;
