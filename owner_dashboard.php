<?php
ob_start();
session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$user_name = $_SESSION['user_name'];
$user_email = $_SESSION['user_email'];
require_once 'config/database.php';
$pdo = getDBConnection();

// --- DATA LOADING ---

// 1. Listings (Items I own)
$items_file = 'items.json';
$all_items_json = file_exists($items_file) ? json_decode(file_get_contents($items_file), true) : [];
$my_listings = [];

// Try database first
if ($pdo) {
    try {
        $stmt = $pdo->prepare("SELECT * FROM items WHERE owner_id = ? ORDER BY created_at DESC");
        $stmt->execute([$user_id]);
        $db_listings = $stmt->fetchAll();
        foreach ($db_listings as $dl) {
            $item = $dl;
            $item['status'] = $dl['admin_status'] === 'approved' ? 'Active' : ($dl['admin_status'] === 'pending' ? 'Pending Approval' : 'Rejected');
            $item['price'] = $dl['price_per_day'];
            $images = json_decode($dl['images'], true) ?: [];
            $item['images'] = $images;
            $my_listings[] = $item;
        }
    } catch (Exception $e) {}
}

// Fallback/Merge with JSON for items not in DB
foreach($all_items_json as $i) {
    if (isset($i['user_id']) && $i['user_id'] === $user_id) {
        $already_added = false;
        foreach ($my_listings as $ml) {
            if ($ml['title'] === $i['title']) { $already_added = true; break; }
        }
        if (!$already_added) $my_listings[] = $i;
    }
}

// 2. Rentals (Global)
$rentals_file = 'rentals.json';
$all_rentals = file_exists($rentals_file) ? json_decode(file_get_contents($rentals_file), true) : [];

// 3. Process Rentals
$my_incoming_rentals = []; // People renting MY items
$my_outgoing_rentals = []; // Items I am renting
$total_earnings = 0;

foreach($all_rentals as $r) {
    // Check if I am the owner of the item
    if (isset($r['item']['user_id']) && $r['item']['user_id'] === $user_id) {
        $my_incoming_rentals[] = $r;
        if (isset($r['total_price'])) {
            $total_earnings += $r['total_price'];
        }
    }
    
    // Check if I am the renter
    if ($r['user_id'] === $user_id) {
        $my_outgoing_rentals[] = $r;
    }
}

// --- ACTIONS ---

// Delete Listing
if (isset($_POST['delete_listing_id'])) {
    $del_id = $_POST['delete_listing_id'];
    $new_items = [];
    foreach($all_items as $i) {
        if ($i['id'] !== $del_id) $new_items[] = $i;
    }
    file_put_contents($items_file, json_encode($new_items, JSON_PRETTY_PRINT));
    header("Location: owner_dashboard.php?tab=listings&msg=deleted");
    exit();
}

// Confirm Return (Owner Action)
if (isset($_POST['confirm_return_id'])) {
    $r_id = $_POST['confirm_return_id'];
    
    // 1. Update Rental JSON
    $updated_rentals = [];
    $item_id = null;
    $rental_found = false;

    foreach($all_rentals as $r) {
        if ($r['id'] === $r_id) {
            // Verify ownership
            if (isset($r['item']['user_id']) && $r['item']['user_id'] === $user_id) {
                // Determine if it was pending inspection
                if (isset($r['return_status']) && $r['return_status'] === 'pending_inspection') {
                     $r['status'] = 'returned';
                     $r['return_status'] = 'completed';
                     $r['owner_confirm_at'] = date('Y-m-d H:i:s');
                     $r['actual_end_date'] = date('Y-m-d'); // Set actual end date
                     $item_id = $r['item']['id'];
                     $rental_found = true;
                }
            }
        }
        $updated_rentals[] = $r;
    }
    
    if ($rental_found) {
        file_put_contents($rentals_file, json_encode($updated_rentals, JSON_PRETTY_PRINT));
        
        // 2. Update Items JSON (Make available)
        if ($item_id) {
            $updated_items = [];
            foreach($all_items_json as $item) {
                if ($item['id'] === $item_id) {
                    $item['availability_status'] = 'available';
                    $item['status'] = 'Active'; // Ensure it's active
                }
                $updated_items[] = $item;
            }
            file_put_contents($items_file, json_encode($updated_items, JSON_PRETTY_PRINT));
        }

        // 3. Update Database
        if ($pdo) {
            try {
                // Update Rental
                $stmt = $pdo->prepare("UPDATE rentals SET status = 'returned', return_status = 'completed', owner_confirm_at = NOW(), end_date = NOW() WHERE id = ?");
                $stmt->execute([$r_id]);

                // Update Item
                $stmt = $pdo->prepare("UPDATE items SET availability_status = 'available' WHERE id = ?");
                $stmt->execute([$item_id]);
            } catch (Exception $e) {}
        }

        header("Location: owner_dashboard.php?tab=incoming&msg=return_confirmed");
        exit();
    }
}

// Current Tab
$tab = isset($_GET['tab']) ? $_GET['tab'] : 'overview';

?>
<!DOCTYPE html>
<html class="light" lang="en">
<head>
    <meta charset="utf-8"/>
    <meta content="width=device-width, initial-scale=1.0" name="viewport"/>
    <title>Owner Dashboard - RendeX</title>
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
              "surface-light": "#ffffff",
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
<body class="bg-gray-50 text-text-main min-h-screen flex flex-col">

    <!-- Navbar -->
    <header class="sticky top-0 z-50 bg-white border-b border-gray-200 px-6 py-4">
        <div class="max-w-7xl mx-auto flex items-center justify-between">
            <div class="flex items-center gap-8">
                <a href="dashboard.php" class="flex items-center gap-2">
                    <div class="size-8 text-primary">
                        <svg class="w-full h-full" fill="none" viewbox="0 0 48 48" xmlns="http://www.w3.org/2000/svg">
                            <path d="M36.7273 44C33.9891 44 31.6043 39.8386 30.3636 33.69C29.123 39.8386 26.7382 44 24 44C21.2618 44 18.877 39.8386 17.6364 33.69C16.3957 39.8386 14.0109 44 11.2727 44C7.25611 44 4 35.0457 4 24C4 12.9543 7.25611 4 11.2727 4C14.0109 4 16.3957 8.16144 17.6364 14.31C18.877 8.16144 21.2618 4 24 4C26.7382 4 29.123 8.16144 30.3636 14.31C31.6043 8.16144 33.9891 4 36.7273 4C40.7439 4 44 12.9543 44 24C44 35.0457 40.7439 44 36.7273 44Z" fill="currentColor"></path>
                        </svg>
                    </div>
                    <span class="font-bold text-xl tracking-tight">RendeX <span class="text-xs bg-black text-white px-2 py-0.5 rounded ml-1">OWNER</span></span>
                </a>
                
                <nav class="hidden md:flex gap-1 bg-gray-100 p-1 rounded-full">
                    <a href="?tab=overview" class="px-5 py-2 rounded-full text-sm font-bold transition-all <?php echo $tab=='overview' ? 'bg-white shadow-sm text-black' : 'text-gray-500 hover:text-black'; ?>">Overview</a>
                    <a href="?tab=listings" class="px-5 py-2 rounded-full text-sm font-bold transition-all <?php echo $tab=='listings' ? 'bg-white shadow-sm text-black' : 'text-gray-500 hover:text-black'; ?>">My Listings</a>
                    <a href="?tab=incoming" class="px-5 py-2 rounded-full text-sm font-bold transition-all <?php echo $tab=='incoming' ? 'bg-white shadow-sm text-black' : 'text-gray-500 hover:text-black'; ?>">Rentals (Incoming)</a>
                    <a href="?tab=outgoing" class="px-5 py-2 rounded-full text-sm font-bold transition-all <?php echo $tab=='outgoing' ? 'bg-white shadow-sm text-black' : 'text-gray-500 hover:text-black'; ?>">My Orders</a>
                </nav>
            </div>
            
            <div class="flex items-center gap-4">
                <a href="dashboard.php" class="text-sm font-bold text-gray-500 hover:text-black">Switch to Buyer View</a>
                <div class="h-6 w-px bg-gray-200"></div>
                <div class="flex items-center gap-2">
                    <div class="w-8 h-8 rounded-full bg-primary flex items-center justify-center font-bold text-xs">
                        <?php echo substr($user_name, 0, 1); ?>
                    </div>
                    <a href="logout.php" class="text-sm font-bold text-red-500 hover:text-red-600">Logout</a>
                </div>
            </div>
        </div>
    </header>

    <!-- Main Content -->
    <main class="flex-1 max-w-7xl mx-auto w-full p-6 md:p-10">
        
        <?php if($tab == 'overview'): ?>
            <div class="mb-10">
                <h1 class="text-3xl font-black mb-2">Welcome back, <?php echo htmlspecialchars($user_name); ?>!</h1>
                <p class="text-text-muted">Here is what's happening with your business today.</p>
            </div>

            <!-- Stats -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-12">
                <div class="bg-white p-6 rounded-2xl shadow-sm border border-gray-100">
                    <div class="flex justify-between items-start mb-4">
                         <div class="p-3 bg-green-50 text-green-600 rounded-xl">
                            <span class="material-symbols-outlined">payments</span>
                        </div>
                    </div>
                    <p class="text-sm font-bold text-gray-500">Total Earnings</p>
                    <p class="text-3xl font-black mt-1">₹<?php echo number_format($total_earnings); ?></p>
                </div>
                <div class="bg-white p-6 rounded-2xl shadow-sm border border-gray-100">
                    <div class="flex justify-between items-start mb-4">
                         <div class="p-3 bg-blue-50 text-blue-600 rounded-xl">
                            <span class="material-symbols-outlined">inventory_2</span>
                        </div>
                    </div>
                    <p class="text-sm font-bold text-gray-500">Active Listings</p>
                    <p class="text-3xl font-black mt-1"><?php echo count($my_listings); ?></p>
                </div>
                <div class="bg-white p-6 rounded-2xl shadow-sm border border-gray-100">
                    <div class="flex justify-between items-start mb-4">
                         <div class="p-3 bg-orange-50 text-orange-600 rounded-xl">
                            <span class="material-symbols-outlined">shopping_bag</span>
                        </div>
                    </div>
                    <p class="text-sm font-bold text-gray-500">Active Rentals (Incoming)</p>
                    <p class="text-3xl font-black mt-1"><?php echo count($my_incoming_rentals); ?></p>
                </div>
            </div>

            <!-- Quick Actions -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <a href="lend-item.php" class="group bg-black text-white p-8 rounded-2xl flex items-center justify-between hover:scale-[1.01] transition-transform">
                    <div>
                        <h3 class="text-xl font-bold mb-2">List a New Item</h3>
                        <p class="text-gray-400 text-sm">Upload photos and set your price.</p>
                    </div>
                    <span class="material-symbols-outlined text-4xl group-hover:rotate-45 transition-transform">add_circle</span>
                </a>
                <a href="dashboard.php" class="group bg-primary text-black p-8 rounded-2xl flex items-center justify-between hover:scale-[1.01] transition-transform">
                    <div>
                        <h3 class="text-xl font-bold mb-2">Browse & Rent</h3>
                        <p class="text-black/70 text-sm">Find items you need for your next project.</p>
                    </div>
                    <span class="material-symbols-outlined text-4xl group-hover:translate-x-2 transition-transform">arrow_forward</span>
                </a>
            </div>

        <?php elseif($tab == 'listings'): ?>
            <div class="flex justify-between items-center mb-8">
                <h2 class="text-2xl font-bold">My Listings</h2>
                <a href="lend-item.php" class="bg-black text-white px-5 py-2.5 rounded-full font-bold text-sm flex items-center gap-2">
                    <span class="material-symbols-outlined text-sm">add</span> List Item
                </a>
            </div>
            
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
                <?php foreach($my_listings as $item): ?>
                <div class="bg-white rounded-2xl border border-gray-200 overflow-hidden group hover:shadow-md transition-all">
                    <div class="aspect-[4/3] bg-gray-100 relative">
                        <img src="<?php echo !empty($item['images']) ? 'uploads/'.(is_array($item['images']) ? $item['images'][0] : json_decode($item['images'])[0]) : 'https://source.unsplash.com/random?'.urlencode($item['category']); ?>" class="w-full h-full object-cover">
                        <?php 
                        $status = $item['status'] ?? 'Pending Approval';
                        $status_class = 'bg-yellow-100 text-yellow-700';
                        if ($status === 'Active' || $status === 'approved') {
                            $status = 'Live';
                            $status_class = 'bg-green-100 text-green-700';
                        } elseif ($status === 'Rejected' || $status === 'rejected') {
                            $status_class = 'bg-red-100 text-red-700';
                        }
                        ?>
                        <span class="absolute top-3 right-3 <?php echo $status_class; ?> backdrop-blur-sm px-2 py-1 rounded text-[10px] font-bold uppercase"><?php echo $status; ?></span>
                    </div>
                    <div class="p-4">
                        <h3 class="font-bold truncate mb-1"><?php echo htmlspecialchars($item['title']); ?></h3>
                        <p class="text-green-600 font-bold mb-4">₹<?php echo $item['price']; ?> <span class="text-gray-400 text-xs font-normal">/ day</span></p>
                        
                        <div class="flex gap-2 text-sm font-bold border-t pt-4">
                            <a href="lend-item.php?edit_id=<?php echo $item['id']; ?>" class="flex-1 text-center py-2 rounded-lg bg-gray-50 hover:bg-gray-100">Edit</a>
                            <form method="POST" class="flex-1" onsubmit="return confirm('Delete?');">
                                <input type="hidden" name="delete_listing_id" value="<?php echo $item['id']; ?>">
                                <button class="w-full py-2 rounded-lg text-red-500 hover:bg-red-50">Delete</button>
                            </form>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
                <?php if(empty($my_listings)) echo '<p class="text-gray-500 col-span-full py-10 text-center">No listings found.</p>'; ?>
            </div>

        <?php elseif($tab == 'incoming'): ?>
            <h2 class="text-2xl font-bold mb-8">Incoming Rentals (People renting your items)</h2>
            <div class="bg-white rounded-2xl border border-gray-200 overflow-hidden">
                <table class="w-full text-left text-sm">
                    <thead class="bg-gray-50 font-bold text-gray-500 border-b">
                        <tr>
                            <th class="px-6 py-4">Item</th>
                            <th class="px-6 py-4">Status</th>
                            <th class="px-6 py-4">Dates</th>
                            <th class="px-6 py-4">Earnings</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y">
                        <?php foreach(array_reverse($my_incoming_rentals) as $r): ?>
                        <tr>
                            <td class="px-6 py-4">
                                <div class="flex items-center gap-3">
                                    <div class="w-10 h-10 rounded bg-gray-200 overflow-hidden">
                                        <img src="<?php echo (strpos($r['item']['img'], 'uploads/') === 0) ? $r['item']['img'] : 'https://source.unsplash.com/random?'.urlencode($r['item']['name']); ?>" class="w-full h-full object-cover">
                                    </div>
                                    <span class="font-bold"><?php echo htmlspecialchars($r['item']['name']); ?></span>
                                </div>
                            </td>
                            <td class="px-6 py-4">
                                <?php 
                                    $status = $r['status'] ?? 'active';
                                    $class = 'bg-green-100 text-green-700';
                                    if ($status == 'returned') $class = 'bg-blue-100 text-blue-700';
                                    if ($status == 'cancelled') $class = 'bg-red-100 text-red-700';
                                ?>
                                <span class="px-2 py-1 rounded text-xs font-bold uppercase <?php echo $class; ?>"><?php echo $status; ?></span>
                            </td>
                            <td class="px-6 py-4 text-gray-500">
                                <?php echo date('M d', strtotime($r['start_date'])) . ' - ' . date('M d', strtotime($r['end_date'])); ?>
                            </td>
                            <td class="px-6 py-4 font-bold text-green-600">
                                <?php if(isset($r['return_status']) && ($r['return_status'] === 'pending_inspection' || $r['return_status'] === 'scheduled')): ?>
                                    <button onclick='openReturnModal(<?php echo json_encode($r); ?>)' class="bg-black text-white px-4 py-2 rounded-lg text-xs font-bold hover:scale-105 transition-transform shadow-lg flex items-center gap-2">
                                        <span class="material-symbols-outlined text-sm text-primary">assignment_returned</span>
                                        Review Return
                                    </button>
                                <?php else: ?>
                                    ₹<?php echo $r['total_price']; ?>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                        <?php if(empty($my_incoming_rentals)) echo '<tr><td colspan="4" class="px-6 py-8 text-center text-gray-500">No rentals yet.</td></tr>'; ?>
                    </tbody>
                </table>
</div>

            <!-- Return Review Modal -->
            <div id="return-modal" class="hidden fixed inset-0 z-[100] flex items-center justify-center p-4">
                <div class="absolute inset-0 bg-black/60 backdrop-blur-sm transition-opacity" onclick="closeReturnModal()"></div>
                <div class="relative bg-white dark:bg-surface-dark rounded-3xl p-8 w-full max-w-lg shadow-2xl transform transition-all scale-100">
                    <button onclick="closeReturnModal()" class="absolute top-4 right-4 text-gray-400 hover:text-black dark:hover:text-white">
                        <span class="material-symbols-outlined">close</span>
                    </button>
                    
                    <h3 class="text-2xl font-black mb-6">Review Returned Item</h3>
                    
                    <div class="space-y-6 mb-8">
                        <div>
                             <h4 class="text-xs font-bold text-gray-400 uppercase tracking-widest mb-2">Renter's Note</h4>
                             <div class="bg-gray-50 dark:bg-gray-800 p-4 rounded-xl">
                                <p id="modal-note" class="text-sm font-medium text-gray-700 dark:text-gray-300 italic">No notes.</p>
                             </div>
                        </div>
                        
                        <div>
                            <h4 class="text-xs font-bold text-gray-400 uppercase tracking-widest mb-3">Condition Photos</h4>
                            <div id="modal-photos" class="grid grid-cols-3 gap-3">
                                <!-- Photos injected here -->
                            </div>
                        </div>
                    </div>

                    <form method="POST">
                        <input type="hidden" name="confirm_return_id" id="modal-rental-id">
                        <div class="flex gap-3">
                            <button type="button" onclick="closeReturnModal()" class="flex-1 py-3 font-bold text-gray-500 hover:bg-gray-50 dark:hover:bg-gray-800 rounded-xl transition-colors">Cancel</button>
                            <button type="submit" class="flex-1 bg-primary text-black font-bold py-3 rounded-xl hover:bg-yellow-300 transition-colors shadow-lg shadow-yellow-200/50 flex items-center justify-center gap-2">
                                <span class="material-symbols-outlined font-bold">check_circle</span>
                                Confirm Receipt
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            <script>
                function openReturnModal(rental) {
                    document.getElementById('modal-rental-id').value = rental.id;
                    
                    // Note
                    const note = rental.condition_note || 'No notes provided.';
                    document.getElementById('modal-note').textContent = note;

                    // Photos
                    const photosContainer = document.getElementById('modal-photos');
                    photosContainer.innerHTML = '';
                    
                    if (rental.condition_images && rental.condition_images.length > 0) {
                        try {
                             // Handle if it's already an array or a JSON string (depending on how PHP encoded it in the attribute)
                             // Since we passed via json_encode($r), it should be an array in JS object
                             let images = rental.condition_images;
                             if (typeof images === 'string') {
                                 images = JSON.parse(images);
                             }
                             
                             images.forEach(img => {
                                 const div = document.createElement('div');
                                 div.className = 'aspect-square rounded-xl bg-gray-100 dark:bg-gray-800 overflow-hidden cursor-pointer hover:opacity-90 transition-opacity border border-gray-200 dark:border-gray-700';
                                 div.innerHTML = `<img src="uploads/${img}" class="w-full h-full object-cover" onclick="window.open(this.src, '_blank')">`;
                                 photosContainer.appendChild(div);
                            });
                        } catch(e) {
                            console.error(e);
                        }
                    } else {
                        photosContainer.innerHTML = '<p class="text-xs text-gray-400 col-span-3 bg-gray-50 dark:bg-gray-800 p-4 rounded-xl text-center">No photos uploaded.</p>';
                    }

                    document.getElementById('return-modal').classList.remove('hidden');
                    document.getElementById('return-modal').classList.add('flex');
                }

                function closeReturnModal() {
                    document.getElementById('return-modal').classList.add('hidden');
                    document.getElementById('return-modal').classList.remove('flex');
                }
            </script>

        <?php elseif($tab == 'outgoing'): ?>
            <h2 class="text-2xl font-bold mb-8">My Orders (Items I rented)</h2>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <?php foreach(array_reverse($my_outgoing_rentals) as $r): ?>
                <div class="bg-white p-4 rounded-xl border border-gray-200 flex gap-4">
                     <div class="w-24 h-24 rounded-lg bg-gray-200 overflow-hidden shrink-0">
                        <img src="<?php echo (strpos($r['item']['img'], 'uploads/') === 0) ? $r['item']['img'] : 'https://source.unsplash.com/random?'.urlencode($r['item']['name']); ?>" class="w-full h-full object-cover">
                    </div>
                    <div class="flex-1">
                        <h3 class="font-bold"><?php echo htmlspecialchars($r['item']['name']); ?></h3>
                         <div class="flex items-center gap-2 mt-2">
                             <?php 
                                $status = $r['status'] ?? 'active';
                                $class = 'text-green-600';
                                if ($status == 'returned') $class = 'text-blue-600';
                            ?>
                             <span class="text-xs font-bold uppercase <?php echo $class; ?>"><?php echo $status; ?></span>
                             <span class="text-gray-300">•</span>
                             <span class="text-xs text-gray-500">Ends: <?php echo $r['end_date']; ?></span>
                         </div>
                         <?php if($status === 'active'): ?>
                         <div class="mt-3">
                             <a href="rentals.php" class="text-xs font-bold bg-black text-white px-3 py-1.5 rounded-full">Manage / Return</a>
                         </div>
                         <?php endif; ?>
                    </div>
                </div>
                <?php endforeach; ?>
                 <?php if(empty($my_outgoing_rentals)) echo '<p class="text-gray-500 col-span-full py-10 text-center">You haven\'t rented anything.</p>'; ?>
            </div>

        <?php endif; ?>

    </main>

</body>
</html>
