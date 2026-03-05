<?php
ob_start();
session_start();

if (!isset($_SESSION['user_id'])) {
    $_SESSION['redirect_after_login'] = $_SERVER['REQUEST_URI'];
    header("Location: login.php");
    exit();
}

require_once 'config/database.php';
$user_id = $_SESSION['user_id'];

$purchases = [];
try {
    $pdo = getDBConnection();
    if ($pdo) {
        $stmt = $pdo->prepare("
            SELECT p.*, i.title as item_title, i.category, i.images, i.location,
                   u.name as seller_name
            FROM purchases p
            LEFT JOIN items i ON p.item_id = i.id
            LEFT JOIN users u ON p.seller_id = u.id
            WHERE p.buyer_id = ?
            ORDER BY p.created_at DESC
        ");
        $stmt->execute([$user_id]);
        $purchases = $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
} catch (Exception $e) {}
?>
<!DOCTYPE html>
<html class="light" lang="en">
<head>
    <meta charset="utf-8"/>
    <meta content="width=device-width, initial-scale=1.0" name="viewport"/>
    <title>My Purchases - RendeX</title>
    <link href="https://fonts.googleapis.com" rel="preconnect"/>
    <link crossorigin="" href="https://fonts.gstatic.com" rel="preconnect"/>
    <link href="https://fonts.googleapis.com/css2?family=Spline+Sans:wght@300;400;500;600;700&display=swap" rel="stylesheet"/>
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&display=swap" rel="stylesheet"/>
    <script src="https://cdn.tailwindcss.com?plugins=forms,container-queries"></script>
    <script>
      tailwind.config = {
        darkMode: "class",
        theme: { extend: {
            colors: { "primary": "#f9f506", "background-light": "#f8f8f5", "surface-light": "#ffffff", "text-main": "#1c1c0d", "text-muted": "#5e5e4a" },
            fontFamily: { "display": ["Spline Sans", "sans-serif"] },
        }},
      }
    </script>
    <style>body { font-family: "Spline Sans", sans-serif; } .material-symbols-outlined { font-variation-settings: 'FILL' 0, 'wght' 400, 'GRAD' 0, 'opsz' 24 }</style>
</head>
<body class="bg-background-light text-text-main min-h-screen">

<!-- Navbar -->
<header class="sticky top-0 z-50 flex items-center justify-between border-b border-[#e9e8ce] bg-background-light/95 backdrop-blur-sm px-6 py-4 lg:px-10">
    <div class="flex items-center gap-8 w-full max-w-[1400px] mx-auto">
        <a href="dashboard.php" class="flex items-center gap-2">
            <div class="size-8 text-primary">
                <svg class="w-full h-full" fill="none" viewBox="0 0 48 48" xmlns="http://www.w3.org/2000/svg">
                    <path d="M36.7273 44C33.9891 44 31.6043 39.8386 30.3636 33.69C29.123 39.8386 26.7382 44 24 44C21.2618 44 18.877 39.8386 17.6364 33.69C16.3957 39.8386 14.0109 44 11.2727 44C7.25611 44 4 35.0457 4 24C4 12.9543 7.25611 4 11.2727 4C14.0109 4 16.3957 8.16144 17.6364 14.31C18.877 8.16144 21.2618 4 24 4C26.7382 4 29.123 8.16144 30.3636 14.31C31.6043 8.16144 33.9891 4 36.7273 4C40.7439 4 44 12.9543 44 24C44 35.0457 40.7439 44 36.7273 44Z" fill="currentColor"></path>
                </svg>
            </div>
            <h2 class="text-xl font-bold tracking-tight">RendeX</h2>
        </a>
        <div class="ml-auto flex items-center gap-6">
            <a href="rentals.php" class="text-sm font-bold hover:text-primary transition-colors">My Rentals</a>
            <a href="my-purchases.php" class="text-sm font-bold text-primary border-b-2 border-primary pb-0.5">My Purchases</a>
            <a href="dashboard.php" class="text-sm font-bold hover:text-primary transition-colors">Browse</a>
            <a href="profile.php" class="w-9 h-9 rounded-full bg-primary flex items-center justify-center text-black font-black text-sm shadow-sm">
                <?= strtoupper(substr($_SESSION['user_name'], 0, 1)) ?>
            </a>
            <a href="logout.php" class="bg-black text-white size-9 rounded-full flex items-center justify-center hover:bg-primary hover:text-black transition-all shadow-sm" title="Logout">
                <span class="material-symbols-outlined text-[18px]">logout</span>
            </a>
        </div>
    </div>
</header>

<main class="max-w-[1400px] mx-auto px-4 md:px-10 py-12">
    <div class="flex items-center justify-between mb-10">
        <div>
            <h1 class="text-4xl font-black mb-2">My Purchases</h1>
            <p class="text-text-muted">Items you've permanently bought through RendeX.</p>
        </div>
        <a href="dashboard.php" class="bg-primary hover:bg-yellow-300 text-black font-bold px-6 py-3 rounded-full transition-all shadow-sm flex items-center gap-2">
            <span class="material-symbols-outlined text-sm">add</span> Shop More
        </a>
    </div>

    <?php if (empty($purchases)): ?>
    <div class="flex flex-col items-center justify-center py-24 text-center">
        <div class="w-20 h-20 rounded-full bg-white border border-[#e9e8ce] flex items-center justify-center mb-6 shadow-sm">
            <span class="material-symbols-outlined text-4xl text-text-muted">shopping_bag</span>
        </div>
        <h2 class="text-2xl font-black mb-2">No purchases yet</h2>
        <p class="text-text-muted mb-8 max-w-sm">When you buy an item, it'll show up here. Start exploring items available for purchase!</p>
        <a href="dashboard.php" class="bg-primary hover:bg-yellow-300 text-black font-black px-8 py-4 rounded-full transition-all shadow-lg">
            Browse Items
        </a>
    </div>
    <?php else: ?>
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
        <?php foreach ($purchases as $p):
            // Parse image
            $images = json_decode($p['images'] ?? '[]', true) ?: [];
            $img_src = !empty($images) ? 'uploads/' . $images[0] : '';

            // Status config
            $status = $p['status'] ?? 'confirmed';
            $status_map = [
                'pending'   => ['⏳ Pending',   'bg-yellow-100 text-yellow-700'],
                'confirmed' => ['✅ Confirmed', 'bg-green-100 text-green-700'],
                'shipped'   => ['🚚 Shipped',   'bg-blue-100 text-blue-700'],
                'delivered' => ['📦 Delivered', 'bg-emerald-100 text-emerald-700'],
                'cancelled' => ['❌ Cancelled', 'bg-red-100 text-red-700'],
            ];
            [$status_label, $status_class] = $status_map[$status] ?? ['Unknown', 'bg-gray-100 text-gray-700'];
        ?>
        <div class="bg-white rounded-2xl border border-[#e9e8ce] overflow-hidden shadow-sm hover:shadow-md transition-all group">
            <!-- Image -->
            <div class="aspect-[4/3] bg-gray-100 relative overflow-hidden">
                <?php if ($img_src): ?>
                <img src="<?= htmlspecialchars($img_src) ?>" class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-500"
                     onerror="this.src='https://placehold.co/400x300?text=No+Image'">
                <?php else: ?>
                <div class="w-full h-full flex items-center justify-center bg-gray-200">
                    <span class="material-symbols-outlined text-5xl text-gray-400">inventory_2</span>
                </div>
                <?php endif; ?>

                <!-- Status Badge -->
                <span class="absolute top-3 left-3 text-xs font-bold px-3 py-1 rounded-full <?= $status_class ?>">
                    <?= $status_label ?>
                </span>

                <!-- Purchase Badge -->
                <div class="absolute top-3 right-3 bg-green-500 text-white text-[10px] font-black px-2 py-1 rounded-full flex items-center gap-1">
                    <span class="material-symbols-outlined text-[12px]">shopping_bag</span> Bought
                </div>
            </div>

            <div class="p-5">
                <!-- Title + Category -->
                <p class="text-[10px] font-bold text-text-muted uppercase tracking-wider mb-1">
                    <?= htmlspecialchars(ucwords(str_replace('-',' ', $p['category'] ?? 'Item'))) ?>
                </p>
                <h3 class="font-black text-lg leading-tight truncate mb-3">
                    <?= htmlspecialchars($p['item_title'] ?? 'Unknown Item') ?>
                </h3>

                <!-- Order Info -->
                <div class="space-y-1.5 text-sm text-text-muted mb-4">
                    <div class="flex items-center gap-1.5">
                        <span class="material-symbols-outlined text-[16px]">receipt</span>
                        <span class="font-mono text-xs"><?= htmlspecialchars($p['purchase_ref']) ?></span>
                    </div>
                    <div class="flex items-center gap-1.5">
                        <span class="material-symbols-outlined text-[16px]">person</span>
                        Seller: <span class="font-bold text-text-main"><?= htmlspecialchars($p['seller_name'] ?? 'Owner') ?></span>
                    </div>
                    <div class="flex items-center gap-1.5">
                        <span class="material-symbols-outlined text-[16px]">calendar_today</span>
                        <?= date('M d, Y', strtotime($p['created_at'])) ?>
                    </div>
                </div>

                <!-- Price -->
                <div class="flex items-center justify-between pt-3 border-t border-[#e9e8ce]">
                    <div>
                        <p class="text-[10px] font-bold text-text-muted uppercase tracking-wider">Total Paid</p>
                        <p class="text-xl font-black text-green-600">₹<?= number_format($p['amount'] + $p['platform_fee'], 2) ?></p>
                    </div>
                    <?php if ($p['delivery_address']): ?>
                    <div class="text-right">
                        <p class="text-[10px] font-bold text-text-muted uppercase">Delivery to</p>
                        <p class="text-xs font-bold truncate max-w-[120px]"><?= htmlspecialchars($p['delivery_city'] . ', ' . $p['delivery_state']) ?></p>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>
</main>

<footer class="bg-white border-t border-[#e9e8ce] py-6 px-4 md:px-10 text-center text-sm text-text-muted mt-12">
    © 2026 RendeX. All rights reserved.
</footer>
</body>
</html>
