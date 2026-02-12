-- Update script for Rental Return Process
-- Adds necessary columns to track return status, condition, and logistics

-- Update rentals table
ALTER TABLE rentals
ADD COLUMN return_status ENUM('none', 'scheduled', 'in_transit', 'pending_inspection', 'completed', 'disputed') DEFAULT 'none' AFTER status,
ADD COLUMN return_method ENUM('dropoff', 'pickup') DEFAULT NULL AFTER return_status,
ADD COLUMN return_scheduled_at DATETIME DEFAULT NULL,
ADD COLUMN condition_notes TEXT DEFAULT NULL,
ADD COLUMN condition_images JSON DEFAULT NULL,
ADD COLUMN owner_confirm_at DATETIME DEFAULT NULL,
ADD COLUMN damage_fee DECIMAL(10, 2) DEFAULT 0.00;

-- Update deliveries table to support return trips
ALTER TABLE deliveries
ADD COLUMN type ENUM('delivery', 'return') DEFAULT 'delivery' AFTER id;
