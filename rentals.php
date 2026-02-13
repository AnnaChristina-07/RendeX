<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$rentals_file = 'rentals.json';
$rentals = file_exists($rentals_file) ? json_decode(file_get_contents($rentals_file), true) : [];
if (!is_array($rentals)) $rentals = [];

// Load Deliveries
$deliveries_file = 'deliveries.json';
$deliveries = file_exists($deliveries_file) ? json_decode(file_get_contents($deliveries_file), true) : [];

// Load Users (for Driver Info)
$users_file = 'users.json';
$users = file_exists($users_file) ? json_decode(file_get_contents($users_file), true) : [];
$users_map = [];
foreach ($users as $u) {
    if (isset($u['id'])) {
        $users_map[$u['id']] = $u;
    }
}


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
                        
                        // Delivery Tracking Logic
                        $is_delivery = (isset($rental['fulfillment']) && $rental['fulfillment'] === 'delivery');
                        $delivery_status = 'pending';
                        $driver = null;
                        
                        if ($is_delivery) {
                            $rental_id = $rental['id'];
                            // Find delivery record
                            foreach ($deliveries as $d) {
                                if (isset($d['rental_id']) && $d['rental_id'] === $rental_id) {
                                    $delivery_status = $d['status']; // pending, assigned, picked_up, delivered
                                    // Get driver info
                                    if (isset($d['partner_id']) && isset($users_map[$d['partner_id']])) {
                                        $driver = $users_map[$d['partner_id']];
                                    }
                                    break;
                                }
                            }
                        }
                    ?>
                    <div class="bg-surface-light dark:bg-surface-dark p-6 rounded-2xl border border-[#e9e8ce] dark:border-[#3e3d2a] flex flex-col gap-6">
                        <div class="flex flex-col md:flex-row items-center gap-6">
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
                                <?php if(isset($rental['status']) && $rental['status'] === 'dispute'): ?>
                                    <span class="bg-red-50 text-red-600 text-xs font-bold px-4 py-2 rounded-full inline-flex items-center gap-1 border border-red-100">
                                         <span class="material-symbols-outlined text-sm">gavel</span> Dispute Active
                                    </span>
                                <?php elseif(isset($rental['return_status']) && $rental['return_status'] === 'pending_inspection'): ?>
                                    <span class="bg-yellow-100 text-yellow-800 text-xs font-bold px-4 py-2 rounded-full inline-block border border-yellow-200">
                                        Return Initiated
                                    </span>
                                <?php else: ?>
                                    <a href="return-rental.php?id=<?php echo $rental['id']; ?>" class="bg-black text-white text-xs font-bold px-4 py-2 rounded-full hover:bg-gray-800 transition-colors inline-block">
                                        Return Item
                                    </a>
                                <?php endif; ?>
                            </div>
                        </div>

                        <!-- Delivery Tracking Section -->
                        <?php if ($is_delivery): ?>
                        <div class="border-t border-gray-100 dark:border-gray-700 pt-6 mt-6">
                            <h4 class="text-sm font-bold mb-6 flex items-center gap-2 text-text-muted">
                                <span class="material-symbols-outlined text-lg">local_shipping</span> 
                                Delivery Status
                            </h4>
                            
                            <!-- Enhanced Timeline -->
                            <?php
                                // Status Levels
                                $levels = ['pending' => 0, 'assigned' => 1, 'picked_up' => 2, 'delivered' => 3];
                                $current_level = $levels[$delivery_status] ?? 0;
                                
                                $steps = [
                                    ['label' => 'Order Placed', 'icon' => 'inventory_2'],
                                    ['label' => 'Driver Assigned', 'icon' => 'person_pin'],
                                    ['label' => 'In Transit', 'icon' => 'local_shipping'],
                                    ['label' => 'Delivered', 'icon' => 'home_pin']
                                ];
                            ?>
                            <div class="w-full max-w-4xl mx-auto mb-8">
                                <div class="flex items-center justify-between w-full relative">
                                    <?php foreach ($steps as $index => $step): 
                                        $is_completed = $index <= $current_level;
                                        $is_current = $index === $current_level;
                                        
                                        // Colors
                                        $bg_color = $is_completed ? 'bg-black text-white dark:bg-primary dark:text-black' : 'bg-gray-100 text-gray-400 dark:bg-gray-700 dark:text-gray-500';
                                        $text_color = $is_completed ? 'text-black dark:text-white' : 'text-gray-400 dark:text-gray-500';
                                        $border_color = $is_current ? 'ring-4 ring-gray-100 dark:ring-gray-800' : '';
                                    ?>
                                        <!-- Step Circle -->
                                        <div class="relative z-10 flex flex-col items-center">
                                            <div class="w-10 h-10 rounded-full <?php echo $bg_color . ' ' . $border_color; ?> flex items-center justify-center transition-all duration-500 shadow-sm relative group">
                                                <span class="material-symbols-outlined text-xl"><?php echo $step['icon']; ?></span>
                                                <?php if($is_current && $delivery_status !== 'delivered'): ?>
                                                    <span class="absolute inline-flex h-full w-full rounded-full bg-black opacity-10 animate-ping"></span>
                                                <?php endif; ?>
                                            </div>
                                            <span class="absolute top-12 text-[10px] md:text-xs font-bold uppercase tracking-wider text-center w-32 <?php echo $text_color; ?> transition-colors duration-500">
                                                <?php echo $step['label']; ?>
                                            </span>
                                        </div>

                                        <!-- Connecting Line (Skip on last item) -->
                                        <?php if ($index < count($steps) - 1): 
                                            $line_color = ($index < $current_level) ? 'bg-black dark:bg-primary' : 'bg-gray-100 dark:bg-gray-700';
                                        ?>
                                            <div class="flex-1 h-1 mx-2 rounded-full relative overflow-hidden bg-gray-100 dark:bg-gray-700">
                                                <div class="absolute top-0 left-0 h-full w-full <?php echo $line_color; ?> transition-all duration-700 origin-left"></div>
                                            </div>
                                        <?php endif; ?>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                            
                            <!-- Spacer for text below circles -->
                            <div class="h-6"></div>

                            <!-- Driver Info Card -->
                            <?php if ($driver): ?>
                            <div class="mt-4 bg-white dark:bg-[#2d2c18] border border-gray-100 dark:border-gray-700 rounded-2xl p-5 flex items-center justify-between gap-4 max-w-lg shadow-sm hover:shadow-md transition-shadow duration-300">
                                <div class="flex items-center gap-4">
                                    <div class="w-14 h-14 rounded-full bg-[#f9f506] flex items-center justify-center text-black font-black text-xl shadow-inner">
                                        <?php echo strtoupper(substr($driver['name'], 0, 1)); ?>
                                    </div>
                                    <div>
                                        <div class="flex items-center gap-2 mb-1">
                                            <h5 class="font-bold text-lg leading-none"><?php echo htmlspecialchars($driver['name']); ?></h5>
                                            <span class="bg-gray-100 dark:bg-gray-700 text-[10px] font-bold px-2 py-0.5 rounded uppercase">Driver</span>
                                        </div>
                                        <p class="text-xs text-text-muted flex items-center gap-1">
                                            <span class="material-symbols-outlined text-sm">two_wheeler</span>
                                            <?php echo htmlspecialchars($driver['delivery_application']['vehicle_number'] ?? 'Vehicle Assigned'); ?>
                                        </p>
                                    </div>
                                </div>
                                <a href="tel:<?php echo htmlspecialchars($driver['phone']); ?>" class="group bg-black text-white dark:bg-white dark:text-black w-12 h-12 rounded-full flex items-center justify-center shadow-lg hover:scale-110 transition-transform active:scale-95">
                                    <span class="material-symbols-outlined group-hover:animate-shake">call</span>
                                </a>
                            </div>
                            <?php elseif ($is_delivery && $current_level < 1): ?>
                                <div class="mt-6 flex flex-col items-center justify-center py-6 bg-yellow-50 dark:bg-yellow-900/20 rounded-2xl border border-yellow-100 dark:border-yellow-900/50 text-center animate-pulse">
                                    <div class="w-10 h-10 bg-yellow-100 dark:bg-yellow-800/50 rounded-full flex items-center justify-center mb-2 text-yellow-700 dark:text-yellow-400">
                                        <span class="material-symbols-outlined animate-spin">progress_activity</span>
                                    </div>
                                    <p class="text-sm font-bold text-yellow-800 dark:text-yellow-400">Finding nearest delivery partner...</p>
                                    <p class="text-xs text-yellow-600 dark:text-yellow-500 mt-1">This usually takes 2-5 minutes</p>
                                </div>
                            <?php endif; ?>

                        </div>
                        <?php endif; ?>
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

    <!-- Toast Notification -->
    <div id="toast" class="fixed bottom-5 right-5 z-50 transform translate-y-20 opacity-0 transition-all duration-300">
        <div class="bg-black text-white px-6 py-4 rounded-2xl shadow-2xl flex items-center gap-4">
             <span class="material-symbols-outlined text-primary">check_circle</span>
             <p class="font-bold pr-2" id="toast-message">Success</p>
        </div>
    </div>

    <script>
        // Check for msg parameter
        const urlParams = new URLSearchParams(window.location.search);
        const msg = urlParams.get('msg');
        if (msg) {
            const toast = document.getElementById('toast');
            const toastMsg = document.getElementById('toast-message');
            
            if (msg === 'return_initiated') {
                toastMsg.textContent = 'Return request submitted! Waiting for owner confirmation.';
            } else if (msg === 'returned_success') {
                toastMsg.textContent = 'Return confirmed successfully.';
            }
            
            if (msg === 'return_initiated' || msg === 'returned_success') {
                setTimeout(() => {
                    toast.classList.remove('translate-y-20', 'opacity-0');
                    setTimeout(() => {
                         toast.classList.add('translate-y-20', 'opacity-0');
                         // Clean URL
                         window.history.replaceState({}, document.title, window.location.pathname);
                    }, 4000);
                }, 500);
            }
        }
    </script>
</body>
</html>
