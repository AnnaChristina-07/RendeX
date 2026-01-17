<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// Redirect Admin and Owner to their respective dashboards if they stumble here?
// well, maybe they want to see this view. But generally, let's keep it accessible for now.
// However, strictly:
if (isset($_SESSION['user_email'])) {
    if ($_SESSION['user_email'] === 'annachristina2005@gmail.com') {
         header("Location: admin_dashboard.php");
         exit();
    }
    if ($_SESSION['user_email'] === 'owner@gmail.com') {
         header("Location: owner_dashboard.php");
         exit();
    }
}

$rentals_file = 'rentals.json';
$rentals = file_exists($rentals_file) ? json_decode(file_get_contents($rentals_file), true) : [];
if (!is_array($rentals)) $rentals = [];

// Handle Return Action
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['return_id'])) {
    $return_id = $_POST['return_id'];
    $updated_rentals = [];
    foreach ($rentals as $rental) {
        if ($rental['id'] === $return_id && $rental['user_id'] === $_SESSION['user_id']) {
            $rental['status'] = 'returned';
            $rental['end_date'] = date('Y-m-d'); // Update end date to today
        }
        $updated_rentals[] = $rental;
    }
    $rentals = $updated_rentals;
    file_put_contents($rentals_file, json_encode($rentals, JSON_PRETTY_PRINT));
    
    // Refresh
    header("Location: client_dashboard.php");
    exit();
}

$active_rentals = [];
$past_rentals = [];

foreach ($rentals as $rental) {
    if ($rental['user_id'] === $_SESSION['user_id']) {
        $is_returned = (isset($rental['status']) && $rental['status'] === 'returned');
        $is_cancelled = (isset($rental['status']) && $rental['status'] === 'cancelled');
        $is_expired = (strtotime($rental['end_date']) < strtotime(date('Y-m-d')));
        
        if (!$is_returned && !$is_expired && !$is_cancelled) {
            $active_rentals[] = $rental;
        } else {
            $past_rentals[] = $rental;
        }
    }
}
?>
<!DOCTYPE html>
<html class="light" lang="en">
<head>
    <meta charset="utf-8"/>
    <meta content="width=device-width, initial-scale=1.0" name="viewport"/>
    <title>My Dashboard - RendeX</title>
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
            
            <div class="flex items-center gap-6 ml-auto">
                <a href="dashboard.php" class="text-sm font-bold hover:text-primary transition-colors">Browse Marketplace</a>
                <span class="h-6 w-px bg-gray-300 dark:bg-gray-700"></span>
                <span class="text-sm font-medium">
                    <?php echo htmlspecialchars($_SESSION['user_name']); ?>
                </span>
                <a href="logout.php" class="bg-primary hover:bg-yellow-300 text-black text-sm font-bold px-6 py-2 rounded-full transition-all">
                    Logout
                </a>
            </div>
        </div>
    </header>

    <main class="flex-1 w-full max-w-[1400px] mx-auto px-4 md:px-10 py-12">
        <div class="mb-10 flex flex-col md:flex-row justify-between items-start md:items-center gap-4">
            <div>
                <h1 class="text-3xl font-black mb-2">My Dashboard</h1>
                <p class="text-text-muted">Manage your rentals and view your history.</p>
            </div>
            <a href="dashboard.php" class="bg-black dark:bg-white text-white dark:text-black px-6 py-3 rounded-full font-bold flex items-center gap-2 hover:opacity-90 transition-opacity">
                <span class="material-symbols-outlined">add_shopping_cart</span>
                Rent New Items
            </a>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
            <!-- Left Column: Rentals -->
            <div class="lg:col-span-2 space-y-12">
                <!-- Now Rented -->
                <div>
                    <h2 class="text-xl font-bold mb-6 flex items-center gap-2">
                        <span class="w-3 h-3 rounded-full bg-green-500 animate-pulse"></span>
                        Active Rentals (<?php echo count($active_rentals); ?>)
                    </h2>
                    
                    <div class="space-y-4">
                        <?php if (empty($active_rentals)): ?>
                            <div class="p-8 rounded-2xl bg-surface-light dark:bg-surface-dark border border-[#e9e8ce] dark:border-[#3e3d2a] text-center border-dashed">
                                <span class="material-symbols-outlined text-4xl text-text-muted mb-2">shopping_bag</span>
                                <p class="text-text-muted font-bold">You aren't renting anything currently.</p>
                                <a href="dashboard.php" class="text-primary hover:underline text-sm font-bold mt-2 inline-block">Browse Items</a>
                            </div>
                        <?php else: ?>
                            <?php foreach ($active_rentals as $rental): 
                                $item = $rental['item'];
                                $img_src = (strpos($item['img'], 'uploads/') === 0) ? $item['img'] : 'https://source.unsplash.com/random/200x200?' . urlencode($item['img']);
                            ?>
                            <div class="bg-surface-light dark:bg-surface-dark p-6 rounded-2xl border border-[#e9e8ce] dark:border-[#3e3d2a] flex flex-col md:flex-row items-center gap-6 shadow-sm">
                                <div class="w-full md:w-32 h-32 rounded-xl bg-gray-100 overflow-hidden shrink-0 border border-gray-200">
                                    <img src="<?php echo htmlspecialchars($img_src); ?>" class="w-full h-full object-cover">
                                </div>
                                <div class="flex-1 text-center md:text-left w-full">
                                    <div class="flex justify-between items-start mb-2">
                                        <h3 class="text-xl font-bold"><?php echo htmlspecialchars($item['name']); ?></h3>
                                        <span class="bg-green-100 text-green-800 text-xs font-bold px-2 py-1 rounded-full uppercase tracking-wide">Active</span>
                                    </div>
                                    <p class="text-sm text-text-muted mb-4">
                                        Rented on: <?php echo date('M d, Y', strtotime($rental['start_date'])); ?> <br>
                                        Due Return: <span class="font-bold text-text-main"><?php echo date('M d, Y', strtotime($rental['end_date'])); ?></span>
                                    </p>
                                    <div class="flex items-center justify-between md:justify-start gap-4 pt-4 border-t border-gray-100 dark:border-gray-800">
                                        <div class="text-left">
                                            <span class="text-xs text-text-muted uppercase font-bold">Total Cost</span>
                                            <span class="block font-black text-xl">₹<?php echo $rental['total_price']; ?></span>
                                        </div>
                                        <form method="POST" class="ml-auto">
                                            <input type="hidden" name="return_id" value="<?php echo $rental['id']; ?>">
                                            <button type="submit" onclick="return confirm('Confirm return of this item?')" class="bg-black dark:bg-white text-white dark:text-black text-sm font-bold px-6 py-2.5 rounded-full hover:opacity-80 transition-opacity flex items-center gap-2">
                                                <span class="material-symbols-outlined text-lg">assignment_return</span>
                                                Return
                                            </button>
                                        </form>
                                    </div>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- History -->
                <div>
                     <h2 class="text-xl font-bold mb-6 flex items-center gap-2">
                        <span class="material-symbols-outlined text-text-muted">history</span>
                        Rental History
                    </h2>
                    <div class="space-y-4 opacity-80 hover:opacity-100 transition-opacity">
                        <?php if (empty($past_rentals)): ?>
                            <p class="text-text-muted italic px-2">No history yet.</p>
                        <?php else: ?>
                            <?php foreach ($past_rentals as $rental): 
                                $item = $rental['item'];
                                $img_src = (strpos($item['img'], 'uploads/') === 0) ? $item['img'] : 'https://source.unsplash.com/random/200x200?' . urlencode($item['img']);
                                $status = $rental['status'] ?? 'expired';
                            ?>
                            <div class="bg-surface-light dark:bg-surface-dark p-4 rounded-xl border border-[#e9e8ce] dark:border-[#3e3d2a] flex items-center gap-4">
                                <div class="w-16 h-16 rounded-lg bg-gray-100 overflow-hidden shrink-0">
                                    <img src="<?php echo htmlspecialchars($img_src); ?>" class="w-full h-full object-cover grayscale">
                                </div>
                                <div class="flex-1">
                                    <h4 class="font-bold"><?php echo htmlspecialchars($item['name']); ?></h4>
                                    <p class="text-xs text-text-muted"><?php echo date('M d, Y', strtotime($rental['end_date'])); ?></p>
                                </div>
                                <div class="text-right">
                                    <span class="block font-bold">₹<?php echo $rental['total_price']; ?></span>
                                    <span class="text-[10px] font-bold uppercase bg-gray-100 px-2 py-0.5 rounded text-gray-500"><?php echo $status; ?></span>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- Right Column: Profile Summary -->
            <div class="lg:col-span-1">
                <div class="bg-surface-light dark:bg-surface-dark p-6 rounded-3xl border border-[#e9e8ce] dark:border-[#3e3d2a] sticky top-32">
                    <div class="flex flex-col items-center text-center mb-6">
                        <div class="w-24 h-24 rounded-full bg-primary flex items-center justify-center text-4xl font-black mb-4">
                            <?php echo substr($_SESSION['user_name'], 0, 1); ?>
                        </div>
                        <h2 class="text-2xl font-bold"><?php echo htmlspecialchars($_SESSION['user_name']); ?></h2>
                        <p class="text-text-muted"><?php echo htmlspecialchars($_SESSION['user_email']); ?></p>
                        <span class="mt-2 text-xs font-bold bg-gray-100 dark:bg-gray-800 px-3 py-1 rounded-full uppercase tracking-wider">Client Account</span>
                    </div>
                    
                    <div class="space-y-4 border-t border-[#e9e8ce] dark:border-[#3e3d2a] pt-6">
                        <div class="flex justify-between items-center">
                            <span class="text-text-muted text-sm font-bold">Total Rentals</span>
                            <span class="font-black text-lg"><?php echo count($rentals); ?></span>
                        </div>
                         <div class="flex justify-between items-center">
                            <span class="text-text-muted text-sm font-bold">Member Since</span>
                            <span class="font-bold">2026</span>
                        </div>
                    </div>

                    <a href="logout.php" class="w-full mt-8 border border-[#e9e8ce] dark:border-[#3e3d2a] hover:bg-red-50 hover:text-red-600 hover:border-red-100 dark:hover:bg-red-900/10 font-bold py-3 rounded-xl flex items-center justify-center gap-2 transition-all">
                        <span class="material-symbols-outlined">logout</span>
                        Sign Out
                    </a>
                </div>
            </div>
        </div>

    </main>

    <!-- Footer -->
    <footer class="bg-surface-light dark:bg-surface-dark border-t border-[#e9e8ce] dark:border-[#3e3d2a] pt-16 pb-8 px-4 md:px-10 mt-auto">
        <div class="text-center text-sm font-bold text-text-muted">
            &copy; 2026 RendeX. All rights reserved.
        </div>
    </footer>
</body>
</html>
