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
<?php session_start(); ?>
<header class="sticky top-0 z-50 flex items-center justify-between border-b border-[#e9e8ce] dark:border-[#3e3d2a] bg-background-light/95 dark:bg-background-dark/95 backdrop-blur-sm px-6 py-4 lg:px-10">
<div class="flex items-center gap-8 w-full max-w-[1400px] mx-auto">
<a href="index.php" class="flex items-center gap-2 text-text-main dark:text-white">
<div class="size-8 text-primary">
<svg class="w-full h-full" fill="none" viewbox="0 0 48 48" xmlns="http://www.w3.org/2000/svg">
<path d="M36.7273 44C33.9891 44 31.6043 39.8386 30.3636 33.69C29.123 39.8386 26.7382 44 24 44C21.2618 44 18.877 39.8386 17.6364 33.69C16.3957 39.8386 14.0109 44 11.2727 44C7.25611 44 4 35.0457 4 24C4 12.9543 7.25611 4 11.2727 4C14.0109 4 16.3957 8.16144 17.6364 14.31C18.877 8.16144 21.2618 4 24 4C26.7382 4 29.123 8.16144 30.3636 14.31C31.6043 8.16144 33.9891 4 36.7273 4C40.7439 4 44 12.9543 44 24C44 35.0457 40.7439 44 36.7273 44Z" fill="currentColor"></path>
</svg>
</div>
<h2 class="text-xl font-bold tracking-tight">RendeX</h2>
</a>
<!-- Desktop Search -->
<div class="hidden md:flex flex-1 max-w-md mx-4">
<div class="flex w-full items-center rounded-full bg-white dark:bg-surface-dark border border-[#e9e8ce] dark:border-[#3e3d2a] px-4 py-2 shadow-sm focus-within:ring-2 focus-within:ring-primary">
<span class="material-symbols-outlined text-text-muted">search</span>
<input class="ml-2 flex-1 bg-transparent border-none text-sm outline-none placeholder:text-text-muted focus:ring-0 text-text-main dark:text-white" placeholder="Search items..."/>
</div>
</div>
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
<input class="bg-transparent border-none w-full ml-2 outline-none focus:ring-0 text-text-main dark:text-white placeholder:text-text-muted text-sm md:text-base" placeholder="Search items..." type="text"/>
</div>
</div>
<div class="flex-1 w-full">
<label class="block text-sm font-bold mb-2 ml-1">Where?</label>
<div class="flex items-center bg-background-light dark:bg-background-dark rounded-full px-4 py-3 border border-transparent focus-within:border-primary transition-colors">
<span class="material-symbols-outlined text-text-muted">location_on</span>
<input class="bg-transparent border-none w-full ml-2 outline-none focus:ring-0 text-text-main dark:text-white placeholder:text-text-muted text-sm md:text-base" placeholder="Zip code or City" type="text"/>
</div>
</div>
<div class="flex-1 w-full">
<label class="block text-sm font-bold mb-2 ml-1">When?</label>
<div class="flex items-center bg-background-light dark:bg-background-dark rounded-full px-4 py-3 border border-transparent focus-within:border-primary transition-colors">
<span class="material-symbols-outlined text-text-muted">calendar_today</span>
<input class="bg-transparent border-none w-full ml-2 outline-none focus:ring-0 text-text-main dark:text-white placeholder:text-text-muted text-sm md:text-base" placeholder="Add dates" type="text"/>
</div>
</div>
<button class="w-full md:w-auto bg-black dark:bg-white text-white dark:text-black font-bold h-[48px] px-8 rounded-full flex items-center justify-center hover:opacity-90 transition-opacity">
                            Search
                        </button>
</div>
</div>
</div>
<!-- Categories -->
<section class="mt-16">
<div class="flex items-center justify-between mb-6">
<h2 class="text-2xl font-bold">Browse by Category</h2>
<a class="text-sm font-bold underline decoration-primary decoration-2 underline-offset-4 hover:text-text-muted" href="dashboard.php">View All</a>
</div>
<div class="flex gap-4 overflow-x-auto pb-4 scrollbar-hide snap-x">
<!-- Category Item - Student Essentials -->
<a class="snap-start shrink-0 flex flex-col items-center gap-3 min-w-[100px] group cursor-pointer" href="dashboard.php">
<div class="w-20 h-20 rounded-full bg-surface-light dark:bg-surface-dark border border-[#e9e8ce] dark:border-[#3e3d2a] flex items-center justify-center group-hover:border-primary group-hover:bg-primary/10 transition-all">
<span class="material-symbols-outlined text-3xl group-hover:scale-110 transition-transform">school</span>
</div>
<span class="text-sm font-medium text-center">Student Essentials</span>
</a>
<!-- Category Item - Clothing -->
<a class="snap-start shrink-0 flex flex-col items-center gap-3 min-w-[100px] group cursor-pointer" href="dashboard.php">
<div class="w-20 h-20 rounded-full bg-surface-light dark:bg-surface-dark border border-[#e9e8ce] dark:border-[#3e3d2a] flex items-center justify-center group-hover:border-primary group-hover:bg-primary/10 transition-all">
<span class="material-symbols-outlined text-3xl group-hover:scale-110 transition-transform">checkroom</span>
</div>
<span class="text-sm font-medium text-center">Clothing</span>
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

// Limit to 8 items for the homepage
$display_items = array_slice($active_items, 0, 8);

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
                    <span class="text-xl font-bold">$<?php echo htmlspecialchars($item['price']); ?></span>
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
<li><a class="hover:text-primary transition-colors" href="signup.php">Become a Partner</a></li>
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