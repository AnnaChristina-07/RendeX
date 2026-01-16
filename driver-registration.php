<?php
ob_start();
session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// Include database connection
require_once 'config/database.php';

// Get current user from database
$current_user = null;
$is_delivery = false;
$delivery_pending = false;

try {
    $pdo = getDBConnection();
    if ($pdo) {
        // Get user info
        $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
        $stmt->execute([$_SESSION['user_id']]);
        $current_user = $stmt->fetch();
        
        if ($current_user) {
            $is_delivery = ($current_user['role'] === 'delivery_partner');
            
            // Check if there's a pending application
            $stmt = $pdo->prepare("SELECT * FROM driver_applications WHERE user_id = ? AND status = 'pending'");
            $stmt->execute([$_SESSION['user_id']]);
            $pending_app = $stmt->fetch();
            $delivery_pending = ($pending_app !== false);
        }
    }
} catch (PDOException $e) {
    // Fallback to JSON if database not available
    $users_file = 'users.json';
    $users = file_exists($users_file) ? json_decode(file_get_contents($users_file), true) : [];
    foreach ($users as $u) {
        if ($u['id'] === $_SESSION['user_id']) {
            $current_user = $u;
            break;
        }
    }
    $is_delivery = (($current_user['role'] ?? '') === 'delivery_partner');
    $delivery_pending = (($current_user['role'] ?? '') === 'delivery_partner_pending');
}

// Redirect if already approved driver
if ($is_delivery) {
    header("Location: delivery_dashboard.php");
    exit();
}

$success_message = '';
$error_message = '';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_application'])) {
    $full_name = trim($_POST['full_name'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $date_of_birth = trim($_POST['date_of_birth'] ?? '');
    $address = trim($_POST['address'] ?? '');
    $city = trim($_POST['city'] ?? '');
    $pincode = trim($_POST['pincode'] ?? '');
    $vehicle_type = trim($_POST['vehicle_type'] ?? '');
    $vehicle_number = trim($_POST['vehicle_number'] ?? '');
    $license_number = trim($_POST['license_number'] ?? '');
    $license_expiry = trim($_POST['license_expiry'] ?? '');
    $service_areas = isset($_POST['service_areas']) ? $_POST['service_areas'] : [];
    $availability_hours = trim($_POST['availability_hours'] ?? '');
    $experience = trim($_POST['experience'] ?? '');
    $has_smartphone = isset($_POST['has_smartphone']) ? true : false;
    $agree_terms = isset($_POST['agree_terms']) ? true : false;
    
    // Validation
    if (empty($full_name) || empty($phone) || empty($email) || empty($date_of_birth) ||
        empty($address) || empty($city) || empty($pincode) || empty($vehicle_type) ||
        empty($vehicle_number) || empty($license_number) || empty($license_expiry) ||
        empty($service_areas) || empty($availability_hours)) {
        $error_message = 'Please fill in all required fields.';
    } elseif (!isset($_FILES['license_photo']) || $_FILES['license_photo']['error'] !== 0) {
        $error_message = 'Please upload your driving license photo.';
    } elseif (!$agree_terms) {
        $error_message = 'You must agree to the terms and conditions.';
    } elseif (!$has_smartphone) {
        $error_message = 'A smartphone with internet connection is required for delivery tracking.';
    } else {
        $saved_to_db = false;
        $license_photo_path = '';

        // Handle File Upload
        $upload_dir = 'uploads/licenses/';
        if (!is_dir($upload_dir)) {
            mkdir($upload_dir, 0777, true);
        }

        $file_ext = strtolower(pathinfo($_FILES['license_photo']['name'], PATHINFO_EXTENSION));
        $file_name = uniqid('license_') . '.' . $file_ext;
        $target_file = $upload_dir . $file_name;

        $allowed_types = ['jpg', 'jpeg', 'png', 'webp', 'pdf'];
        if (!in_array($file_ext, $allowed_types)) {
            $error_message = 'Only JPG, JPEG, PNG, WEBP and PDF files are allowed.';
        } elseif (move_uploaded_file($_FILES['license_photo']['tmp_name'], $target_file)) {
            $license_photo_path = $target_file;

            try {
                $pdo = getDBConnection();
                if ($pdo) {
                    // First, check if user exists in database
                    $stmt = $pdo->prepare("SELECT id FROM users WHERE id = ?");
                    $stmt->execute([$_SESSION['user_id']]);
                    $userExists = $stmt->fetch();
                    
                    // If user doesn't exist in DB, migrate from JSON
                    if (!$userExists) {
                        $users_file = 'users.json';
                        if (file_exists($users_file)) {
                            $json_users = json_decode(file_get_contents($users_file), true) ?: [];
                            foreach ($json_users as $ju) {
                                if ($ju['id'] === $_SESSION['user_id']) {
                                    // Insert user into database
                                    $stmt = $pdo->prepare("
                                        INSERT INTO users (id, name, email, phone, role, password_hash, created_at)
                                        VALUES (?, ?, ?, ?, 'user', ?, ?)
                                    ");
                                    $stmt->execute([
                                        $ju['id'],
                                        $ju['name'],
                                        $ju['email'],
                                        $ju['phone'] ?? '',
                                        $ju['password_hash'],
                                        $ju['created_at'] ?? date('Y-m-d H:i:s')
                                    ]);
                                    break;
                                }
                            }
                        }
                    }
                    
                    // Map vehicle type to ENUM values
                    $db_vehicle_type = 'bike';
                    if ($vehicle_type === 'bicycle') $db_vehicle_type = 'bike';
                    elseif ($vehicle_type === 'scooter') $db_vehicle_type = 'scooter';
                    elseif ($vehicle_type === 'motorcycle') $db_vehicle_type = 'bike';
                    elseif ($vehicle_type === 'car') $db_vehicle_type = 'car';
                    elseif ($vehicle_type === 'van') $db_vehicle_type = 'van';
                    
                    // Insert driver application into driver_applications table
                    $stmt = $pdo->prepare("
                        INSERT INTO driver_applications (
                            user_id, full_name, date_of_birth, phone, address, city, pincode,
                            vehicle_type, vehicle_number, driving_license, license_expiry, license_photo, status, applied_at
                        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'pending', NOW())
                    ");
                    
                    $stmt->execute([
                        $_SESSION['user_id'],
                        $full_name,
                        $date_of_birth,
                        $phone,
                        $address,
                        $city,
                        $pincode,
                        $db_vehicle_type,
                        $vehicle_number,
                        $license_number,
                        $license_expiry,
                        $license_photo_path
                    ]);
                    
                    $application_id = $pdo->lastInsertId();
                    $saved_to_db = true;
                    
                    // Try to create admin notification (but don't fail if table doesn't exist)
                    try {
                        $stmt = $pdo->prepare("
                            INSERT INTO admin_notifications (type, reference_id, title, message, is_read, created_at)
                            VALUES ('driver_application', ?, ?, ?, 0, NOW())
                        ");
                        $stmt->execute([
                            $application_id,
                            'New Driver Application',
                            "New delivery partner application from {$full_name}. Vehicle: " . ucfirst($db_vehicle_type) . " - {$vehicle_number}"
                        ]);
                    } catch (PDOException $notifError) {
                        // Notification table might not exist, ignore
                    }
                    
                    // Redirect to dashboard after successful submission
                    header("Location: dashboard.php?msg=application_submitted");
                    exit();
                }
            } catch (PDOException $e) {
                // Show the error for debugging (don't redirect so user can see error)
                $error_message = 'Database error: ' . $e->getMessage();
            }
            
            // Fallback: save to JSON if database failed
            if (!$saved_to_db) {
                $users_file = 'users.json';
                $users = file_exists($users_file) ? json_decode(file_get_contents($users_file), true) : [];
                
                foreach ($users as &$u) {
                    if ($u['id'] === $_SESSION['user_id']) {
                        $u['role'] = 'delivery_partner_pending';
                        $u['delivery_application'] = [
                            'full_name' => $full_name,
                            'phone' => $phone,
                            'email' => $email,
                            'date_of_birth' => $date_of_birth,
                            'address' => $address,
                            'city' => $city,
                            'pincode' => $pincode,
                            'vehicle_type' => $vehicle_type,
                            'vehicle_number' => $vehicle_number,
                            'license_number' => $license_number,
                            'license_expiry' => $license_expiry,
                            'license_photo' => $license_photo_path,
                            'service_areas' => $service_areas,
                            'availability_hours' => $availability_hours,
                            'experience' => $experience,
                            'has_smartphone' => $has_smartphone,
                            'applied_at' => date('Y-m-d H:i:s'),
                            'status' => 'pending'
                        ];
                        break;
                    }
                }
                file_put_contents($users_file, json_encode($users, JSON_PRETTY_PRINT));
                
                // Only redirect if no error message to show
                if (empty($error_message)) {
                    header("Location: dashboard.php?msg=application_submitted");
                    exit();
                }
            }
        } else {
            $error_message = 'Error uploading license photo.';
        }
    }
}
?>
<!DOCTYPE html>
<html class="light" lang="en">
<head>
    <meta charset="utf-8"/>
    <meta content="width=device-width, initial-scale=1.0" name="viewport"/>
    <title>Join as Delivery Partner - RendeX</title>
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com" rel="preconnect"/>
    <link crossorigin="" href="https://fonts.gstatic.com" rel="preconnect"/>
    <link href="https://fonts.googleapis.com/css2?family=Spline+Sans:wght@300;400;500;600;700&display=swap" rel="stylesheet"/>
    <link href="https://fonts.googleapis.com/css2?family=Noto+Sans:wght@400;500;700&display=swap" rel="stylesheet"/>
    <!-- Material Symbols -->
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&display=swap" rel="stylesheet"/>
    <!-- Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com?plugins=forms,container-queries"></script>
    <script>
        tailwind.config = {
            darkMode: "class",
            theme: {
                extend: {
                    colors: {
                        "primary": "#f9f506",
                        "background-light": "#f8f8f5",
                        "background-dark": "#23220f",
                        "surface-light": "#ffffff",
                        "surface-dark": "#2d2c18",
                        "text-main": "#1c1c0d",
                        "text-muted": "#5e5e4a",
                    },
                    fontFamily: {
                        "display": ["Spline Sans", "sans-serif"],
                        "body": ["Noto Sans", "sans-serif"],
                    },
                    borderRadius: {
                        "DEFAULT": "1rem",
                        "lg": "2rem",
                        "xl": "3rem",
                        "full": "9999px"
                    },
                },
            },
        }
    </script>
    <style>
        body {
            font-family: "Spline Sans", sans-serif;
        }
        .material-symbols-outlined {
            font-variation-settings:
            'FILL' 0,
            'wght' 400,
            'GRAD' 0,
            'opsz' 24
        }
        .form-input:focus {
            border-color: #f9f506;
            box-shadow: 0 0 0 3px rgba(249, 245, 6, 0.2);
        }
        .checkbox-custom:checked {
            background-color: #f9f506;
            border-color: #f9f506;
        }
        @keyframes float {
            0%, 100% { transform: translateY(0px); }
            50% { transform: translateY(-10px); }
        }
        .float-animation {
            animation: float 3s ease-in-out infinite;
        }
    </style>
</head>
<body class="bg-background-light dark:bg-background-dark text-text-main dark:text-white transition-colors duration-200">
<div class="relative flex min-h-screen w-full flex-col overflow-x-hidden">
    <!-- Navbar -->
    <header class="sticky top-0 z-50 flex items-center justify-between border-b border-[#e9e8ce] dark:border-[#3e3d2a] bg-background-light/95 dark:bg-background-dark/95 backdrop-blur-sm px-6 py-4 lg:px-10">
        <div class="flex items-center gap-8">
            <a href="dashboard.php" class="text-xl font-bold tracking-tight hover:text-primary transition-colors">RendeX</a>
        </div>
        <div class="flex items-center gap-4">
            <span class="text-sm font-medium text-text-muted dark:text-gray-300">
                Welcome, <span class="text-text-main dark:text-white font-bold"><?php echo htmlspecialchars($_SESSION['user_name']); ?></span>
            </span>
            <a href="dashboard.php" class="bg-primary hover:bg-yellow-300 text-black text-sm font-bold px-6 py-2.5 rounded-full transition-all shadow-sm hover:shadow-md flex items-center gap-2">
                <span class="material-symbols-outlined text-[18px]">arrow_back</span>
                Back to Dashboard
            </a>
        </div>
    </header>

    <main class="flex-1 w-full max-w-[1200px] mx-auto px-4 md:px-10 py-10">
        
        <?php if ($delivery_pending && !$success_message): ?>
        <!-- Pending Approval State -->
        <div class="max-w-2xl mx-auto">
            <div class="bg-gradient-to-br from-amber-50 to-yellow-100 dark:from-amber-900/30 dark:to-yellow-900/20 rounded-2xl p-8 md:p-12 text-center border border-amber-200 dark:border-amber-800 shadow-xl">
                <div class="w-24 h-24 mx-auto mb-6 bg-primary/20 rounded-full flex items-center justify-center float-animation">
                    <span class="material-symbols-outlined text-5xl text-amber-600">hourglass_top</span>
                </div>
                <h1 class="text-3xl md:text-4xl font-black mb-4">Application Under Review</h1>
                <p class="text-lg text-text-muted dark:text-gray-300 mb-6 max-w-md mx-auto">
                    Thank you for applying to become a RendeX Delivery Partner! Our team is reviewing your application.
                </p>
                <div class="bg-white dark:bg-surface-dark rounded-xl p-6 shadow-sm border border-amber-100 dark:border-amber-800/50 mb-8">
                    <div class="flex items-center justify-center gap-3 text-amber-600 dark:text-amber-400">
                        <span class="material-symbols-outlined">schedule</span>
                        <span class="font-medium">Expected response: 2-3 business days</span>
                    </div>
                </div>
                <div class="space-y-3">
                    <p class="text-sm text-text-muted">What happens next?</p>
                    <div class="flex flex-col md:flex-row gap-4 justify-center">
                        <div class="flex items-center gap-2 bg-white dark:bg-surface-dark rounded-lg px-4 py-2 shadow-sm">
                            <span class="material-symbols-outlined text-green-500">verified</span>
                            <span class="text-sm">Document Verification</span>
                        </div>
                        <div class="flex items-center gap-2 bg-white dark:bg-surface-dark rounded-lg px-4 py-2 shadow-sm">
                            <span class="material-symbols-outlined text-blue-500">call</span>
                            <span class="text-sm">Phone Interview</span>
                        </div>
                        <div class="flex items-center gap-2 bg-white dark:bg-surface-dark rounded-lg px-4 py-2 shadow-sm">
                            <span class="material-symbols-outlined text-purple-500">badge</span>
                            <span class="text-sm">Account Activation</span>
                        </div>
                    </div>
                </div>
                <a href="dashboard.php" class="inline-block mt-8 bg-black dark:bg-white text-white dark:text-black font-bold px-8 py-3 rounded-full hover:opacity-90 transition-opacity">
                    Return to Dashboard
                </a>
            </div>
        </div>
        
        <?php elseif ($success_message): ?>
        <!-- Success State -->
        <div class="max-w-2xl mx-auto">
            <div class="bg-gradient-to-br from-green-50 to-emerald-100 dark:from-green-900/30 dark:to-emerald-900/20 rounded-2xl p-8 md:p-12 text-center border border-green-200 dark:border-green-800 shadow-xl">
                <div class="w-24 h-24 mx-auto mb-6 bg-green-500/20 rounded-full flex items-center justify-center">
                    <span class="material-symbols-outlined text-5xl text-green-600">check_circle</span>
                </div>
                <h1 class="text-3xl md:text-4xl font-black mb-4 text-green-800 dark:text-green-300">Application Submitted!</h1>
                <p class="text-lg text-text-muted dark:text-gray-300 mb-8 max-w-md mx-auto">
                    <?php echo htmlspecialchars($success_message); ?>
                </p>
                <a href="dashboard.php" class="inline-block bg-green-600 hover:bg-green-700 text-white font-bold px-8 py-3 rounded-full transition-colors shadow-lg">
                    Return to Dashboard
                </a>
            </div>
        </div>
        
        <?php else: ?>
        <!-- Registration Form -->
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
            <!-- Left Column - Benefits -->
            <div class="lg:col-span-1">
                <div class="sticky top-28 space-y-6">
                    <div class="bg-gradient-to-br from-[#23220f] to-[#3e3d2a] text-white rounded-2xl p-6 shadow-xl">
                        <div class="inline-block bg-primary text-black text-xs font-bold px-3 py-1 rounded-full mb-4">JOIN OUR FLEET</div>
                        <h2 class="text-2xl font-black mb-4">Become a Delivery Partner</h2>
                        <p class="text-gray-300 text-sm mb-6">Join RendeX's growing network of delivery partners and start earning on your own schedule.</p>
                        
                        <div class="space-y-4">
                            <div class="flex items-start gap-3">
                                <div class="w-10 h-10 rounded-full bg-primary/20 flex items-center justify-center shrink-0">
                                    <span class="material-symbols-outlined text-primary">payments</span>
                                </div>
                                <div>
                                    <h4 class="font-bold text-sm">Competitive Pay</h4>
                                    <p class="text-xs text-gray-400">Earn per delivery with weekly payouts</p>
                                </div>
                            </div>
                            <div class="flex items-start gap-3">
                                <div class="w-10 h-10 rounded-full bg-primary/20 flex items-center justify-center shrink-0">
                                    <span class="material-symbols-outlined text-primary">schedule</span>
                                </div>
                                <div>
                                    <h4 class="font-bold text-sm">Flexible Hours</h4>
                                    <p class="text-xs text-gray-400">Work when you want, be your own boss</p>
                                </div>
                            </div>
                            <div class="flex items-start gap-3">
                                <div class="w-10 h-10 rounded-full bg-primary/20 flex items-center justify-center shrink-0">
                                    <span class="material-symbols-outlined text-primary">location_on</span>
                                </div>
                                <div>
                                    <h4 class="font-bold text-sm">Choose Your Area</h4>
                                    <p class="text-xs text-gray-400">Deliver in areas you know best</p>
                                </div>
                            </div>
                            <div class="flex items-start gap-3">
                                <div class="w-10 h-10 rounded-full bg-primary/20 flex items-center justify-center shrink-0">
                                    <span class="material-symbols-outlined text-primary">support_agent</span>
                                </div>
                                <div>
                                    <h4 class="font-bold text-sm">24/7 Support</h4>
                                    <p class="text-xs text-gray-400">Dedicated partner support team</p>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="bg-white dark:bg-surface-dark rounded-2xl p-6 border border-[#e9e8ce] dark:border-[#3e3d2a] shadow-sm">
                        <h4 class="font-bold mb-3 flex items-center gap-2">
                            <span class="material-symbols-outlined text-primary">checklist</span>
                            Requirements
                        </h4>
                        <ul class="space-y-2 text-sm text-text-muted dark:text-gray-400">
                            <li class="flex items-center gap-2">
                                <span class="material-symbols-outlined text-green-500 text-sm">check</span>
                                Valid driving license
                            </li>
                            <li class="flex items-center gap-2">
                                <span class="material-symbols-outlined text-green-500 text-sm">check</span>
                                Two-wheeler or four-wheeler vehicle
                            </li>
                            <li class="flex items-center gap-2">
                                <span class="material-symbols-outlined text-green-500 text-sm">check</span>
                                Smartphone with internet
                            </li>
                            <li class="flex items-center gap-2">
                                <span class="material-symbols-outlined text-green-500 text-sm">check</span>
                                Age 18+ years
                            </li>
                            <li class="flex items-center gap-2">
                                <span class="material-symbols-outlined text-green-500 text-sm">check</span>
                                Clean background check
                            </li>
                        </ul>
                    </div>
                </div>
            </div>
            
            <!-- Right Column - Form -->
            <div class="lg:col-span-2">
                <div class="bg-white dark:bg-surface-dark rounded-2xl shadow-xl border border-[#e9e8ce] dark:border-[#3e3d2a] overflow-hidden">
                    <div class="bg-gradient-to-r from-primary/10 to-primary/5 px-6 py-4 border-b border-[#e9e8ce] dark:border-[#3e3d2a]">
                        <h1 class="text-2xl font-black">Delivery Partner Application</h1>
                        <p class="text-sm text-text-muted dark:text-gray-400">Fill in your details to join our delivery fleet</p>
                    </div>
                    
                    <?php if ($error_message): ?>
                    <div class="mx-6 mt-6 p-4 bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 rounded-xl text-red-700 dark:text-red-300 flex items-center gap-3">
                        <span class="material-symbols-outlined">error</span>
                        <span><?php echo htmlspecialchars($error_message); ?></span>
                    </div>
                    <?php endif; ?>
                    
                    <form method="POST" enctype="multipart/form-data" class="p-6 space-y-8">
                        
                        <!-- Personal Information -->
                        <div class="space-y-4">
                            <h3 class="font-bold text-lg flex items-center gap-2 pb-2 border-b border-[#e9e8ce] dark:border-[#3e3d2a]">
                                <span class="material-symbols-outlined text-primary">person</span>
                                Personal Information
                            </h3>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-sm font-medium mb-2">Full Name *</label>
                                    <input type="text" name="full_name" value="<?php echo htmlspecialchars($current_user['name'] ?? ''); ?>" required
                                        class="form-input w-full px-4 py-3 rounded-xl border border-[#e9e8ce] dark:border-[#3e3d2a] bg-background-light dark:bg-background-dark focus:outline-none transition-all"/>
                                </div>
                                <div>
                                    <label class="block text-sm font-medium mb-2">Email Address *</label>
                                    <input type="email" name="email" value="<?php echo htmlspecialchars($current_user['email'] ?? ''); ?>" required
                                        class="form-input w-full px-4 py-3 rounded-xl border border-[#e9e8ce] dark:border-[#3e3d2a] bg-background-light dark:bg-background-dark focus:outline-none transition-all"/>
                                </div>
                                <div>
                                    <label class="block text-sm font-medium mb-2">Phone Number *</label>
                                    <input type="tel" name="phone" value="<?php echo htmlspecialchars($current_user['phone'] ?? ''); ?>" required placeholder="e.g., 9876543210"
                                        class="form-input w-full px-4 py-3 rounded-xl border border-[#e9e8ce] dark:border-[#3e3d2a] bg-background-light dark:bg-background-dark focus:outline-none transition-all"/>
                                </div>
                                <div>
                                    <label class="block text-sm font-medium mb-2">Date of Birth *</label>
                                    <input type="date" name="date_of_birth" required
                                        class="form-input w-full px-4 py-3 rounded-xl border border-[#e9e8ce] dark:border-[#3e3d2a] bg-background-light dark:bg-background-dark focus:outline-none transition-all"/>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Address Information -->
                        <div class="space-y-4">
                            <h3 class="font-bold text-lg flex items-center gap-2 pb-2 border-b border-[#e9e8ce] dark:border-[#3e3d2a]">
                                <span class="material-symbols-outlined text-primary">home</span>
                                Address Information
                            </h3>
                            <div>
                                <label class="block text-sm font-medium mb-2">Full Address *</label>
                                <textarea name="address" rows="2" required placeholder="House/Building, Street, Landmark"
                                    class="form-input w-full px-4 py-3 rounded-xl border border-[#e9e8ce] dark:border-[#3e3d2a] bg-background-light dark:bg-background-dark focus:outline-none transition-all resize-none"></textarea>
                            </div>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-sm font-medium mb-2">City *</label>
                                    <input type="text" name="city" required placeholder="e.g., Kochi"
                                        class="form-input w-full px-4 py-3 rounded-xl border border-[#e9e8ce] dark:border-[#3e3d2a] bg-background-light dark:bg-background-dark focus:outline-none transition-all"/>
                                </div>
                                <div>
                                    <label class="block text-sm font-medium mb-2">PIN Code *</label>
                                    <input type="text" name="pincode" required placeholder="e.g., 682001" pattern="[0-9]{6}"
                                        class="form-input w-full px-4 py-3 rounded-xl border border-[#e9e8ce] dark:border-[#3e3d2a] bg-background-light dark:bg-background-dark focus:outline-none transition-all"/>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Vehicle Information -->
                        <div class="space-y-4">
                            <h3 class="font-bold text-lg flex items-center gap-2 pb-2 border-b border-[#e9e8ce] dark:border-[#3e3d2a]">
                                <span class="material-symbols-outlined text-primary">two_wheeler</span>
                                Vehicle Information
                            </h3>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-sm font-medium mb-2">Vehicle Type *</label>
                                    <select name="vehicle_type" required
                                        class="form-input w-full px-4 py-3 rounded-xl border border-[#e9e8ce] dark:border-[#3e3d2a] bg-background-light dark:bg-background-dark focus:outline-none transition-all">
                                        <option value="">Select vehicle type</option>
                                        <option value="bicycle">Bicycle</option>
                                        <option value="scooter">Scooter/Moped</option>
                                        <option value="motorcycle">Motorcycle</option>
                                        <option value="car">Car</option>
                                        <option value="van">Van/Mini Truck</option>
                                    </select>
                                </div>
                                <div>
                                    <label class="block text-sm font-medium mb-2">Vehicle Number *</label>
                                    <input type="text" name="vehicle_number" required placeholder="e.g., KL 07 AB 1234"
                                        class="form-input w-full px-4 py-3 rounded-xl border border-[#e9e8ce] dark:border-[#3e3d2a] bg-background-light dark:bg-background-dark focus:outline-none transition-all uppercase"/>
                                </div>
                                <div>
                                    <label class="block text-sm font-medium mb-2">Driving License Number *</label>
                                    <input type="text" name="license_number" required placeholder="e.g., KL0720200001234"
                                        class="form-input w-full px-4 py-3 rounded-xl border border-[#e9e8ce] dark:border-[#3e3d2a] bg-background-light dark:bg-background-dark focus:outline-none transition-all uppercase"/>
                                </div>
                                <div>
                                    <label class="block text-sm font-medium mb-2">License Expiry Date *</label>
                                    <input type="date" name="license_expiry" required
                                        class="form-input w-full px-4 py-3 rounded-xl border border-[#e9e8ce] dark:border-[#3e3d2a] bg-background-light dark:bg-background-dark focus:outline-none transition-all"/>
                                </div>
                                <div class="md:col-span-2">
                                    <label class="block text-sm font-medium mb-2">Driving License Photo * <span class="text-xs text-text-muted">(Clear photo of your license)</span></label>
                                    <input type="file" name="license_photo" required accept="image/*,.pdf"
                                        class="form-input w-full px-4 py-3 rounded-xl border border-[#e9e8ce] dark:border-[#3e3d2a] bg-background-light dark:bg-background-dark focus:outline-none transition-all 
                                        file:mr-4 file:py-2 file:px-4 file:rounded-full file:border-0 file:text-sm file:font-semibold file:bg-primary file:text-black hover:file:bg-yellow-300"/>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Availability & Service Areas -->
                        <div class="space-y-4">
                            <h3 class="font-bold text-lg flex items-center gap-2 pb-2 border-b border-[#e9e8ce] dark:border-[#3e3d2a]">
                                <span class="material-symbols-outlined text-primary">schedule</span>
                                Availability & Service Areas
                            </h3>
                            
                            <div>
                                <label class="block text-sm font-medium mb-2">Preferred Service Areas * <span class="text-text-muted">(Select all that apply)</span></label>
                                <div class="grid grid-cols-2 md:grid-cols-3 gap-3">
                                    <?php
                                    $areas = ['Kochi', 'Ernakulam', 'Aluva', 'Kakkanad', 'Thrissur', 'Kaloor', 'Edappally', 'Vytilla', 'Palarivattom'];
                                    foreach ($areas as $area): ?>
                                    <label class="flex items-center gap-2 p-3 rounded-xl border border-[#e9e8ce] dark:border-[#3e3d2a] hover:border-primary cursor-pointer transition-colors bg-background-light dark:bg-background-dark">
                                        <input type="checkbox" name="service_areas[]" value="<?php echo $area; ?>" class="checkbox-custom w-4 h-4 rounded border-2 border-gray-300 focus:ring-primary">
                                        <span class="text-sm"><?php echo $area; ?></span>
                                    </label>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                            
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-sm font-medium mb-2">Availability Hours *</label>
                                    <select name="availability_hours" required
                                        class="form-input w-full px-4 py-3 rounded-xl border border-[#e9e8ce] dark:border-[#3e3d2a] bg-background-light dark:bg-background-dark focus:outline-none transition-all">
                                        <option value="">Select your availability</option>
                                        <option value="morning">Morning (6 AM - 12 PM)</option>
                                        <option value="afternoon">Afternoon (12 PM - 6 PM)</option>
                                        <option value="evening">Evening (6 PM - 10 PM)</option>
                                        <option value="full_day">Full Day (6 AM - 10 PM)</option>
                                        <option value="flexible">Flexible/On-demand</option>
                                    </select>
                                </div>
                                <div>
                                    <label class="block text-sm font-medium mb-2">Delivery Experience</label>
                                    <select name="experience"
                                        class="form-input w-full px-4 py-3 rounded-xl border border-[#e9e8ce] dark:border-[#3e3d2a] bg-background-light dark:bg-background-dark focus:outline-none transition-all">
                                        <option value="none">No prior experience</option>
                                        <option value="less_than_1">Less than 1 year</option>
                                        <option value="1_to_3">1-3 years</option>
                                        <option value="more_than_3">More than 3 years</option>
                                    </select>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Terms & Conditions -->
                        <div class="space-y-4 pt-4 border-t border-[#e9e8ce] dark:border-[#3e3d2a]">
                            <label class="flex items-start gap-3 p-4 rounded-xl bg-background-light dark:bg-background-dark cursor-pointer hover:bg-primary/5 transition-colors">
                                <input type="checkbox" name="has_smartphone" class="checkbox-custom w-5 h-5 rounded border-2 border-gray-300 focus:ring-primary mt-0.5">
                                <div>
                                    <span class="font-medium">I have a smartphone with active internet connection *</span>
                                    <p class="text-xs text-text-muted dark:text-gray-400 mt-1">Required for real-time tracking and order management</p>
                                </div>
                            </label>
                            
                            <label class="flex items-start gap-3 p-4 rounded-xl bg-background-light dark:bg-background-dark cursor-pointer hover:bg-primary/5 transition-colors">
                                <input type="checkbox" name="agree_terms" class="checkbox-custom w-5 h-5 rounded border-2 border-gray-300 focus:ring-primary mt-0.5">
                                <div>
                                    <span class="font-medium">I agree to the Terms of Service and Privacy Policy *</span>
                                    <p class="text-xs text-text-muted dark:text-gray-400 mt-1">By checking this, you agree to our background verification process and delivery partner guidelines</p>
                                </div>
                            </label>
                        </div>
                        
                        <!-- Submit Button -->
                        <div class="flex flex-col sm:flex-row gap-4 pt-4">
                            <button type="submit" name="submit_application" 
                                class="flex-1 bg-primary hover:bg-yellow-300 text-black font-bold py-4 px-8 rounded-xl transition-all shadow-lg hover:shadow-xl hover:-translate-y-0.5 flex items-center justify-center gap-2">
                                <span class="material-symbols-outlined">send</span>
                                Submit Application
                            </button>
                            <a href="dashboard.php" class="flex-1 bg-gray-100 dark:bg-gray-800 hover:bg-gray-200 dark:hover:bg-gray-700 text-text-main dark:text-white font-bold py-4 px-8 rounded-xl transition-colors text-center">
                                Cancel
                            </a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
        <?php endif; ?>
        
    </main>
    
    <!-- Footer -->
    <footer class="bg-surface-light dark:bg-surface-dark border-t border-[#e9e8ce] dark:border-[#3e3d2a] py-6 px-4 text-center">
        <p class="text-sm text-text-muted dark:text-gray-500">© 2024 RendeX Inc. All rights reserved.</p>
    </footer>
</div>
</body>
</html>
