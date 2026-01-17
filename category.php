<?php
ob_start();
session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}
?>
<!DOCTYPE html>
<html class="light" lang="en"><head>
<meta charset="utf-8"/>
<meta content="width=device-width, initial-scale=1.0" name="viewport"/>
<title>RendeX - Category</title>
<!-- Include database connection -->
<?php require_once 'config/database.php'; ?>
<!-- Google Fonts -->
<link href="https://fonts.googleapis.com" rel="preconnect"/>
<link crossorigin="" href="https://fonts.gstatic.com" rel="preconnect"/>
<link href="https://fonts.googleapis.com/css2?family=Spline+Sans:wght@300;400;500;600;700&amp;display=swap" rel="stylesheet"/>
<link href="https://fonts.googleapis.com/css2?family=Noto+Sans:wght@400;500;700&amp;display=swap" rel="stylesheet"/>
<!-- Material Symbols -->
<link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&amp;display=swap" rel="stylesheet"/>
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
    </style>
</head>
<body class="bg-background-light dark:bg-background-dark text-text-main dark:text-white transition-colors duration-200">
<div class="relative flex min-h-screen w-full flex-col overflow-x-hidden">
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

<!-- Mobile Menu Button -->
<button class="lg:hidden p-2 text-text-main dark:text-white">
<span class="material-symbols-outlined">menu</span>
</button>
</div>
</header>
<main class="flex-1 w-full max-w-[1400px] mx-auto px-4 md:px-10 pb-20">
    <?php
    $cat_slug = isset($_GET['cat']) ? $_GET['cat'] : '';
    $categories = [
        'student-essentials' => ['title' => 'Student Essentials', 'icon' => 'school'],
        'clothing' => ['title' => 'Clothing', 'icon' => 'checkroom'],
        'electronics' => ['title' => 'Electronic Devices', 'icon' => 'devices'],
        'outdoor-gear' => ['title' => 'Travel & Outdoor Gear', 'icon' => 'backpack'],
        'home-essentials' => ['title' => 'Home & Daily Essentials', 'icon' => 'home'],
        'furniture' => ['title' => 'Furniture', 'icon' => 'chair'],
        'vintage' => ['title' => 'Vintage Collections', 'icon' => 'auto_awesome'],
        'fitness' => ['title' => 'Fitness Equipment', 'icon' => 'fitness_center'],
        'agriculture' => ['title' => 'Agricultural Tools', 'icon' => 'agriculture'],
        'medical' => ['title' => 'Medical Items', 'icon' => 'medical_services'],
    ];

    $current_cat = isset($categories[$cat_slug]) ? $categories[$cat_slug] : null;

    // Load Dynamic Items from JSON
    $items_file = 'items.json';
    $dynamic_items = file_exists($items_file) ? json_decode(file_get_contents($items_file), true) : [];
    if (!is_array($dynamic_items)) $dynamic_items = [];

    $items_to_show = [];
    
    // 1. Add Dynamic Items from Database (if available)
    try {
        $pdo = getDBConnection();
        if ($pdo) {
            $stmt = $pdo->prepare("
                SELECT i.*, u.name as owner_name 
                FROM items i 
                JOIN users u ON i.owner_id = u.id 
                WHERE i.category = ? AND i.admin_status = 'approved'
            ");
            $stmt->execute([$cat_slug]);
            $db_items = $stmt->fetchAll();
            
            foreach ($db_items as $db_item) {
                $item = $db_item;
                $item['name'] = $db_item['title'];
                $item['price'] = $db_item['price_per_day'];
                
                // Handle JSON images
                $images = [];
                if (!empty($db_item['images'])) {
                    $images = json_decode($db_item['images'], true);
                    if (!is_array($images)) $images = [];
                }
                $item['all_images'] = array_map(function($img) { return 'uploads/' . $img; }, $images);
                $item['img'] = !empty($item['all_images']) ? $item['all_images'][0] : $db_item['category'];
                $item['type'] = 'dynamic';
                $items_to_show[] = $item;
            }
        }
    } catch (Exception $e) {}

    // 2. Add Dynamic Items from JSON (for fallback or those not in DB)
    foreach ($dynamic_items as $d_item) {
        if (isset($d_item['category']) && $d_item['category'] === $cat_slug) {
            // Avoid duplicates if already added from DB (match by title and owner if ID differs)
            $already_added = false;
            foreach ($items_to_show as $existing) {
                if (isset($existing['title']) && $existing['title'] === $d_item['title'] && 
                    isset($existing['owner_id']) && $existing['owner_id'] === $d_item['user_id']) {
                    $already_added = true;
                    break;
                }
            }
            if ($already_added) continue;

            if (isset($d_item['status']) && $d_item['status'] === 'Active') {
                $item = $d_item;
            } else {
                continue;
            }
            
            $d_item['name'] = $d_item['title'];
            $all_images = !empty($d_item['images']) ? array_map(function($img) { return 'uploads/' . $img; }, $d_item['images']) : [];
            $d_item['all_images'] = $all_images;
            $d_item['img'] = !empty($all_images) ? $all_images[0] : $d_item['category'];
            $d_item['type'] = 'dynamic';
            $items_to_show[] = $d_item;
        }
    }
    ?>

    <?php if ($current_cat): ?>
        <!-- Simple Category Banner - Black Background with Yellow Text -->
        <section class="mt-8 md:mt-12 rounded-3xl overflow-hidden shadow-lg bg-black border-2 border-[#3e3d2a]">
            <div class="px-8 py-12 md:px-16 md:py-16">
                <div class="max-w-6xl mx-auto">
                    <div class="flex flex-col md:flex-row items-center gap-8">
                        <!-- Icon -->
                        <div class="flex-shrink-0">
                            <div class="w-32 h-32 rounded-2xl bg-primary flex items-center justify-center shadow-xl">
                                <span class="material-symbols-outlined text-7xl text-black font-bold"><?php echo $current_cat['icon']; ?></span>
                            </div>
                        </div>
                        
                        <!-- Text Content -->
                        <div class="flex-1 text-center md:text-left">
                            <h1 class="text-5xl md:text-6xl font-black tracking-tight mb-4 text-primary">
                                <?php echo $current_cat['title']; ?>
                            </h1>
                            <p class="text-gray-300 text-lg md:text-xl leading-relaxed max-w-2xl mb-6">
                                Discover quality <?php echo strtolower($current_cat['title']); ?> available for rent. Verified owners, instant booking, and best prices guaranteed.
                            </p>
                            
                            <!-- Stats Badges -->
                            <div class="flex flex-wrap gap-3 justify-center md:justify-start">
                                <div class="bg-[#2d2c18] border border-primary/20 rounded-full px-5 py-2 flex items-center gap-2">
                                    <span class="material-symbols-outlined text-primary text-lg">inventory_2</span>
                                    <span class="text-white font-bold text-sm"><?php echo count($items_to_show); ?>+ Items</span>
                                </div>
                                <div class="bg-[#2d2c18] border border-primary/20 rounded-full px-5 py-2 flex items-center gap-2">
                                    <span class="material-symbols-outlined text-primary text-lg">verified</span>
                                    <span class="text-white font-bold text-sm">Verified Sellers</span>
                                </div>
                                <div class="bg-[#2d2c18] border border-primary/20 rounded-full px-5 py-2 flex items-center gap-2">
                                    <span class="material-symbols-outlined text-primary text-lg">bolt</span>
                                    <span class="text-white font-bold text-sm">Instant Booking</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- Search Bar (Full Width) -->
        <div class="mt-10 mb-6">
            <div class="relative">
                <input 
                    type="text" 
                    id="searchInput" 
                    placeholder="Search for items in <?php echo $current_cat['title']; ?>..." 
                    class="w-full bg-surface-light dark:bg-surface-dark border-2 border-[#e9e8ce] dark:border-[#3e3d2a] rounded-2xl px-6 py-4 pl-14 text-base font-medium focus:ring-2 focus:ring-primary focus:border-primary outline-none transition-all shadow-sm"
                />
                <span class="material-symbols-outlined absolute left-5 top-1/2 -translate-y-1/2 text-text-muted text-xl">search</span>
                <button id="clearSearch" class="absolute right-5 top-1/2 -translate-y-1/2 hidden bg-primary hover:opacity-80 text-black rounded-full p-1.5 transition-all">
                    <span class="material-symbols-outlined text-base">close</span>
                </button>
            </div>
        </div>

        <!-- 2-Column Layout: Sidebar + Items Grid -->
        <div class="flex flex-col lg:flex-row gap-6">
            <!-- Left Sidebar - Filters -->
            <aside class="w-full lg:w-64 flex-shrink-0">
                <div class="bg-surface-light dark:bg-surface-dark rounded-2xl border-2 border-[#e9e8ce] dark:border-[#3e3d2a] p-5 sticky top-24 shadow-md">
                    <!-- Filter Header -->
                    <div class="flex items-center justify-between mb-5 pb-4 border-b-2 border-[#e9e8ce] dark:border-[#3e3d2a]">
                        <div class="flex items-center gap-2">
                            <span class="material-symbols-outlined text-primary text-xl">tune</span>
                            <h3 class="font-black text-lg text-text-main dark:text-white">Filters</h3>
                        </div>
                        <button id="resetFilters" class="text-primary hover:opacity-80 text-xs font-bold transition-all">
                            Reset All
                        </button>
                    </div>

                    <!-- Items Count -->
                    <div class="mb-5 p-3 bg-primary/10 border border-primary/30 rounded-xl">
                        <p class="text-sm font-bold text-text-main dark:text-white text-center">
                            <span id="itemCount"><?php echo count($items_to_show); ?></span> Items Found
                        </p>
                    </div>

                    <!-- Sort By -->
                    <div class="mb-5">
                        <label class="block text-xs font-bold text-text-main dark:text-white mb-2">SORT BY</label>
                        <select id="sortSelect" class="w-full bg-black text-primary border-2 border-primary rounded-xl px-4 py-2.5 text-sm font-bold focus:ring-2 focus:ring-primary outline-none transition-all cursor-pointer">
                            <option value="recommended">⭐ Recommended</option>
                            <option value="price-low">↑ Price: Low to High</option>
                            <option value="price-high">↓ Price: High to Low</option>
                            <option value="rating">⭐ Top Rated</option>
                            <option value="distance">📍 Nearest First</option>
                        </select>
                    </div>

                    <!-- Price Range -->
                    <div class="mb-5">
                        <label class="block text-xs font-bold text-text-main dark:text-white mb-2">PRICE RANGE</label>
                        <select id="priceFilter" class="w-full bg-surface-light dark:bg-surface-dark border-2 border-[#e9e8ce] dark:border-[#3e3d2a] rounded-xl px-4 py-2.5 text-sm font-bold focus:ring-2 focus:ring-primary outline-none transition-all cursor-pointer">
                            <option value="all">All Prices</option>
                            <option value="0-10">₹0 - ₹10/day</option>
                            <option value="10-25">₹10 - ₹25/day</option>
                            <option value="25-50">₹25 - ₹50/day</option>
                            <option value="50+">₹50+/day</option>
                        </select>
                    </div>

                    <!-- Condition -->
                    <div class="mb-5">
                        <label class="block text-xs font-bold text-text-main dark:text-white mb-3">CONDITION</label>
                        <div class="space-y-2">
                            <label class="flex items-center gap-2 cursor-pointer group">
                                <input type="checkbox" class="condition-filter w-4 h-4 rounded border-2 border-primary text-primary focus:ring-primary cursor-pointer" value="new">
                                <span class="text-sm font-medium group-hover:text-primary transition-colors">New</span>
                            </label>
                            <label class="flex items-center gap-2 cursor-pointer group">
                                <input type="checkbox" class="condition-filter w-4 h-4 rounded border-2 border-primary text-primary focus:ring-primary cursor-pointer" value="like-new">
                                <span class="text-sm font-medium group-hover:text-primary transition-colors">Like New</span>
                            </label>
                            <label class="flex items-center gap-2 cursor-pointer group">
                                <input type="checkbox" class="condition-filter w-4 h-4 rounded border-2 border-primary text-primary focus:ring-primary cursor-pointer" value="good">
                                <span class="text-sm font-medium group-hover:text-primary transition-colors">Good</span>
                            </label>
                            <label class="flex items-center gap-2 cursor-pointer group">
                                <input type="checkbox" class="condition-filter w-4 h-4 rounded border-2 border-primary text-primary focus:ring-primary cursor-pointer" value="fair">
                                <span class="text-sm font-medium group-hover:text-primary transition-colors">Fair</span>
                            </label>
                        </div>
                    </div>

                    <!-- Rating -->
                    <div class="mb-5">
                        <label class="block text-xs font-bold text-text-main dark:text-white mb-3">MINIMUM RATING</label>
                        <div class="space-y-2">
                            <label class="flex items-center gap-2 cursor-pointer group">
                                <input type="radio" name="rating" class="rating-filter w-4 h-4 border-2 border-primary text-primary focus:ring-primary cursor-pointer" value="all" checked>
                                <span class="text-sm font-medium group-hover:text-primary transition-colors">All Ratings</span>
                            </label>
                            <label class="flex items-center gap-2 cursor-pointer group">
                                <input type="radio" name="rating" class="rating-filter w-4 h-4 border-2 border-primary text-primary focus:ring-primary cursor-pointer" value="4">
                                <span class="text-sm font-medium group-hover:text-primary transition-colors">⭐ 4.0+</span>
                            </label>
                            <label class="flex items-center gap-2 cursor-pointer group">
                                <input type="radio" name="rating" class="rating-filter w-4 h-4 border-2 border-primary text-primary focus:ring-primary cursor-pointer" value="4.5">
                                <span class="text-sm font-medium group-hover:text-primary transition-colors">⭐ 4.5+</span>
                            </label>
                        </div>
                    </div>

                    <!-- Distance -->
                    <div class="mb-5">
                        <label class="block text-xs font-bold text-text-main dark:text-white mb-2">MAX DISTANCE</label>
                        <select id="distanceFilter" class="w-full bg-surface-light dark:bg-surface-dark border-2 border-[#e9e8ce] dark:border-[#3e3d2a] rounded-xl px-4 py-2.5 text-sm font-bold focus:ring-2 focus:ring-primary outline-none transition-all cursor-pointer">
                            <option value="all">Any Distance</option>
                            <option value="5">Within 5 km</option>
                            <option value="10">Within 10 km</option>
                            <option value="20">Within 20 km</option>
                            <option value="50">Within 50 km</option>
                        </select>
                    </div>

                    <!-- Availability -->
                    <div class="mb-5">
                        <label class="block text-xs font-bold text-text-main dark:text-white mb-3">AVAILABILITY</label>
                        <div class="space-y-2">
                            <label class="flex items-center gap-2 cursor-pointer group">
                                <input type="checkbox" class="availability-filter w-4 h-4 rounded border-2 border-primary text-primary focus:ring-primary cursor-pointer" value="instant">
                                <span class="text-sm font-medium group-hover:text-primary transition-colors flex items-center gap-1">
                                    <span class="material-symbols-outlined text-sm">bolt</span> Instant Booking
                                </span>
                            </label>
                            <label class="flex items-center gap-2 cursor-pointer group">
                                <input type="checkbox" class="availability-filter w-4 h-4 rounded border-2 border-primary text-primary focus:ring-primary cursor-pointer" value="verified">
                                <span class="text-sm font-medium group-hover:text-primary transition-colors flex items-center gap-1">
                                    <span class="material-symbols-outlined text-sm">verified</span> Verified Owner
                                </span>
                            </label>
                        </div>
                    </div>

                    <!-- Clear Filters Button -->
                    <button id="clearAllFilters" class="w-full bg-primary hover:opacity-90 text-black font-bold py-3 rounded-xl transition-all shadow-md flex items-center justify-center gap-2">
                        <span class="material-symbols-outlined text-lg">refresh</span>
                        Reset Filters
                    </button>
                </div>
            </aside>

            <!-- Right - Items Grid & Controls -->
            <div class="flex-1">
                <!-- Top Controls Bar -->
                <div class="flex flex-wrap justify-between items-center mb-6 gap-4 bg-surface-light dark:bg-surface-dark rounded-xl p-4 border border-[#e9e8ce] dark:border-[#3e3d2a]">
                    <p class="font-bold text-text-main dark:text-white text-sm">
                        Showing <span id="visibleCount"><?php echo count($items_to_show); ?></span> of <span id="totalCount"><?php echo count($items_to_show); ?></span>
                    </p>
                    
                    <!-- View Toggle -->
                    <div class="flex bg-white dark:bg-[#2d2c18] border-2 border-[#e9e8ce] dark:border-[#3e3d2a] rounded-xl overflow-hidden">
                        <button id="gridView" class="px-3 py-2 bg-primary text-black transition-all" title="Grid View">
                            <span class="material-symbols-outlined text-lg">grid_view</span>
                        </button>
                        <button id="listView" class="px-3 py-2 hover:bg-[#e9e8ce] dark:hover:bg-[#3e3d2a] transition-all" title="List View">
                            <span class="material-symbols-outlined text-lg">view_list</span>
                        </button>
                    </div>
                </div>

        <!-- Items Grid with Enhanced Cards -->
        <div id="itemsGrid" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6">
            <?php foreach($items_to_show as $index => $item): ?>
            <a href="item-details.php?id=<?php echo $item['id']; ?>" 
               class="item-card block group bg-surface-light dark:bg-surface-dark rounded-2xl overflow-hidden shadow-md hover:shadow-2xl transition-all duration-300 border-2 border-transparent hover:border-primary transform hover:-translate-y-1"
               data-name="<?php echo strtolower(htmlspecialchars($item['name'])); ?>"
               data-price="<?php echo $item['price']; ?>"
               data-rating="4.<?php echo rand(5, 9); ?>"
               data-images='<?php echo json_encode($item['all_images'] ?? [(strpos($item['img'], 'uploads/') === 0 ? $item['img'] : 'https://source.unsplash.com/random/400x300?' . urlencode($item['img']) . '&sig=' . $index)]); ?>'>
                
                <!-- Image Container -->
                <div class="relative aspect-[4/3] overflow-hidden bg-gray-100 dark:bg-[#2d2c18]">
                    <img class="product-image object-cover w-full h-full group-hover:scale-110 transition-transform duration-700" 
                         src="<?php echo (strpos($item['img'], 'uploads/') === 0) ? $item['img'] : 'https://source.unsplash.com/random/400x300?' . urlencode($item['img']) . '&sig=' . $index; ?>" 
                         alt="<?php echo htmlspecialchars($item['name']); ?>">
                    
                    <!-- Dark Overlay on Hover -->
                    <div class="absolute inset-0 bg-gradient-to-t from-black/60 via-transparent to-transparent opacity-0 group-hover:opacity-100 transition-opacity duration-300"></div>
                    
                    <!-- Image Counter -->
                    <?php if (!empty($item['all_images']) && count($item['all_images']) > 1): ?>
                    <div class="absolute bottom-3 right-3 z-10 bg-black/80 backdrop-blur-sm text-primary text-xs font-bold px-3 py-1 rounded-full opacity-0 group-hover:opacity-100 transition-opacity border border-primary/30">
                        <span class="material-symbols-outlined text-xs inline mr-1">photo_library</span>
                        1/<?php echo count($item['all_images']); ?>
                    </div>
                    <?php endif; ?>

                    <!-- Favorite Button -->
                    <button class="absolute top-3 right-3 p-2 bg-white/95 backdrop-blur-sm rounded-full text-text-main hover:bg-primary hover:scale-110 transition-all shadow-lg z-10">
                        <span class="material-symbols-outlined text-xl">favorite</span>
                    </button>
                    
                    <!-- Distance Badge -->
                    <div class="absolute bottom-3 left-3 bg-primary text-black text-xs font-black px-3 py-1.5 rounded-full flex items-center gap-1 shadow-lg">
                        <span class="material-symbols-outlined text-sm">location_on</span>
                        <?php echo rand(1, 10); ?> km
                    </div>
                    
                    <!-- Premium Badge for expensive items -->
                    <?php if ($item['price'] > 40): ?>
                    <div class="absolute top-3 left-3 bg-black/80 backdrop-blur-sm border border-primary text-primary text-xs font-black px-2.5 py-1 rounded-full">
                        ⭐ PREMIUM
                    </div>
                    <?php endif; ?>
                </div>
                
                <!-- Card Content -->
                <div class="p-4">
                    <!-- Title & Rating -->
                    <div class="flex justify-between items-start mb-3">
                        <div class="flex-1 pr-2">
                            <h3 class="font-black text-base leading-tight text-text-main dark:text-white line-clamp-2 group-hover:text-primary transition-colors">
                                <?php echo htmlspecialchars($item['name']); ?>
                            </h3>
                            <p class="text-xs text-text-muted dark:text-gray-400 mt-1 flex items-center gap-1">
                                <span class="material-symbols-outlined text-xs">category</span>
                                <?php echo $current_cat['title']; ?>
                            </p>
                        </div>
                        <div class="flex items-center gap-1 bg-primary/10 border border-primary/30 text-xs font-bold px-2 py-1 rounded-lg">
                            <span class="material-symbols-outlined text-sm text-primary">star</span>
                            <span class="text-text-main dark:text-white">4.<?php echo rand(5, 9); ?></span>
                        </div>
                    </div>
                    
                    <!-- Divider -->
                    <div class="h-px bg-gradient-to-r from-transparent via-[#e9e8ce] dark:via-[#3e3d2a] to-transparent my-3"></div>
                    
                    <!-- Price & Owner -->
                    <div class="flex items-center justify-between">
                        <div>
                            <div class="flex items-baseline gap-1">
                                <span class="text-2xl font-black text-text-main dark:text-white">₹<?php echo $item['price']; ?></span>
                                <span class="text-xs text-text-muted font-bold">/ day</span>
                            </div>
                            <p class="text-xs text-text-muted mt-0.5">Best price</p>
                        </div>
                        <?php 
                        $initial = "R";
                        if (isset($item['owner_name']) && !empty($item['owner_name'])) {
                            $initial = strtoupper(substr($item['owner_name'], 0, 1));
                        }
                        ?>
                        <div class="relative">
                            <div class="w-11 h-11 rounded-xl bg-primary flex items-center justify-center text-black text-sm font-black border-2 border-primary/30 shadow-md transform group-hover:scale-110 transition-all">
                                <?php echo $initial; ?>
                            </div>
                            <!-- Online Status -->
                            <div class="absolute -bottom-0.5 -right-0.5 w-3.5 h-3.5 bg-green-500 rounded-full border-2 border-surface-light dark:border-surface-dark"></div>
                        </div>
                    </div>
                </div>
            </a>
            <?php endforeach; ?>
            
            <!-- Empty State -->
            <?php if (empty($items_to_show)): ?>
                 <div class="col-span-full text-center py-20 bg-surface-light dark:bg-surface-dark rounded-2xl border-2 border-dashed border-[#e9e8ce] dark:border-[#3e3d2a]">
                     <div class="inline-block p-6 bg-primary/10 rounded-2xl mb-4">
                         <span class="material-symbols-outlined text-7xl text-primary">inventory_2</span>
                     </div>
                     <h3 class="text-2xl font-black text-text-main dark:text-white mb-2">No Items Available</h3>
                     <p class="text-text-muted dark:text-gray-400 mb-6">Be the first to list an item in this category!</p>
                     <button class="bg-primary hover:opacity-90 text-black font-black px-8 py-3 rounded-xl shadow-lg transition-all transform hover:scale-105">
                         <span class="material-symbols-outlined inline mr-2">add_circle</span>
                         List Your Item
                     </button>
                 </div>
            <?php endif; ?>
         </div>
         
         <!-- No Results Message (Hidden by default) -->
         <div id="noResults" class="hidden text-center py-20 bg-surface-light dark:bg-surface-dark rounded-2xl border-2 border-dashed border-[#e9e8ce] dark:border-[#3e3d2a]">
             <div class="inline-block p-6 bg-gray-100 dark:bg-[#2d2c18] rounded-2xl mb-4">
                 <span class="material-symbols-outlined text-7xl text-gray-400">search_off</span>
             </div>
             <h3 class="text-2xl font-black text-text-main dark:text-white mb-2">No Matching Items</h3>
             <p class="text-text-muted dark:text-gray-400 mb-6">Try adjusting your search or filters</p>
             <button id="clearFiltersBtn" class="bg-primary hover:opacity-90 text-black font-bold px-6 py-3 rounded-xl transition-all">
                 Clear All Filters
             </button>
         </div>
            </div><!-- Close flex-1 div (Right Items Grid) -->
        </div><!-- Close 2-column layout div -->

        <script>
        // Image Gallery Functionality
        document.querySelectorAll('.item-card').forEach(card => {
            const images = JSON.parse(card.dataset.images);
            if (!images || images.length <= 1) return;

            const imgElement = card.querySelector('.product-image');
            const counterElement = card.querySelector('.group-hover\\:opacity-100');
            let interval = null;
            let currentIdx = 0;

            card.addEventListener('mouseenter', () => {
                interval = setInterval(() => {
                    currentIdx = (currentIdx + 1) % images.length;
                    imgElement.src = images[currentIdx];
                    if (counterElement) {
                        const match = counterElement.textContent.match(/\/(\d+)/);
                        if (match) {
                            counterElement.innerHTML = `<span class="material-symbols-outlined text-xs inline mr-1">photo_library</span>${currentIdx + 1}/${match[1]}`;
                        }
                    }
                }, 1200);
            });

            card.addEventListener('mouseleave', () => {
                clearInterval(interval);
                currentIdx = 0;
                imgElement.src = images[0];
                if (counterElement) {
                    const match = counterElement.textContent.match(/\/(\d+)/);
                    if (match) {
                        counterElement.innerHTML = `<span class="material-symbols-outlined text-xs inline mr-1">photo_library</span>1/${match[1]}`;
                    }
                }
            });
        });
        
        // Advanced Search & Filter System
        const searchInput = document.getElementById('searchInput');
        const clearSearchBtn = document.getElementById('clearSearch');
        const sortSelect = document.getElementById('sortSelect');
        const priceFilter = document.getElementById('priceFilter');
        const resetBtn = document.getElementById('resetFilters');
        const clearFiltersBtn = document.getElementById('clearFiltersBtn');
        const itemCount = document.getElementById('itemCount');
        const itemsGrid = document.getElementById('itemsGrid');
        const noResults = document.getElementById('noResults');
        const allCards = Array.from(document.querySelectorAll('.item-card'));
        
        function filterAndSort() {
            const searchTerm = searchInput.value.toLowerCase().trim();
            const sortValue = sortSelect.value;
            const priceRange = priceFilter.value;
            
            let visibleCards = allCards.filter(card => {
                // Search filter
                const name = card.dataset.name;
                const matchesSearch = searchTerm === '' || name.includes(searchTerm);
                if (!matchesSearch) return false;
                
                // Price filter
                const price = parseFloat(card.dataset.price);
                let matchesPrice = true;
                
                if (priceRange !== 'all') {
                    if (priceRange === '0-10') matchesPrice = price <= 10;
                    else if (priceRange === '10-25') matchesPrice = price > 10 && price <= 25;
                    else if (priceRange === '25-50') matchesPrice = price > 25 && price <= 50;
                    else if (priceRange === '50+') matchesPrice = price > 50;
                }
                
                return matchesPrice;
            });
            
            // Sort
            if (sortValue === 'price-low') {
                visibleCards.sort((a, b) => parseFloat(a.dataset.price) - parseFloat(b.dataset.price));
            } else if (sortValue === 'price-high') {
                visibleCards.sort((a, b) => parseFloat(b.dataset.price) - parseFloat(a.dataset.price));
            } else if (sortValue === 'rating') {
                visibleCards.sort((a, b) => parseFloat(b.dataset.rating) - parseFloat(a.dataset.rating));
            }
            
            // Hide all cards first
            allCards.forEach(card => card.classList.add('hidden'));
            
            // Show filtered and sorted cards
            visibleCards.forEach(card => {
                card.classList.remove('hidden');
                itemsGrid.appendChild(card);
            });
            
            // Update count and show/hide no results
            itemCount.textContent = visibleCards.length;
            
            if (visibleCards.length === 0) {
                noResults.classList.remove('hidden');
            } else {
                noResults.classList.add('hidden');
            }
        }
        
        // Event Listeners
        searchInput.addEventListener('input', (e) => {
            clearSearchBtn.classList.toggle('hidden', e.target.value === '');
            filterAndSort();
        });
        
        clearSearchBtn.addEventListener('click', () => {
            searchInput.value = '';
            clearSearchBtn.classList.add('hidden');
            filterAndSort();
        });
        
        sortSelect.addEventListener('change', filterAndSort);
        priceFilter.addEventListener('change', filterAndSort);
        
        const resetFunction = () => {
            searchInput.value = '';
            sortSelect.value = 'recommended';
            priceFilter.value = 'all';
            clearSearchBtn.classList.add('hidden');
            filterAndSort();
        };
        
        resetBtn.addEventListener('click', resetFunction);
        clearFiltersBtn.addEventListener('click', resetFunction);
        
        // View Toggle
        const gridViewBtn = document.getElementById('gridView');
        const listViewBtn = document.getElementById('listView');
        
        gridViewBtn.addEventListener('click', () => {
            itemsGrid.className = 'grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6';
            gridViewBtn.classList.add('bg-primary', 'text-black');
            gridViewBtn.classList.remove('hover:bg-[#e9e8ce]', 'dark:hover:bg-[#3e3d2a]');
            listViewBtn.classList.remove('bg-primary', 'text-black');
            listViewBtn.classList.add('hover:bg-[#e9e8ce]', 'dark:hover:bg-[#3e3d2a]');
        });
        
        listViewBtn.addEventListener('click', () => {
            itemsGrid.className = 'grid grid-cols-1 gap-4';
            listViewBtn.classList.add('bg-primary', 'text-black');
            listViewBtn.classList.remove('hover:bg-[#e9e8ce]', 'dark:hover:bg-[#3e3d2a]');
            gridViewBtn.classList.remove('bg-primary', 'text-black');
            gridViewBtn.classList.add('hover:bg-[#e9e8ce]', 'dark:hover:bg-[#3e3d2a]');
        });
        </script>
        
    <?php else: ?>
        <div class="mt-20 text-center">
            <div class="w-24 h-24 rounded-full bg-red-100 flex items-center justify-center mx-auto mb-6">
                <span class="material-symbols-outlined text-5xl text-red-500">error</span>
            </div>
            <h1 class="text-3xl font-bold mb-4">Category Not Found</h1>
            <p class="text-text-muted mb-8">The category you are looking for does not exist.</p>
            <a href="dashboard.php" class="bg-black text-white px-8 py-3 rounded-full font-bold">Back to Dashboard</a>
        </div>
    <?php endif; ?>
</main>
<!-- Footer -->
<footer class="bg-surface-light dark:bg-surface-dark border-t border-[#e9e8ce] dark:border-[#3e3d2a] pt-16 pb-8 px-4 md:px-10">
<div class="max-w-[1400px] mx-auto">
<div class="grid grid-cols-1 md:grid-cols-4 gap-12 mb-12">
<div class="col-span-1 md:col-span-1">
<div class="flex items-center gap-2 text-text-main dark:text-white mb-6">
<div class="size-6 text-primary">
<svg class="w-full h-full" fill="none" viewbox="0 0 48 48" xmlns="http://www.w3.org/2000/svg">
<path d="M36.7273 44C33.9891 44 31.6043 39.8386 30.3636 33.69C29.123 39.8386 26.7382 44 24 44C21.2618 44 18.877 39.8386 17.6364 33.69C16.3957 39.8386 14.0109 44 11.2727 44C7.25611 44 4 35.0457 4 24C4 12.9543 7.25611 4 11.2727 4C14.0109 4 16.3957 8.16144 17.6364 14.31C18.877 8.16144 21.2618 4 24 4C26.7382 4 29.123 8.16144 30.3636 14.31C31.6043 8.16144 33.9891 4 36.7273 4C40.7439 4 44 12.9543 44 24C44 35.0457 40.7439 44 36.7273 44Z" fill="currentColor"></path>
</svg>
</div>
<h2 class="text-lg font-bold tracking-tight">RendeX</h2>
</div>
<p class="text-sm text-text-muted dark:text-gray-400">
                            The safest peer-to-peer rental marketplace. Own less, experience more.
                        </p>
</div>
<div>
<h4 class="font-bold mb-4">RendeX</h4>
<ul class="space-y-3 text-sm text-text-muted dark:text-gray-400">
<li><a class="hover:text-primary transition-colors" href="#">About Us</a></li>
<li><a class="hover:text-primary transition-colors" href="#">Careers</a></li>
<li><a class="hover:text-primary transition-colors" href="#">Press</a></li>
<li><a class="hover:text-primary transition-colors" href="#">Blog</a></li>
</ul>
</div>
<div>
<h4 class="font-bold mb-4">Support</h4>
<ul class="space-y-3 text-sm text-text-muted dark:text-gray-400">
<li><a class="hover:text-primary transition-colors" href="#">Help Center</a></li>
<li><a class="hover:text-primary transition-colors" href="#">Safety &amp; Trust</a></li>
<li><a class="hover:text-primary transition-colors" href="#">Insurance</a></li>
<li><a class="hover:text-primary transition-colors" href="#">Dispute Resolution</a></li>
</ul>
</div>
<div>
<h4 class="font-bold mb-4">Legal</h4>
<ul class="space-y-3 text-sm text-text-muted dark:text-gray-400">
<li><a class="hover:text-primary transition-colors" href="#">Terms of Service</a></li>
<li><a class="hover:text-primary transition-colors" href="#">Privacy Policy</a></li>
<li><a class="hover:text-primary transition-colors" href="#">Cookie Policy</a></li>
</ul>
</div>
</div>
<div class="flex flex-col md:flex-row justify-between items-center pt-8 border-t border-[#e9e8ce] dark:border-[#3e3d2a] gap-4">
<p class="text-sm text-text-muted dark:text-gray-500">© 2024 RendeX Inc. All rights reserved.</p>
<div class="flex gap-4">
<a class="text-text-muted hover:text-primary transition-colors" href="#"><span class="material-symbols-outlined">public</span></a>
<a class="text-text-muted hover:text-primary transition-colors" href="#"><span class="material-symbols-outlined">alternate_email</span></a>
</div>
</div>
</div>
</footer>
</div>
</body></html>