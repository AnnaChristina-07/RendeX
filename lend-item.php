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
$images = [];
$edit_id = null;
$is_edit = false;

// Load existing items
$items = file_exists($items_file) ? json_decode(file_get_contents($items_file), true) : [];
if (!is_array($items)) $items = [];

// Handle Edit Request (GET)
if (isset($_GET['edit_id'])) {
    $edit_id = $_GET['edit_id'];
    foreach ($items as $item) {
        if ((string)$item['id'] === (string)$edit_id && (string)$item['user_id'] === (string)$_SESSION['user_id']) {
            $title = $item['title'];
            $category = $item['category'];
            $price = $item['price'];
            $security_deposit = $item['security_deposit'] ?? '';
            $handover_methods = $item['handover_methods'] ?? ['pickup'];
            $description = $item['description'];
            $address = $item['address'];
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
    $title = $_POST['title'];
    $category = $_POST['category'];
    $price = $_POST['price'];
    $security_deposit = $_POST['security_deposit'] ?? 0;
    $handover_methods = $_POST['handover_methods'] ?? ['pickup'];
    $description = $_POST['description'];
    $address = $_POST['address'];
    
    // Handle File Uploads
    $uploaded_images = [];
    if (isset($_FILES['item_images']) && !empty($_FILES['item_images']['name'][0])) {
        if (!is_dir($uploads_dir)) mkdir($uploads_dir, 0777, true);
        
        $file_count = count($_FILES['item_images']['name']);
        for ($i = 0; $i < $file_count; $i++) {
            if ($_FILES['item_images']['error'][$i] === UPLOAD_ERR_OK) {
                $tmp_name = $_FILES['item_images']['tmp_name'][$i];
                $name = basename($_FILES['item_images']['name'][$i]);
                $new_name = uniqid() . '_' . $name;
                if (move_uploaded_file($tmp_name, $uploads_dir . $new_name)) {
                    $uploaded_images[] = $new_name;
                }
            }
        }
    }
    
    // If Editing: merge new images or keep old if no new ones (flexible logic)
    if (!empty($uploaded_images)) {
        $final_images = $uploaded_images;
    } else {
        $final_images = $images; // existing images from GET load
    }

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

        <form id="lendItemForm" class="space-y-10" method="POST" enctype="multipart/form-data" onsubmit="const btn = this.querySelector('button[type=submit]'); if(btn.disabled) return false; btn.disabled = true; btn.classList.add('opacity-50', 'cursor-not-allowed');">
            
            <!-- Section 1: Item Details -->
            <div class="bg-white dark:bg-[#1e1e1e] p-8 md:p-10 rounded-[2.5rem] shadow-xl shadow-gray-200/50 dark:shadow-none space-y-6">
                <div class="flex items-center gap-3 pb-2">
                    <span class="w-10 h-10 rounded-full bg-yellow-100 flex items-center justify-center text-yellow-700 font-bold">1</span>
                    <h3 class="text-xl font-bold text-gray-900 dark:text-white">Item Details</h3>
                </div>
                
                <div>
                    <label class="block text-sm font-bold mb-2 ml-1 text-gray-700 dark:text-gray-300">Item Name</label>
                    <input type="text" name="title" value="<?php echo htmlspecialchars($title); ?>" placeholder="e.g. GoPro Hero 10 Black" class="w-full bg-gray-50 dark:bg-gray-800 border-none focus:bg-white dark:focus:bg-gray-700 focus:ring-2 focus:ring-yellow-400 rounded-2xl px-5 py-4 font-medium transition-all" required>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <label class="block text-sm font-bold mb-2 ml-1 text-gray-700 dark:text-gray-300">Category</label>
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
                    <label class="block text-sm font-bold mb-2 ml-1 text-gray-700 dark:text-gray-300">Description</label>
                    <textarea name="description" rows="4" placeholder="Describe the condition, features, and what's included..." class="w-full bg-gray-50 dark:bg-gray-800 border-none focus:bg-white dark:focus:bg-gray-700 focus:ring-2 focus:ring-yellow-400 rounded-2xl px-5 py-4 font-medium transition-all resize-none" required><?php echo htmlspecialchars($description); ?></textarea>
                </div>
            </div>

            <!-- Section 2: Photos -->
            <div class="bg-white dark:bg-[#1e1e1e] p-8 md:p-10 rounded-[2.5rem] shadow-xl shadow-gray-200/50 dark:shadow-none space-y-6">
                <div class="flex items-center gap-3 pb-2">
                    <span class="w-10 h-10 rounded-full bg-yellow-100 flex items-center justify-center text-yellow-700 font-bold">2</span>
                    <h3 class="text-xl font-bold text-gray-900 dark:text-white">Photos</h3>
                </div>
                
                <div id="dropzone" class="border-3 border-dashed border-gray-200 dark:border-gray-700 rounded-3xl p-10 text-center hover:bg-yellow-50 dark:hover:bg-yellow-900/10 hover:border-yellow-400 transition-all cursor-pointer group" onclick="document.getElementById('fileInput').click()">
                    <div id="initialContent" class="<?php echo !empty($images) ? 'hidden' : ''; ?>">
                        <div class="w-16 h-16 bg-yellow-100 rounded-full flex items-center justify-center mx-auto mb-4 group-hover:scale-110 transition-transform shadow-sm">
                            <span class="material-symbols-outlined text-3xl text-yellow-700">add_a_photo</span>
                        </div>
                        <p class="font-bold text-lg text-gray-900 dark:text-white">Click to upload photos</p>
                        <p class="text-xs text-gray-500 mt-1">Supports JPG, PNG, GIF</p>
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
                                <label class="block text-xs font-bold text-gray-500 uppercase tracking-wider mb-2 ml-1">Daily Rate (₹)</label>
                                <div class="relative group">
                                    <span class="absolute left-5 top-1/2 -translate-y-1/2 text-gray-400 font-bold text-lg group-focus-within:text-yellow-600 transition-colors">₹</span>
                                    <input type="number" name="price" value="<?php echo htmlspecialchars($price); ?>" placeholder="500.00" class="w-full bg-gray-50 dark:bg-gray-800 border-2 border-transparent focus:border-yellow-400 focus:bg-white dark:focus:bg-gray-700 rounded-2xl pl-10 pr-5 py-4 font-bold text-lg transition-all" required>
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
                                    <input type="number" name="security_deposit" value="<?php echo htmlspecialchars($security_deposit); ?>" placeholder="0.00" class="w-full bg-gray-50 dark:bg-gray-800 border-2 border-transparent focus:border-yellow-400 focus:bg-white dark:focus:bg-gray-700 rounded-2xl pl-10 pr-5 py-4 font-bold text-lg transition-all">
                                </div>
                            </div>
                        </div>
                    </div>

                    <hr class="border-gray-100 dark:border-gray-800">

                    <!-- Handover Method -->
                    <div class="space-y-4">
                        <div class="flex items-center gap-2 text-gray-900 dark:text-white">
                            <span class="material-symbols-outlined text-yellow-600">local_shipping</span>
                            <h4 class="font-bold">Handover Method</h4>
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
            function handleFileSelect(input) {
                const previewContainer = document.getElementById('previewContainer');
                const initialContent = document.getElementById('initialContent');
                
                if (input.files && input.files.length > 0) {
                    initialContent.classList.add('hidden');
                    previewContainer.classList.remove('hidden');
                    previewContainer.innerHTML = '';
                    
                    Array.from(input.files).forEach(file => {
                        const reader = new FileReader();
                        reader.onload = function(e) {
                            const imgDiv = document.createElement('div');
                            imgDiv.className = 'relative aspect-square rounded-2xl overflow-hidden shadow-sm';
                            imgDiv.innerHTML = `<img src="${e.target.result}" class="w-full h-full object-cover">`;
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
                
                <div class="relative">
                    <span class="absolute left-5 top-1/2 -translate-y-1/2 material-symbols-outlined text-gray-400">location_on</span>
                    <input type="text" name="address" value="<?php echo htmlspecialchars($address); ?>" placeholder="Enter city or area (e.g. Indiranagar, Bangalore)" class="w-full bg-gray-50 dark:bg-gray-800 border-none focus:bg-white dark:focus:bg-gray-700 focus:ring-2 focus:ring-yellow-400 rounded-2xl pl-12 pr-5 py-4 font-medium transition-all" required>
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
