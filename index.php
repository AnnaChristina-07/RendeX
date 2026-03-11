<?php
session_start();
require_once 'config/database.php';
?>
<!DOCTYPE html>
<html class="light" lang="en"><head>
<meta charset="utf-8"/>
<meta content="width=device-width, initial-scale=1.0" name="viewport"/>
<title>RendeX - Own Less. Experience More.</title>
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
<header class="sticky top-0 z-50 flex items-center justify-between border-b border-[#e9e8ce] dark:border-[#3e3d2a] bg-background-light/95 dark:bg-background-dark/95 backdrop-blur-sm px-6 py-4 lg:px-10">
<div class="flex items-center gap-8 w-full max-w-[1400px] mx-auto">
<a href="index.php" class="flex items-center gap-2 text-text-main dark:text-white">
<div class="size-8 text-primary">
<svg class="w-full h-full" fill="none" viewbox="0 0 48 48" xmlns="http://www.w3.org/2000/svg">
    <ellipse cx="14" cy="24" rx="10" ry="20" fill="currentColor" />
    <ellipse cx="24" cy="24" rx="10" ry="20" fill="currentColor" />
    <ellipse cx="34" cy="24" rx="10" ry="20" fill="currentColor" />
</svg>
</div>
<h2 class="text-xl font-bold tracking-tight">RendeX</h2>
</a>

<div class="hidden lg:flex items-center gap-6 ml-auto">
<nav class="flex gap-6">
<a class="text-sm font-medium hover:text-primary transition-colors" href="#how-it-works">How it Works</a>
<?php if (isset($_SESSION['user_id'])): ?>
    <a class="text-sm font-medium hover:text-primary transition-colors" href="dashboard.php">Dashboard</a>
    <a href="profile.php" class="w-10 h-10 rounded-full bg-primary flex items-center justify-center text-black text-sm font-black border-2 border-transparent hover:border-primary transition-all shadow-sm">
        <?php echo strtoupper(substr($_SESSION['user_name'], 0, 1)); ?>
    </a>
<?php else: ?>
    <a class="text-sm font-medium hover:text-primary transition-colors" href="login.php">Login</a>
    <a class="text-sm font-medium hover:text-primary transition-colors" href="signup.php">Sign Up</a>
    <a href="lend-item.php" class="bg-primary hover:bg-yellow-300 text-black text-sm font-bold px-6 py-2.5 rounded-full transition-all shadow-sm hover:shadow-md">
        List an Item
    </a>
<?php endif; ?>
</nav>
</div>
<!-- Mobile Menu Button -->
<button class="lg:hidden p-2 text-text-main dark:text-white">
<span class="material-symbols-outlined">menu</span>
</button>
</div>
</header>
<main class="flex-1 w-full max-w-[1400px] mx-auto px-4 md:px-10 pb-20 mt-0 pt-0">
<!-- Hero Section -->
<section class="mt-0 bg-surface-light dark:bg-surface-dark overflow-hidden shadow-sm border-b border-x border-[#e9e8ce] dark:border-[#3e3d2a]" style="margin-top: 0 !important;">
<div class="grid grid-cols-1 md:grid-cols-2 min-h-[450px] md:min-h-[500px]">
<div class="flex flex-col justify-center px-8 md:px-12 py-8 md:py-12 gap-5">
<div class="space-y-3">
<div class="inline-flex items-center gap-2 px-4 py-2 rounded-full bg-primary/20 text-sm font-bold uppercase tracking-wider text-black dark:text-white dark:bg-primary/10 w-fit">
<span class="w-2 h-2 rounded-full bg-primary"></span>
                                Peer-to-Peer Rental
                            </div>
<h1 class="text-4xl md:text-5xl lg:text-6xl font-black leading-[1.1] tracking-tight">
                                Own Less. <br/>
<span class="relative inline-block">
<span class="relative z-10">Experience More.</span>
<span class="absolute bottom-2 left-0 w-full h-3 bg-primary/60 -z-0 skew-x-[-10deg]"></span>
</span>
</h1>
<p class="text-lg text-text-muted dark:text-gray-300 max-w-lg leading-relaxed">
                                An online second-hand rental space management system connecting Renters, Owners, and Delivery Partners. Access daily-use items from multiple categories securely and transparently.
                            </p>
</div>
<div class="flex flex-wrap gap-4">
<a href="dashboard.php" class="bg-primary hover:bg-yellow-300 text-black text-base font-bold px-8 py-3.5 rounded-full shadow-lg transition-transform hover:-translate-y-0.5 inline-block">
                                Start Renting
                            </a>
<a href="lend-item.php" class="bg-background-light dark:bg-background-dark border border-[#e9e8ce] dark:border-[#3e3d2a] hover:bg-[#e9e8ce] dark:hover:bg-[#3e3d2a] text-text-main dark:text-white text-base font-bold px-8 py-3.5 rounded-full transition-all inline-block">
                                Lend Items
                            </a>


</div>
</div>
<div class="relative h-80 md:h-full w-full bg-cover bg-center" data-alt="People enjoying outdoors with rented camping gear" style="background-image: url('https://lh3.googleusercontent.com/aida-public/AB6AXuB_lQaL7O_ILeq15cN2TnrxhIEUjhy83tSURhMYy7cGdWJi2PwJ0JJinrIcrv2kpeZy1w96Sxog7Cv5LsS_ZZ62a7yYJAX5RPY3c72Xho-6cls5NNiUR39kw79KQzVKX-aZDU8JsGkJW1aGnNPCTaAAt4_DrbwKE_F8TYrZ2fs9-288vZnqDXrj-F_ZSbGXGnF1xgN9jhBBks3FzufiPJpf9EKngWwV_qjMhx0A1VmN3mIMACSJVc4mWGKtjnmx7GsWQ65ymwW3zdM');">
<div class="absolute inset-0 bg-gradient-to-t from-black/50 to-transparent md:hidden"></div>
</div>
</div>
</section>
<!-- Search Bar Floating -->
<div class="relative -mt-8 md:-mt-10 mx-4 md:mx-auto max-w-4xl z-20">
<div class="bg-white dark:bg-surface-dark rounded-xl shadow-xl p-4 md:p-6 border border-[#e9e8ce] dark:border-[#3e3d2a]">
<div class="flex flex-col md:flex-row gap-4 items-end">
<div class="flex-1 w-full">
<label class="block text-sm font-bold mb-2 ml-1">What?</label>
<div class="flex items-center bg-background-light dark:bg-background-dark rounded-full px-4 py-3 border border-transparent focus-within:border-primary transition-colors">
<span class="material-symbols-outlined text-text-muted">search</span>
<input id="hero-search-input" class="bg-transparent border-none w-full ml-2 outline-none focus:ring-0 text-text-main dark:text-white placeholder:text-text-muted text-sm md:text-base" placeholder="Search items..." type="text"/>
</div>
</div>
<div class="flex-1 w-full">
<label class="block text-sm font-bold mb-2 ml-1">When?</label>
<div class="flex items-center bg-background-light dark:bg-background-dark rounded-full px-4 py-3 border border-transparent focus-within:border-primary transition-colors">
<span class="material-symbols-outlined text-text-muted">calendar_today</span>
<input id="hero-date-input" class="bg-transparent border-none w-full ml-2 outline-none focus:ring-0 text-text-main dark:text-white placeholder:text-text-muted text-sm md:text-base" placeholder="Add dates" type="text" onfocus="(this.type='date')" onblur="(this.type='text')"/>
</div>
</div>
<button onclick="validateAndSearch()" class="w-full md:w-auto bg-black dark:bg-white text-white dark:text-black font-bold h-[48px] px-8 rounded-full flex items-center justify-center hover:opacity-90 transition-opacity">
                            Search
                        </button>
</div>
<div id="search-error" class="hidden text-red-500 text-xs font-bold mt-2 ml-2 flex items-center gap-1">
    <span class="material-symbols-outlined text-sm">error</span> Please enter both an item name and a date.
</div>
</div>
</div>

<script>
function validateAndSearch() {
    const query = document.getElementById('hero-search-input').value.trim();
    const date = document.getElementById('hero-date-input').value.trim();
    const errorMsg = document.getElementById('search-error');
    
    if (!query || !date) {
        errorMsg.classList.remove('hidden');
        if (!query) document.getElementById('hero-search-input').parentElement.classList.add('border-red-500');
        if (!date) document.getElementById('hero-date-input').parentElement.classList.add('border-red-500');
        
        setTimeout(() => {
            errorMsg.classList.add('hidden');
            document.getElementById('hero-search-input').parentElement.classList.remove('border-red-500');
            document.getElementById('hero-date-input').parentElement.classList.remove('border-red-500');
        }, 3000);
        return;
    }
    
    window.location.href = 'category.php?q=' + encodeURIComponent(query) + '&date=' + encodeURIComponent(date);
}
</script>
<!-- Categories -->
<section id="browse-categories" class="mt-16">
<div class="flex items-center justify-between mb-6">
<h2 class="text-2xl font-bold">Browse by Category</h2>

</div>
<div class="flex gap-4 overflow-x-auto pb-4 scrollbar-hide snap-x">
<!-- Category Item - Student Essentials -->
<a class="snap-start shrink-0 flex flex-col items-center gap-3 min-w-[100px] group cursor-pointer" href="dashboard.php">
<div class="w-20 h-20 rounded-full bg-surface-light dark:bg-surface-dark border border-[#e9e8ce] dark:border-[#3e3d2a] flex items-center justify-center group-hover:border-primary group-hover:bg-primary/10 transition-all">
<span class="material-symbols-outlined text-3xl group-hover:scale-110 transition-transform">school</span>
</div>
<span class="text-sm font-medium text-center">Student Essentials</span>
</a>

<!-- Category Item - Electronic Devices -->
<a class="snap-start shrink-0 flex flex-col items-center gap-3 min-w-[100px] group cursor-pointer" href="dashboard.php">
<div class="w-20 h-20 rounded-full bg-surface-light dark:bg-surface-dark border border-[#e9e8ce] dark:border-[#3e3d2a] flex items-center justify-center group-hover:border-primary group-hover:bg-primary/10 transition-all">
<span class="material-symbols-outlined text-3xl group-hover:scale-110 transition-transform">devices</span>
</div>
<span class="text-sm font-medium text-center">Electronic Devices</span>
</a>
<!-- Category Item - Travel/Outdoor Gear -->
<a class="snap-start shrink-0 flex flex-col items-center gap-3 min-w-[100px] group cursor-pointer" href="dashboard.php">
<div class="w-20 h-20 rounded-full bg-surface-light dark:bg-surface-dark border border-[#e9e8ce] dark:border-[#3e3d2a] flex items-center justify-center group-hover:border-primary group-hover:bg-primary/10 transition-all">
<span class="material-symbols-outlined text-3xl group-hover:scale-110 transition-transform">backpack</span>
</div>
<span class="text-sm font-medium text-center">Travel/Outdoor Gear</span>
</a>
<!-- Category Item - Home-Daily Essentials -->
<a class="snap-start shrink-0 flex flex-col items-center gap-3 min-w-[100px] group cursor-pointer" href="dashboard.php">
<div class="w-20 h-20 rounded-full bg-surface-light dark:bg-surface-dark border border-[#e9e8ce] dark:border-[#3e3d2a] flex items-center justify-center group-hover:border-primary group-hover:bg-primary/10 transition-all">
<span class="material-symbols-outlined text-3xl group-hover:scale-110 transition-transform">home</span>
</div>
<span class="text-sm font-medium text-center">Home-Daily Essentials</span>
</a>
<!-- Category Item - Furniture -->
<a class="snap-start shrink-0 flex flex-col items-center gap-3 min-w-[100px] group cursor-pointer" href="dashboard.php">
<div class="w-20 h-20 rounded-full bg-surface-light dark:bg-surface-dark border border-[#e9e8ce] dark:border-[#3e3d2a] flex items-center justify-center group-hover:border-primary group-hover:bg-primary/10 transition-all">
<span class="material-symbols-outlined text-3xl group-hover:scale-110 transition-transform">chair</span>
</div>
<span class="text-sm font-medium text-center">Furniture</span>
</a>

<!-- Hidden Additional Categories -->
<div id="moreCategories" class="hidden flex gap-4">
    <!-- Category Item - Vintage Collections -->
    <a class="snap-start shrink-0 flex flex-col items-center gap-3 min-w-[100px] group cursor-pointer" href="dashboard.php">
    <div class="w-20 h-20 rounded-full bg-surface-light dark:bg-surface-dark border border-[#e9e8ce] dark:border-[#3e3d2a] flex items-center justify-center group-hover:border-primary group-hover:bg-primary/10 transition-all">
    <span class="material-symbols-outlined text-3xl group-hover:scale-110 transition-transform">auto_awesome</span>
    </div>
    <span class="text-sm font-medium text-center">Vintage Collections</span>
    </a>
    
    <!-- Category Item - Fitness Equipment -->
    <a class="snap-start shrink-0 flex flex-col items-center gap-3 min-w-[100px] group cursor-pointer" href="dashboard.php">
    <div class="w-20 h-20 rounded-full bg-surface-light dark:bg-surface-dark border border-[#e9e8ce] dark:border-[#3e3d2a] flex items-center justify-center group-hover:border-primary group-hover:bg-primary/10 transition-all">
    <span class="material-symbols-outlined text-3xl group-hover:scale-110 transition-transform">fitness_center</span>
    </div>
    <span class="text-sm font-medium text-center">Fitness Equipment</span>
    </a>
    
    <!-- Category Item - Agricultural Tools -->
    <a class="snap-start shrink-0 flex flex-col items-center gap-3 min-w-[100px] group cursor-pointer" href="dashboard.php">
    <div class="w-20 h-20 rounded-full bg-surface-light dark:bg-surface-dark border border-[#e9e8ce] dark:border-[#3e3d2a] flex items-center justify-center group-hover:border-primary group-hover:bg-primary/10 transition-all">
    <span class="material-symbols-outlined text-3xl group-hover:scale-110 transition-transform">agriculture</span>
    </div>
    <span class="text-sm font-medium text-center">Agricultural Tools</span>
    </a>
    
    <!-- Category Item - Medical Items -->
    <a class="snap-start shrink-0 flex flex-col items-center gap-3 min-w-[100px] group cursor-pointer" href="dashboard.php">
    <div class="w-20 h-20 rounded-full bg-surface-light dark:bg-surface-dark border border-[#e9e8ce] dark:border-[#3e3d2a] flex items-center justify-center group-hover:border-primary group-hover:bg-primary/10 transition-all">
    <span class="material-symbols-outlined text-3xl group-hover:scale-110 transition-transform">medical_services</span>
    </div>
    <span class="text-sm font-medium text-center">Medical Items</span>
    </a>
</div>

<!-- More Button -->
<button id="moreBtn" onclick="toggleMoreCategories()" class="snap-start shrink-0 flex flex-col items-center gap-3 min-w-[100px] group cursor-pointer">
<div class="w-20 h-20 rounded-full bg-surface-light dark:bg-surface-dark border border-[#e9e8ce] dark:border-[#3e3d2a] flex items-center justify-center group-hover:border-primary group-hover:bg-primary/10 transition-all">
<span class="material-symbols-outlined text-3xl group-hover:scale-110 transition-transform">grid_view</span>
</div>
<span id="moreText" class="text-sm font-medium text-center">More</span>
</button>
</div>

<script>
function toggleMoreCategories() {
    const moreCategories = document.getElementById('moreCategories');
    const moreText = document.getElementById('moreText');
    
    if (moreCategories.classList.contains('hidden')) {
        moreCategories.classList.remove('hidden');
        moreText.textContent = 'Less';
    } else {
        moreCategories.classList.add('hidden');
        moreText.textContent = 'More';
    }
}
</script>
</section>
<!-- Trending Near You -->
<section class="mt-12">
<h2 class="text-2xl font-bold mb-6">Trending Near You</h2>
<div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6">
<?php
$display_items = [];
// Try fetching from Database first
if (function_exists('getDBConnection')) {
    $pdo = getDBConnection();
    if ($pdo) {
        try {
            // Fetch items that are admin-approved and marked active by user
            $stmt = $pdo->query("SELECT * FROM items WHERE admin_status = 'approved' AND is_active = 1 ORDER BY RAND() LIMIT 20");
            $db_raw_items = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            $seen_titles = [];
            foreach ($db_raw_items as $row) {
                if (count($display_items) >= 4) break;
                
                // Avoid duplicate titles
                $lower_title = strtolower(trim($row['title']));
                if (in_array($lower_title, $seen_titles)) continue;
                $seen_titles[] = $lower_title;
                
                // Normalize keys to match JSON structure used in view
                $item = $row;
                $item['price'] = $row['price_per_day']; // View uses 'price'
                $item['address'] = $row['location'];    // View uses 'address'
                $item['status'] = 'Active';             // Since we filtered correctly
                
                // Handle Images safely
                $item['images'] = [];
                if (!empty($row['images'])) {
                    $decoded = json_decode($row['images'], true);
                    if (is_array($decoded)) {
                        $item['images'] = $decoded;
                    } elseif (is_string($decoded)) {
                         // Double encoded case or single string
                         $item['images'] = [$decoded];
                    } else {
                         // Raw string case
                         $item['images'] = [$row['images']];
                    }
                }
                
                $display_items[] = $item;
            }
        } catch (Exception $e) {
            // Silent fail, fall back to JSON
            error_log("DB Fetch Error in index.php: " . $e->getMessage());
        }
    }
}

// Fallback to JSON if DB gave nothing
if (empty($display_items)) {
    $items_file = 'items.json';
    $items = [];
    if (file_exists($items_file)) {
        $json_content = file_get_contents($items_file);
        $items = json_decode($json_content, true) ?? [];
    }
    
    // Filter only Active items
    $active_items = array_filter($items, function($item) {
        return isset($item['status']) && $item['status'] === 'Active';
    });
    
    // Sort by creation date (newest first)
    usort($active_items, function($a, $b) {
        return strtotime($b['created_at']) - strtotime($a['created_at']);
    });
    
    // Limit to 4 items for the homepage
    $display_items = array_slice($active_items, 0, 4);
}


if (empty($display_items)): 
?>
    <div class="col-span-full text-center py-12">
        <div class="inline-flex items-center justify-center w-16 h-16 rounded-full bg-surface-light dark:bg-surface-dark mb-4">
            <span class="material-symbols-outlined text-3xl text-text-muted">inventory_2</span>
        </div>
        <h3 class="text-lg font-bold text-text-main dark:text-white">No items available yet</h3>
        <p class="text-text-muted mt-2">Be the first to list an item!</p>
        <a href="lend-item.php" class="inline-block mt-4 bg-primary text-black font-bold px-6 py-2 rounded-full hover:bg-yellow-400 transition-colors">List Item</a>
    </div>
<?php else: ?>
    <?php foreach ($display_items as $item): 
        $image_path = !empty($item['images']) ? 'uploads/' . $item['images'][0] : 'assets/placeholder.jpg';
        // Fallback if image file doesn't exist
        if (!file_exists($image_path) && !empty($item['images'])) {
             // Try to look for it relative to root if needed, but for web src assume uploads/
        }
    ?>
    <div class="group bg-surface-light dark:bg-surface-dark rounded-xl p-3 shadow-sm hover:shadow-md transition-all border border-transparent hover:border-[#e9e8ce] dark:hover:border-[#3e3d2a]">
        <div class="relative aspect-[4/3] rounded-lg overflow-hidden bg-gray-200">
            <img class="object-cover w-full h-full group-hover:scale-105 transition-transform duration-500" 
                 src="<?php echo htmlspecialchars($image_path); ?>" 
                 alt="<?php echo htmlspecialchars($item['title']); ?>"
                 onerror="this.src='https://placehold.co/400x300?text=No+Image'"/>
            
            <button class="absolute top-2 right-2 p-2 bg-white/90 rounded-full text-black hover:bg-primary hover:text-black transition-colors">
                <span class="material-symbols-outlined text-xl block">favorite</span>
            </button>
            <div class="absolute bottom-2 left-2 bg-black/70 text-white text-xs font-bold px-2 py-1 rounded">
                <?php echo htmlspecialchars($item['address'] ?? 'Nearby'); ?>
            </div>
        </div>
        
        <div class="pt-3 px-1">
            <div class="flex justify-between items-start">
                <div class="overflow-hidden">
                    <h3 class="font-bold text-lg leading-tight truncate"><?php echo htmlspecialchars(ucfirst($item['title'])); ?></h3>
                    <p class="text-sm text-text-muted dark:text-gray-400 mt-1 truncate capitalize"><?php echo htmlspecialchars(str_replace('-', ' ', $item['category'])); ?></p>
                </div>
                <!-- Random rating for demo purposes since valid ratings aren't in JSON yet -->
                <div class="flex items-center gap-1 text-xs font-bold bg-[#f4f4e6] dark:bg-[#3e3d2a] px-2 py-1 rounded-full shrink-0">
                    <span class="material-symbols-outlined text-sm text-yellow-600">star</span>
                    <?php echo number_format(4.5 + (rand(0, 4) / 10), 1); ?>
                </div>
            </div>
            
            <div class="flex items-end justify-between mt-4">
                <div class="flex items-baseline gap-1">
                    <span class="text-xl font-bold">₹<?php echo htmlspecialchars($item['price']); ?></span>
                    <span class="text-sm text-text-muted">/ day</span>
                </div>
                 <a href="item-details.php?id=<?php echo $item['id']; ?>" class="w-8 h-8 rounded-full bg-primary flex items-center justify-center text-black hover:bg-yellow-400 transition-colors shadow-sm">
                    <span class="material-symbols-outlined text-sm font-bold">arrow_forward</span>
                </a>
            </div>
        </div>
    </div>
    <?php endforeach; ?>
<?php endif; ?>
</div>
</section>


<!-- How it Works -->
<section id="how-it-works" class="mt-24 py-12 bg-white dark:bg-surface-dark rounded-2xl border border-[#e9e8ce] dark:border-[#3e3d2a]">
<div class="text-center mb-12 px-4">
<h2 class="text-3xl font-black mb-4">Rental Made Simple</h2>
<p class="text-text-muted dark:text-gray-300">Secure, easy, and insured. Here is how RendeX works.</p>
</div>
<div class="grid grid-cols-1 md:grid-cols-3 gap-8 px-8 max-w-5xl mx-auto">
<!-- Step 1 -->
<div class="flex flex-col items-center text-center gap-4">
<div class="w-20 h-20 rounded-full bg-primary flex items-center justify-center text-black text-4xl font-black mb-2 shadow-lg rotate-3">
                            1
                        </div>
<h3 class="text-xl font-bold">Search &amp; Book</h3>
<p class="text-sm text-text-muted dark:text-gray-400 leading-relaxed">
                            Browse thousands of items nearby. Select your dates and book securely through our platform.
                        </p>
</div>
<!-- Step 2 -->
<div class="flex flex-col items-center text-center gap-4">
<div class="w-20 h-20 rounded-full bg-background-light dark:bg-background-dark border-2 border-primary flex items-center justify-center text-text-main dark:text-white text-4xl font-black mb-2 -rotate-2">
                            2
                        </div>
<h3 class="text-xl font-bold">Pick Up or Delivery</h3>
<p class="text-sm text-text-muted dark:text-gray-400 leading-relaxed">
                            Meet the owner or use our Delivery Partners to get the item delivered to your door.
                        </p>
</div>
<!-- Step 3 -->
<div class="flex flex-col items-center text-center gap-4">
<div class="w-20 h-20 rounded-full bg-primary flex items-center justify-center text-black text-4xl font-black mb-2 rotate-3">
                            3
                        </div>
<h3 class="text-xl font-bold">Enjoy &amp; Return</h3>
<p class="text-sm text-text-muted dark:text-gray-400 leading-relaxed">
                            Use the item for your project or adventure, then return it hassle-free.
                        </p>
</div>
</div>
</section>
<!-- CTA Grid -->
<section class="mt-20 grid grid-cols-1 md:grid-cols-2 gap-6">
<!-- Become a Partner -->
<div class="relative overflow-hidden rounded-2xl bg-[#23220f] text-white p-8 md:p-12 flex flex-col justify-between min-h-[300px]">
    <div class="relative z-10">
    <div class="inline-block bg-primary text-black text-xs font-bold px-3 py-1 rounded-full mb-4">FOR DRIVERS</div>
    <h3 class="text-3xl font-black mb-4">Earn on the go</h3>
    <p class="text-gray-300 max-w-xs mb-8">Join our fleet of Delivery Partners. Flexible hours, competitive pay. Sign up to get started!</p>
    <a href="login.php" class="inline-block bg-white text-black font-bold px-6 py-3 rounded-full hover:bg-primary transition-colors">Join as Driver</a>
    </div>
    <div class="absolute right-0 bottom-0 w-1/2 h-full opacity-30">
    <img class="w-full h-full object-cover mix-blend-overlay" data-alt="Abstract delivery route map pattern" src="https://lh3.googleusercontent.com/aida-public/AB6AXuCWaVoFTJJEcP7O8KP5aJfI_M_0KZzbvK3JK-ba27CkyO30Dmd-xguQt05LVHe4MsyLpNAT_DfKoGZ6lbTQjSoMVlI6uGwPO2tsL19vLL0tWZQtSW-f4K1zi_n3YyJou_kO_NtPjRSfxwDbLJCx_MNKmOA1eZAx47knHVi-SnfxAx-lZffbKrMmlVJInrKh7OMCuTOEvcIxF2RfeMlD7AkeEJafQVqj48TwJ0zsUDDKYHL1sZxaAEBt5mfGk5PlMsb3scYY-rSdFMg"/>
    </div>
    </div>
    <!-- List Items -->
    <div class="relative overflow-hidden rounded-2xl bg-primary text-black p-8 md:p-12 flex flex-col justify-between min-h-[300px]">
    <div class="relative z-10">
    <div class="inline-block bg-black text-white text-xs font-bold px-3 py-1 rounded-full mb-4">FOR OWNERS</div>
    <h3 class="text-3xl font-black mb-4">Monetize your clutter</h3>
    <p class="text-gray-800 max-w-xs mb-8">Got gear gathering dust? List it safely on RendeX and start earning.</p>
    <a href="lend-item.php" class="inline-block bg-black text-white font-bold px-6 py-3 rounded-full hover:bg-gray-800 transition-colors">Start Listing</a>
</div>
<div class="absolute -right-10 -bottom-10 w-64 h-64 bg-white/30 rounded-full blur-3xl"></div>
</div>
</section>
</main>
<!-- Footer -->
<footer class="bg-[#23220f] border-t border-[#3e3d2a] pt-16 pb-8 px-4 md:px-10 text-white">
<div class="max-w-[1400px] mx-auto">
<div class="grid grid-cols-1 md:grid-cols-3 gap-12 mb-12">
<div class="col-span-1 border-r-transparent md:border-r md:border-[#3e3d2a] pr-8">
<h2 class="text-3xl font-black tracking-tight mb-4 text-white">RendeX</h2>
<p class="text-sm text-gray-400 leading-relaxed mb-6">
    A unified digital ecosystem for your community rental and peer-to-peer sharing journey.
</p>
<!-- Radial Social Media FAB -->
<div class="relative flex items-center" style="height: 56px;">
    <!-- Social Media Icons (hidden by default, fan out on click) -->
    <a id="social-whatsapp" href="https://wa.me/?text=Check%20out%20RendeX%20-%20Own%20Less.%20Experience%20More!" target="_blank"
       class="social-icon absolute w-12 h-12 rounded-full flex items-center justify-center shadow-lg transition-all duration-300 ease-[cubic-bezier(0.68,-0.55,0.27,1.55)] opacity-0 scale-0"
       style="background: linear-gradient(135deg, #25D366, #128C7E); left: 0; top: 50%; transform: translate(0, -50%) scale(0);"
       title="WhatsApp">
        <svg class="w-5 h-5" fill="white" viewBox="0 0 24 24"><path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413z"/></svg>
    </a>
    <a id="social-instagram" href="https://www.instagram.com/" target="_blank"
       class="social-icon absolute w-12 h-12 rounded-full flex items-center justify-center shadow-lg transition-all duration-300 ease-[cubic-bezier(0.68,-0.55,0.27,1.55)] opacity-0 scale-0"
       style="background: linear-gradient(135deg, #f09433, #e6683c, #dc2743, #cc2366, #bc1888); left: 0; top: 50%; transform: translate(0, -50%) scale(0);"
       title="Instagram">
        <svg class="w-5 h-5" fill="white" viewBox="0 0 24 24"><path d="M12 2.163c3.204 0 3.584.012 4.85.07 3.252.148 4.771 1.691 4.919 4.919.058 1.265.069 1.645.069 4.849 0 3.205-.012 3.584-.069 4.849-.149 3.225-1.664 4.771-4.919 4.919-1.266.058-1.644.07-4.85.07-3.204 0-3.584-.012-4.849-.07-3.26-.149-4.771-1.699-4.919-4.92-.058-1.265-.07-1.644-.07-4.849 0-3.204.013-3.583.07-4.849.149-3.227 1.664-4.771 4.919-4.919 1.266-.057 1.645-.069 4.849-.069zm0-2.163c-3.259 0-3.667.014-4.947.072-4.358.2-6.78 2.618-6.98 6.98-.059 1.281-.073 1.689-.073 4.948 0 3.259.014 3.668.072 4.948.2 4.358 2.618 6.78 6.98 6.98 1.281.058 1.689.072 4.948.072 3.259 0 3.668-.014 4.948-.072 4.354-.2 6.782-2.618 6.979-6.98.059-1.28.073-1.689.073-4.948 0-3.259-.014-3.667-.072-4.947-.196-4.354-2.617-6.78-6.979-6.98-1.281-.059-1.69-.073-4.949-.073zm0 5.838c-3.403 0-6.162 2.759-6.162 6.162s2.759 6.163 6.162 6.163 6.162-2.759 6.162-6.163c0-3.403-2.759-6.162-6.162-6.162zm0 10.162c-2.209 0-4-1.79-4-4 0-2.209 1.791-4 4-4s4 1.791 4 4c0 2.21-1.791 4-4 4zm6.406-11.845c-.796 0-1.441.645-1.441 1.44s.645 1.44 1.441 1.44c.795 0 1.439-.645 1.439-1.44s-.644-1.44-1.439-1.44z"/></svg>
    </a>
    <a id="social-facebook" href="https://www.facebook.com/" target="_blank"
       class="social-icon absolute w-12 h-12 rounded-full flex items-center justify-center shadow-lg transition-all duration-300 ease-[cubic-bezier(0.68,-0.55,0.27,1.55)] opacity-0 scale-0"
       style="background: linear-gradient(135deg, #1877F2, #0C5DC7); left: 0; top: 50%; transform: translate(0, -50%) scale(0);"
       title="Facebook">
        <svg class="w-5 h-5" fill="white" viewBox="0 0 24 24"><path d="M24 12.073c0-6.627-5.373-12-12-12s-12 5.373-12 12c0 5.99 4.388 10.954 10.125 11.854v-8.385H7.078v-3.47h3.047V9.43c0-3.007 1.792-4.669 4.533-4.669 1.312 0 2.686.235 2.686.235v2.953H15.83c-1.491 0-1.956.925-1.956 1.874v2.25h3.328l-.532 3.47h-2.796v8.385C19.612 23.027 24 18.062 24 12.073z"/></svg>
    </a>
    
    <!-- Main Share Button (Toggle) -->
    <button id="share-fab-btn" onclick="toggleSocialFab()" 
            class="relative z-10 w-14 h-14 rounded-full border-2 border-[#3e3d2a] flex items-center justify-center text-gray-400 hover:border-[#0d9488] hover:text-[#0d9488] transition-all duration-300 group"
            style="background: rgba(35, 34, 15, 0.8); backdrop-filter: blur(8px);">
        <svg id="share-icon" class="w-5 h-5 transition-transform duration-300" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
            <circle cx="18" cy="5" r="3"/><circle cx="6" cy="12" r="3"/><circle cx="18" cy="19" r="3"/>
            <line x1="8.59" y1="13.51" x2="15.42" y2="17.49"/><line x1="15.41" y1="6.51" x2="8.59" y2="10.49"/>
        </svg>
    </button>
</div>

<style>
    .social-fab-open #share-icon {
        transform: rotate(45deg);
    }
    .social-fab-open #share-fab-btn {
        border-color: #0d9488 !important;
        color: #0d9488 !important;
        box-shadow: 0 0 20px rgba(13, 148, 136, 0.3);
    }
    .social-icon {
        z-index: 5;
    }
    .social-icon:hover {
        filter: brightness(1.15);
        transform: scale(1.12) !important;
    }
    @keyframes pulse-ring {
        0% { box-shadow: 0 0 0 0 rgba(13, 148, 136, 0.4); }
        70% { box-shadow: 0 0 0 10px rgba(13, 148, 136, 0); }
        100% { box-shadow: 0 0 0 0 rgba(13, 148, 136, 0); }
    }
    .social-fab-open #share-fab-btn {
        animation: pulse-ring 2s infinite;
    }
</style>

<script>
let socialFabOpen = false;

function toggleSocialFab() {
    socialFabOpen = !socialFabOpen;
    const container = document.querySelector('.social-fab-open')?.closest('.relative') || 
                      document.getElementById('share-fab-btn').closest('.relative');
    
    const whatsapp = document.getElementById('social-whatsapp');
    const instagram = document.getElementById('social-instagram');
    const facebook = document.getElementById('social-facebook');
    
    if (socialFabOpen) {
        container.classList.add('social-fab-open');
        
        // Fan out in a radial arc pattern (like the reference image)
        // Position: upper-left, directly above, upper-right
        setTimeout(() => {
            whatsapp.style.opacity = '1';
            whatsapp.style.transform = 'translate(70px, -50%) scale(1)';
        }, 50);
        setTimeout(() => {
            instagram.style.opacity = '1';
            instagram.style.transform = 'translate(50px, calc(-50% - 55px)) scale(1)';
        }, 120);
        setTimeout(() => {
            facebook.style.opacity = '1';
            facebook.style.transform = 'translate(10px, calc(-50% - 80px)) scale(1)';
        }, 190);
    } else {
        container.classList.remove('social-fab-open');
        
        // Collapse back
        [whatsapp, instagram, facebook].forEach(el => {
            el.style.opacity = '0';
            el.style.transform = 'translate(0, -50%) scale(0)';
        });
    }
}

// Close on outside click
document.addEventListener('click', function(e) {
    if (socialFabOpen && !e.target.closest('.relative')) {
        toggleSocialFab();
    }
});
</script>
</div>
<div>
<h4 class="font-bold text-lg mb-6 text-white">Platform</h4>
<ul class="space-y-4 text-sm text-gray-400">
<li><a class="hover:text-primary transition-colors" href="index.php">Home</a></li>
<li><a class="hover:text-primary transition-colors" href="lend-item.php">Lend Item</a></li>
<li><a class="hover:text-primary transition-colors" href="#browse-categories">Browse</a></li>
</ul>
</div>
<div>
<h4 class="font-bold text-lg mb-6 text-white">Support</h4>
<ul class="space-y-4 text-sm text-gray-400">
<li><a class="hover:text-primary transition-colors" href="about.php">About Us</a></li>
<li><a class="hover:text-primary transition-colors" href="contact.php">Contact Us</a></li>
<li><a class="hover:text-primary transition-colors" href="privacy.php">Privacy Policy</a></li>
<li><a class="hover:text-primary transition-colors" href="terms.php">Terms and Conditions</a></li>
</ul>
</div>
</div>
<div class="flex flex-col md:flex-row justify-between items-center pt-8 border-t border-[#3e3d2a] gap-4">
<p class="text-sm text-gray-500">© 2026 RendeX Inc. All rights reserved.</p>
</div>
</div>
</footer>
</div>
</body></html>