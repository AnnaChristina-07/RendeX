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

    // Specific Items Data (Static)
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
    
    // Add IDs to static items
    foreach ($static_items_data as $cat => &$items) {
        foreach ($items as &$item) {
            $item['id'] = md5($item['name']); // Generate deterministic ID
            $item['type'] = 'static';
        }
    }

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

    // 2. Add Static Items
    if (isset($static_items_data[$cat_slug])) {
        $items_to_show = array_merge($items_to_show, $static_items_data[$cat_slug]);
    }
    ?>

    <?php if ($current_cat): ?>
        <!-- Category Header -->
        <section class="mt-8 md:mt-12 rounded-[2rem] bg-surface-light dark:bg-surface-dark overflow-hidden shadow-sm border border-[#e9e8ce] dark:border-[#3e3d2a] relative">
             <div class="absolute inset-0 bg-primary/5 dark:bg-primary/5"></div>
             <div class="relative p-12 md:p-20 flex flex-col items-center text-center">
                 <div class="w-24 h-24 rounded-full bg-white dark:bg-[#1e2019] shadow-lg flex items-center justify-center mb-6">
                     <span class="material-symbols-outlined text-5xl text-primary"><?php echo $current_cat['icon']; ?></span>
                 </div>
                 <h1 class="text-4xl md:text-5xl font-black tracking-tight mb-4"><?php echo $current_cat['title']; ?></h1>
                 <p class="text-text-muted dark:text-gray-400 max-w-xl mx-auto text-lg">
                     Browse our wide selection of <?php echo strtolower($current_cat['title']); ?> available for rent in your area.
                 </p>
             </div>
        </section>

        <!-- Sort/Filter Toolbar -->
        <div class="flex flex-col md:flex-row justify-between items-center mt-12 mb-8 gap-4">
            <p class="font-bold text-text-muted dark:text-gray-400">Showing <?php echo count($items_to_show); ?> available items</p>
            <div class="flex gap-3">
                 <select class="bg-white dark:bg-[#1e2019] border border-[#e9e8ce] dark:border-[#3e3d2a] rounded-full px-6 py-2.5 text-sm font-bold focus:ring-2 focus:ring-primary outline-none">
                     <option>Sort by: Recommended</option>
                     <option>Price: Low to High</option>
                     <option>Price: High to Low</option>
                     <option>Distance: Nearest</option>
                 </select>
                 <button class="bg-white dark:bg-[#1e2019] border border-[#e9e8ce] dark:border-[#3e3d2a] rounded-full px-6 py-2.5 text-sm font-bold hover:bg-gray-50 dark:hover:bg-[#2d2c18] flex items-center gap-2">
                     <span class="material-symbols-outlined text-lg">tune</span> Filters
                 </button>
            </div>
        </div>

        <!-- Items Grid -->
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6">
            <?php foreach($items_to_show as $index => $item): ?>
            <a href="item-details.php?id=<?php echo $item['id']; ?>" 
               class="item-card block group bg-surface-light dark:bg-surface-dark rounded-xl p-3 shadow-sm hover:shadow-md transition-all border border-transparent hover:border-[#e9e8ce] dark:hover:border-[#3e3d2a]"
               data-images='<?php echo json_encode($item['all_images'] ?? [(strpos($item['img'], 'uploads/') === 0 ? $item['img'] : 'https://source.unsplash.com/random/400x300?' . urlencode($item['img']) . '&sig=' . $index)]); ?>'>
                <div class="relative aspect-[4/3] rounded-lg overflow-hidden bg-gray-200">
                    <img class="product-image object-cover w-full h-full group-hover:scale-105 transition-transform duration-500" 
                         src="<?php echo (strpos($item['img'], 'uploads/') === 0) ? $item['img'] : 'https://source.unsplash.com/random/400x300?' . urlencode($item['img']) . '&sig=' . $index; ?>" 
                         alt="<?php echo htmlspecialchars($item['name']); ?>">
                    
                    <?php if (!empty($item['all_images']) && count($item['all_images']) > 1): ?>
                    <div class="absolute bottom-2 right-2 z-10 bg-black/50 backdrop-blur-sm text-white text-[10px] font-bold px-2 py-0.5 rounded-full opacity-0 group-hover:opacity-100 transition-opacity">
                        1/<?php echo count($item['all_images']); ?>
                    </div>
                    <?php endif; ?>

                    <button class="absolute top-2 right-2 p-2 bg-white/90 rounded-full text-black hover:bg-primary hover:text-black transition-colors">
                        <span class="material-symbols-outlined text-xl block">favorite</span>
                    </button>
                    <div class="absolute bottom-2 left-2 bg-black/70 text-white text-xs font-bold px-2 py-1 rounded">
                        <?php echo rand(1, 10); ?> km away
                    </div>
                </div>
                <div class="pt-3 px-1">
                    <div class="flex justify-between items-start">
                        <div>
                            <h3 class="font-bold text-lg leading-tight truncate"><?php echo htmlspecialchars($item['name']); ?></h3>
                            <p class="text-sm text-text-muted dark:text-gray-400 mt-1"><?php echo $current_cat['title']; ?></p>
                        </div>
                        <div class="flex items-center gap-1 text-xs font-bold bg-[#f4f4e6] dark:bg-[#3e3d2a] px-2 py-1 rounded-full">
                            <span class="material-symbols-outlined text-sm text-yellow-600">star</span>
                            4.<?php echo rand(5, 9); ?>
                        </div>
                    </div>
                    <div class="flex items-end justify-between mt-4">
                        <div class="flex items-baseline gap-1">
                            <span class="text-xl font-bold">₹<?php echo $item['price']; ?></span>
                            <span class="text-sm text-text-muted">/ day</span>
                        </div>
                        <?php 
                        $initial = "R";
                        if (isset($item['owner_name']) && !empty($item['owner_name'])) {
                            $initial = strtoupper(substr($item['owner_name'], 0, 1));
                        }
                        ?>
                        <div class="w-8 h-8 rounded-full bg-primary flex items-center justify-center text-black text-[10px] font-bold border border-white shadow-sm">
                            <?php echo $initial; ?>
                        </div>
                    </div>
                </div>
            </a>
            <?php endforeach; ?>
            
            <?php if (empty($items_to_show)): ?>
                 <div class="col-span-full text-center py-20">
                     <p class="text-xl text-text-muted">No items found in this category yet.</p>
                     <button class="mt-4 bg-primary text-black font-bold px-6 py-2 rounded-full">Be the first to list!</button>
                 </div>
            <?php endif; ?>
        </div>

        <script>
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