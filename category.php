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

    // Load Dynamic Items
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
                WHERE i.category = ? AND i.admin_status = 'approved' AND (i.active_until IS NULL OR i.active_until > NOW())
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

                // Deduplicate: Check if exactly the same item (by title and owner) is already in the list
                $is_duplicate = false;
                foreach ($items_to_show as $existing_item) {
                    if ($existing_item['title'] === $item['title'] && 
                        $existing_item['owner_id'] === $item['owner_id'] && 
                        $existing_item['price'] == $item['price']) { // Price loose check
                        $is_duplicate = true;
                        break;
                    }
                }

                if (!$is_duplicate) {
                    $items_to_show[] = $item;
                }
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

            if (isset($d_item['status']) && in_array($d_item['status'], ['Active', 'Unavailable'])) {
                // Check expiry
                if (isset($d_item['active_until']) && strtotime($d_item['active_until']) < time()) continue;
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



        <!-- Redesigned Full Width Banner -->
        <section class="mt-8 mb-12 rounded-[2.5rem] bg-black overflow-hidden shadow-2xl border border-gray-800 relative w-full">
                <div class="relative p-12 md:p-16 flex flex-col justify-center min-h-[300px] text-left">
                    <div class="absolute right-0 top-0 h-full w-1/3 bg-gradient-to-l from-primary/10 to-transparent"></div>
                    <div class="flex items-center gap-6 mb-4 relative z-10">
                        <div class="w-20 h-20 rounded-2xl bg-primary flex items-center justify-center shadow-lg shadow-primary/20">
                            <span class="material-symbols-outlined text-5xl text-black"><?php echo $current_cat['icon']; ?></span>
                        </div>
                        <div>
                            <h1 class="text-5xl md:text-6xl font-black tracking-tighter text-white uppercase leading-none">
                                <?php echo $current_cat['title']; ?>
                            </h1>
                        </div>
                    </div>
                    <p class="text-primary font-bold text-xl max-w-2xl mt-4 relative z-10 pl-1">
                        Premium selection of <?php echo strtolower($current_cat['title']); ?>. Verified & Ready for use.
                    </p>
                </div>
        </section>

        <!-- Main Content with Sidebar -->
        <div class="flex flex-col lg:flex-row gap-8 mb-20">
            <!-- Sidebar Filters -->
            <!-- Sidebar Filters -->
            <aside class="w-full lg:w-1/4 lg:min-w-[300px]">
                <div class="bg-[#121212]/95 backdrop-blur-xl text-white rounded-[2rem] p-8 border border-white/5 sticky top-28 shadow-2xl ring-1 ring-white/10">
                    <div class="flex items-center justify-between mb-6 border-b border-gray-800 pb-4">
                        <h3 class="font-black text-xl text-primary">Filters</h3>
                        <button class="text-xs font-bold text-gray-500 hover:text-primary transition-colors tracking-wider">RESET</button>
                    </div>
                    
                    <!-- Price Range -->
                    <div class="mb-8">
                        <h4 class="font-bold text-sm mb-4 text-primary flex items-center gap-2">
                            <span class="material-symbols-outlined text-lg">payments</span> Price Range
                        </h4>
                        <div class="flex items-center gap-2 mb-4">
                            <div class="relative w-full">
                                <span class="absolute left-3 top-2.5 text-gray-500 text-xs">₹</span>
                                <input type="number" placeholder="Min" class="w-full pl-6 bg-[#1e2019] border border-gray-800 rounded-xl px-3 py-2 text-sm font-bold focus:ring-1 focus:ring-primary focus:border-primary outline-none transition-all placeholder-gray-600 text-white">
                            </div>
                            <span class="text-gray-600 font-bold">-</span>
                            <div class="relative w-full">
                                <span class="absolute left-3 top-2.5 text-gray-500 text-xs">₹</span>
                                <input type="number" placeholder="Max" class="w-full pl-6 bg-[#1e2019] border border-gray-800 rounded-xl px-3 py-2 text-sm font-bold focus:ring-1 focus:ring-primary focus:border-primary outline-none transition-all placeholder-gray-600 text-white">
                            </div>
                        </div>
                    </div>

                    <!-- Availability -->
                    <div class="mb-8">
                        <h4 class="font-bold text-sm mb-4 text-primary flex items-center gap-2">
                            <span class="material-symbols-outlined text-lg">calendar_month</span> Availability
                        </h4>
                        <div class="space-y-3">
                            <label class="flex items-center gap-3 cursor-pointer group">
                                <div class="w-5 h-5 rounded border-2 border-gray-700 flex items-center justify-center transition-colors group-hover:border-primary bg-[#1e2019]">
                                    <input type="checkbox" class="appearance-none peer">
                                    <span class="material-symbols-outlined text-[14px] opacity-0 peer-checked:opacity-100 text-primary font-bold">check</span>
                                </div>
                                <span class="text-sm font-medium text-gray-400 group-hover:text-white transition-colors">Available Today</span>
                            </label>
                            <label class="flex items-center gap-3 cursor-pointer group">
                                <div class="w-5 h-5 rounded border-2 border-gray-700 flex items-center justify-center transition-colors group-hover:border-primary bg-[#1e2019]">
                                    <input type="checkbox" class="appearance-none peer">
                                    <span class="material-symbols-outlined text-[14px] opacity-0 peer-checked:opacity-100 text-primary font-bold">check</span>
                                </div>
                                <span class="text-sm font-medium text-gray-400 group-hover:text-white transition-colors">Next 3 Days</span>
                            </label>
                        </div>
                    </div>



                    <!-- Condition (New) -->
                    <div class="mb-8">
                        <h4 class="font-bold text-sm mb-4 text-primary flex items-center gap-2">
                            <span class="material-symbols-outlined text-lg">verified</span> Condition
                        </h4>
                        <div class="space-y-3">
                            <label class="flex items-center gap-3 cursor-pointer group">
                                <div class="w-5 h-5 rounded-full border-2 border-gray-700 flex items-center justify-center transition-colors group-hover:border-primary bg-[#1e2019]">
                                    <input type="checkbox" class="appearance-none peer">
                                    <div class="w-2.5 h-2.5 rounded-full bg-primary opacity-0 peer-checked:opacity-100 transition-opacity"></div>
                                </div>
                                <span class="text-sm font-medium text-gray-400 group-hover:text-white transition-colors">Like New / Excellent</span>
                            </label>
                            <label class="flex items-center gap-3 cursor-pointer group">
                                <div class="w-5 h-5 rounded-full border-2 border-gray-700 flex items-center justify-center transition-colors group-hover:border-primary bg-[#1e2019]">
                                    <input type="checkbox" class="appearance-none peer">
                                    <div class="w-2.5 h-2.5 rounded-full bg-primary opacity-0 peer-checked:opacity-100 transition-opacity"></div>
                                </div>
                                <span class="text-sm font-medium text-gray-400 group-hover:text-white transition-colors">Good / Fair</span>
                            </label>
                        </div>
                    </div>

                    <!-- Handover (New) -->
                    <div class="mb-8">
                        <h4 class="font-bold text-sm mb-4 text-primary flex items-center gap-2">
                            <span class="material-symbols-outlined text-lg">local_shipping</span> Handover
                        </h4>
                        <div class="flex gap-2">
                             <button class="flex-1 py-2 px-3 rounded-lg border border-gray-700 bg-[#1e2019] text-xs font-bold text-gray-400 hover:border-primary hover:text-white transition-all focus:border-primary focus:text-white focus:bg-primary/10">
                                 Delivery
                             </button>
                             <button class="flex-1 py-2 px-3 rounded-lg border border-gray-700 bg-[#1e2019] text-xs font-bold text-gray-400 hover:border-primary hover:text-white transition-all focus:border-primary focus:text-white focus:bg-primary/10">
                                 Pickup
                             </button>
                        </div>
                    </div>

                    <!-- Distance -->
                    <div class="mb-8">
                        <div class="flex justify-between mb-4">
                            <h4 class="font-bold text-sm flex items-center gap-2 text-primary">
                                <span class="material-symbols-outlined text-lg">location_on</span> Distance
                            </h4>
                            <span class="text-xs font-bold bg-primary text-black px-2 py-0.5 rounded">15 km</span>
                        </div>
                        <input type="range" class="w-full h-1.5 bg-gray-800 rounded-lg appearance-none cursor-pointer [&::-webkit-slider-thumb]:appearance-none [&::-webkit-slider-thumb]:w-4 [&::-webkit-slider-thumb]:h-4 [&::-webkit-slider-thumb]:bg-primary [&::-webkit-slider-thumb]:rounded-full [&::-webkit-slider-thumb]:shadow-lg hover:[&::-webkit-slider-thumb]:scale-110 transition-all">
                        <div class="flex justify-between text-[10px] font-bold text-gray-500 mt-2">
                            <span>1 km</span>
                            <span>50 km</span>
                        </div>
                    </div>

                    <button id="apply-filters-btn" onclick="applyFilters()" class="w-full bg-primary text-black font-black py-4 rounded-full hover:bg-white hover:text-black transition-all shadow-lg hover:shadow-primary/25 active:scale-95 uppercase tracking-wide text-sm flex items-center justify-center gap-2">
                        <span>Apply Filters</span>
                    </button>
                </div>
            </aside>

            <!-- Items Grid -->
            <div class="flex-1 w-full">

                <div class="flex justify-between items-center mb-6 px-1">
                    <p class="font-bold text-text-muted dark:text-gray-400 text-sm">Showing <?php echo count($items_to_show); ?> items</p>
                    <div class="flex items-center gap-2">
                        <span class="text-sm font-medium text-text-muted hidden sm:inline">Sort by:</span>
                        <select class="bg-transparent border-none text-sm font-bold focus:ring-0 cursor-pointer text-right py-0 pr-8 pl-0">
                             <option>Recommended</option>
                             <option>Price: Low to High</option>
                             <option>Price: High to Low</option>
                             <option>Distance: Nearest</option>
                        </select>
                    </div>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6">
                    <?php foreach($items_to_show as $index => $item): 
                        $distance = rand(1, 15); 
                        $avail_status = isset($item['availability_status']) ? strtolower($item['availability_status']) : '';
                        $main_status = isset($item['status']) ? strtolower($item['status']) : '';
                        
                        $is_rented = ($avail_status === 'rented' || $avail_status === 'unavailable' || $main_status === 'unavailable');
                    ?>
                    <a href="item-details.php?id=<?php echo $item['id']; ?>" 
                       class="item-card block group bg-surface-light dark:bg-surface-dark rounded-2xl p-3 shadow-sm hover:shadow-xl hover:shadow-gray-200/50 dark:hover:shadow-black/50 transition-all duration-300 border border-transparent hover:border-[#e9e8ce] dark:hover:border-[#3e3d2a] <?php echo $is_rented ? 'opacity-75 grayscale' : ''; ?>"
                       data-price="<?php echo $item['price']; ?>"
                       data-distance="<?php echo $distance; ?>"
                       data-images='<?php echo json_encode($item['all_images'] ?? [(strpos($item['img'], 'uploads/') === 0 ? $item['img'] : 'https://source.unsplash.com/random/400x300?' . urlencode($item['img']) . '&sig=' . $index)]); ?>'>
                        <div class="relative aspect-[4/3] rounded-xl overflow-hidden bg-gray-100 dark:bg-[#12120b]">
                            <img class="product-image object-cover w-full h-full group-hover:scale-105 transition-transform duration-700" 
                                 src="<?php echo (strpos($item['img'], 'uploads/') === 0) ? $item['img'] : 'https://source.unsplash.com/random/400x300?' . urlencode($item['img']) . '&sig=' . $index; ?>" 
                                 alt="<?php echo htmlspecialchars($item['name']); ?>">
                            
                            <?php if ($is_rented): ?>
                                <div class="absolute inset-0 bg-black/60 backdrop-blur-[2px] z-20 flex items-center justify-center">
                                    <span class="bg-red-600 text-white font-black text-xs uppercase tracking-widest px-4 py-2 rounded-full border-2 border-white shadow-xl rotate-[-10deg]">Rented</span>
                                </div>
                            <?php endif; ?>

                            <?php if (!empty($item['all_images']) && count($item['all_images']) > 1): ?>
                            <div class="absolute bottom-2 right-2 z-10 bg-black/60 backdrop-blur-md text-white text-[10px] font-bold px-2 py-1 rounded-full opacity-0 group-hover:opacity-100 transition-opacity">
                                1/<?php echo count($item['all_images']); ?>
                            </div>
                            <?php endif; ?>

                            <button class="absolute top-3 right-3 p-2 bg-white/95 rounded-full text-black hover:bg-primary hover:text-black transition-all scale-90 opacity-0 group-hover:opacity-100 group-hover:scale-100 shadow-sm">
                                <span class="material-symbols-outlined text-lg block">favorite</span>
                            </button>
                            <div class="absolute bottom-3 left-3 bg-white/90 dark:bg-black/80 backdrop-blur-sm text-xs font-bold px-2.5 py-1 rounded-full shadow-sm flex items-center gap-1">
                                <span class="material-symbols-outlined text-[14px]">location_on</span>
                                <?php echo $distance; ?> km
                            </div>
                        </div>
                        <div class="pt-4 px-2 pb-2">
                            <div class="flex justify-between items-start mb-1">
                                <h3 class="font-bold text-lg leading-tight truncate flex-1 pr-2"><?php echo htmlspecialchars($item['name']); ?></h3>
                                <div class="flex items-center gap-1 text-[10px] font-black bg-primary/10 text-text-main dark:text-primary px-2 py-1 rounded-full">
                                    <span class="material-symbols-outlined text-[12px] text-primary fill-current">star</span>
                                    4.<?php echo rand(5, 9); ?>
                                </div>
                            </div>
                            <p class="text-xs text-text-muted dark:text-gray-500 font-medium mb-4"><?php echo $current_cat['title']; ?></p>
                            
                            <div class="flex items-end justify-between border-t border-dashed border-gray-200 dark:border-gray-800 pt-3">
                                <div>
                                    <span class="text-xs text-text-muted block mb-0.5">Rent per day</span>
                                    <div class="flex items-baseline gap-1">
                                        <span class="text-xl font-black">₹<?php echo $item['price']; ?></span>
                                    </div>
                                </div>
                                <?php 
                                $initial = "R";
                                if (isset($item['owner_name']) && !empty($item['owner_name'])) {
                                    $initial = strtoupper(substr($item['owner_name'], 0, 1));
                                }
                                ?>
                                <div class="w-8 h-8 rounded-full bg-primary text-black flex items-center justify-center text-xs font-black border-2 border-white dark:border-[#23220f] shadow-sm" title="Owned by <?php echo htmlspecialchars($item['owner_name'] ?? 'RendeX'); ?>">
                                    <?php echo $initial; ?>
                                </div>
                            </div>
                        </div>
                    </a>
                    <?php endforeach; ?>
                    
                    <div id="no-items-msg" class="hidden col-span-full flex flex-col items-center justify-center py-20 text-center bg-gray-50 dark:bg-white/5 rounded-3xl border border-dashed border-gray-300 dark:border-gray-700">
                         <div class="w-20 h-20 rounded-full bg-gray-100 dark:bg-white/10 flex items-center justify-center mb-6">
                            <span class="material-symbols-outlined text-4xl text-text-muted">filter_alt_off</span>
                         </div>
                         <h3 class="text-xl font-bold mb-2">No items match your filters</h3>
                         <p class="text-text-muted mb-6 max-w-xs mx-auto">Try adjusting your price range or distance.</p>
                         <button onclick="window.location.reload()" class="bg-primary text-black font-bold px-8 py-3 rounded-xl hover:bg-black hover:text-white transition-colors">
                             Clear Filters
                         </button>
                    </div>

                    <?php if (empty($items_to_show)): ?>
                         <div class="col-span-full flex flex-col items-center justify-center py-20 text-center bg-gray-50 dark:bg-white/5 rounded-3xl border border-dashed border-gray-300 dark:border-gray-700">
                             <div class="w-20 h-20 rounded-full bg-gray-100 dark:bg-white/10 flex items-center justify-center mb-6">
                                <span class="material-symbols-outlined text-4xl text-text-muted">manage_search</span>
                             </div>
                             <h3 class="text-xl font-bold mb-2">No items found</h3>
                             <p class="text-text-muted mb-6 max-w-xs mx-auto">We couldn't find any items in this category matching your criteria.</p>
                             <button class="bg-primary text-black font-bold px-8 py-3 rounded-xl hover:bg-black hover:text-white transition-colors">
                                 List an Item
                             </button>
                         </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <script>
        function applyFilters() {
            const btn = document.getElementById('apply-filters-btn');
            const originalText = btn.innerHTML;
            
            // Get Filter Values
            const minPrice = parseFloat(document.querySelector('input[placeholder="Min"]').value) || 0;
            const maxPrice = parseFloat(document.querySelector('input[placeholder="Max"]').value) || Infinity;
            
            // Distance
            const distInput = document.querySelector('input[type="range"]');
            const maxDistance = distInput ? parseFloat(distInput.value) : 50;



            // Show loading state
            btn.disabled = true;
            btn.innerHTML = `
                <svg class="animate-spin h-5 w-5 text-black" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                </svg>
                Processing...
            `;
            
            // Simulate processing
            setTimeout(() => {
                let matchCount = 0;
                const items = document.querySelectorAll('.item-card');
                
                items.forEach(item => {
                    const price = parseFloat(item.dataset.price);
                    const dist = parseFloat(item.dataset.distance);
                    // Mock data for brand and rating since they aren't in dataset yet
                    // In a real app, you'd output these as data attributes too
                    const itemRating = 4.0 + (Math.random() * 1.0); 
                    
                    let isVisible = true;
                    
                    if (price < minPrice || price > maxPrice) isVisible = false;
                    if (dist > maxDistance) isVisible = false;
                    
                    item.style.display = isVisible ? 'block' : 'none';
                    if (isVisible) matchCount++;
                });

                // Show/Hide "No Items" message
                const noItemsMsg = document.getElementById('no-items-msg');
                if (noItemsMsg) {
                    noItemsMsg.classList.toggle('hidden', matchCount > 0);
                }

                btn.disabled = false;
                btn.innerHTML = originalText;
            }, 800);
        }


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
                        counterElement.textContent = `${currentIdx + 1}/${images.length}`;
                    }
                }, 1200);
            });

            card.addEventListener('mouseleave', () => {
                clearInterval(interval);
                currentIdx = 0;
                imgElement.src = images[0];
                if (counterElement) {
                    counterElement.textContent = `1/${images.length}`;
                }
            });
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