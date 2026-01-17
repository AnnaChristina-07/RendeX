<?php
ob_start();
session_start();

// Auth Check
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// Include database connection
require_once 'config/database.php';

// Load User to check role - Check both JSON and Database
$users_file = 'users.json';
$users = file_exists($users_file) ? json_decode(file_get_contents($users_file), true) : [];
$current_user = null;
foreach($users as $u) {
    if ($u['id'] === $_SESSION['user_id']) {
        $current_user = $u;
        break;
    }
}

$user_role = $current_user['role'] ?? '';
$is_approved_delivery_partner = ($user_role === 'delivery_partner');
$is_pending = false;

// Also check database for approved delivery partner status
try {
    $pdo = getDBConnection();
    if ($pdo) {
        // Check if user is approved delivery partner in database
        $stmt = $pdo->prepare("SELECT role FROM users WHERE id = ?");
        $stmt->execute([$_SESSION['user_id']]);
        $db_user = $stmt->fetch();
        if ($db_user && $db_user['role'] === 'delivery_partner') {
            $is_approved_delivery_partner = true;
        }
        
        // Check if user has a pending driver application
        $stmt = $pdo->prepare("SELECT id FROM driver_applications WHERE user_id = ? AND status = 'pending'");
        $stmt->execute([$_SESSION['user_id']]);
        if ($stmt->fetch()) {
            $is_pending = true;
        }
    }
} catch (PDOException $e) {
    // Database not available, use JSON data only
}

// Access control - Only approved delivery partners can access this dashboard
if (!$is_approved_delivery_partner) {
    if ($is_pending || $user_role === 'delivery_partner_pending') {
        // Pending partners should go to dashboard to see pending status
        header("Location: dashboard.php?msg=pending_approval");
        exit();
    } else {
        // Not a delivery partner at all, redirect to dashboard
        header("Location: dashboard.php");
        exit();
    }
}

$user_id = $_SESSION['user_id'];
$user_name = $_SESSION['user_name'];

// --- DATA LOADING ---
$deliveries_file = 'deliveries.json';
$rentals_file = 'rentals.json'; 

$deliveries = file_exists($deliveries_file) ? json_decode(file_get_contents($deliveries_file), true) : [];
$rentals = file_exists($rentals_file) ? json_decode(file_get_contents($rentals_file), true) : [];

// Map Rentals
$rentals_map = [];
foreach($rentals as $r) {
    $rentals_map[$r['id']] = $r;
}

// Filter My Deliveries
$my_deliveries = [];
$stats = [
    'assigned' => 0,
    'picked_up' => 0,
    'delivered' => 0
];

foreach($deliveries as $d) {
    if ($d['partner_id'] === $user_id) {
        // Enhance with rental info
        if (isset($rentals_map[$d['rental_id']])) {
            $d['rental'] = $rentals_map[$d['rental_id']];
        } else {
            $d['rental'] = null; // Should not happen ideally
        }
        
        $my_deliveries[] = $d;
        
        if (isset($stats[$d['status']])) {
            $stats[$d['status']]++;
        }
    }
}

// --- ACTIONS ---

// Update Status
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_status_id'])) {
    $did = $_POST['update_status_id'];
    $new_status = $_POST['new_status'];
    
    // Update logic
    $updated = false;
    foreach($deliveries as &$d) {
        if ($d['id'] === $did && $d['partner_id'] === $user_id) { // Security check
            $d['status'] = $new_status;
            $d['history'][] = [
                'status' => $new_status,
                'timestamp' => date('Y-m-d H:i:s')
            ];
            $updated = true;
            break;
        }
    }
    
    if ($updated) {
        file_put_contents($deliveries_file, json_encode($deliveries, JSON_PRETTY_PRINT));
        header("Location: delivery_dashboard.php?tab=tasks&msg=updated");
        exit();
    }
}

$tab = isset($_GET['tab']) ? $_GET['tab'] : 'overview';

?>
<!DOCTYPE html>
<html class="light" lang="en">
<head>
    <meta charset="utf-8"/>
    <meta content="width=device-width, initial-scale=1.0" name="viewport"/>
    <title>Delivery Dashboard - RendeX</title>
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
              "primary": "#dfff00",
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
    </style>
</head>
<body class="bg-gray-50 text-text-main min-h-screen flex flex-col">

    <!-- Navbar -->
    <header class="sticky top-0 z-50 bg-[#1e2015] text-white border-b border-gray-800 px-6 py-4">
        <div class="max-w-7xl mx-auto flex items-center justify-between">
            <div class="flex items-center gap-8">
                <a href="#" class="flex items-center gap-3">
                    <div class="w-10 h-10 bg-[#dfff00] rounded-full flex items-center justify-center text-black font-bold text-xl">R</div>
                    <span class="font-bold text-xl tracking-tight text-white">RendeX <span class="text-xs bg-[#dfff00] text-black px-2 py-0.5 rounded ml-1 font-bold">DRIVER</span></span>
                </a>
                
                <nav class="hidden md:flex gap-1">
                    <a href="?tab=overview" class="px-5 py-2 rounded-full text-sm font-bold transition-all <?php echo $tab=='overview' ? 'bg-[#dfff00] text-black' : 'text-gray-400 hover:text-white'; ?>">Overview</a>
                    <a href="?tab=tasks" class="px-5 py-2 rounded-full text-sm font-bold transition-all <?php echo $tab=='tasks' ? 'bg-[#dfff00] text-black' : 'text-gray-400 hover:text-white'; ?>">My Tasks</a>
                </nav>
            </div>
            
            <div class="flex items-center gap-4">
                <div class="flex items-center gap-3">
                    <div class="w-8 h-8 rounded-full bg-gray-700 flex items-center justify-center font-bold text-xs text-white">
                        <?php echo substr($user_name, 0, 1); ?>
                    </div>
                    <a href="logout.php" class="text-sm font-bold text-red-500 hover:text-red-400">Logout</a>
                </div>
            </div>
        </div>
    </header>

    <!-- Main Content -->
    <main class="flex-1 max-w-7xl mx-auto w-full p-6 md:p-10">
        
        <?php if($tab == 'overview'): ?>
            <div class="mb-10">
                <h1 class="text-3xl font-black mb-2">Hello, <?php echo htmlspecialchars($user_name); ?>.</h1>
                <p class="text-text-muted">You have <strong><?php echo $stats['assigned']; ?></strong> new delivery tasks pending.</p>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-12">
                <div class="bg-white p-6 rounded-2xl shadow-sm border border-gray-200">
                    <div class="p-3 bg-yellow-50 text-yellow-600 rounded-xl w-fit mb-4">
                        <span class="material-symbols-outlined">pending_actions</span>
                    </div>
                    <p class="text-sm font-bold text-gray-500">Pending Pickup</p>
                    <p class="text-3xl font-black mt-1"><?php echo $stats['assigned']; ?></p>
                </div>
                <div class="bg-white p-6 rounded-2xl shadow-sm border border-gray-200">
                    <div class="p-3 bg-blue-50 text-blue-600 rounded-xl w-fit mb-4">
                        <span class="material-symbols-outlined">local_shipping</span>
                    </div>
                    <p class="text-sm font-bold text-gray-500">In Transit</p>
                    <p class="text-3xl font-black mt-1"><?php echo $stats['picked_up']; ?></p>
                </div>
                <div class="bg-white p-6 rounded-2xl shadow-sm border border-gray-200">
                    <div class="p-3 bg-green-50 text-green-600 rounded-xl w-fit mb-4">
                        <span class="material-symbols-outlined">check_circle</span>
                    </div>
                    <p class="text-sm font-bold text-gray-500">Completed</p>
                    <p class="text-3xl font-black mt-1"><?php echo $stats['delivered']; ?></p>
                </div>
            </div>

            <?php if($stats['assigned'] > 0): ?>
            <div class="bg-[#dfff00] rounded-2xl p-8 flex flex-col md:flex-row items-center justify-between gap-6">
                <div>
                    <h2 class="text-2xl font-black text-black">Ready to roll?</h2>
                    <p class="text-black/80 font-medium">You have new tasks waiting for acceptance.</p>
                </div>
                <a href="?tab=tasks" class="bg-black text-white px-8 py-4 rounded-full font-bold shadow-lg hover:scale-105 transition-transform">
                    View Tasks
                </a>
            </div>
            <?php endif; ?>

        <?php elseif($tab == 'tasks'): ?>
            <h2 class="text-2xl font-bold mb-8">Delivery Tasks</h2>
            
            <div class="space-y-6">
                <?php foreach(array_reverse($my_deliveries) as $d): 
                    $r = $d['rental'];
                ?>
                <div class="bg-white rounded-2xl border border-gray-200 p-6 shadow-sm flex flex-col md:flex-row gap-6">
                    <!-- Status Indicator -->
                    <div class="md:w-48 shrink-0 flex flex-col gap-2">
                        <span class="font-mono text-xs text-gray-400">ID: <?php echo $d['id']; ?></span>
                        <?php 
                            $badge_color = 'bg-gray-100 text-gray-600';
                            if ($d['status'] == 'assigned') $badge_color = 'bg-yellow-100 text-yellow-800';
                            if ($d['status'] == 'picked_up') $badge_color = 'bg-blue-100 text-blue-800';
                            if ($d['status'] == 'delivered') $badge_color = 'bg-green-100 text-green-800';
                        ?>
                        <div class="px-3 py-2 rounded-lg font-bold text-xs uppercase text-center <?php echo $badge_color; ?>">
                            <?php echo str_replace('_', ' ', $d['status']); ?>
                        </div>
                        <div class="text-xs text-gray-500 mt-auto">
                            Assigned: <?php echo date('M d, H:i', strtotime($d['assigned_at'])); ?>
                        </div>
                    </div>
                    
                    <!-- Details -->
                    <div class="flex-1 border-l border-gray-100 md:pl-6 space-y-4">
                        <div>
                             <h3 class="font-bold text-lg"><?php echo htmlspecialchars($r['item']['name']); ?></h3>
                             <p class="text-sm text-gray-500"><?php echo isset($r['item']['address']) ? htmlspecialchars($r['item']['address']) : 'Location info unavailable'; ?></p>
                        </div>
                        
                        <div class="grid grid-cols-2 gap-4 text-sm bg-gray-50 p-4 rounded-xl">
                            <div>
                                <span class="block text-xs font-bold text-gray-400">PICKUP FROM</span>
                                <span class="block font-bold mt-1">Owner</span>
                                <span class="block text-xs text-gray-500"><?php echo isset($r['item']['address']) ? $r['item']['address'] : 'N/A'; ?></span>
                            </div>
                            <div>
                                <span class="block text-xs font-bold text-gray-400">DELIVER TO</span>
                                <span class="block font-bold mt-1">Renter (User)</span>
                                <span class="block text-xs text-gray-500">Address not in file</span>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Actions -->
                    <div class="md:w-48 shrink-0 flex items-center justify-center">
                        <?php if($d['status'] === 'assigned'): ?>
                            <form method="POST" class="w-full">
                                <input type="hidden" name="update_status_id" value="<?php echo $d['id']; ?>">
                                <input type="hidden" name="new_status" value="picked_up">
                                <button class="w-full bg-black text-white py-3 rounded-xl font-bold hover:bg-gray-800 transition-colors flex flex-col items-center">
                                    <span class="material-symbols-outlined mb-1">package</span>
                                    Confirm Pickup
                                </button>
                            </form>
                        <?php elseif($d['status'] === 'picked_up'): ?>
                            <form method="POST" class="w-full">
                                <input type="hidden" name="update_status_id" value="<?php echo $d['id']; ?>">
                                <input type="hidden" name="new_status" value="delivered">
                                <button class="w-full bg-[#dfff00] text-black py-3 rounded-xl font-bold hover:bg-[#eaff3d] transition-colors flex flex-col items-center">
                                    <span class="material-symbols-outlined mb-1">check_circle</span>
                                    Confirm Delivery
                                </button>
                            </form>
                        <?php else: ?>
                            <div class="text-center text-green-600">
                                <span class="material-symbols-outlined text-4xl">task_alt</span>
                                <p class="text-xs font-bold mt-1">Completed</p>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
                <?php endforeach; ?>
                
                <?php if(empty($my_deliveries)) echo '<div class="text-center py-20 text-gray-500">No tasks assigned to you yet.</div>'; ?>
            </div>
        <?php endif; ?>

    </main>

</body>
</html>
