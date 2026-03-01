<?php
ob_start();
session_start();
if (!isset($_SESSION['user_id'])) { header("Location: login.php"); exit(); }
require_once 'config/database.php';

$user_id = $_SESSION['user_id'];
$pdo = getDBConnection();

// Handle cancel action
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['cancel_ref'])) {
    $ref = $_POST['cancel_ref'];
    try {
        if ($pdo) {
            $pdo->prepare("UPDATE pre_bookings SET status = 'cancelled' WHERE booking_ref = ? AND user_id = ? AND status IN ('pending','confirmed')")
                ->execute([$ref, $user_id]);
        }
    } catch (Exception $e) {}
    header("Location: my-prebookings.php?msg=cancelled");
    exit();
}

// Load pre-bookings
$bookings = [];
try {
    if ($pdo) {
        $has_tb = $pdo->query("SHOW TABLES LIKE 'pre_bookings'")->rowCount() > 0;
        if ($has_tb) {
            $stmt = $pdo->prepare("
                SELECT pb.*, i.title as item_title, i.category, i.images, i.price_per_day,
                       u.name as owner_name
                FROM pre_bookings pb
                LEFT JOIN items i ON pb.item_id = i.id
                LEFT JOIN users u ON pb.owner_id = u.id
                WHERE pb.user_id = ?
                ORDER BY pb.created_at DESC
            ");
            $stmt->execute([$user_id]);
            $bookings = $stmt->fetchAll(PDO::FETCH_ASSOC);
        }
    }
} catch (Exception $e) {}

$status_map = [
    'pending'   => ['⏳ Pending',    'bg-yellow-100 text-yellow-700 border-yellow-200'],
    'confirmed' => ['✅ Confirmed',  'bg-blue-100 text-blue-700 border-blue-200'],
    'active'    => ['🔄 Active',     'bg-green-100 text-green-700 border-green-200'],
    'completed' => ['📦 Completed',  'bg-gray-100 text-gray-700 border-gray-200'],
    'cancelled' => ['❌ Cancelled',  'bg-red-100 text-red-600 border-red-200'],
    'expired'   => ['⌛ Expired',    'bg-gray-100 text-gray-400 border-gray-200'],
];
?>
<!DOCTYPE html>
<html class="light" lang="en">
<head>
    <meta charset="utf-8"/>
    <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
    <title>My Pre-Bookings — RendeX</title>
    <link href="https://fonts.googleapis.com" rel="preconnect"/>
    <link crossorigin href="https://fonts.gstatic.com" rel="preconnect"/>
    <link href="https://fonts.googleapis.com/css2?family=Spline+Sans:wght@300;400;500;600;700&display=swap" rel="stylesheet"/>
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&display=swap" rel="stylesheet"/>
    <script src="https://cdn.tailwindcss.com?plugins=forms"></script>
    <script>
      tailwind.config = {
        darkMode: "class",
        theme: { extend: {
            colors: {"primary":"#f9f506","background-light":"#f8f8f5","text-main":"#1c1c0d","text-muted":"#5e5e4a"},
            fontFamily: {"display":["Spline Sans","sans-serif"]}
        }}
      }
    </script>
    <style>body{font-family:"Spline Sans",sans-serif}.material-symbols-outlined{font-variation-settings:'FILL' 0,'wght' 400,'GRAD' 0,'opsz' 24}</style>
</head>
<body class="bg-background-light text-text-main min-h-screen">

<!-- Navbar -->
<header class="sticky top-0 z-50 flex items-center border-b border-[#e9e8ce] bg-background-light/95 backdrop-blur-sm px-6 py-4">
    <div class="flex items-center gap-8 w-full max-w-[1400px] mx-auto">
        <a href="dashboard.php" class="flex items-center gap-2">
            <div class="size-8 text-primary">
                <svg class="w-full h-full" fill="none" viewBox="0 0 48 48" xmlns="http://www.w3.org/2000/svg">
                    <path d="M36.7273 44C33.9891 44 31.6043 39.8386 30.3636 33.69C29.123 39.8386 26.7382 44 24 44C21.2618 44 18.877 39.8386 17.6364 33.69C16.3957 39.8386 14.0109 44 11.2727 44C7.25611 44 4 35.0457 4 24C4 12.9543 7.25611 4 11.2727 4C14.0109 4 16.3957 8.16144 17.6364 14.31C18.877 8.16144 21.2618 4 24 4C26.7382 4 29.123 8.16144 30.3636 14.31C31.6043 8.16144 33.9891 4 36.7273 4C40.7439 4 44 12.9543 44 24C44 35.0457 40.7439 44 36.7273 44Z" fill="currentColor"/>
                </svg>
            </div>
            <h2 class="text-xl font-bold tracking-tight">RendeX</h2>
        </a>
        <div class="ml-auto flex items-center gap-6">
            <a href="rentals.php" class="text-sm font-bold hover:text-primary transition-colors">My Rentals</a>
            <a href="my-prebookings.php" class="text-sm font-bold text-primary border-b-2 border-primary pb-0.5">Pre-Bookings</a>
            <a href="my-purchases.php" class="text-sm font-bold hover:text-primary transition-colors">Purchases</a>
            <a href="profile.php" class="w-9 h-9 rounded-full bg-primary flex items-center justify-center text-black font-black text-sm shadow-sm">
                <?= strtoupper(substr($_SESSION['user_name'], 0, 1)) ?>
            </a>
        </div>
    </div>
</header>

<?php if (isset($_GET['msg']) && $_GET['msg'] === 'cancelled'): ?>
<div class="max-w-[1400px] mx-auto px-4 md:px-10 pt-6">
    <div class="bg-red-50 border border-red-200 text-red-700 p-4 rounded-2xl font-bold flex items-center gap-3">
        <span class="material-symbols-outlined">cancel</span> Pre-booking cancelled successfully.
    </div>
</div>
<?php endif; ?>

<main class="max-w-[1400px] mx-auto px-4 md:px-10 py-12">

    <div class="flex items-center justify-between mb-10">
        <div>
            <h1 class="text-4xl font-black mb-2">My Pre-Bookings</h1>
            <p class="text-text-muted">Upcoming reservations pending owner approval or confirmed.</p>
        </div>
        <a href="dashboard.php" class="bg-primary hover:bg-yellow-300 text-black font-bold px-6 py-3 rounded-full transition-all shadow-sm flex items-center gap-2">
            <span class="material-symbols-outlined text-sm">search</span> Browse Items
        </a>
    </div>

    <?php if (empty($bookings)): ?>
    <div class="flex flex-col items-center py-24 text-center">
        <div class="w-20 h-20 rounded-full bg-white border border-[#e9e8ce] flex items-center justify-center mb-6 shadow-sm">
            <span class="material-symbols-outlined text-4xl text-text-muted">calendar_month</span>
        </div>
        <h2 class="text-2xl font-black mb-2">No pre-bookings yet</h2>
        <p class="text-text-muted mb-8 max-w-sm">Find an item and click "Schedule a Future Date" to reserve it in advance.</p>
        <a href="dashboard.php" class="bg-primary hover:bg-yellow-300 text-black font-black px-8 py-4 rounded-full transition-all shadow-lg">Browse Items</a>
    </div>
    <?php else: ?>

    <!-- Stats row -->
    <?php
    $pending_ct   = count(array_filter($bookings, fn($b) => $b['status'] === 'pending'));
    $confirmed_ct = count(array_filter($bookings, fn($b) => $b['status'] === 'confirmed'));
    $active_ct    = count(array_filter($bookings, fn($b) => $b['status'] === 'active'));
    ?>
    <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-10">
        <div class="bg-white border border-[#e9e8ce] rounded-2xl p-5 text-center shadow-sm">
            <p class="text-3xl font-black text-yellow-600"><?= $pending_ct ?></p>
            <p class="text-xs font-bold text-text-muted mt-1 uppercase tracking-wide">Pending</p>
        </div>
        <div class="bg-white border border-[#e9e8ce] rounded-2xl p-5 text-center shadow-sm">
            <p class="text-3xl font-black text-blue-600"><?= $confirmed_ct ?></p>
            <p class="text-xs font-bold text-text-muted mt-1 uppercase tracking-wide">Confirmed</p>
        </div>
        <div class="bg-white border border-[#e9e8ce] rounded-2xl p-5 text-center shadow-sm">
            <p class="text-3xl font-black text-green-600"><?= $active_ct ?></p>
            <p class="text-xs font-bold text-text-muted mt-1 uppercase tracking-wide">Active</p>
        </div>
        <div class="bg-white border border-[#e9e8ce] rounded-2xl p-5 text-center shadow-sm">
            <p class="text-3xl font-black"><?= count($bookings) ?></p>
            <p class="text-xs font-bold text-text-muted mt-1 uppercase tracking-wide">Total</p>
        </div>
    </div>

    <div class="space-y-4">
        <?php foreach ($bookings as $b):
            $images = json_decode($b['images'] ?? '[]', true) ?: [];
            $img    = !empty($images) ? 'uploads/' . $images[0] : '';
            $s      = $b['status'] ?? 'pending';
            [$status_label, $status_class] = $status_map[$s] ?? ['Unknown', 'bg-gray-100 text-gray-500 border-gray-200'];
            $can_cancel = in_array($s, ['pending', 'confirmed']);
        ?>
        <div class="bg-white border border-[#e9e8ce] rounded-2xl overflow-hidden shadow-sm hover:shadow-md transition-all">
            <div class="flex flex-col md:flex-row">
                <!-- Image -->
                <div class="w-full md:w-36 h-36 bg-gray-100 shrink-0 overflow-hidden">
                    <?php if ($img): ?>
                    <img src="<?= htmlspecialchars($img) ?>" class="w-full h-full object-cover"
                         onerror="this.src='https://placehold.co/200x200?text=Item'">
                    <?php else: ?>
                    <div class="w-full h-full flex items-center justify-center bg-gray-200">
                        <span class="material-symbols-outlined text-3xl text-gray-400">inventory_2</span>
                    </div>
                    <?php endif; ?>
                </div>

                <!-- Details -->
                <div class="flex-1 p-6 flex flex-col md:flex-row gap-4 items-start md:items-center">
                    <div class="flex-1">
                        <div class="flex items-center gap-3 mb-2">
                            <span class="border text-xs font-bold px-3 py-1 rounded-full <?= $status_class ?>"><?= $status_label ?></span>
                            <span class="text-[10px] font-mono text-text-muted"><?= $b['booking_ref'] ?></span>
                        </div>
                        <h3 class="text-lg font-black mb-1"><?= htmlspecialchars($b['item_title'] ?? 'Unknown Item') ?></h3>
                        <div class="flex flex-wrap gap-4 text-sm text-text-muted mt-2">
                            <span class="flex items-center gap-1">
                                <span class="material-symbols-outlined text-sm">calendar_today</span>
                                <?= date('M d, Y', strtotime($b['start_date'])) ?> → <?= date('M d, Y', strtotime($b['end_date'])) ?>
                            </span>
                            <span class="flex items-center gap-1">
                                <span class="material-symbols-outlined text-sm">schedule</span>
                                <?= $b['total_days'] ?> day<?= $b['total_days'] > 1 ? 's' : '' ?>
                            </span>
                            <span class="flex items-center gap-1">
                                <span class="material-symbols-outlined text-sm">person</span>
                                <?= htmlspecialchars($b['owner_name'] ?? 'Owner') ?>
                            </span>
                            <?php if ($b['delivery_method'] === 'delivery'): ?>
                            <span class="flex items-center gap-1 text-blue-600">
                                <span class="material-symbols-outlined text-sm">local_shipping</span> Delivery
                            </span>
                            <?php else: ?>
                            <span class="flex items-center gap-1">
                                <span class="material-symbols-outlined text-sm">storefront</span> Pickup
                            </span>
                            <?php endif; ?>
                        </div>
                        <?php if ($b['notes']): ?>
                        <p class="text-xs text-text-muted mt-2 italic">"<?= htmlspecialchars($b['notes']) ?>"</p>
                        <?php endif; ?>
                    </div>

                    <!-- Amount + Actions -->
                    <div class="text-right shrink-0">
                        <p class="text-2xl font-black text-yellow-700">₹<?= number_format($b['total_amount'], 0) ?></p>
                        <p class="text-[10px] text-text-muted mb-4">₹<?= number_format($b['daily_rate'], 0) ?>/day × <?= $b['total_days'] ?> days</p>

                        <?php if ($s === 'confirmed'): ?>
                        <a href="confirm-rental.php?id=<?= urlencode($b['item_id']) ?>&prebooking_ref=<?= urlencode($b['booking_ref']) ?>&duration=<?= $b['total_days'] ?>"
                           class="inline-flex items-center gap-1 bg-primary text-black text-sm font-black px-5 py-2.5 rounded-full hover:bg-yellow-300 transition-all shadow-sm mb-2">
                            <span class="material-symbols-outlined text-sm">payments</span> Pay &amp; Confirm
                        </a><br>
                        <?php endif; ?>

                        <?php if ($can_cancel): ?>
                        <form method="POST" onsubmit="return confirm('Cancel this pre-booking?')">
                            <input type="hidden" name="cancel_ref" value="<?= htmlspecialchars($b['booking_ref']) ?>">
                            <button class="text-xs text-red-500 font-bold hover:underline">Cancel Request</button>
                        </form>
                        <?php endif; ?>

                        <?php if ($s === 'pending' && $b['expires_at']): ?>
                        <p class="text-[10px] text-text-muted mt-2">Expires: <?= date('M d, g:i A', strtotime($b['expires_at'])) ?></p>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>
</main>

<footer class="bg-white border-t border-[#e9e8ce] py-6 px-4 text-center text-sm text-text-muted mt-12">
    © 2026 RendeX. All rights reserved.
</footer>
</body>
</html>
