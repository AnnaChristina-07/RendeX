<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

if (!isset($_SESSION['user_email']) || $_SESSION['user_email'] !== 'owner@gmail.com') {
    header("Location: dashboard.php");
    exit();
}

$items_file = 'items.json';
$items_json = file_exists($items_file) ? json_decode(file_get_contents($items_file), true) : [];
if (!is_array($items_json)) $items_json = [];

require_once 'config/database.php';
$pdo = getDBConnection();

// Handle Delete
if (isset($_GET['delete_id'])) {
    $delete_id = $_GET['delete_id'];
    
    // Delete from JSON
    $new_items_json = [];
    foreach ($items_json as $item) {
        if ($item['id'] === $delete_id && $item['user_id'] === $_SESSION['user_id']) continue;
        $new_items_json[] = $item;
    }
    file_put_contents($items_file, json_encode($new_items_json, JSON_PRETTY_PRINT));

    // Delete from Database
    if ($pdo) {
        try {
            $stmt = $pdo->prepare("DELETE FROM items WHERE id = ? AND owner_id = ?");
            $stmt->execute([$delete_id, $_SESSION['user_id']]);
        } catch (Exception $e) {}
    }
    
    header("Location: my-items.php");
    exit();
}

// Get User Items - Prioritize DB
$my_items = [];
if ($pdo) {
    try {
        $stmt = $pdo->prepare("SELECT * FROM items WHERE owner_id = ? ORDER BY created_at DESC");
        $stmt->execute([$_SESSION['user_id']]);
        $db_items = $stmt->fetchAll();
        foreach ($db_items as $db_item) {
            $i = $db_item;
            $i['status'] = ($db_item['admin_status'] === 'approved') ? 'Active' : (($db_item['admin_status'] === 'pending') ? 'Pending Approval' : 'Rejected');
            $i['price'] = $db_item['price_per_day'];
            $i['images'] = json_decode($db_item['images'], true) ?: [];
            $i['user_id'] = $db_item['owner_id']; // for consistency
            $my_items[] = $i;
        }
    } catch (Exception $e) {}
}

// Merge with JSON
foreach ($items_json as $item) {
    if ($item['user_id'] === $_SESSION['user_id']) {
        $already_added = false;
        foreach ($my_items as $mi) {
            if ($mi['title'] === $item['title']) { $already_added = true; break; }
        }
        if (!$already_added) $my_items[] = $item;
    }
}
?>
<!DOCTYPE html>
<html class="light" lang="en">
<head>
    <meta charset="utf-8"/>
    <meta content="width=device-width, initial-scale=1.0" name="viewport"/>
    <title>RendeX - My Items</title>
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
                    <a href="rentals.php" class="text-sm font-bold hover:text-primary transition-colors">My Rentals</a>
                    <a href="lend-item.php" class="bg-black text-white px-5 py-2 rounded-full text-sm font-bold">List New Item</a>
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
        <div class="flex flex-col md:flex-row justify-between items-start md:items-center mb-10 gap-4">
            <div>
                <h1 class="text-3xl font-black mb-2">My Listings</h1>
                <p class="text-text-muted">Manage your shared items and view their status.</p>
            </div>
            <div class="flex gap-2">
                <button class="px-4 py-2 rounded-full bg-white border border-[#e9e8ce] font-bold text-sm shadow-sm">Active (<?php echo count($my_items); ?>)</button>
                <button class="px-4 py-2 rounded-full hover:bg-white border border-transparent font-bold text-sm text-text-muted">Drafts (0)</button>
                <button class="px-4 py-2 rounded-full hover:bg-white border border-transparent font-bold text-sm text-text-muted">Archived</button>
            </div>
        </div>

        <!-- Listings Grid -->
        <div class="grid grid-cols-1 gap-6">
            <?php if (empty($my_items)): ?>
                <div class="text-center py-20 bg-surface-light dark:bg-surface-dark rounded-2xl border border-[#e9e8ce] dark:border-[#3e3d2a]">
                    <span class="material-symbols-outlined text-6xl text-text-muted mb-4">inventory_2</span>
                    <h3 class="text-xl font-bold mb-2">No listings yet</h3>
                    <p class="text-text-muted mb-6">You haven't listed any items for rent.</p>
                    <a href="lend-item.php" class="bg-primary text-black font-bold px-6 py-3 rounded-full inline-block">List your first item</a>
                </div>
            <?php else: ?>
                <?php foreach ($my_items as $item): ?>
                    <?php 
                        $image_src = 'https://source.unsplash.com/random/400x300?'.urlencode($item['category']);
                        if (!empty($item['images'])) {
                            $image_src = 'uploads/' . $item['images'][0];
                        }
                    ?>
                    <div class="bg-surface-light dark:bg-surface-dark rounded-2xl p-6 shadow-sm border border-[#e9e8ce] dark:border-[#3e3d2a] flex flex-col md:flex-row gap-6">
                    <div class="item-card w-full md:w-64 aspect-[4/3] rounded-xl overflow-hidden bg-gray-100 shrink-0 border border-[#e9e8ce] group cursor-pointer"
                         data-images='<?php echo json_encode(!empty($item['images']) ? array_map(function($img) { return 'uploads/' . $img; }, $item['images']) : [$image_src]); ?>'>
                        <img src="<?php echo htmlspecialchars($image_src); ?>" class="product-image w-full h-full object-cover transition-transform duration-500 group-hover:scale-105">
                    </div>
                        <div class="flex-1 flex flex-col justify-between">
                            <div>
                                <div class="flex justify-between items-start">
                                    <div>
                                        <h3 class="text-2xl font-bold mb-2"><?php echo htmlspecialchars($item['title']); ?></h3>
                                        <div class="flex items-center gap-2 text-sm text-text-muted mb-4">
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
                                            <span class="px-2 py-1 rounded <?php echo $status_class; ?> font-bold text-xs uppercase"><?php echo $status; ?></span>
                                            <span>•</span>
                                            <span><?php echo date('M d, Y', strtotime($item['created_at'])); ?></span>
                                            <span>•</span>
                                            <span><?php echo htmlspecialchars(ucwords(str_replace('-', ' ', $item['category']))); ?></span>
                                        </div>
                                    </div>
                                    <div class="text-right">
                                        <span class="block text-2xl font-black">₹<?php echo htmlspecialchars($item['price']); ?></span>
                                        <span class="text-sm text-text-muted">/ day</span>
                                    </div>
                                </div>
                                <p class="text-text-muted dark:text-gray-400 line-clamp-2"><?php echo htmlspecialchars($item['description']); ?></p>
                            </div>
                            
                            <div class="flex items-center justify-between mt-6 pt-6 border-t border-[#e9e8ce] dark:border-[#3e3d2a]">
                                <div class="flex gap-6 text-sm font-bold">
                                    <span class="flex items-center gap-2"><span class="material-symbols-outlined text-lg">visibility</span> <?php echo rand(10, 200); ?> Views</span>
                                    <span class="flex items-center gap-2"><span class="material-symbols-outlined text-lg">favorite</span> <?php echo rand(0, 20); ?> Likes</span>
                                </div>
                                <div class="flex gap-3">
                                    <a href="lend-item.php?edit_id=<?php echo $item['id']; ?>" class="px-4 py-2 rounded-lg font-bold hover:bg-background-light dark:hover:bg-background-dark transition-colors border border-transparent hover:border-[#e9e8ce]">Edit</a>
                                    <a href="my-items.php?delete_id=<?php echo $item['id']; ?>" onclick="return confirm('Are you sure you want to delete this listing?');" class="px-4 py-2 rounded-lg font-bold text-red-600 hover:bg-red-50 transition-colors">Delete</a>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </main>

    <!-- Footer -->
    <footer class="bg-surface-light dark:bg-surface-dark border-t border-[#e9e8ce] dark:border-[#3e3d2a] pt-16 pb-8 px-4 md:px-10 mt-auto">
        <div class="text-center text-sm font-bold text-text-muted">
            &copy; 2026 RendeX. All rights reserved.
        </div>
    </footer>
        </div>
    </main>

    <script>
    document.querySelectorAll('.item-card').forEach(card => {
        const images = JSON.parse(card.dataset.images);
        if (!images || images.length <= 1) return;

        const imgElement = card.querySelector('.product-image');
        let interval = null;
        let currentIdx = 0;

        card.addEventListener('mouseenter', () => {
            interval = setInterval(() => {
                currentIdx = (currentIdx + 1) % images.length;
                imgElement.src = images[currentIdx];
            }, 1200);
        });

        card.addEventListener('mouseleave', () => {
            clearInterval(interval);
            currentIdx = 0;
            imgElement.src = images[0];
        });
    });
    </script>
</body>
</html>
