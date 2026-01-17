<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
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
            $rental['end_date'] = date('Y-m-d'); // Update end date to today (actual return date)
        }
        $updated_rentals[] = $rental;
    }
    $rentals = $updated_rentals;
    file_put_contents($rentals_file, json_encode($rentals, JSON_PRETTY_PRINT));
    
    // Refresh to show changes
    header("Location: rentals.php");
    exit();
}

$active_rentals = [];
$past_rentals = [];

foreach ($rentals as $rental) {
    if ($rental['user_id'] === $_SESSION['user_id']) {
        $is_returned = (isset($rental['status']) && $rental['status'] === 'returned');
        $is_expired = (strtotime($rental['end_date']) < strtotime(date('Y-m-d')));
        
        if (!$is_returned && !$is_expired) {
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
    <title>RendeX - My Rentals</title>
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
                    <a href="dashboard.php" class="text-sm font-bold hover:text-primary transition-colors">Browse Gear</a>
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
        </div>
    </header>

    <main class="flex-1 w-full max-w-[1400px] mx-auto px-4 md:px-10 py-12">
        <h1 class="text-3xl font-black mb-10">My Rentals History</h1>

        <!-- Now Rented -->
        <div class="mb-12">
            <h2 class="text-xl font-bold mb-6 flex items-center gap-2">
                <span class="w-2 h-2 rounded-full bg-green-500"></span>
                Now Rented (Active)
            </h2>
            
            <div class="space-y-4">
                <?php if (empty($active_rentals)): ?>
                    <p class="text-text-muted italic">No active rentals at the moment.</p>
                <?php else: ?>
                    <?php foreach ($active_rentals as $rental): 
                        $item = $rental['item'];
                        $img_src = (strpos($item['img'], 'uploads/') === 0) ? $item['img'] : 'https://source.unsplash.com/random/200x200?' . urlencode($item['img']);
                    ?>
                    <div class="bg-surface-light dark:bg-surface-dark p-6 rounded-2xl border border-[#e9e8ce] dark:border-[#3e3d2a] flex flex-col md:flex-row items-center gap-6">
                        <div class="w-full md:w-24 h-24 rounded-xl bg-gray-100 overflow-hidden shrink-0">
                            <img src="<?php echo htmlspecialchars($img_src); ?>" class="w-full h-full object-cover">
                        </div>
                        <div class="flex-1 text-center md:text-left">
                            <h3 class="text-lg font-bold"><?php echo htmlspecialchars($item['name']); ?></h3>
                            <p class="text-sm text-text-muted mt-1">
                                Due Return: <span class="font-bold text-text-main"><?php echo date('M d, Y', strtotime($rental['end_date'])); ?></span>
                            </p>
                        </div>
                        <div class="text-right flex flex-col items-center md:items-end gap-2">
                             <span class="block font-black text-xl">₹<?php echo $rental['total_price']; ?></span>
                             <form method="POST">
                                 <input type="hidden" name="return_id" value="<?php echo $rental['id']; ?>">
                                 <button type="submit" onclick="return confirm('Confirm return of this item?')" class="bg-black text-white text-xs font-bold px-4 py-2 rounded-full hover:bg-gray-800 transition-colors">
                                     Return Item
                                 </button>
                             </form>
                        </div>
                    </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>

        <!-- Earlierly Rented -->
        <div>
            <h2 class="text-xl font-bold mb-6 flex items-center gap-2">
                <span class="w-2 h-2 rounded-full bg-gray-400"></span>
                Earlierly Rented (History)
            </h2>
            
             <div class="space-y-4">
                <?php if (empty($past_rentals)): ?>
                    <p class="text-text-muted italic">No past rental history.</p>
                <?php else: ?>
                    <?php foreach ($past_rentals as $rental): 
                        $item = $rental['item'];
                         $img_src = (strpos($item['img'], 'uploads/') === 0) ? $item['img'] : 'https://source.unsplash.com/random/200x200?' . urlencode($item['img']);
                         $is_returned_early = (isset($rental['status']) && $rental['status'] === 'returned');
                    ?>
                    <div class="bg-surface-light dark:bg-surface-dark p-6 rounded-2xl border border-[#e9e8ce] dark:border-[#3e3d2a] flex flex-col md:flex-row items-center gap-6 opacity-75 grayscale hover:grayscale-0 transition-all">
                        <div class="w-full md:w-24 h-24 rounded-xl bg-gray-100 overflow-hidden shrink-0">
                            <img src="<?php echo htmlspecialchars($img_src); ?>" class="w-full h-full object-cover">
                        </div>
                        <div class="flex-1 text-center md:text-left">
                            <h3 class="text-lg font-bold"><?php echo htmlspecialchars($item['name']); ?></h3>
                            <p class="text-sm text-text-muted mt-1">
                                <?php echo $is_returned_early ? 'Returned on' : 'Expired on'; ?>: <span class="font-bold text-text-main"><?php echo date('M d, Y', strtotime($rental['end_date'])); ?></span>
                            </p>
                        </div>
                        <div class="text-right">
                             <span class="block font-black text-xl">₹<?php echo $rental['total_price']; ?></span>
                             <span class="text-xs <?php echo $is_returned_early ? 'bg-blue-100 text-blue-800' : 'bg-gray-100 text-gray-800'; ?> font-bold px-2 py-1 rounded-md uppercase">
                                 <?php echo $is_returned_early ? 'Returned' : 'Completed'; ?>
                             </span>
                        </div>
                    </div>
                    <?php endforeach; ?>
                <?php endif; ?>
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
