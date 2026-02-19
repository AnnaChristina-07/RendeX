<?php
ob_start();
session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}



$items_file = 'items.json';
$uploads_dir = 'uploads/';

// Initialize variables
$title = '';
$category = '';
$price = '';
$security_deposit = '';
$handover_methods = ['pickup']; // Default
$description = '';
$address = '';
$addr_house = '';
$addr_street = '';
$addr_city = '';
$addr_state = '';
$addr_pin = '';
$images = [];
$edit_id = null;
$is_edit = false;
$errors = []; // Initialize errors array

// Load existing items
$items = file_exists($items_file) ? json_decode(file_get_contents($items_file), true) : [];
if (!is_array($items)) $items = [];

// Prefill from Request
if (isset($_GET['request_id'])) {
    require_once 'config/database.php';
    try {
        $pdo = getDBConnection();
        $stmt = $pdo->prepare("SELECT item_name, category, description, min_price, max_price FROM item_requests WHERE id = ?");
        $stmt->execute([$_GET['request_id']]);
        $req = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($req) {
            $title = $req['item_name'];
            $category = $req['category'];
            $description = "Lending in response to request: " . $req['item_name'] . "\n\n" . $req['description'];
            // Intelligent price suggestion (average of range or min or max)
            if ($req['min_price'] && $req['max_price']) {
                $price = ($req['min_price'] + $req['max_price']) / 2;
            } elseif ($req['max_price']) {
                $price = $req['max_price'];
            } elseif ($req['min_price']) {
                $price = $req['min_price'];
            }
        }
    } catch (Exception $e) {
        // Silently fail if DB error, just don't prefill
    }
}

// Handle Edit Request (GET)
if (isset($_GET['edit_id'])) {
    $edit_id = $_GET['edit_id'];
    foreach ($items as $item) {
        if (isset($item['id']) && (string)$item['id'] === (string)$edit_id && isset($item['user_id']) && (string)$item['user_id'] === (string)$_SESSION['user_id']) {
            $title = $item['title'];
            $category = $item['category'];
            $price = $item['price'];
            $security_deposit = $item['security_deposit'] ?? '';
            $handover_methods = $item['handover_methods'] ?? ['pickup'];
            $description = $item['description'];
            $address = $item['address'];
            $address = $item['address'];
            // Try to parse address components
            if (preg_match('/^(.*), (.*), (.*), (.*) - (.*)$/', $address, $matches)) {
                $addr_house = $matches[1];
                $addr_street = $matches[2];
                $addr_city = $matches[3];
                $addr_state = $matches[4];
                $addr_pin = $matches[5];
            } else {
                $addr_city = $address; // Fallback for legacy data
            }
            $images = $item['images']; // Keep existing images
            $is_edit = true;
            break;
        }
    }

    // Fallback to Database if not found in JSON
    if (!$is_edit) {
        try {
            require_once 'config/database.php';
            $pdo = getDBConnection();
            if ($pdo) {
                $stmt = $pdo->prepare("SELECT * FROM items WHERE id = ? AND owner_id = ?");
                $stmt->execute([$edit_id, $_SESSION['user_id']]);
                $db_item = $stmt->fetch();
                
                if ($db_item) {
                    $title = $db_item['title'];
                    $category = $db_item['category'];
                    $price = $db_item['price_per_day'];
                    $security_deposit = $db_item['security_deposit'];
                    $handover_methods = json_decode($db_item['handover_methods'], true) ?: ['pickup'];
                    $description = $db_item['description'];
                    $address = $db_item['location'];
                    $address = $db_item['location'];
                    // Try to parse address components
                    if (preg_match('/^(.*), (.*), (.*), (.*) - (.*)$/', $address, $matches)) {
                        $addr_house = $matches[1];
                        $addr_street = $matches[2];
                        $addr_city = $matches[3];
                        $addr_state = $matches[4];
                        $addr_pin = $matches[5];
                    } else {
                        $addr_city = $address; // Fallback for legacy data
                    }
                    $images = json_decode($db_item['images'], true) ?: [];
                    $is_edit = true;
                }
            }
        } catch (Exception $e) {
            // Error handling if DB fails
        }
    }
}

// Handle Form Submission (POST)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $errors = [];

    // 1. Sanitize and Validate Basic Fields
    $title = trim($_POST['title']);
    $category = trim($_POST['category']);
    $price = trim($_POST['price']);
    $security_deposit = trim($_POST['security_deposit'] ?? 0);
    $handover_methods = $_POST['handover_methods'] ?? [];
    $description = trim($_POST['description']);
    $addr_house = trim($_POST['addr_house'] ?? '');
    $addr_street = trim($_POST['addr_street'] ?? '');
    $addr_city = trim($_POST['addr_city'] ?? '');
    $addr_state = trim($_POST['addr_state'] ?? '');
    $addr_pin = trim($_POST['addr_pin'] ?? '');

    if (empty($title)) $errors[] = "Item Name is required.";
    if (empty($category)) $errors[] = "Please select a Category.";
    if (empty($description)) $errors[] = "Description is required.";
    
    if (empty($addr_house) || empty($addr_street) || empty($addr_city) || empty($addr_state) || empty($addr_pin)) {
        $errors[] = "Please provide complete pickup address details (House No, Street, City, State, Pincode).";
    }
    
    $address = "$addr_house, $addr_street, $addr_city, $addr_state - $addr_pin";
    
    // Validate Price
    if (!is_numeric($price) || $price <= 0) {
        $errors[] = "Price must be a valid number greater than 0.";
    }
    
    // Validate Security Deposit
    if ($security_deposit !== '' && (!is_numeric($security_deposit) || $security_deposit < 0)) {
        $errors[] = "Security Deposit must be a valid non-negative number.";
    }

    // Validate Handover Methods
    if (empty($handover_methods) || !is_array($handover_methods)) {
        $errors[] = "Please select at least one Handover Method.";
    }

    // 2. Handle File Uploads with Validation
    $uploaded_images = [];
    if (isset($_FILES['item_images']) && !empty($_FILES['item_images']['name'][0])) {
        // Create directory if not exists
        if (!is_dir($uploads_dir)) mkdir($uploads_dir, 0777, true);
        
        $file_count = count($_FILES['item_images']['name']);
        
        // Allowed types
        $allowed_types = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
        $max_size = 5 * 1024 * 1024; // 5 MB

        for ($i = 0; $i < $file_count; $i++) {
            if ($_FILES['item_images']['error'][$i] === UPLOAD_ERR_OK) {
                $tmp_name = $_FILES['item_images']['tmp_name'][$i];
                $name = basename($_FILES['item_images']['name'][$i]);
                $size = $_FILES['item_images']['size'][$i];
                $type = mime_content_type($tmp_name);

                // Validate Type
                if (!in_array($type, $allowed_types)) {
                    $errors[] = "File '$name' is not a valid image. Allowed types: JPG, PNG, GIF, WEBP.";
                    continue;
                }

                // Validate Size
                if ($size > $max_size) {
                    $errors[] = "File '$name' is too large. Maximum size is 5MB.";
                    continue;
                }

                $new_name = uniqid() . '_' . $name;
                if (move_uploaded_file($tmp_name, $uploads_dir . $new_name)) {
                    $uploaded_images[] = $new_name;
                } else {
                    $errors[] = "Failed to upload file '$name'.";
                }
            } elseif ($_FILES['item_images']['error'][$i] !== UPLOAD_ERR_NO_FILE) {
                $errors[] = "Error uploading file '$name'. Code: " . $_FILES['item_images']['error'][$i];
            }
        }
    }
    
    // If Editing: merge new images or keep old if no new ones (flexible logic)
    if (!empty($uploaded_images)) {
        // Validated new images
        $final_images = $uploaded_images;
        // Optionally, merge with old ones if needed, but usually upload replaces or appends. 
        // Here we'll treat new uploads as replacing the set or appending. 
        // For simplicity/standard behavior: New uploads replace active set? Or append?
        // User requirements usually imply adding, but logic above replaced. 
        // Let's stick to: If new files uploaded, use them. If not, use old.
        // If we wanted to Append: $final_images = array_merge($images, $uploaded_images);
        // Current logic: New overrides old.
    } else {
        $final_images = $images; // existing images from GET load
    }

    // Require at least one image for new listings
    if (!$is_edit && empty($final_images)) {
        $errors[] = "Please upload at least one photo of your item.";
    }

    // 3. Process if No Errors
    if (empty($errors)) {
        $item_data = [
            'id' => $is_edit ? $edit_id : uniqid('item_'),
            'user_id' => $_SESSION['user_id'],
            'owner_name' => $_SESSION['user_name'] ?? 'Verified Owner',
            'title' => $title,
            'category' => $category,
            'price' => $price,
            'security_deposit' => $security_deposit,
            'handover_methods' => $handover_methods,
            'description' => $description,
            'address' => $address,
            'images' => $final_images,
            'created_at' => $is_edit ? ($item['created_at'] ?? date('Y-m-d H:i:s')) : date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s'),
            'status' => 'Pending Approval'
        ];

        if ($is_edit) {
            // Update existing
            foreach ($items as &$item) {
                if ($item['id'] === $edit_id) {
                    $item = array_merge($item, $item_data);
                    break;
                }
            }
        } else {
            // Add new
            $items[] = $item_data;
        }

        // Save to file
        file_put_contents($items_file, json_encode($items, JSON_PRETTY_PRINT));
        
        // Also save to database
        try {
            require_once 'config/database.php';
            $pdo = getDBConnection();
            if ($pdo) {
                $images_json = json_encode($final_images);
                $handover_json = json_encode($handover_methods);
                
                if ($is_edit) {
                    $stmt = $pdo->prepare("UPDATE items SET 
                        title = ?, description = ?, category = ?, 
                        price_per_day = ?, security_deposit = ?, 
                        handover_methods = ?, location = ?, images = ?,
                        admin_status = 'pending', updated_at = NOW() 
                        WHERE id = ? AND owner_id = ?");
                    $stmt->execute([
                        $title, $description, $category, 
                        $price, $security_deposit, 
                        $handover_json, $address, $images_json,
                        $edit_id, $_SESSION['user_id']
                    ]);
                } else {
                    $stmt = $pdo->prepare("INSERT INTO items (
                        owner_id, title, description, category, 
                        price_per_day, security_deposit, handover_methods, 
                        location, images, admin_status, created_at
                    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 'pending', NOW())");
                    $stmt->execute([
                        $_SESSION['user_id'], $title, $description, $category, 
                        $price, $security_deposit, $handover_json, 
                        $address, $images_json
                    ]);
    
                    // Add Admin Notification
                    $item_id = $pdo->lastInsertId();
                    $notif_stmt = $pdo->prepare("INSERT INTO admin_notifications (type, reference_id, title, message) VALUES ('item_listing', ?, ?, ?)");
                    $notif_title = "New Item Listing: " . $title;
                    $notif_msg = "A new item '" . $title . "' has been listed by " . ($_SESSION['user_name'] ?? 'User') . " and is awaiting approval.";
                    $notif_stmt->execute([$item_id, $notif_title, $notif_msg]);
                }
            }
        } catch (Exception $e) {
            // Log error or ignore if DB not fully setup - JSON is still saved
        }
    
        header("Location: dashboard.php?msg=item_listed");
        exit();
    }
}
?>
<!DOCTYPE html>
<html class="light" lang="en">
<head>
    <meta charset="utf-8"/>
    <meta content="width=device-width, initial-scale=1.0" name="viewport"/>
    <title>RendeX - List an Item</title>
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
          },
        },
      }
    </script>
    <style>
        body { font-family: "Spline Sans", sans-serif; }
        .material-symbols-outlined { font-variation-settings: 'FILL' 0, 'wght' 400, 'GRAD' 0, 'opsz' 24 }
        .handover-card input:checked + div {
            border-color: #f9f506;
            background-color: rgba(249, 245, 6, 0.05);
        }
        .handover-card input:checked + div .check-icon {
            display: block;
        }
    </style>
</head>
<body class="bg-background-light dark:bg-background-dark text-text-main dark:text-white transition-colors duration-200">
    
    <?php if (isset($_GET['alert']) && $_GET['alert'] === 'accepted'): ?>
    <div class="fixed top-6 left-1/2 transform -translate-x-1/2 z-[100] w-full max-w-lg px-6">
        <div class="bg-green-100 border-l-4 border-green-500 text-green-700 p-4 rounded-r shadow-lg flex items-start gap-3 relative animate-bounce-in">
            <span class="material-symbols-outlined text-green-600">check_circle</span>
            <div>
                <p class="font-bold">Request Accepted Successfully!</p>
                <p class="text-sm mt-1">Great! Now, please list the item below so the renter can book it. If you already have it listed, you can skip this step.</p>
            </div>
            <button onclick="this.parentElement.remove()" class="absolute top-2 right-2 hover:bg-green-200 rounded-full p-1">
                <span class="material-symbols-outlined text-sm">close</span>
            </button>
        </div>
    </div>
    <script>
        setTimeout(() => {
            document.querySelector('.animate-bounce-in').parentElement.remove();
        }, 10000); // Remove after 10 seconds
    </script>
    <?php endif; ?>
    <!-- Navbar -->
    <header class="sticky top-0 z-50 flex items-center justify-between border-b border-[#e9e8ce] dark:border-[#3e3d2a] bg-background-light/95 dark:bg-background-dark/95 backdrop-blur-sm px-6 py-4 lg:px-10">
        <div class="flex items-center gap-8 w-full max-w-[1400px] mx-auto">
            <a href="dashboard.php" class="flex items-center gap-2 text-text-main dark:text-white">
                <div class="size-8 text-primary">
                    <svg class="w-full h-full" fill="none" viewbox="0 0 48 48" xmlns="http://www.w3.org/2000/svg">
                        <path d="M36.7273 44C33.9891 44 31.6043 39.8386 30.3636 33.69C29.123 39.8386 26.7382 44 24 44C21.2618 44 18.877 39.8386 17.6364 33.69C16.3957 39.8386 14.0109 44 11.2727 44C7.25611 44 4 35.0457 4 24C4 12.9543 7.25611 4 11.2727 4C14.0109 4 16.3957 8.16144 17.6364 14.31C18.877 8.16144 21.2618 4 24 4C26.7382 4 29.123 8.16144 30.3636 14.31C31.6043 8.16144 33.9891 4 36.7273 4C40.7439 4 44 12.9543 44 24C44 35.0457 40.7439 44 36.7273 44Z" fill="currentColor"></path>
                    </svg>
                </div>
                <h2 class="text-xl font-bold tracking-tight">RendeX</h2>
            </a>
            <div class="hidden lg:flex items-center gap-6 ml-auto">
                <span class="text-sm font-medium text-text-muted dark:text-gray-300">
                    Listing as <span class="text-text-main dark:text-white font-bold"><?php echo htmlspecialchars($_SESSION['user_name']); ?></span>
                </span>
                <a href="dashboard.php" class="bg-white border border-[#e9e8ce] hover:bg-gray-50 text-black text-sm font-bold px-6 py-2.5 rounded-full transition-all flex items-center gap-2">
                    <span class="material-symbols-outlined text-[18px]">close</span>
                    Cancel
                </a>
            </div>
        </div>
    </header>

    <main class="w-full max-w-2xl mx-auto px-6 py-16">
        <div class="mb-12">
            <h1 class="text-4xl font-black mb-3 tracking-tight text-gray-900 dark:text-white"><?php echo $is_edit ? 'Edit your Item' : 'List an item'; ?></h1>
            <p class="text-lg text-gray-500 dark:text-gray-400">Share your gear with the community. It takes seconds.</p>
        </div>

        <?php if (!empty($errors)): ?>
        <div class="bg-red-50 border-l-4 border-red-500 p-4 mb-8 rounded-lg">
            <div class="flex">
                <div class="flex-shrink-0">
                    <span class="material-symbols-outlined text-red-500">error</span>
                </div>
                <div class="ml-3">
                    <h3 class="text-sm font-bold text-red-800">Please fix the following errors:</h3>
                    <ul class="mt-2 list-disc list-inside text-sm text-red-700">
                        <?php foreach ($errors as $error): ?>
                            <li><?php echo htmlspecialchars($error); ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            </div>
        </div>
        <?php endif; ?>

        <form id="lendItemForm" class="space-y-10" method="POST" enctype="multipart/form-data" onsubmit="const btn = this.querySelector('button[type=submit]'); if(btn.disabled) return false; btn.disabled = true; btn.classList.add('opacity-50', 'cursor-not-allowed');">
            
            <!-- Section 1: Item Details -->
            <div class="bg-white dark:bg-[#1e1e1e] p-8 md:p-10 rounded-[2.5rem] shadow-xl shadow-gray-200/50 dark:shadow-none space-y-6">
                <div class="flex items-center gap-3 pb-2">
                    <span class="w-10 h-10 rounded-full bg-yellow-100 flex items-center justify-center text-yellow-700 font-bold">1</span>
                    <h3 class="text-xl font-bold text-gray-900 dark:text-white">Item Details</h3>
                </div>
                
                <div>
                    <label class="block text-sm font-bold mb-2 ml-1 text-gray-700 dark:text-gray-300">Item Name <span class="text-red-500">*</span></label>
                    <input type="text" name="title" value="<?php echo htmlspecialchars($title); ?>" placeholder="e.g. GoPro Hero 10 Black" class="w-full bg-gray-50 dark:bg-gray-800 border-none focus:bg-white dark:focus:bg-gray-700 focus:ring-2 focus:ring-yellow-400 rounded-2xl px-5 py-4 font-medium transition-all" required>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <label class="block text-sm font-bold mb-2 ml-1 text-gray-700 dark:text-gray-300">Category <span class="text-red-500">*</span></label>
                        <div class="relative">
                            <select name="category" class="w-full bg-gray-50 dark:bg-gray-800 border-none focus:bg-white dark:focus:bg-gray-700 focus:ring-2 focus:ring-yellow-400 rounded-2xl px-5 py-4 font-medium appearance-none transition-all cursor-pointer" required>
                                <option value="">Select Category</option>
                                <option value="student-essentials" <?php if($category == 'student-essentials') echo 'selected'; ?>>Student Essentials</option>
                                <option value="clothing" <?php if($category == 'clothing') echo 'selected'; ?>>Clothing</option>
                                <option value="electronics" <?php if($category == 'electronics') echo 'selected'; ?>>Electronic Devices</option>
                                <option value="outdoor-gear" <?php if($category == 'outdoor-gear') echo 'selected'; ?>>Travel/Outdoor Gear</option>
                                <option value="home-essentials" <?php if($category == 'home-essentials') echo 'selected'; ?>>Home-Daily Essentials</option>
                                <option value="furniture" <?php if($category == 'furniture') echo 'selected'; ?>>Furniture</option>
                                <option value="vintage" <?php if($category == 'vintage') echo 'selected'; ?>>Vintage Collections</option>
                                <option value="fitness" <?php if($category == 'fitness') echo 'selected'; ?>>Fitness Equipment</option>
                                <option value="agriculture" <?php if($category == 'agriculture') echo 'selected'; ?>>Agricultural Tools</option>
                                <option value="medical" <?php if($category == 'medical') echo 'selected'; ?>>Medical Items</option>
                            </select>
                            <span class="absolute right-5 top-1/2 -translate-y-1/2 pointer-events-none text-gray-400 material-symbols-outlined">expand_more</span>
                        </div>
                    </div>
                </div>


                <div>
                    <label class="block text-sm font-bold mb-2 ml-1 text-gray-700 dark:text-gray-300">Description <span class="text-red-500">*</span></label>
                    <textarea name="description" rows="4" placeholder="Describe the condition, features, and what's included..." class="w-full bg-gray-50 dark:bg-gray-800 border-none focus:bg-white dark:focus:bg-gray-700 focus:ring-2 focus:ring-yellow-400 rounded-2xl px-5 py-4 font-medium transition-all resize-none" required><?php echo htmlspecialchars($description); ?></textarea>
                </div>
            </div>

            <!-- Section 2: Photos -->
            <div class="bg-white dark:bg-[#1e1e1e] p-8 md:p-10 rounded-[2.5rem] shadow-xl shadow-gray-200/50 dark:shadow-none space-y-6">
                <div class="flex items-center gap-3 pb-2">
                    <span class="w-10 h-10 rounded-full bg-yellow-100 flex items-center justify-center text-yellow-700 font-bold">2</span>
                    <h3 class="text-xl font-bold text-gray-900 dark:text-white">Photos <span class="text-red-500">*</span></h3>
                </div>
                
                <div id="dropzone" class="border-3 border-dashed border-gray-200 dark:border-gray-700 rounded-3xl p-10 text-center hover:bg-yellow-50 dark:hover:bg-yellow-900/10 hover:border-yellow-400 transition-all cursor-pointer group" onclick="document.getElementById('fileInput').click()">
                    <div id="initialContent" class="<?php echo !empty($images) ? 'hidden' : ''; ?>">
                        <div class="w-16 h-16 bg-yellow-100 rounded-full flex items-center justify-center mx-auto mb-4 group-hover:scale-110 transition-transform shadow-sm">
                            <span class="material-symbols-outlined text-3xl text-yellow-700">add_a_photo</span>
                        </div>
                        <p class="font-bold text-lg text-gray-900 dark:text-white">Click to upload photos (Add multiple)</p>
                        <p class="text-xs text-gray-500 mt-1">Supports JPG, PNG, GIF, WEBP (Max 5MB)</p>
                    </div>
                    
                    <div id="previewContainer" class="<?php echo empty($images) ? 'hidden' : ''; ?> grid grid-cols-2 sm:grid-cols-3 gap-4 mt-4">
                        <?php if(!empty($images)): ?>
                            <?php foreach($images as $img): ?>
                                <div class="relative aspect-square rounded-2xl overflow-hidden shadow-sm">
                                    <img src="uploads/<?php echo htmlspecialchars($img); ?>" class="w-full h-full object-cover">
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>

                    <input type="file" id="fileInput" name="item_images[]" class="hidden" multiple accept="image/*" onchange="handleFileSelect(this)">
                </div>
                <?php if(!$is_edit): ?>
                    <p class="text-xs text-gray-400 ml-2">Please upload at least one photo.</p>
                <?php endif; ?>
            </div>

            <!-- Section 3: Pricing & Terms -->
            <div class="bg-white dark:bg-[#1e1e1e] p-8 md:p-10 rounded-[2.5rem] shadow-xl shadow-gray-200/50 dark:shadow-none space-y-8">
                <div class="flex items-center justify-between">
                    <div>
                        <h2 class="text-2xl font-black text-gray-900 dark:text-white">Pricing & Terms</h2>
                        <p class="text-sm text-gray-500 mt-1 font-medium">Set fair rates and choose how renters get your item.</p>
                    </div>
                    <span class="w-10 h-10 rounded-full bg-yellow-100 flex items-center justify-center text-yellow-700 font-bold text-lg">3</span>
                </div>

                <div class="space-y-6">
                    <!-- Rental Rates -->
                    <div class="space-y-4">
                        <div class="flex items-center gap-2 text-gray-900 dark:text-white">
                            <span class="material-symbols-outlined text-yellow-600">payments</span>
                            <h4 class="font-bold">Rental Rates</h4>
                        </div>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div>
                                <label class="block text-xs font-bold text-gray-500 uppercase tracking-wider mb-2 ml-1">Daily Rate (₹) <span class="text-red-500">*</span></label>
                                <div class="relative group">
                                    <span class="absolute left-5 top-1/2 -translate-y-1/2 text-gray-400 font-bold text-lg group-focus-within:text-yellow-600 transition-colors">₹</span>
                                    <input type="number" name="price" value="<?php echo htmlspecialchars($price); ?>" placeholder="500.00" min="0" step="0.01" class="w-full bg-gray-50 dark:bg-gray-800 border-2 border-transparent focus:border-yellow-400 focus:bg-white dark:focus:bg-gray-700 rounded-2xl pl-10 pr-5 py-4 font-bold text-lg transition-all" required>
                                </div>
                                <p class="text-[11px] text-gray-400 mt-2 ml-1 flex items-center gap-1">
                                    <span class="material-symbols-outlined text-sm text-yellow-600">auto_awesome</span>
                                    Suggested: ₹300 - ₹800 based on similar items.
                                </p>
                            </div>
                            <div>
                                <label class="block text-xs font-bold text-gray-500 uppercase tracking-wider mb-2 ml-1 flex items-center gap-1">
                                    Security Deposit (₹)
                                    <span class="material-symbols-outlined text-sm text-gray-400 cursor-help" title="Optional deposit held during rental">info</span>
                                </label>
                                <div class="relative group">
                                    <span class="absolute left-5 top-1/2 -translate-y-1/2 text-gray-400 font-bold text-lg group-focus-within:text-yellow-600 transition-colors">₹</span>
                                    <input type="number" name="security_deposit" value="<?php echo htmlspecialchars($security_deposit); ?>" placeholder="0.00" min="0" step="0.01" class="w-full bg-gray-50 dark:bg-gray-800 border-2 border-transparent focus:border-yellow-400 focus:bg-white dark:focus:bg-gray-700 rounded-2xl pl-10 pr-5 py-4 font-bold text-lg transition-all">
                                </div>
                            </div>
                        </div>
                    </div>

                    <hr class="border-gray-100 dark:border-gray-800">

                    <!-- Handover Method -->
                    <div class="space-y-4">
                        <div class="flex items-center gap-2 text-gray-900 dark:text-white">
                            <span class="material-symbols-outlined text-yellow-600">local_shipping</span>
                            <h4 class="font-bold">Handover Method <span class="text-red-500">*</span></h4>
                        </div>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <!-- Option 1 -->
                            <label class="handover-card relative cursor-pointer">
                                <input type="checkbox" name="handover_methods[]" value="pickup" class="sr-only" <?php echo in_array('pickup', $handover_methods) ? 'checked' : ''; ?>>
                                <div class="border-2 border-gray-100 dark:border-gray-800 rounded-2xl p-5 transition-all hover:bg-gray-50 dark:hover:bg-gray-800/50">
                                    <div class="flex items-start justify-between">
                                        <span class="material-symbols-outlined text-yellow-600 text-3xl">storefront</span>
                                        <div class="check-icon hidden">
                                            <span class="material-symbols-outlined text-yellow-600 text-2xl">check_box</span>
                                        </div>
                                    </div>
                                    <h5 class="font-bold mt-3 text-gray-900 dark:text-white">Renter Pick-up</h5>
                                    <p class="text-xs text-gray-500 mt-1">Renters come to you. Free.</p>
                                </div>
                            </label>

                            <!-- Option 2 -->
                            <label class="handover-card relative cursor-pointer">
                                <input type="checkbox" name="handover_methods[]" value="delivery" class="sr-only" <?php echo in_array('delivery', $handover_methods) ? 'checked' : ''; ?>>
                                <div class="border-2 border-gray-100 dark:border-gray-800 rounded-2xl p-5 transition-all hover:bg-gray-50 dark:hover:bg-gray-800/50">
                                    <div class="flex items-start justify-between">
                                        <span class="material-symbols-outlined text-yellow-600 text-3xl">local_shipping</span>
                                        <div class="check-icon hidden">
                                            <span class="material-symbols-outlined text-yellow-600 text-2xl">check_box</span>
                                        </div>
                                    </div>
                                    <h5 class="font-bold mt-3 text-gray-900 dark:text-white">RendeX Delivery</h5>
                                    <div class="flex items-center justify-between mt-1">
                                        <p class="text-xs text-gray-500">We handle the logistics.</p>
                                        <span class="text-[10px] bg-yellow-100 text-yellow-800 px-1.5 py-0.5 rounded font-bold">+₹150.00</span>
                                    </div>
                                </div>
                            </label>
                        </div>
                        <?php if(in_array('Please select at least one Handover Method.', $errors)): ?>
                            <p class="text-xs text-red-500">Please select at least one method.</p>
                        <?php endif; ?>
                    </div>

                    <div class="pt-4">
                        <label class="flex items-start gap-4 cursor-pointer group">
                            <input type="checkbox" name="terms" class="mt-1 w-5 h-5 rounded border-2 border-gray-300 text-yellow-500 focus:ring-yellow-400 transition-all cursor-pointer" required>
                            <span class="text-sm text-gray-600 dark:text-gray-400 group-hover:text-gray-900 dark:group-hover:text-gray-200 transition-colors">
                                <span class="font-bold">I agree to the Rental Terms & Conditions</span><br>
                                <span class="text-xs">By listing this item, you agree to our insurance policy and owner guidelines.</span>
                            </span>
                        </label>
                    </div>
                </div>
            </div>

            <!-- Photos JS -->
            <script>
            const dt = new DataTransfer(); // Store files globally

            function handleFileSelect(input) {
                const previewContainer = document.getElementById('previewContainer');
                const initialContent = document.getElementById('initialContent');
                
                if (input.files && input.files.length > 0) {
                    // Add new files to the DataTransfer object
                    for(let i = 0; i < input.files.length; i++) {
                        dt.items.add(input.files[i]);
                    }
                    
                    // Update input files to include accumulated files
                    input.files = dt.files;

                    // Update UI
                    initialContent.classList.add('hidden');
                    previewContainer.classList.remove('hidden');
                    previewContainer.innerHTML = '';
                    
                    // Render all files from DataTransfer
                    Array.from(dt.files).forEach(file => {
                        const reader = new FileReader();
                        reader.onload = function(e) {
                            const imgDiv = document.createElement('div');
                            imgDiv.className = 'relative aspect-square rounded-2xl overflow-hidden shadow-sm group/img';
                            imgDiv.innerHTML = `
                                <img src="${e.target.result}" class="w-full h-full object-cover">
                            `;
                            previewContainer.appendChild(imgDiv);
                        }
                        reader.readAsDataURL(file);
                    });
                }
            }
            </script>

            <!-- Section 4: Location -->
            <div class="bg-white dark:bg-[#1e1e1e] p-8 md:p-10 rounded-[2.5rem] shadow-xl shadow-gray-200/50 dark:shadow-none space-y-6">
                <div class="flex items-center gap-3 pb-2">
                    <span class="w-10 h-10 rounded-full bg-yellow-100 flex items-center justify-center text-yellow-700 font-bold">4</span>
                    <h3 class="text-xl font-bold text-gray-900 dark:text-white">Pickup Location</h3>
                </div>
                
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div class="md:col-span-2">
                        <label class="block text-xs font-bold text-gray-500 uppercase tracking-wider mb-2 ml-1">Flat, House no., Building <span class="text-red-500">*</span></label>
                        <input type="text" name="addr_house" value="<?php echo htmlspecialchars($addr_house); ?>" placeholder="e.g. Flat 4B, Emerald Apartments" class="w-full bg-gray-50 dark:bg-gray-800 border-none focus:bg-white dark:focus:bg-gray-700 focus:ring-2 focus:ring-yellow-400 rounded-2xl px-5 py-4 font-medium transition-all" required>
                    </div>
                    
                    <div class="md:col-span-2">
                        <label class="block text-xs font-bold text-gray-500 uppercase tracking-wider mb-2 ml-1">Area, Street, Sector, Village <span class="text-red-500">*</span></label>
                        <input type="text" name="addr_street" value="<?php echo htmlspecialchars($addr_street); ?>" placeholder="e.g. 12th Main Road, Indiranagar" class="w-full bg-gray-50 dark:bg-gray-800 border-none focus:bg-white dark:focus:bg-gray-700 focus:ring-2 focus:ring-yellow-400 rounded-2xl px-5 py-4 font-medium transition-all" required>
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-gray-500 uppercase tracking-wider mb-2 ml-1">Town/City <span class="text-red-500">*</span></label>
                        <input type="text" name="addr_city" value="<?php echo htmlspecialchars($addr_city); ?>" placeholder="e.g. Bangalore" class="w-full bg-gray-50 dark:bg-gray-800 border-none focus:bg-white dark:focus:bg-gray-700 focus:ring-2 focus:ring-yellow-400 rounded-2xl px-5 py-4 font-medium transition-all" required>
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-gray-500 uppercase tracking-wider mb-2 ml-1">State <span class="text-red-500">*</span></label>
                        <input type="text" name="addr_state" value="<?php echo htmlspecialchars($addr_state); ?>" placeholder="e.g. Karnataka" class="w-full bg-gray-50 dark:bg-gray-800 border-none focus:bg-white dark:focus:bg-gray-700 focus:ring-2 focus:ring-yellow-400 rounded-2xl px-5 py-4 font-medium transition-all" required>
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-gray-500 uppercase tracking-wider mb-2 ml-1">Pincode <span class="text-red-500">*</span></label>
                        <input type="text" name="addr_pin" value="<?php echo htmlspecialchars($addr_pin); ?>" placeholder="e.g. 560038" pattern="[0-9]{6}" maxlength="6" class="w-full bg-gray-50 dark:bg-gray-800 border-none focus:bg-white dark:focus:bg-gray-700 focus:ring-2 focus:ring-yellow-400 rounded-2xl px-5 py-4 font-medium transition-all" required>
                    </div>
                </div>
            </div>

            <!-- Footer Actions -->
            <div class="flex flex-col md:flex-row items-center gap-4 pt-6">
                <a href="dashboard.php" class="w-full md:w-1/3 bg-white dark:bg-gray-800 border-2 border-gray-100 dark:border-gray-700 hover:bg-gray-50 text-gray-700 dark:text-gray-300 font-bold py-5 rounded-2xl text-center transition-all">
                    Back
                </a>
                <div class="flex flex-1 gap-4 w-full">
                    <button type="button" class="flex-1 bg-white dark:bg-gray-800 border-2 border-gray-100 dark:border-gray-700 hover:bg-gray-50 text-gray-900 dark:text-white font-bold py-5 rounded-2xl transition-all">
                        Preview Listing
                    </button>
                    <button type="submit" class="flex-[1.5] bg-primary hover:bg-[#e6e200] text-black font-black text-lg py-5 rounded-2xl shadow-xl shadow-yellow-200/50 dark:shadow-none transition-all hover:scale-[1.02] active:scale-[0.98] flex items-center justify-center gap-2">
                        <span><?php echo $is_edit ? 'Save Changes' : 'List Item Now'; ?></span>
                        <span class="material-symbols-outlined">check_circle</span>
                    </button>
                </div>
            </div>
            <p class="text-center text-xs text-gray-400 font-medium">Safe & Secure Rental Platform • RendeX Insurance Protection</p>

        </form>
    </main>
</body>
</html>
