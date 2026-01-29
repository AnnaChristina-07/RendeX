<?php
ob_start();
session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// Include database connection
require_once 'config/database.php';

// --- LOGIC: CHECK ROLES & HANDLE ACTIONS ---
$users_file = 'users.json';
$items_file = 'items.json';

$users = file_exists($users_file) ? (json_decode(file_get_contents($users_file), true) ?? []) : [];
$items = file_exists($items_file) ? (json_decode(file_get_contents($items_file), true) ?? []) : [];

$current_user = null;
foreach ($users as $u) {
    if ($u['id'] === $_SESSION['user_id']) {
        $current_user = $u;
        break;
    }
}

// 1. Check if Owner (Has items OR role='owner')
$is_owner = (($current_user['role'] ?? '') === 'owner');
if (!$is_owner) {
    foreach ($items as $i) {
        if (isset($i['user_id']) && $i['user_id'] === $_SESSION['user_id']) {
            $is_owner = true;
            break;
        }
    }
}
// Also check database for owner status
if (!$is_owner) {
    try {
        if ($pdo) {
            $stmt = $pdo->prepare("SELECT id FROM items WHERE owner_id = ? LIMIT 1");
            $stmt->execute([$_SESSION['user_id']]);
            if ($stmt->fetch()) {
                $is_owner = true;
            }
        }
    } catch (Exception $e) {}
}

// 2. Check if Delivery Partner - First check JSON, then database
$is_delivery = (($current_user['role'] ?? '') === 'delivery_partner');
$delivery_pending = (($current_user['role'] ?? '') === 'delivery_partner_pending');

// Also check database for pending driver application
try {
    $pdo = getDBConnection();
    if ($pdo) {
        // Check if user is approved delivery partner in database
        $stmt = $pdo->prepare("SELECT role FROM users WHERE id = ?");
        $stmt->execute([$_SESSION['user_id']]);
        $db_user = $stmt->fetch();
        if ($db_user && $db_user['role'] === 'delivery_partner') {
            $is_delivery = true;
        }
        
        // Check if user has a pending driver application in database
        $stmt = $pdo->prepare("SELECT id FROM driver_applications WHERE user_id = ? AND status = 'pending'");
        $stmt->execute([$_SESSION['user_id']]);
        if ($stmt->fetch()) {
            $delivery_pending = true;
        }
    }
} catch (PDOException $e) {
    // Database not available, use JSON data only
}

// 3. Handle Delivery Application (legacy - kept for backward compatibility)
if (isset($_POST['apply_delivery'])) {
    // Update user role to pending
    foreach ($users as &$u) {
        if ($u['id'] === $_SESSION['user_id']) {
            $u['role'] = 'delivery_partner_pending';
            break;
        }
    }
    file_put_contents($users_file, json_encode($users, JSON_PRETTY_PRINT));
    header("Location: dashboard.php?msg=applied");
    exit();
}
?>
<!DOCTYPE html>
<html class="light" lang="en"><head>
<meta charset="utf-8"/>
<meta content="width=device-width, initial-scale=1.0" name="viewport"/>
<title>RendeX - Dashboard</title>
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
<div class="flex items-center gap-8 w
<h2 class="text-xl font-bold tracking-tight">RendeX</h2>
</a>

<!-- Right Side: Navigation & User Menu -->
<div class="flex items-center gap-4 lg:gap-8">
    <div class="hidden lg:flex items-center gap-6">
        <!-- Base Role: Renter -->
        <a href="rentals.php" class="font-bold text-sm hover:text-primary transition-colors">My Rentals</a>
        
        <!-- Owner Role -->
        <?php if ($is_owner): ?>
        <a href="owner_dashboard.php" class="font-bold text-sm hover:text-primary transition-colors border-l pl-4 border-gray-300">Owner Dashboard</a>
        <?php endif; ?>

        <!-- Delivery Role -->
        <?php if ($is_delivery): ?>
        <a href="delivery_dashboard.php" class="font-bold text-sm hover:text-primary transition-colors border-l pl-4 border-gray-300">Delivery Tasks</a>
        <?php endif; ?>
    </div>

    <!-- Profile Circle -->
    <a href="profile.php" class="group flex items-center gap-3">
        <div class="w-10 h-10 rounded-full bg-primary flex items-center justify-center text-black text-sm font-black border-2 border-transparent group-hover:border-primary transition-all shadow-sm">
            <?php echo strtoupper(substr($_SESSION['user_name'], 0, 1)); ?>
        </div>
        <div class="hidden sm:block text-left">
            <p class="text-[10px] font-black uppercase text-text-muted leading-none mb-1">Account</p>
            <p class="text-xs font-bold leading-none"><?php echo htmlspecialchars($_SESSION['user_name']); ?></p>
        </div>
    </a>

    <a href="logout.php" class="bg-black text-white dark:bg-white dark:text-black size-10 rounded-full flex items-center justify-center hover:bg-primary hover:text-black transition-all shadow-sm" title="Logout">
        <span class="material-symbols-outlined text-[20px]">logout</span>
    </a>
</div>

</header>
<main class="flex-1 w-full max-w-[1400px] mx-auto px-4 md:px-10 pb-20 mt-0 pt-0">

<?php if (isset($_GET['msg']) && $_GET['msg'] === 'application_submitted'): ?>
<!-- Success Notification -->
<div class="mt-6 mb-6 bg-green-50 border-l-4 border-green-500 p-4 rounded-lg shadow-md">
    <div class="flex items-center">
        <span class="material-symbols-outlined text-green-500 mr-3 text-2xl">check_circle</span>
        <div>
            <h3 class="text-green-800 font-bold">Application Submitted Successfully!</h3>
            <p class="text-green-700 text-sm">Your delivery partner application is under review. We'll notify you within 2-3 business days.</p>
        </div>
    </div>
</div>
<?php endif; ?>

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
<p class="text-lg text-text-muted dark:text-gray-300 max-w-md leading-relaxed">
                                Join the secure marketplace connecting Renters, Owners, and Delivery Partners. 
                            </p>
</div>
<div class="flex flex-wrap gap-4">
    <button onclick="document.getElementById('product-search').focus()" class="bg-primary hover:bg-yellow-300 text-black text-base font-bold px-8 py-3.5 rounded-full shadow-lg transition-transform hover:-translate-y-0.5">
        Start Renting
    </button>
    
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
<input class="bg-transparent border-none w-full ml-2 outline-none focus:ring-0 text-text-main dark:text-white placeholder:text-text-muted text-sm md:text-base" placeholder="Search items..." type="text" name="q"/>
</div>
</div>
<!-- Removed Where Section -->
<div class="flex-1 w-full">
<label class="block text-sm font-bold mb-2 ml-1">When?</label>
<div class="flex items-center bg-background-light dark:bg-background-dark rounded-full px-4 py-3 border border-transparent focus-within:border-primary transition-colors">
<span class="material-symbols-outlined text-text-muted">calendar_today</span>
<input class="bg-transparent border-none w-full ml-2 outline-none focus:ring-0 text-text-main dark:text-white placeholder:text-text-muted text-sm md:text-base" placeholder="Start Date" type="text" onfocus="(this.type='date')" onblur="(this.type='text')" name="start_date"/>
</div>
</div>
<button onclick="window.location.href='category.php?cat=student-essentials&q='+document.getElementsByName('q')[0].value" class="w-full md:w-auto bg-black dark:bg-white text-white dark:text-black font-bold h-[48px] px-8 rounded-full flex items-center justify-center hover:opacity-90 transition-opacity">
                            Search
                        </button>
</div>
</div>
</div>
<!-- Categories -->
<section class="mt-16">
<div class="flex items-center justify-between mb-6">
<h2 class="text-2xl font-bold">Browse by Category</h2>
<a class="text-sm font-bold underline decoration-primary decoration-2 underline-offset-4 hover:text-text-muted" href="#">View All</a>
</div>
<div class="flex gap-4 overflow-x-auto pb-4 scrollbar-hide snap-x">
<!-- Category Item - Student Essentials -->
<a class="snap-start shrink-0 flex flex-col items-center gap-3 min-w-[100px] group cursor-pointer" href="category.php?cat=student-essentials">
<div class="w-20 h-20 rounded-full bg-surface-light dark:bg-surface-dark border border-[#e9e8ce] dark:border-[#3e3d2a] flex items-center justify-center group-hover:border-primary group-hover:bg-primary/10 transition-all">
<span class="material-symbols-outlined text-3xl group-hover:scale-110 transition-transform">school</span>
</div>
<span class="text-sm font-medium text-center">Student Essentials</span>
</a>
<!-- Category Item - Clothing -->
<a class="snap-start shrink-0 flex flex-col items-center gap-3 min-w-[100px] group cursor-pointer" href="category.php?cat=clothing">
<div class="w-20 h-20 rounded-full bg-surface-light dark:bg-surface-dark border border-[#e9e8ce] dark:border-[#3e3d2a] flex items-center justify-center group-hover:border-primary group-hover:bg-primary/10 transition-all">
<span class="material-symbols-outlined text-3xl group-hover:scale-110 transition-transform">checkroom</span>
</div>
<span class="text-sm font-medium text-center">Clothing</span>
</a>
<!-- Category Item - Electronic Devices -->
<a class="snap-start shrink-0 flex flex-col items-center gap-3 min-w-[100px] group cursor-pointer" href="category.php?cat=electronics">
<div class="w-20 h-20 rounded-full bg-surface-light dark:bg-surface-dark border border-[#e9e8ce] dark:border-[#3e3d2a] flex items-center justify-center group-hover:border-primary group-hover:bg-primary/10 transition-all">
<span class="material-symbols-outlined text-3xl group-hover:scale-110 transition-transform">devices</span>
</div>
<span class="text-sm font-medium text-center">Electronic Devices</span>
</a>
<!-- Category Item - Travel/Outdoor Gear -->
<a class="snap-start shrink-0 flex flex-col items-center gap-3 min-w-[100px] group cursor-pointer" href="category.php?cat=outdoor-gear">
<div class="w-20 h-20 rounded-full bg-surface-light dark:bg-surface-dark border border-[#e9e8ce] dark:border-[#3e3d2a] flex items-center justify-center group-hover:border-primary group-hover:bg-primary/10 transition-all">
<span class="material-symbols-outlined text-3xl group-hover:scale-110 transition-transform">backpack</span>
</div>
<span class="text-sm font-medium text-center">Travel/Outdoor Gear</span>
</a>
<!-- Category Item - Home-Daily Essentials -->
<a class="snap-start shrink-0 flex flex-col items-center gap-3 min-w-[100px] group cursor-pointer" href="category.php?cat=home-essentials">
<div class="w-20 h-20 rounded-full bg-surface-light dark:bg-surface-dark border border-[#e9e8ce] dark:border-[#3e3d2a] flex items-center justify-center group-hover:border-primary group-hover:bg-primary/10 transition-all">
<span class="material-symbols-outlined text-3xl group-hover:scale-110 transition-transform">home</span>
</div>
<span class="text-sm font-medium text-center">Home-Daily Essentials</span>
</a>
<!-- Category Item - Furniture -->
<a class="snap-start shrink-0 flex flex-col items-center gap-3 min-w-[100px] group cursor-pointer" href="category.php?cat=furniture">
<div class="w-20 h-20 rounded-full bg-surface-light dark:bg-surface-dark border border-[#e9e8ce] dark:border-[#3e3d2a] flex items-center justify-center group-hover:border-primary group-hover:bg-primary/10 transition-all">
<span class="material-symbols-outlined text-3xl group-hover:scale-110 transition-transform">chair</span>
</div>
<span class="text-sm font-medium text-center">Furniture</span>
</a>

<!-- Hidden Additional Categories -->
<div id="moreCategories" class="hidden flex gap-4">
    <!-- Category Item - Vintage Collections -->
    <a class="snap-start shrink-0 flex flex-col items-center gap-3 min-w-[100px] group cursor-pointer" href="category.php?cat=vintage">
    <div class="w-20 h-20 rounded-full bg-surface-light dark:bg-surface-dark border border-[#e9e8ce] dark:border-[#3e3d2a] flex items-center justify-center group-hover:border-primary group-hover:bg-primary/10 transition-all">
    <span class="material-symbols-outlined text-3xl group-hover:scale-110 transition-transform">auto_awesome</span>
    </div>
    <span class="text-sm font-medium text-center">Vintage Collections</span>
    </a>
    
    <!-- Category Item - Fitness Equipment -->
    <a class="snap-start shrink-0 flex flex-col items-center gap-3 min-w-[100px] group cursor-pointer" href="category.php?cat=fitness">
    <div class="w-20 h-20 rounded-full bg-surface-light dark:bg-surface-dark border border-[#e9e8ce] dark:border-[#3e3d2a] flex items-center justify-center group-hover:border-primary group-hover:bg-primary/10 transition-all">
    <span class="material-symbols-outlined text-3xl group-hover:scale-110 transition-transform">fitness_center</span>
    </div>
    <span class="text-sm font-medium text-center">Fitness Equipment</span>
    </a>
    
    <!-- Category Item - Agricultural Tools -->
    <a class="snap-start shrink-0 flex flex-col items-center gap-3 min-w-[100px] group cursor-pointer" href="category.php?cat=agriculture">
    <div class="w-20 h-20 rounded-full bg-surface-light dark:bg-surface-dark border border-[#e9e8ce] dark:border-[#3e3d2a] flex items-center justify-center group-hover:border-primary group-hover:bg-primary/10 transition-all">
    <span class="material-symbols-outlined text-3xl group-hover:scale-110 transition-transform">agriculture</span>
    </div>
    <span class="text-sm font-medium text-center">Agricultural Tools</span>
    </a>
    
    <!-- Category Item - Medical Items -->
    <a class="snap-start shrink-0 flex flex-col items-center gap-3 min-w-[100px] group cursor-pointer" href="category.php?cat=medical">
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
            <?php if (empty($items)): ?>
                <div class="col-span-full flex flex-col items-center justify-center py-16 text-center">
                    <div class="w-16 h-16 bg-gray-100 dark:bg-zinc-800 rounded-full flex items-center justify-center mb-4">
                        <span class="material-symbols-outlined text-gray-400 text-3xl">inventory_2</span>
                    </div>
                    <h3 class="text-lg font-bold mb-2">No items found</h3>
                    <p class="text-text-muted mb-6 max-w-md">There are currently no items listed in the marketplace. Be the first to list something!</p>
                    <a href="lend-item.php" class="bg-primary hover:bg-yellow-300 text-black font-bold px-6 py-3 rounded-full transition-colors">
                        List an Item
                    </a>
                </div>
            <?php else: ?>
                <?php 
                    // Filter and limit items to show only 4 "nearby" trending items
                    $valid_items = array_filter($items, function($item) {
                        return !empty($item['title']) && 
                               (!isset($item['active_until']) || strtotime($item['active_until']) >= time());
                    });
                    
                    // Sort by newest first
                    usort($valid_items, function($a, $b) {
                        $t1 = isset($a['created_at']) ? strtotime($a['created_at']) : 0;
                        $t2 = isset($b['created_at']) ? strtotime($b['created_at']) : 0;
                        return $t2 - $t1;
                    });

                    // Limit to 4 items
                    $display_items = array_slice($valid_items, 0, 4);
                ?>

                <?php foreach ($display_items as $item): 
                    $image_path = 'assets/placeholder-image.jpg'; // Default
                    if (!empty($item['images']) && is_array($item['images']) && count($item['images']) > 0) {
                        $image_path = 'uploads/' . $item['images'][0];
                    } elseif (!empty($item['img'])) {
                         // Fallback for older items if any
                        $image_path = $item['img'];
                    }

                    // Status Badge Logic
                    $is_rented = (isset($item['availability_status']) && ($item['availability_status'] === 'rented' || $item['availability_status'] === 'unavailable')) || (isset($item['status']) && $item['status'] === 'unavailable');
                    $status_badge = '';
                    if ($is_rented) {
                         $status_badge = '<div class="absolute top-2 left-2 bg-red-500 text-white text-[10px] font-bold px-2 py-1 rounded-full uppercase tracking-wide z-10">Rented</div>';
                    } else {
                         //$status_badge = '<div class="absolute top-2 left-2 bg-green-500 text-white text-[10px] font-bold px-2 py-1 rounded-full uppercase tracking-wide">Available</div>';
                    }
                ?>
                <a href="item-details.php?id=<?php echo $item['id']; ?>" class="group block bg-surface-light dark:bg-surface-dark rounded-xl p-3 shadow-sm hover:shadow-md transition-all border border-transparent hover:border-[#e9e8ce] dark:hover:border-[#3e3d2a] <?php echo $is_rented ? 'opacity-75 grayscale' : ''; ?>">
                    <div class="relative aspect-[4/3] rounded-lg overflow-hidden bg-gray-200">
                        <img class="object-cover w-full h-full group-hover:scale-105 transition-transform duration-500" 
                             src="<?php echo htmlspecialchars($image_path); ?>" 
                             alt="<?php echo htmlspecialchars($item['title']); ?>"
                             onerror="this.src='https://via.placeholder.com/400x300?text=No+Image'">
                        
                        <?php echo $status_badge; ?>

                        <div class="absolute top-2 right-2 p-2 bg-white/90 rounded-full text-black hover:bg-primary hover:text-black transition-colors z-10">
                            <span class="material-symbols-outlined text-xl block">favorite</span>
                        </div>
                        
                        <div class="absolute bottom-2 left-2 bg-black/70 text-white text-xs font-bold px-2 py-1 rounded backdrop-blur-sm">
                            <?php echo htmlspecialchars($item['address'] ?? 'Nearby'); ?>
                        </div>
                    </div>
                    
                    <div class="pt-3 px-1">
                        <div class="flex justify-between items-start mb-2">
                            <div class="min-w-0 pr-2">
                                <h3 class="font-bold text-lg leading-tight truncate"><?php echo htmlspecialchars($item['title']); ?></h3>
                                <p class="text-sm text-text-muted dark:text-gray-400 mt-1 truncate">
                                    <?php echo ucwords(str_replace('-', ' ', $item['category'] ?? 'General')); ?>
                                </p>
                            </div>
                            <div class="flex items-center gap-1 text-xs font-bold bg-[#f4f4e6] dark:bg-[#3e3d2a] px-2 py-1 rounded-full shrink-0">
                                <span class="material-symbols-outlined text-sm text-yellow-600">star</span>
                                5.0
                            </div>
                        </div>
                        
                        <div class="flex items-end justify-between mt-4">
                            <div class="flex items-baseline gap-1">
                                <span class="text-xl font-bold">₹<?php echo htmlspecialchars($item['price']); ?></span>
                                <span class="text-sm text-text-muted">/ day</span>
                            </div>
                            <div class="w-8 h-8 rounded-full bg-primary flex items-center justify-center text-black text-[10px] font-bold border border-white shadow-sm">
                                <span class="material-symbols-outlined text-sm">arrow_outward</span>
                            </div>
                        </div>
                    </div>
                </a>
                <?php endforeach; ?>
            <?php endif; ?>
</section>
<!-- How it Works -->
<section class="mt-24 py-12 bg-white dark:bg-surface-dark rounded-2xl border border-[#e9e8ce] dark:border-[#3e3d2a]">
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
    <p class="text-gray-300 max-w-xs mb-8">Join our fleet of Delivery Partners. Flexible hours, competitive pay.</p>
    
    <?php if ($is_delivery): ?>
        <a href="delivery_dashboard.php" class="inline-block bg-white text-black font-bold px-6 py-3 rounded-full hover:bg-primary transition-colors">Go to Delivery Dashboard</a>
    <?php elseif ($delivery_pending): ?>
        <a href="driver-registration.php" class="inline-flex items-center gap-2 bg-amber-100 text-amber-800 font-bold px-6 py-3 rounded-full cursor-default">
            <span class="material-symbols-outlined text-lg animate-spin" style="animation-duration: 2s;">hourglass_top</span>
            Pending Approval
        </a>
    <?php else: ?>
        <a href="driver-registration.php" class="inline-block bg-white text-black font-bold px-6 py-3 rounded-full hover:bg-primary transition-colors">Join as Driver</a>
    <?php endif; ?>
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
<!-- Chatbot Widget -->
<div id="chatbot-widget" class="fixed bottom-6 right-6 z-50 flex flex-col items-end gap-4">
    <!-- Chat Window -->
    <div id="chat-window" class="hidden w-[350px] h-[500px] bg-white dark:bg-surface-dark rounded-2xl shadow-2xl border border-[#e9e8ce] dark:border-[#3e3d2a] flex flex-col overflow-hidden transition-all duration-300 origin-bottom-right transform scale-95 opacity-0">
        <!-- Header -->
        <div class="bg-primary p-4 flex justify-between items-center">
            <div class="flex items-center gap-2">
                <span class="material-symbols-outlined text-black">smart_toy</span>
                <h3 class="font-bold text-black">RendeX Assistant</h3>
            </div>
            <button onclick="toggleChat()" class="text-black hover:bg-black/10 rounded-full p-1 transition-colors">
                <span class="material-symbols-outlined">close</span>
            </button>
        </div>
        
        <!-- Messages -->
        <div id="chat-messages" class="flex-1 p-4 overflow-y-auto space-y-4 bg-background-light dark:bg-zinc-900">
            <!-- Welcome Message -->
            <div class="flex items-start gap-2">
                <div class="w-8 h-8 rounded-full bg-primary flex items-center justify-center shrink-0">
                    <span class="material-symbols-outlined text-black text-sm">smart_toy</span>
                </div>
                <div class="bg-white dark:bg-surface-dark border border-[#e9e8ce] dark:border-[#3e3d2a] p-3 rounded-2xl rounded-tl-none shadow-sm max-w-[80%]">
                    <p class="text-sm">Hi! I'm here to help. Ask me anything about RendeX!</p>
                </div>
            </div>
        </div>
        
        <!-- Input -->
        <div class="p-3 bg-white dark:bg-surface-dark border-t border-[#e9e8ce] dark:border-[#3e3d2a]">
            <form id="chat-form" onsubmit="handleChatSubmit(event)" class="flex gap-2">
                <input type="text" id="chat-input" placeholder="Type a message..." class="flex-1 bg-background-light dark:bg-zinc-900 border-none rounded-full px-4 py-2 text-sm focus:ring-2 focus:ring-primary outline-none text-text-main dark:text-white">
                <button type="submit" class="bg-primary text-black w-10 h-10 rounded-full flex items-center justify-center hover:bg-yellow-300 transition-colors shrink-0">
                    <span class="material-symbols-outlined text-xl">send</span>
                </button>
            </form>
        </div>
    </div>

    <!-- Toggle Button -->
    <button onclick="toggleChat()" class="w-14 h-14 bg-primary text-black rounded-full shadow-lg flex items-center justify-center hover:scale-110 transition-transform duration-200 group border-2 border-white dark:border-zinc-800">
        <span class="material-symbols-outlined text-2xl group-hover:hidden">chat</span>
        <span class="material-symbols-outlined text-2xl hidden group-hover:block">expand_less</span>
    </button>
</div>

<script>
    function toggleChat() {
        const window = document.getElementById('chat-window');
        const isHidden = window.classList.contains('hidden');
        
        if (isHidden) {
            window.classList.remove('hidden');
            setTimeout(() => {
                window.classList.remove('scale-95', 'opacity-0');
            }, 10);
            // Auto focus input
            setTimeout(() => {
                document.getElementById('chat-input').focus();
            }, 300);
        } else {
            window.classList.add('scale-95', 'opacity-0');
            setTimeout(() => {
                window.classList.add('hidden');
            }, 300);
        }
    }

    function handleChatSubmit(e) {
        e.preventDefault();
        const input = document.getElementById('chat-input');
        const message = input.value.trim();
        if (!message) return;

        // Add User Message
        addMessage(message, 'user');
        input.value = '';

        // Simulate Bot Response
        const responseDelay = Math.random() * 800 + 400;
        
        setTimeout(() => {
            const response = getBotResponse(message);
            addMessage(response, 'bot');
        }, responseDelay);
    }

    function getBotResponse(msg) {
        const m = msg.toLowerCase();
        
        // Greetings
        if (m.match(/^(hi|hello|hey|yo|greetings)/)) {
            return "Hello! Ready to find a great item or list one of your own?";
        }
        
        // Renting logic
        if (m.includes('rent') || m.includes('book') || m.includes('search') || m.includes('find')) {
            if (m.includes('how')) return "To rent an item, simply browse the categories or use the search bar. Click on an item you like, select your dates, and click 'Request Rental'.";
            return "You can find items to rent by using the search bar above or browsing categories like Electronics, Outdoor Gear, and more.";
        }
        
        // Lending/Listing logic
        if (m.includes('lend') || m.includes('list') || m.includes('sell') || m.includes('post')) {
            if (m.includes('how')) return "Listing is easy! Just click the 'Lend Items' button, upload a few photos, set your price, and you're good to go.";
            if (m.includes('money') || m.includes('earn')) return "You keep 90% of the rental fee. We take a small 10% commission to cover platform maintenance and insurance.";
            return "got something lying around? Click 'Lend Items' in the menu to start earning money from your unused gear.";
        }

        // Delivery logic
        if (m.includes('delivery') || m.includes('driver') || m.includes('ship') || m.includes('pickup')) {
            if (m.includes('partner') || m.includes('job') || m.includes('work')) return "You can apply to become a Delivery Partner! Check the 'Earn on the go' section on the dashboard to apply.";
            return "We offer flexible delivery options. You can either pick up the item yourself or choose a Delivery Partner during checkout for a small fee.";
        }

        // Account/Login
        if (m.includes('login') || m.includes('signup') || m.includes('account') || m.includes('password')) {
            return "You can manage your account settings in the Profile page. If you're having trouble logging in, try the 'Forgot Password' link on the login page.";
        }

        // Pricing/Cost
        if (m.includes('price') || m.includes('cost') || m.includes('fee') || m.includes('pay')) {
            return "Rental prices are set by the owners. RendeX charges a small service fee on transactions to ensure secure payments and user verification.";
        }

        // Safety/Trust
        if (m.includes('safe') || m.includes('scam') || m.includes('trust') || m.includes('insurance') || m.includes('verify')) {
            return "Safety is our priority. We verify all users and offer protection plans for rented items. Always communicate through the platform for your safety.";
        }

        // Contact
        if (m.includes('contact') || m.includes('support') || m.includes('email') || m.includes('human')) {
            return "You can reach our support team at support@rendex.com or call us at 1-800-RENDEX. We're available 24/7!";
        }

        // Return policy
        if (m.includes('return') || m.includes('late')) {
            return "Items should be returned by the agreed time. Late returns may incur additional fees as set by the owner. Please coordinate with the owner or delivery partner.";
        }

        // Generic catch-all
        return "I'm not sure about that specific detail. You can browse our FAQ section or try asking about renting, lending, or delivery services!";
    }

    function addMessage(text, sender) {
        const container = document.getElementById('chat-messages');
        const div = document.createElement('div');
        div.className = sender === 'user' ? 'flex items-end justify-end gap-2' : 'flex items-start gap-2';
        
        // Escape HTML to prevent XSS (basic)
        const safeText = text.replace(/&/g, "&amp;").replace(/</g, "&lt;").replace(/>/g, "&gt;");
        
        let html = '';
        if (sender === 'bot') {
            html += `
                <div class="w-8 h-8 rounded-full bg-primary flex items-center justify-center shrink-0">
                    <span class="material-symbols-outlined text-black text-sm">smart_toy</span>
                </div>
                <div class="bg-white dark:bg-surface-dark border border-[#e9e8ce] dark:border-[#3e3d2a] p-3 rounded-2xl rounded-tl-none shadow-sm max-w-[80%]">
                    <p class="text-sm">${safeText}</p>
                </div>
            `;
        } else {
            html += `
                <div class="bg-black text-white dark:bg-white dark:text-black p-3 rounded-2xl rounded-tr-none shadow-sm max-w-[80%]">
                    <p class="text-sm">${safeText}</p>
                </div>
            `;
        }
        
        div.innerHTML = html;
        container.appendChild(div);
        
        // Smooth scroll to bottom
        setTimeout(() => {
            container.scrollTo({
                top: container.scrollHeight,
                behavior: 'smooth'
            });
        }, 10);
    }
</script>
</body></html>