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
<!-- Card 1: Student Essentials - Calculator -->
<div class="group bg-surface-light dark:bg-surface-dark rounded-xl p-3 shadow-sm hover:shadow-md transition-all border border-transparent hover:border-[#e9e8ce] dark:hover:border-[#3e3d2a]">
<div class="relative aspect-[4/3] rounded-lg overflow-hidden bg-gray-200">
<img class="object-cover w-full h-full group-hover:scale-105 transition-transform duration-500" data-alt="Scientific Calculator" src="https://thumbs.dreamstime.com/b/notebook-calculator-study-table-back-to-school-concept-generated-image-open-scientific-simple-focused-academic-392115780.jpg"/>
<button class="absolute top-2 right-2 p-2 bg-white/90 rounded-full text-black hover:bg-primary hover:text-black transition-colors">
<span class="material-symbols-outlined text-xl block">favorite</span>
</button>
<div class="absolute bottom-2 left-2 bg-black/70 text-white text-xs font-bold px-2 py-1 rounded">
                                2.1 km away
                            </div>
</div>
<div class="pt-3 px-1">
<div class="flex justify-between items-start">
<div>
<h3 class="font-bold text-lg leading-tight truncate">Scientific Calculator</h3>
<p class="text-sm text-text-muted dark:text-gray-400 mt-1">Student Essentials</p>
</div>
<div class="flex items-center gap-1 text-xs font-bold bg-[#f4f4e6] dark:bg-[#3e3d2a] px-2 py-1 rounded-full">
<span class="material-symbols-outlined text-sm text-yellow-600">star</span>
                                    4.7
                                </div>
</div>
<div class="flex items-end justify-between mt-4">
<div class="flex items-baseline gap-1">
<span class="text-xl font-bold">$5</span>
<span class="text-sm text-text-muted">/ day</span>
</div>
                        <div class="w-8 h-8 rounded-full bg-primary flex items-center justify-center text-black text-[10px] font-bold border border-white shadow-sm">
                            O
                        </div>
</div>
</div>
</div>

<!-- Card 3: Vintage Collections - Camera -->
<div class="group bg-surface-light dark:bg-surface-dark rounded-xl p-3 shadow-sm hover:shadow-md transition-all border border-transparent hover:border-[#e9e8ce] dark:hover:border-[#3e3d2a]">
<div class="relative aspect-[4/3] rounded-lg overflow-hidden bg-gray-200">
<img class="object-cover w-full h-full group-hover:scale-105 transition-transform duration-500" data-alt="Vintage Film Camera" src="https://i0.wp.com/legionxstudios.com/wp-content/uploads/2022/10/nikon-f2-vintage-film-camera-e1666333556259.jpg?resize=1208%2C919&ssl=1"/>
<button class="absolute top-2 right-2 p-2 bg-white/90 rounded-full text-black hover:bg-primary hover:text-black transition-colors">
<span class="material-symbols-outlined text-xl block">favorite</span>
</button>
<div class="absolute bottom-2 left-2 bg-black/70 text-white text-xs font-bold px-2 py-1 rounded">
                                1.8 km away
                            </div>
</div>
<div class="pt-3 px-1">
<div class="flex justify-between items-start">
<div>
<h3 class="font-bold text-lg leading-tight truncate">Vintage Film Camera</h3>
<p class="text-sm text-text-muted dark:text-gray-400 mt-1">Vintage Collections</p>
</div>
<div class="flex items-center gap-1 text-xs font-bold bg-[#f4f4e6] dark:bg-[#3e3d2a] px-2 py-1 rounded-full">
<span class="material-symbols-outlined text-sm text-yellow-600">star</span>
                                    4.9
                                </div>
</div>
<div class="flex items-end justify-between mt-4">
<div class="flex items-baseline gap-1">
<span class="text-xl font-bold">$25</span>
<span class="text-sm text-text-muted">/ day</span>
</div>
<div class="w-8 h-8 rounded-full bg-primary flex items-center justify-center text-black text-[10px] font-bold border border-white shadow-sm">
O
</div>
</div>
</div>
</div>
<!-- Card 4: Fitness Equipment - Yoga Mat -->
<div class="group bg-surface-light dark:bg-surface-dark rounded-xl p-3 shadow-sm hover:shadow-md transition-all border border-transparent hover:border-[#e9e8ce] dark:hover:border-[#3e3d2a]">
<div class="relative aspect-[4/3] rounded-lg overflow-hidden bg-gray-200">
<img class="object-cover w-full h-full group-hover:scale-105 transition-transform duration-500" data-alt="Rolled Yoga Mat" src="https://thumbs.dreamstime.com/b/rolled-pink-yoga-mat-isolated-white-background-single-image-used-fitness-exercise-wellness-product-390995590.jpg"/>
<button class="absolute top-2 right-2 p-2 bg-white/90 rounded-full text-black hover:bg-primary hover:text-black transition-colors">
<span class="material-symbols-outlined text-xl block">favorite</span>
</button>
<div class="absolute bottom-2 left-2 bg-black/70 text-white text-xs font-bold px-2 py-1 rounded">
                                0.9 km away
                            </div>
</div>
<div class="pt-3 px-1">
<div class="flex justify-between items-start">
<div>
<h3 class="font-bold text-lg leading-tight truncate">Yoga Mat</h3>
<p class="text-sm text-text-muted dark:text-gray-400 mt-1">Fitness Equipment</p>
</div>
<div class="flex items-center gap-1 text-xs font-bold bg-[#f4f4e6] dark:bg-[#3e3d2a] px-2 py-1 rounded-full">
<span class="material-symbols-outlined text-sm text-yellow-600">star</span>
                                    4.6
                                </div>
</div>
<div class="flex items-end justify-between mt-4">
<div class="flex items-baseline gap-1">
<span class="text-xl font-bold">$8</span>
<span class="text-sm text-text-muted">/ day</span>
</div>
<div class="w-8 h-8 rounded-full bg-primary flex items-center justify-center text-black text-[10px] font-bold border border-white shadow-sm">
O
</div>
</div>
</div>
</div>
<!-- Card 5: Agricultural Tools - Grass Trimmer -->
<div class="group bg-surface-light dark:bg-surface-dark rounded-xl p-3 shadow-sm hover:shadow-md transition-all border border-transparent hover:border-[#e9e8ce] dark:hover:border-[#3e3d2a]">
<div class="relative aspect-[4/3] rounded-lg overflow-hidden bg-gray-200">
<img class="object-cover w-full h-full group-hover:scale-105 transition-transform duration-500" data-alt="Cordless Grass Trimmer" src="https://cdn.thewirecutter.com/wp-content/media/2025/06/BEST-STRING-TRIMMER-0052.jpg?auto=webp&quality=60&width=570"/>
<button class="absolute top-2 right-2 p-2 bg-white/90 rounded-full text-black hover:bg-primary hover:text-black transition-colors">
<span class="material-symbols-outlined text-xl block">favorite</span>
</button>
<div class="absolute bottom-2 left-2 bg-black/70 text-white text-xs font-bold px-2 py-1 rounded">
                                4.5 km away
                            </div>
</div>
<div class="pt-3 px-1">
<div class="flex justify-between items-start">
<div>
<h3 class="font-bold text-lg leading-tight truncate">Grass Trimmer</h3>
<p class="text-sm text-text-muted dark:text-gray-400 mt-1">Agricultural Tools</p>
</div>
<div class="flex items-center gap-1 text-xs font-bold bg-[#f4f4e6] dark:bg-[#3e3d2a] px-2 py-1 rounded-full">
<span class="material-symbols-outlined text-sm text-yellow-600">star</span>
                                    4.8
                                </div>
</div>
<div class="flex items-end justify-between mt-4">
<div class="flex items-baseline gap-1">
<span class="text-xl font-bold">$18</span>
<span class="text-sm text-text-muted">/ day</span>
</div>
                        <div class="w-8 h-8 rounded-full bg-primary flex items-center justify-center text-black text-[10px] font-bold border border-white shadow-sm">
                            O
                        </div>
</div>
</div>
</div>
<!-- Card 6: Medical Items - Wheelchair -->
<div class="group bg-surface-light dark:bg-surface-dark rounded-xl p-3 shadow-sm hover:shadow-md transition-all border border-transparent hover:border-[#e9e8ce] dark:hover:border-[#3e3d2a]">
<div class="relative aspect-[4/3] rounded-lg overflow-hidden bg-gray-200">
<img class="object-cover w-full h-full group-hover:scale-105 transition-transform duration-500" data-alt="Foldable Wheelchair" src="https://images.squarespace-cdn.com/content/v1/5f58f6b0e418950766874381/c2a8abc6-e985-47a4-b002-cd10a4365f23/LightGlide_Plus.jpg"/>
<button class="absolute top-2 right-2 p-2 bg-white/90 rounded-full text-black hover:bg-primary hover:text-black transition-colors">
<span class="material-symbols-outlined text-xl block">favorite</span>
</button>
<div class="absolute bottom-2 left-2 bg-black/70 text-white text-xs font-bold px-2 py-1 rounded">
                                2.7 km away
                            </div>
</div>
<div class="pt-3 px-1">
<div class="flex justify-between items-start">
<div>
<h3 class="font-bold text-lg leading-tight truncate">Wheelchair</h3>
<p class="text-sm text-text-muted dark:text-gray-400 mt-1">Medical Items</p>
</div>
<div class="flex items-center gap-1 text-xs font-bold bg-[#f4f4e6] dark:bg-[#3e3d2a] px-2 py-1 rounded-full">
<span class="material-symbols-outlined text-sm text-yellow-600">star</span>
                                    5.0
                                </div>
</div>
<div class="flex items-end justify-between mt-4">
<div class="flex items-baseline gap-1">
<span class="text-xl font-bold">$30</span>
<span class="text-sm text-text-muted">/ day</span>
</div>
<div class="w-8 h-8 rounded-full bg-primary flex items-center justify-center text-black text-[10px] font-bold border border-white shadow-sm">
O
</div>
</div>
</div>
</div>
<!-- Card 7: Electronic Devices - Speaker -->
<div class="group bg-surface-light dark:bg-surface-dark rounded-xl p-3 shadow-sm hover:shadow-md transition-all border border-transparent hover:border-[#e9e8ce] dark:hover:border-[#3e3d2a]">
<div class="relative aspect-[4/3] rounded-lg overflow-hidden bg-gray-200">
<img class="object-cover w-full h-full group-hover:scale-105 transition-transform duration-500" data-alt="Portable Bluetooth Speaker" src="https://cdn.thewirecutter.com/wp-content/media/2024/11/portablebluetoothspeakers-2048px-9119.jpg?auto=webp&quality=75&width=1024"/>
<button class="absolute top-2 right-2 p-2 bg-white/90 rounded-full text-black hover:bg-primary hover:text-black transition-colors">
<span class="material-symbols-outlined text-xl block">favorite</span>
</button>
<div class="absolute bottom-2 left-2 bg-black/70 text-white text-xs font-bold px-2 py-1 rounded">
                                1.2 km away
                            </div>
</div>
<div class="pt-3 px-1">
<div class="flex justify-between items-start">
<div>
<h3 class="font-bold text-lg leading-tight truncate">Bluetooth Speaker</h3>
<p class="text-sm text-text-muted dark:text-gray-400 mt-1">Electronic Devices</p>
</div>
<div class="flex items-center gap-1 text-xs font-bold bg-[#f4f4e6] dark:bg-[#3e3d2a] px-2 py-1 rounded-full">
<span class="material-symbols-outlined text-sm text-yellow-600">star</span>
                                    4.9
                                </div>
</div>
<div class="flex items-end justify-between mt-4">
<div class="flex items-baseline gap-1">
<span class="text-xl font-bold">$15</span>
<span class="text-sm text-text-muted">/ day</span>
</div>
<div class="w-8 h-8 rounded-full bg-primary flex items-center justify-center text-black text-[10px] font-bold border border-white shadow-sm">
O
</div>
</div>
</div>
</div>
<!-- Card 8: Travel/Outdoor Gear - Tent -->
<div class="group bg-surface-light dark:bg-surface-dark rounded-xl p-3 shadow-sm hover:shadow-md transition-all border border-transparent hover:border-[#e9e8ce] dark:hover:border-[#3e3d2a]">
<div class="relative aspect-[4/3] rounded-lg overflow-hidden bg-gray-200">
<img class="object-cover w-full h-full group-hover:scale-105 transition-transform duration-500" data-alt="Lightweight Camping Tent" src="https://tbo-media.sfo2.digitaloceanspaces.com/wp-content/uploads/2023/11/06224622/Hyperlite-Mountain-Gear-Mid-1-lead.jpg"/>
<button class="absolute top-2 right-2 p-2 bg-white/90 rounded-full text-black hover:bg-primary hover:text-black transition-colors">
<span class="material-symbols-outlined text-xl block">favorite</span>
</button>
<div class="absolute bottom-2 left-2 bg-black/70 text-white text-xs font-bold px-2 py-1 rounded">
                                5.1 km away
                            </div>
</div>
<div class="pt-3 px-1">
<div class="flex justify-between items-start">
<div>
<h3 class="font-bold text-lg leading-tight truncate">Camping Tent</h3>
<p class="text-sm text-text-muted dark:text-gray-400 mt-1">Travel/Outdoor Gear</p>
</div>
<div class="flex items-center gap-1 text-xs font-bold bg-[#f4f4e6] dark:bg-[#3e3d2a] px-2 py-1 rounded-full">
<span class="material-symbols-outlined text-sm text-yellow-600">star</span>
                                    4.7
                                </div>
</div>
<div class="flex items-end justify-between mt-4">
<div class="flex items-baseline gap-1">
<span class="text-xl font-bold">$35</span>
<span class="text-sm text-text-muted">/ day</span>
</div>
<div class="w-8 h-8 rounded-full bg-primary flex items-center justify-center text-black text-[10px] font-bold border border-white shadow-sm">
O
</div>
</div>
</div>
</div>
<!-- Card 9: Home-Daily Essentials - Fan -->
<div class="group bg-surface-light dark:bg-surface-dark rounded-xl p-3 shadow-sm hover:shadow-md transition-all border border-transparent hover:border-[#e9e8ce] dark:hover:border-[#3e3d2a]">
<div class="relative aspect-[4/3] rounded-lg overflow-hidden bg-gray-200">
<img class="object-cover w-full h-full group-hover:scale-105 transition-transform duration-500" data-alt="Standing Electric Fan" src="https://static.vecteezy.com/system/resources/thumbnails/065/407/837/small_2x/white-simple-electric-fan-standing-in-front-of-the-sideboard-cabinet-in-the-room-photo.jpg"/>
<button class="absolute top-2 right-2 p-2 bg-white/90 rounded-full text-black hover:bg-primary hover:text-black transition-colors">
<span class="material-symbols-outlined text-xl block">favorite</span>
</button>
<div class="absolute bottom-2 left-2 bg-black/70 text-white text-xs font-bold px-2 py-1 rounded">
                                0.5 km away
                            </div>
</div>
<div class="pt-3 px-1">
<div class="flex justify-between items-start">
<div>
<h3 class="font-bold text-lg leading-tight truncate">Standing Electric Fan</h3>
<p class="text-sm text-text-muted dark:text-gray-400 mt-1">Home-Daily Essentials</p>
</div>
<div class="flex items-center gap-1 text-xs font-bold bg-[#f4f4e6] dark:bg-[#3e3d2a] px-2 py-1 rounded-full">
<span class="material-symbols-outlined text-sm text-yellow-600">star</span>
                                    4.8
                                </div>
</div>
<div class="flex items-end justify-between mt-4">
<div class="flex items-baseline gap-1">
<span class="text-xl font-bold">$12</span>
<span class="text-sm text-text-muted">/ day</span>
</div>
                        <div class="w-8 h-8 rounded-full bg-primary flex items-center justify-center text-black text-[10px] font-bold border border-white shadow-sm">
                            O
                        </div>
</div>
</div>
</div>
<!-- Card 10: Furniture - Bed Frame -->
<div class="group bg-surface-light dark:bg-surface-dark rounded-xl p-3 shadow-sm hover:shadow-md transition-all border border-transparent hover:border-[#e9e8ce] dark:hover:border-[#3e3d2a]">
<div class="relative aspect-[4/3] rounded-lg overflow-hidden bg-gray-200">
<img class="object-cover w-full h-full group-hover:scale-105 transition-transform duration-500" data-alt="Modern Wooden Bed Frame" src="https://cdn.thewirecutter.com/wp-content/media/2025/08/BEST-MODERN-BED-FRAMES-SUB-2048px-4379.jpg"/>
<button class="absolute top-2 right-2 p-2 bg-white/90 rounded-full text-black hover:bg-primary hover:text-black transition-colors">
<span class="material-symbols-outlined text-xl block">favorite</span>
</button>
<div class="absolute bottom-2 left-2 bg-black/70 text-white text-xs font-bold px-2 py-1 rounded">
                                6.3 km away
                            </div>
</div>
<div class="pt-3 px-1">
<div class="flex justify-between items-start">
<div>
<h3 class="font-bold text-lg leading-tight truncate">Modern Bed Frame</h3>
<p class="text-sm text-text-muted dark:text-gray-400 mt-1">Furniture</p>
</div>
<div class="flex items-center gap-1 text-xs font-bold bg-[#f4f4e6] dark:bg-[#3e3d2a] px-2 py-1 rounded-full">
<span class="material-symbols-outlined text-sm text-yellow-600">star</span>
                                    4.9
                                </div>
</div>
<div class="flex items-end justify-between mt-4">
<div class="flex items-baseline gap-1">
<span class="text-xl font-bold">$50</span>
<span class="text-sm text-text-muted">/ day</span>
</div>
                        <div class="w-8 h-8 rounded-full bg-primary flex items-center justify-center text-black text-[10px] font-bold border border-white shadow-sm">
                            O
                        </div>
</div>
</div>
</div>
</div>
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
<button class="bg-black text-white font-bold px-6 py-3 rounded-full hover:bg-gray-800 transition-colors">Start Listing</button>
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
</body></html>