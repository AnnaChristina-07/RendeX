<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

require_once 'config/database.php';

$wishlist_items = [];
try {
    $pdo = getDBConnection();
    if ($pdo) {
        $sql = "SELECT i.*, w.created_at as saved_at, w.id as wishlist_id 
                FROM wishlist w 
                JOIN items i ON w.item_id = i.id 
                WHERE w.user_id = ? 
                ORDER BY w.created_at DESC";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$_SESSION['user_id']]);
        $wishlist_items = $stmt->fetchAll();
    }
} catch (Exception $e) {
    // Fail silently or log
}
?>
<!DOCTYPE html>
<html class="light" lang="en">
<head>
    <meta charset="utf-8"/>
    <meta content="width=device-width, initial-scale=1.0" name="viewport"/>
    <title>My Wishlist - RendeX</title>
    <link href="https://fonts.googleapis.com" rel="preconnect"/>
    <link crossorigin="" href="https://fonts.gstatic.com" rel="preconnect"/>
    <link href="https://fonts.googleapis.com/css2?family=Spline+Sans:wght@300;400;500;600;700&amp;display=swap" rel="stylesheet"/>
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&amp;display=swap" rel="stylesheet"/>
    <script src="https://cdn.tailwindcss.com?plugins=forms"></script>
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
            },
          },
        },
      }
    </script>
    <style>body { font-family: "Spline Sans", sans-serif; }</style>
</head>
<body class="bg-background-light dark:bg-background-dark text-text-main dark:text-white transition-colors duration-200 flex flex-col min-h-screen">
    <header class="sticky top-0 z-50 flex items-center justify-between border-b border-[#e9e8ce] dark:border-[#3e3d2a] bg-background-light/95 dark:bg-background-dark/95 backdrop-blur-sm px-6 py-4 lg:px-10">
        <div class="flex items-center gap-8 w-full max-w-[1400px] mx-auto">
            <a href="dashboard.php" class="flex items-center gap-2 text-text-main dark:text-white">
                <h2 class="text-xl font-bold tracking-tight">RendeX</h2>
            </a>
            <div class="flex items-center gap-6 ml-auto">
                <a href="dashboard.php" class="text-sm font-bold hover:text-primary transition-colors">Home</a>
                <a href="profile.php" class="w-10 h-10 rounded-full bg-primary flex items-center justify-center text-black text-sm font-black">
                    <?php echo strtoupper(substr($_SESSION['user_name'], 0, 1)); ?>
                </a>
            </div>
        </div>
    </header>

    <main class="flex-1 w-full max-w-[1400px] mx-auto px-4 md:px-10 py-12">
        <h1 class="text-3xl font-black mb-10">My Wishlist</h1>
        
        <?php if (empty($wishlist_items)): ?>
            <div class="text-center py-20 opacity-50">
                <span class="material-symbols-outlined text-6xl mb-4">favorite_border</span>
                <p class="text-xl font-bold">Your wishlist is empty.</p>
                <a href="dashboard.php" class="text-primary font-bold hover:underline mt-2 inline-block">Browse Items</a>
            </div>
        <?php else: ?>
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-6">
                <?php foreach ($wishlist_items as $item): 
                    $images = json_decode($item['images'], true);
                    $img_src = !empty($images) ? 'uploads/' . $images[0] : 'https://source.unsplash.com/random/400x400?' . urlencode($item['category']);
                    // Check if absolute path or need uploads/ prefix
                    if (!empty($images) && strpos($images[0], 'uploads/') === false) {
                         $img_src = 'uploads/' . $images[0];
                    }
                     // Fallback for json items that might have absolute paths or different structure
                    if (!empty($images) && (strpos($images[0], 'http') === 0 || strpos($images[0], 'data:') === 0)) {
                         $img_src = $images[0];
                    }
                ?>
                <div class="bg-surface-light dark:bg-surface-dark rounded-3xl overflow-hidden border border-[#e9e8ce] dark:border-[#3e3d2a] hover:shadow-xl transition-all group flex flex-col relative">
                    <button onclick="removeFromWishlist(<?php echo $item['id']; ?>, this)" class="absolute top-4 right-4 z-10 w-10 h-10 rounded-full bg-white/80 backdrop-blur text-red-500 flex items-center justify-center hover:bg-red-500 hover:text-white transition-colors">
                        <span class="material-symbols-outlined text-xl font-variation-settings-fill">close</span>
                    </button>
                    
                    <a href="item-details.php?id=<?php echo $item['id']; ?>" class="block flex-1 flex flex-col">
                        <div class="aspect-square bg-gray-100 overflow-hidden relative">
                            <img src="<?php echo htmlspecialchars($img_src); ?>" class="w-full h-full object-cover transition-transform duration-500 group-hover:scale-110">
                            <?php if($item['availability_status'] !== 'available'): ?>
                                <div class="absolute inset-0 bg-black/50 flex items-center justify-center">
                                    <span class="bg-white text-black text-xs font-black px-3 py-1 rounded-full uppercase tracking-wider">
                                        <?php echo $item['availability_status']; ?>
                                    </span>
                                </div>
                            <?php endif; ?>
                        </div>
                        <div class="p-6">
                            <div class="flex justify-between items-start mb-2">
                                <h3 class="font-bold text-lg line-clamp-1"><?php echo htmlspecialchars($item['title']); ?></h3>
                            </div>
                            <p class="text-sm text-text-muted mb-4 line-clamp-2"><?php echo htmlspecialchars($item['description']); ?></p>
                            <div class="flex items-center justify-between mt-auto">
                                <span class="font-black text-xl">₹<?php echo $item['price_per_day']; ?></span>
                                <span class="text-xs text-text-muted font-bold">/ day</span>
                            </div>
                        </div>
                    </a>
                </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </main>
    
    <script>
        function removeFromWishlist(itemId, btn) {
            if(!confirm('Remove from wishlist?')) return;
            
            fetch('toggle_wishlist.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ item_id: itemId })
            })
            .then(res => res.json())
            .then(data => {
                if (data.status === 'success') {
                    // Remove card visual
                    const card = btn.closest('.group');
                    card.style.opacity = '0';
                    card.style.transform = 'scale(0.9)';
                    setTimeout(() => card.remove(), 300);
                }
            });
        }
    </script>
</body>
</html>
