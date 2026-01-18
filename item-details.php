<?php
ob_start();
session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$item_id = isset($_GET['id']) ? $_GET['id'] : null;
$item = null;
// Include database connection
require_once 'config/database.php';

// Load Dynamic Items
$items_file = 'items.json';
$dynamic_items = file_exists($items_file) ? json_decode(file_get_contents($items_file), true) : [];
if (!is_array($dynamic_items)) $dynamic_items = [];

foreach ($dynamic_items as $d_item) {
    if ($d_item['id'] === $item_id) {
        $item = $d_item;
        $item['name'] = $d_item['title'];
        $item['all_images'] = !empty($d_item['images']) ? array_map(function($img) { return 'uploads/' . $img; }, $d_item['images']) : [];
        $item['img'] = !empty($item['all_images']) ? $item['all_images'][0] : $d_item['category'];
        $item['price'] = $d_item['price'] ?? $d_item['price_per_day'] ?? 0;
        $item['type'] = 'dynamic';
        break;
    }
}

// If not found in JSON, check Database
if (!$item) {
    try {
        $pdo = getDBConnection();
        if ($pdo) {
            $stmt = $pdo->prepare("SELECT * FROM items WHERE id = ?");
            $stmt->execute([$item_id]);
            $db_item = $stmt->fetch();
            
            if ($db_item) {
                $item = $db_item;
                $item['name'] = $db_item['title'];
                $item['price'] = $db_item['price_per_day'];
                $item['address'] = $db_item['location'];
                
                // Handle JSON images
                $images = [];
                if (!empty($db_item['images'])) {
                    $images = json_decode($db_item['images'], true);
                    if (!is_array($images)) $images = [];
                }
                
                $item['all_images'] = array_map(function($img) { return 'uploads/' . $img; }, $images);
                $item['img'] = !empty($item['all_images']) ? $item['all_images'][0] : $db_item['category'];
                $item['type'] = 'dynamic';
            }
        }
    } catch (PDOException $e) {
        // Fallback to static if DB fails
    }
}

// If not found, check static items
if (!$item) {
    $static_items_data = [
        'student-essentials' => [
            ['name' => 'Scientific Calculator fx-991EX', 'price' => 5, 'img' => 'calculator'],
            ['name' => 'Engineering Drawing Set', 'price' => 8, 'img' => 'ruler,compass'],
            ['name' => 'Medical Lab Coat (Size M)', 'price' => 4, 'img' => 'lab coat'],
            ['name' => 'Architecture Drafting Table', 'price' => 15, 'img' => 'drafting table'],
            ['name' => 'LED Study Lamp', 'price' => 3, 'img' => 'desk lamp'],
            ['name' => 'Anatomy Model Skeleton', 'price' => 12, 'img' => 'skeleton model'],
        ],
        'clothing' => [
            ['name' => 'Men\'s Black Tuxedo (Suit)', 'price' => 45, 'img' => 'tuxedo'],
            ['name' => 'Evening Party Gown', 'price' => 55, 'img' => 'gown'],
            ['name' => 'Winter Down Jacket', 'price' => 15, 'img' => 'winter jacket'],
            ['name' => 'Hiking Boots (Size 42)', 'price' => 10, 'img' => 'hiking boots'],
            ['name' => 'Traditional Saree', 'price' => 20, 'img' => 'saree'],
            ['name' => 'Wedding Sherwani', 'price' => 60, 'img' => 'sherwani'],
        ],
        'electronics' => [
            ['name' => 'Canon EOS 5D Mark IV', 'price' => 85, 'img' => 'dslr camera'],
            ['name' => 'DJI Mavic Air 2 Drone', 'price' => 60, 'img' => 'drone'],
            ['name' => 'Sony PlayStation 5', 'price' => 25, 'img' => 'playstation 5'],
            ['name' => 'GoPro Hero 10 Black', 'price' => 20, 'img' => 'gopro'],
            ['name' => 'Epson 4K Home Projector', 'price' => 40, 'img' => 'projector'],
            ['name' => 'JBL PartyBox Speaker', 'price' => 18, 'img' => 'huge speaker'],
        ],
        'outdoor-gear' => [
            ['name' => '4-Person Camping Tent', 'price' => 25, 'img' => 'camping tent'],
            ['name' => 'Sleeping Bag (-5°C)', 'price' => 8, 'img' => 'sleeping bag'],
            ['name' => 'Hiking Backpack 60L', 'price' => 12, 'img' => 'hiking backpack'],
            ['name' => 'Portable Camping Stove', 'price' => 5, 'img' => 'camping stove'],
            ['name' => 'Carbon Trekking Poles', 'price' => 6, 'img' => 'trekking poles'],
            ['name' => 'Roof Rack Cargo Box', 'price' => 30, 'img' => 'roof box'],
        ],
        'home-essentials' => [
            ['name' => 'Bosch Drill Machine Kit', 'price' => 10, 'img' => 'drill machine'],
            ['name' => 'High Pressure Washer', 'price' => 18, 'img' => 'pressure washer'],
            ['name' => 'Aluminum Extension Ladder', 'price' => 12, 'img' => 'ladder'],
            ['name' => 'Heavy Duty Vacuum Cleaner', 'price' => 15, 'img' => 'vacuum cleaner'],
            ['name' => 'Portable Sewing Machine', 'price' => 8, 'img' => 'sewing machine'],
            ['name' => 'Steam Iron Station', 'price' => 7, 'img' => 'steam iron'],
        ],
        'furniture' => [
            ['name' => 'Ergonomic Office Chair', 'price' => 15, 'img' => 'office chair'],
            ['name' => 'Folding Dining Table (6 Seater)', 'price' => 20, 'img' => 'folding table'],
            ['name' => 'Large Bean Bag Chair', 'price' => 8, 'img' => 'bean bag'],
            ['name' => 'Wooden Bookshelf', 'price' => 12, 'img' => 'bookshelf'],
            ['name' => 'Outdoor Patio Set', 'price' => 35, 'img' => 'patio furniture'],
            ['name' => 'Baby Crib / Cot', 'price' => 18, 'img' => 'baby crib'],
        ],
        'vintage' => [
            ['name' => '1950s Typewriter', 'price' => 25, 'img' => 'typewriter'],
            ['name' => 'Vinyl Record Player', 'price' => 20, 'img' => 'vinyl player'],
            ['name' => 'Polaroid 600 Camera', 'price' => 15, 'img' => 'polaroid camera'],
            ['name' => 'Antique Brass Telescope', 'price' => 30, 'img' => 'telescope'],
            ['name' => 'Vintage Trunk/Suitcase', 'price' => 12, 'img' => 'vintage trunk'],
        ],
        'fitness' => [
            ['name' => 'Motorized Treadmill', 'price' => 40, 'img' => 'treadmill'],
            ['name' => 'Adjustable Dumbbell Set', 'price' => 10, 'img' => 'dumbbells'],
            ['name' => 'Spinning Exercise Bike', 'price' => 25, 'img' => 'exercise bike'],
            ['name' => 'Yoga Mat (Extra Thick)', 'price' => 3, 'img' => 'yoga mat'],
            ['name' => 'Rowing Machine', 'price' => 35, 'img' => 'rowing machine'],
        ],
        'agriculture' => [
            ['name' => 'Petrol Grass Trimmer', 'price' => 20, 'img' => 'grass trimmer'],
            ['name' => 'Electric Chainsaw', 'price' => 25, 'img' => 'chainsaw'],
            ['name' => 'Wheelbarrow', 'price' => 8, 'img' => 'wheelbarrow'],
            ['name' => 'Garden Shovel & Spade', 'price' => 4, 'img' => 'garden tools'],
            ['name' => 'Knapsack Sprayer 16L', 'price' => 7, 'img' => 'backpack sprayer'],
        ],
        'medical' => [
            ['name' => 'Foldable Wheelchair', 'price' => 15, 'img' => 'wheelchair'],
            ['name' => 'Adjustable Crutches (Pair)', 'price' => 5, 'img' => 'crutches'],
            ['name' => 'Hospital Bed (Electric)', 'price' => 50, 'img' => 'hospital bed'],
            ['name' => 'Oxygen Concentrator', 'price' => 40, 'img' => 'oxygen concentrator'],
            ['name' => 'Walker with Wheels', 'price' => 8, 'img' => 'medical walker'],
        ],
    ];

    foreach ($static_items_data as $cat => $items) {
        foreach ($items as $s_item) {
            if (md5($s_item['name']) === $item_id) {
                $item = $s_item;
                $item['type'] = 'static';
                // Add fake description for static items
                $item['description'] = "This is a premium quality " . $item['name'] . " available for rent. Well maintained and perfect for your needs. Contact the owner for availability.";
                $item['address'] = "Central District, City";
                break 2;
            }
        }
    }
}

// Fetch Owner Information
$owner_name = $item['owner_name'] ?? "Verified Owner";
$owner_email = "contact@rendex.com";
$owner_phone = "+91 98765 43210";
$owner_id = $item['user_id'] ?? $item['owner_id'] ?? null;

if ($owner_id) {
    // Check users.json
    $users_json_file = 'users.json';
    $all_users = file_exists($users_json_file) ? json_decode(file_get_contents($users_json_file), true) : [];
    if (is_array($all_users)) {
        foreach ($all_users as $u) {
            if ($u['id'] == $owner_id) {
                $owner_name = $u['name'];
                $owner_email = $u['email'] ?? $owner_email;
                $owner_phone = $u['phone'] ?? $owner_phone;
                break;
            }
        }
    }
    
    // Fallback to Database for full info
    try {
        $pdo = getDBConnection();
        if ($pdo) {
            $stmt = $pdo->prepare("SELECT name, email, phone FROM users WHERE id = ?");
            $stmt->execute([$owner_id]);
            $user_db = $stmt->fetch();
            if ($user_db) {
                $owner_name = $user_db['name'];
                $owner_email = $user_db['email'] ?? $owner_email;
                $owner_phone = $user_db['phone'] ?? $owner_phone;
            }
        }
    } catch (Exception $e) {}
} elseif (isset($item['type']) && $item['type'] === 'static') {
    $owner_name = "RendeX Partner";
    $owner_email = "support@rendexpartners.com";
    $owner_phone = "1800-RENDEX-GURU";
}

if (!$item) {
    header("Location: dashboard.php");
    exit();
}

// Visibility Check: Only show Active items unless requester is the owner OR admin
$is_admin = (isset($_SESSION['user_email']) && $_SESSION['user_email'] === 'annachristina2005@gmail.com');
$is_own_item = (isset($item['user_id']) && $item['user_id'] === $_SESSION['user_id']) || (isset($item['owner_id']) && $item['owner_id'] === $_SESSION['user_id']);
$item_status = $item['status'] ?? ($item['admin_status'] ?? 'Active');
$is_active = in_array(strtolower($item_status), ['active', 'approved']);

// Availability Check
$is_unavailable = (isset($item['availability_status']) && in_array(strtolower($item['availability_status']), ['rented', 'unavailable'])) || 
                  (isset($item['status']) && strtolower($item['status']) === 'unavailable');
if (!$is_active && !$is_own_item && !$is_admin) {
    header("Location: dashboard.php?msg=item_pending");
    exit();
}

// Handle Rent Action - Redirect to Confirmation Page
if (isset($_POST['rent_now'])) {
    $duration = $_POST['duration'] ?? 3;
    header("Location: confirm-rental.php?id=" . $item_id . "&duration=" . $duration);
    exit();
}

?>
<!DOCTYPE html>
<html class="light" lang="en">
<head>
    <meta charset="utf-8"/>
    <meta content="width=device-width, initial-scale=1.0" name="viewport"/>
    <title>RendeX - <?php echo htmlspecialchars($item['name']); ?></title>
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com" rel="preconnect"/>
    <link crossorigin="" href="https://fonts.gstatic.com" rel="preconnect"/>
    <link href="https://fonts.googleapis.com/css2?family=Spline+Sans:wght@300;400;500;600;700&amp;display=swap" rel="stylesheet"/>
    <link href="https://fonts.googleapis.com/css2?family=Noto+Sans:wght@400;500;700&amp;display=swap" rel="stylesheet"/>
    <!-- Material Symbols -->
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&amp;display=swap" rel="stylesheet"/>
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
    </style>
</head>
<body class="bg-background-light dark:bg-background-dark text-text-main dark:text-white transition-colors duration-200 flex flex-col min-h-screen">
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
            <!-- Right Side: Navigation & User Menu -->
            <div class="flex items-center gap-4 lg:gap-8 ml-auto">
                <div class="hidden lg:flex items-center gap-6">
                    <a href="dashboard.php" class="text-sm font-bold hover:text-primary transition-colors">Home</a>
                    <a href="rentals.php" class="text-sm font-bold hover:text-primary transition-colors">My Rentals</a>
                </div>

                <!-- User Identity (Reusing logic from dashboard) -->
                <?php
                // Quick role check for the header (copied logic snippet)
                $is_owner_h = false;
                $items_h = file_exists('items.json') ? json_decode(file_get_contents('items.json'), true) : [];
                foreach ((array)$items_h as $i) { if (isset($i['user_id']) && $i['user_id'] === $_SESSION['user_id']) { $is_owner_h = true; break; } }
                ?>

                <!-- Profile Circle -->
                <a href="profile.php" class="group flex items-center gap-3">
                    <div class="w-10 h-10 rounded-full bg-primary flex items-center justify-center text-black text-sm font-black border-2 border-transparent group-hover:border-primary transition-all shadow-sm">
                        <?php echo strtoupper(substr($_SESSION['user_name'], 0, 1)); ?>
                    </div>
                </a>

                <a href="logout.php" class="bg-black text-white dark:bg-white dark:text-black size-10 rounded-full flex items-center justify-center hover:bg-primary hover:text-black transition-all shadow-sm" title="Logout">
                    <span class="material-symbols-outlined text-[20px]">logout</span>
                </a>
            </div>
        </div>
    </header>

    <main class="flex-1 w-full max-w-[1400px] mx-auto px-4 md:px-10 py-12">
        <div class="grid grid-cols-1 md:grid-cols-2 gap-12">
            <!-- Left: Image Section with Hover Slideshow -->
            <div class="space-y-4 sticky top-32">
                <div class="relative rounded-3xl overflow-hidden bg-white dark:bg-surface-dark border border-[#e9e8ce] dark:border-[#3e3d2a] aspect-square group cursor-pointer shadow-lg" id="slideshow-container">
                    <?php if (!empty($item['all_images']) && count($item['all_images']) > 1): ?>
                        <!-- Image Count Badge -->
                        <div class="absolute top-4 left-4 z-20 bg-black/50 backdrop-blur-md text-white text-xs font-bold px-3 py-1.5 rounded-full flex items-center gap-1.5 transition-opacity opacity-0 group-hover:opacity-100">
                             <span class="material-symbols-outlined text-sm">photo_library</span>
                             <span id="image-counter">1 / <?php echo count($item['all_images']); ?></span>
                        </div>

                        <!-- Slideshow Dots -->
                        <div class="absolute bottom-6 left-1/2 -translate-x-1/2 z-20 flex gap-1.5 px-3 py-1.5 bg-black/20 backdrop-blur-md rounded-full border border-white/10 transition-opacity opacity-60 group-hover:opacity-100">
                            <?php foreach($item['all_images'] as $i => $img): ?>
                                <div class="w-1.5 h-1.5 rounded-full bg-white transition-all duration-300 slideshow-dot <?php echo $i === 0 ? 'w-4' : 'opacity-40'; ?>" data-index="<?php echo $i; ?>"></div>
                            <?php endforeach; ?>
                        </div>
                        
                        <!-- Preload images to avoid flicker -->
                        <div class="hidden">
                            <?php foreach($item['all_images'] as $img): ?>
                                <img src="<?php echo $img; ?>">
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>

                    <img id="main-product-image" class="object-cover w-full h-full transition-all duration-500 group-hover:scale-105" 
                         src="<?php echo (strpos($item['img'], 'uploads/') === 0) ? $item['img'] : 'https://source.unsplash.com/random/800x800?' . urlencode($item['img']); ?>" 
                         alt="<?php echo htmlspecialchars($item['name']); ?>">
                    
                    <?php if (!empty($item['all_images']) && count($item['all_images']) > 1): ?>
                    <script>
                        const images = <?php echo json_encode($item['all_images']); ?>;
                        const dots = document.querySelectorAll('.slideshow-dot');
                        const mainImg = document.getElementById('main-product-image');
                        const container = document.getElementById('slideshow-container');
                        const counter = document.getElementById('image-counter');
                        let currentIndex = 0;
                        let intervalId = null;

                        function updateImage(index) {
                            if (index === currentIndex) return;
                            
                            currentIndex = index;
                            
                            // Fade effect
                            mainImg.style.opacity = '0.7';
                            setTimeout(() => {
                                mainImg.src = images[currentIndex];
                                mainImg.style.opacity = '1';
                                
                                // Update dots
                                dots.forEach((dot, i) => {
                                    if (i === currentIndex) {
                                        dot.classList.add('w-4');
                                        dot.classList.remove('opacity-40');
                                    } else {
                                        dot.classList.remove('w-4');
                                        dot.classList.add('opacity-40');
                                    }
                                });
                                
                                // Update counter
                                if (counter) counter.textContent = `${currentIndex + 1} / ${images.length}`;
                                
                                // Update Highlighted Thumbnail
                                document.querySelectorAll('.thumbnail-item').forEach((thumb, i) => {
                                    if (i === currentIndex) {
                                        thumb.classList.add('border-primary', 'opacity-100');
                                        thumb.classList.remove('border-transparent', 'opacity-50');
                                    } else {
                                        thumb.classList.remove('border-primary', 'opacity-100');
                                        thumb.classList.add('border-transparent', 'opacity-50');
                                    }
                                });
                            }, 150);
                        }

                        container.addEventListener('mouseenter', () => {
                            intervalId = setInterval(() => {
                                let nextIndex = (currentIndex + 1) % images.length;
                                updateImage(nextIndex);
                            }, 1500);
                        });

                        container.addEventListener('mouseleave', () => {
                            if (intervalId) {
                                clearInterval(intervalId);
                                intervalId = null;
                            }
                            // Optionally reset to first image
                            // updateImage(0); 
                        });

                        function setMainImage(index) {
                            if (intervalId) {
                                clearInterval(intervalId);
                                intervalId = null;
                            }
                            updateImage(index);
                        }
                    </script>
                    <?php endif; ?>
                </div>

                <!-- Thumbnail Gallery -->
                <?php if (!empty($item['all_images']) && count($item['all_images']) > 1): ?>
                <div class="flex gap-3 overflow-x-auto pb-2 scrollbar-hide py-1">
                    <?php foreach($item['all_images'] as $i => $img): ?>
                        <div onclick="setMainImage(<?php echo $i; ?>)" 
                             class="thumbnail-item shrink-0 w-20 h-20 rounded-xl overflow-hidden border-2 transition-all cursor-pointer <?php echo $i === 0 ? 'border-primary opacity-100' : 'border-transparent opacity-50 hover:opacity-100'; ?>">
                            <img src="<?php echo $img; ?>" class="w-full h-full object-cover">
                        </div>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>
            </div>

            <!-- Right: Details -->
            <div class="flex flex-col gap-8">
                <div>
                    <div class="flex items-center gap-2 mb-4">
                        <?php if (isset($is_unavailable) && $is_unavailable): ?>
                            <span class="bg-red-100 text-red-700 font-bold px-3 py-1 rounded-full text-xs uppercase tracking-wide">Out of Stock</span>
                        <?php else: ?>
                            <span class="bg-primary/20 text-primary-dark font-bold px-3 py-1 rounded-full text-xs uppercase tracking-wide">Available</span>
                        <?php endif; ?>
                        <div class="flex items-center gap-1 text-xs font-bold bg-[#f4f4e6] dark:bg-[#3e3d2a] px-2 py-1 rounded-full">
                            <span class="material-symbols-outlined text-sm text-yellow-600">star</span>
                            4.<?php echo rand(5, 9); ?> (<?php echo rand(10, 50); ?> reviews)
                        </div>
                    </div>
                    <h1 class="text-4xl md:text-5xl font-black mb-4 leading-tight"><?php echo htmlspecialchars($item['name']); ?></h1>
                    
                    <div class="flex items-end gap-2 mb-6">
                        <span class="text-4xl font-black">₹<?php echo $item['price']; ?></span>
                        <span class="text-xl text-text-muted font-medium mb-1">/ day</span>
                    </div>

                    <p class="text-lg text-text-muted leading-relaxed">
                        <?php echo htmlspecialchars($item['description']); ?>
                    </p>
                </div>

                <div class="p-6 rounded-2xl bg-surface-light dark:bg-surface-dark border border-[#e9e8ce] dark:border-[#3e3d2a] flex items-center gap-4">
                    <div class="w-14 h-14 rounded-full bg-primary flex items-center justify-center text-black text-xl font-bold shadow-sm">
                        <?php echo strtoupper(substr($owner_name, 0, 1)); ?>
                    </div>
                    <div>
                        <p class="text-[10px] font-black text-text-muted uppercase tracking-widest mb-0.5 leading-none">Listed by</p>
                        <p class="text-xl font-black leading-tight"><?php echo htmlspecialchars($owner_name); ?></p>
                        <div class="flex items-center gap-1 text-[11px] font-bold text-green-600 uppercase tracking-tighter mt-1">
                            <span class="material-symbols-outlined text-[14px]">verified</span> Identity Verified
                        </div>
                    </div>
                    <button onclick="openContactModal()" class="ml-auto bg-white dark:bg-[#1e2019] border border-[#e9e8ce] dark:border-[#3e3d2a] px-6 py-3 rounded-2xl font-black text-xs uppercase tracking-widest hover:bg-black hover:text-white dark:hover:bg-primary dark:hover:text-black transition-all shadow-sm">Contact</button>
                </div>

                <!-- Location -->
                 <div>
                    <h3 class="font-bold text-lg mb-2">Location</h3>
                    <div class="flex items-center gap-2 text-text-muted">
                        <span class="material-symbols-outlined">location_on</span>
                        <span><?php echo isset($item['address']) ? htmlspecialchars($item['address']) : 'Central District, City'; ?></span>
                    </div>
                 </div>

                 <hr class="border-[#e9e8ce] dark:border-[#3e3d2a]">

                 <!-- Actions -->
                     <!-- Actions -->
                     <?php if ($is_unavailable): ?>
                        <div class="bg-red-50 dark:bg-red-900/10 border border-red-100 dark:border-red-900/30 p-6 rounded-2xl text-center">
                            <span class="material-symbols-outlined text-4xl text-red-500 mb-2">remove_shopping_cart</span>
                            <h3 class="text-xl font-black text-red-600 dark:text-red-400 mb-1">Currently Unavailable</h3>
                            <p class="text-sm text-text-muted mb-6">This item has been rented and is currently out of stock.</p>
                            <button disabled class="w-full bg-gray-200 dark:bg-gray-800 text-gray-400 font-black text-xl py-5 rounded-2xl cursor-not-allowed">
                                Out of Stock
                            </button>
                        </div>
                     <?php else: ?>
                     <form method="POST">
                         <div class="flex gap-4">
                             <div class="flex-1">
                                 <label class="block text-sm font-bold mb-2 ml-1">Rent Duration</label>
                                 <select name="duration" class="w-full bg-surface-light dark:bg-surface-dark border-transparent focus:border-primary focus:ring-0 rounded-xl px-4 py-3 font-bold border border-[#e9e8ce]">
                                     <option value="1">1 Day</option>
                                     <option value="3" selected>3 Days (Most Popular)</option>
                                     <option value="7">1 Week (Save 10%)</option>
                                 </select>
                             </div>
                         </div>
                         <button type="submit" name="rent_now" class="w-full bg-primary hover:bg-yellow-300 text-black font-black text-xl py-5 rounded-2xl shadow-xl shadow-primary/20 transition-all hover:-translate-y-1 mt-6">
                             Rent Now
                         </button>
                         <p class="text-center text-xs text-text-muted mt-4">You won't be charged yet.</p>
                     </form>
                     <?php endif; ?>
            </div>
        </div>
    </main>

    <!-- Contact Modal -->
    <div id="contactModal" class="fixed inset-0 z-[100] hidden items-center justify-center p-4">
        <div class="absolute inset-0 bg-black/60 backdrop-blur-sm" onclick="closeContactModal()"></div>
        <div class="relative bg-white dark:bg-surface-dark w-full max-w-md rounded-3xl overflow-hidden shadow-2xl border border-[#e9e8ce] dark:border-[#3e3d2a] transform transition-all">
            <div class="bg-primary p-8 flex flex-col items-center text-center">
                <div class="w-20 h-20 rounded-full bg-white flex items-center justify-center text-black text-3xl font-black mb-4 shadow-lg">
                    <?php echo strtoupper(substr($owner_name, 0, 1)); ?>
                </div>
                <h3 class="text-2xl font-black text-black"><?php echo htmlspecialchars($owner_name); ?></h3>
                <div class="flex items-center gap-1 text-black/60 text-xs font-bold uppercase mt-1">
                    <span class="material-symbols-outlined text-[14px]">verified</span> Verified Owner
                </div>
            </div>
            <div class="p-8 space-y-6">
                <div>
                    <label class="block text-[10px] font-black text-text-muted uppercase tracking-widest mb-2">Email Address</label>
                    <div class="flex items-center gap-3 bg-gray-50 dark:bg-[#1e2019] p-4 rounded-2xl">
                        <span class="material-symbols-outlined text-primary">mail</span>
                        <span class="font-bold"><?php echo htmlspecialchars($owner_email); ?></span>
                    </div>
                </div>
                <div>
                    <label class="block text-[10px] font-black text-text-muted uppercase tracking-widest mb-2">Phone Number</label>
                    <div class="flex items-center gap-3 bg-gray-50 dark:bg-[#1e2019] p-4 rounded-2xl">
                        <span class="material-symbols-outlined text-primary">call</span>
                        <span class="font-bold"><?php echo htmlspecialchars($owner_phone); ?></span>
                    </div>
                </div>
                <button onclick="closeContactModal()" class="w-full bg-black text-white dark:bg-primary dark:text-black font-black py-4 rounded-2xl mt-4 transition-all hover:scale-[1.02] active:scale-95">
                    Close
                </button>
            </div>
        </div>
    </div>

    <script>
        function openContactModal() {
            const modal = document.getElementById('contactModal');
            modal.classList.remove('hidden');
            modal.classList.add('flex');
            document.body.style.overflow = 'hidden';
        }

        function closeContactModal() {
            const modal = document.getElementById('contactModal');
            modal.classList.add('hidden');
            modal.classList.remove('flex');
            document.body.style.overflow = 'auto';
        }
    </script>
    <!-- Footer -->
    <footer class="bg-surface-light dark:bg-surface-dark border-t border-[#e9e8ce] dark:border-[#3e3d2a] pt-16 pb-8 px-4 md:px-10 mt-auto">
        <div class="text-center text-sm font-bold text-text-muted">
            &copy; 2026 RendeX. All rights reserved.
        </div>
    </footer>
</body>
</html>
