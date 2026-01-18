<?php
session_start();

// Strict Admin Security Check
if (!isset($_SESSION['user_id']) || !isset($_SESSION['user_email']) || $_SESSION['user_email'] !== 'annachristina2005@gmail.com') {
    // Log invalid access attempt if needed
    header("Location: index.php");
    exit();
}

// Include database connection
require_once 'config/database.php';

// Data Loading & Processing - Try database first, fallback to JSON
$users = [];
$items = [];
$rentals = [];
$deliveries = [];
$pending_driver_applications = []; // From database
$admin_notifications = []; // Admin notifications
$unread_notifications_count = 0;

$use_database = false;

try {
    $pdo = getDBConnection();
    if ($pdo) {
        $use_database = true;
        
        // Load users from database
        $stmt = $pdo->query("SELECT * FROM users ORDER BY created_at DESC");
        $users = $stmt->fetchAll();
        
        // Load pending driver applications from database (with user info)
        $stmt = $pdo->query("
            SELECT da.*, u.name as user_name, u.email as user_email 
            FROM driver_applications da 
            JOIN users u ON da.user_id = u.id 
            WHERE da.status = 'pending'
            ORDER BY da.applied_at DESC
        ");
        $pending_driver_applications = $stmt->fetchAll();

        // Load pending items from database
        $stmt = $pdo->query("
            SELECT i.*, u.name as owner_name 
            FROM items i 
            JOIN users u ON i.owner_id = u.id 
            WHERE i.admin_status = 'pending'
            ORDER BY i.created_at DESC
        ");
        $pending_items_db = $stmt->fetchAll();
        
        // Load admin notifications (unread first)
        $stmt = $pdo->query("
            SELECT * FROM admin_notifications 
            ORDER BY is_read ASC, created_at DESC 
            LIMIT 20
        ");
        $admin_notifications = $stmt->fetchAll();
        
        // Count unread notifications
        $stmt = $pdo->query("SELECT COUNT(*) as count FROM admin_notifications WHERE is_read = 0");
        $result = $stmt->fetch();
        $unread_notifications_count = $result['count'] ?? 0;
    }
} catch (PDOException $e) {
    $use_database = false;
}

// Fallback to JSON files
if (!$use_database) {
    $users_file = 'users.json';
    $users = file_exists($users_file) ? json_decode(file_get_contents($users_file), true) : [];
}

$items_file = 'items.json';
$rentals_file = 'rentals.json';
$deliveries_file = 'deliveries.json';

$items = file_exists($items_file) ? json_decode(file_get_contents($items_file), true) : [];
$rentals = file_exists($rentals_file) ? json_decode(file_get_contents($rentals_file), true) : [];
$deliveries = file_exists($deliveries_file) ? json_decode(file_get_contents($deliveries_file), true) : [];

// --- ACTION HANDLERS ---

// 1. Delete Item
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_item_id'])) {
    $delete_id = $_POST['delete_item_id'];
    $new_items = array_filter($items, function($i) use ($delete_id) { return $i['id'] !== $delete_id; });
    file_put_contents($items_file, json_encode(array_values($new_items), JSON_PRETTY_PRINT));
    header("Location: admin_dashboard.php?tab=products&msg=item_deleted");
    exit();
}

// 2. Delete User
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_user_id'])) {
    $delete_uid = $_POST['delete_user_id'];
    
    if ($use_database) {
        try {
            $stmt = $pdo->prepare("DELETE FROM users WHERE id = ? AND email != 'annachristina2005@gmail.com'");
            $stmt->execute([$delete_uid]);
        } catch (PDOException $e) {}
    } else {
        $new_users = [];
        foreach($users as $u) {
            if ($u['id'] === $delete_uid) {
                if ($u['email'] === 'annachristina2005@gmail.com') continue;
            } else {
                $new_users[] = $u;
            }
        }
        file_put_contents('users.json', json_encode(array_values($new_users), JSON_PRETTY_PRINT));
    }
    header("Location: admin_dashboard.php?tab=users&msg=user_deleted");
    exit();
}

// 2.5 Approve Owner
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['approve_user_id'])) {
    $uid = $_POST['approve_user_id'];
    if ($use_database) {
        try {
            $stmt = $pdo->prepare("UPDATE users SET role = 'owner' WHERE id = ?");
            $stmt->execute([$uid]);
        } catch (PDOException $e) {}
    } else {
        foreach ($users as &$u) {
            if ($u['id'] === $uid) {
                $u['role'] = 'owner';
                break;
            }
        }
        file_put_contents('users.json', json_encode($users, JSON_PRETTY_PRINT));
    }
    header("Location: admin_dashboard.php?tab=users&msg=approved");
    exit();
}

// 2.6 Reject Owner
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['reject_user_id'])) {
    $uid = $_POST['reject_user_id'];
    if ($use_database) {
        try {
            $stmt = $pdo->prepare("DELETE FROM users WHERE id = ?");
            $stmt->execute([$uid]);
        } catch (PDOException $e) {}
    } else {
        $users = array_values(array_filter($users, function($u) use ($uid) { return $u['id'] !== $uid; }));
        file_put_contents('users.json', json_encode($users, JSON_PRETTY_PRINT));
    }
    header("Location: admin_dashboard.php?tab=users&msg=rejected");
    exit();
}

// 2.7 Approve Delivery Partner (from database)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['approve_partner_id'])) {
    $app_id = $_POST['approve_partner_id'];
    
    if ($use_database) {
        try {
            // Get the application to find user_id
            $stmt = $pdo->prepare("SELECT user_id FROM driver_applications WHERE id = ?");
            $stmt->execute([$app_id]);
            $app = $stmt->fetch();
            
            if ($app) {
                // Update user role to delivery_partner
                $stmt = $pdo->prepare("UPDATE users SET role = 'delivery_partner' WHERE id = ?");
                $stmt->execute([$app['user_id']]);
                
                // Update application status
                $stmt = $pdo->prepare("UPDATE driver_applications SET status = 'approved', reviewed_at = NOW() WHERE id = ?");
                $stmt->execute([$app_id]);
            }
        } catch (PDOException $e) {}
    } else {
        // Fallback for JSON - approve_partner_id is user_id
        foreach ($users as &$u) {
            if ($u['id'] === $app_id) {
                $u['role'] = 'delivery_partner';
                break;
            }
        }
        file_put_contents('users.json', json_encode($users, JSON_PRETTY_PRINT));
    }
    header("Location: admin_dashboard.php?tab=users&msg=partner_approved");
    exit();
}

// 2.8 Reject Delivery Partner
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['reject_partner_id'])) {
    $app_id = $_POST['reject_partner_id'];
    
    if ($use_database) {
        try {
            // Update application status to rejected
            $stmt = $pdo->prepare("UPDATE driver_applications SET status = 'rejected', reviewed_at = NOW() WHERE id = ?");
            $stmt->execute([$app_id]);
        } catch (PDOException $e) {}
    } else {
        $users = array_values(array_filter($users, function($u) use ($app_id) { return $u['id'] !== $app_id; }));
        file_put_contents('users.json', json_encode($users, JSON_PRETTY_PRINT));
    }
    header("Location: admin_dashboard.php?tab=users&msg=partner_rejected");
    exit();
}

// 2.9 Approve Item
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['approve_item_id'])) {
    $item_id = $_POST['approve_item_id'];
    if ($use_database) {
        try {
            $stmt = $pdo->prepare("UPDATE items SET admin_status = 'approved', availability_status = 'available' WHERE id = ?");
            $stmt->execute([$item_id]);
        } catch (PDOException $e) {}
    }
    
    // Also update JSON for fallback/sync
    foreach ($items as &$i) {
        if ($i['id'] == $item_id || (isset($i['id']) && $i['id'] == $item_id)) {
            $i['status'] = 'Active';
            break;
        }
    }
    file_put_contents($items_file, json_encode($items, JSON_PRETTY_PRINT));
    
    header("Location: admin_dashboard.php?tab=products&msg=item_approved");
    exit();
}

// 2.10 Reject Item
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['reject_item_id'])) {
    $item_id = $_POST['reject_item_id'];
    if ($use_database) {
        try {
            $stmt = $pdo->prepare("UPDATE items SET admin_status = 'rejected' WHERE id = ?");
            $stmt->execute([$item_id]);
        } catch (PDOException $e) {}
    }
    
    // Also update JSON
    foreach ($items as &$i) {
        if ($i['id'] == $item_id) {
            $i['status'] = 'Rejected';
            break;
        }
    }
    file_put_contents($items_file, json_encode($items, JSON_PRETTY_PRINT));
    
    header("Location: admin_dashboard.php?tab=products&msg=item_rejected");
    exit();
}

// 3. Cancel Rental
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['cancel_rental_id'])) {
    $cancel_rid = $_POST['cancel_rental_id'];
    $new_rentals = [];
    foreach($rentals as $r) {
        if ($r['id'] === $cancel_rid) {
            $r['status'] = 'cancelled'; // Or delete it
            $r['end_date'] = date('Y-m-d'); // End immediately
        }
        $new_rentals[] = $r;
    }
    file_put_contents($rentals_file, json_encode($new_rentals, JSON_PRETTY_PRINT));
    header("Location: admin_dashboard.php?tab=rentals&msg=rental_cancelled");
    exit();
}

// 4. System Backup
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['backup_now'])) {
    $users_file_bak = 'users.json';
    if (file_exists($users_file_bak)) copy($users_file_bak, $users_file_bak . '.bak');
    copy($items_file, $items_file . '.bak');
    copy($rentals_file, $rentals_file . '.bak');
    copy($deliveries_file, $deliveries_file . '.bak');
    header("Location: admin_dashboard.php?tab=system&msg=backup_success");
    exit();
}

// 5. Assign Delivery Partner
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['assign_delivery'])) {
    $rental_id = $_POST['rental_id'];
    $partner_id = $_POST['partner_id'];
    
    $new_delivery = [
        'id' => uniqid('del_'),
        'rental_id' => $rental_id,
        'partner_id' => $partner_id,
        'status' => 'assigned',
        'assigned_at' => date('Y-m-d H:i:s'),
        'history' => [
            ['status' => 'assigned', 'timestamp' => date('Y-m-d H:i:s')]
        ]
    ];
    
    $deliveries[] = $new_delivery;
    file_put_contents($deliveries_file, json_encode($deliveries, JSON_PRETTY_PRINT));
    header("Location: admin_dashboard.php?tab=deliveries&msg=assigned");
    exit();
}


// --- DATA PREPARATION ---

// Identify Owners and Renters
$owner_ids = array_unique(array_column($items, 'user_id'));
$renter_ids = array_unique(array_column($rentals, 'user_id'));

$owners = [];
$renters = [];
$delivery_partners = [];
$pending_owners = [];
$pending_partners = [];

// If using database, pending_driver_applications already loaded
// Convert to the format expected by the UI
if ($use_database && !empty($pending_driver_applications)) {
    foreach ($pending_driver_applications as $app) {
        $pending_partners[] = [
            'id' => $app['id'], // Use application ID for approve/reject
            'user_id' => $app['user_id'],
            'name' => $app['full_name'],
            'email' => $app['user_email'],
            'phone' => $app['phone'],
            'delivery_application' => [
                'full_name' => $app['full_name'],
                'phone' => $app['phone'],
                'email' => $app['user_email'],
                'date_of_birth' => $app['date_of_birth'],
                'address' => $app['address'],
                'city' => $app['city'],
                'pincode' => $app['pincode'],
                'vehicle_type' => $app['vehicle_type'],
                'vehicle_number' => $app['vehicle_number'],
                'license_number' => $app['driving_license'],
                'license_expiry' => date('Y-m-d', strtotime('+1 year')), // Default if not stored
                'service_areas' => [$app['city']], // Use city as service area
                'availability_hours' => 'flexible',
                'experience' => 'none',
                'applied_at' => $app['applied_at'],
                'status' => $app['status'],
                'license_photo' => $app['license_document'] ?? $app['license_photo'] ?? ''
            ]
        ];
    }
}

foreach ($users as $u) {
    // Check for Pending Owner
    if (isset($u['role']) && $u['role'] === 'owner_pending') {
        $pending_owners[] = $u;
        continue; 
    }
    // Check for Pending Delivery Partner (from JSON fallback)
    if (!$use_database && isset($u['role']) && $u['role'] === 'delivery_partner_pending') {
        $pending_partners[] = $u;
        continue; 
    }

    $is_special_owner = ($u['email'] === 'owner@gmail.com');
    $is_owner_role = (isset($u['role']) && $u['role'] === 'owner');
    
    // Classification Logic
    // 1. Owners: Listed items OR special owner OR role is 'owner'
    if (in_array($u['id'], $owner_ids) || $is_special_owner || $is_owner_role) {
        $owners[] = $u;
    }
    // 3. Delivery Partners
    elseif (isset($u['role']) && $u['role'] === 'delivery_partner') {
        $delivery_partners[] = $u;
    }
    // 4. Renters: Default - If you are signed up, you are at least a renter
    else {
        $renters[] = $u;
    }
}

// Helper for Rental display: User Name Map
$user_names = array_column($users, 'name', 'id');

// Current Tab
$tab = isset($_GET['tab']) ? $_GET['tab'] : 'overview';

// Helper Stats
$total_users = count($users);
$total_items = count($items);
$total_rentals = count($rentals);
$total_revenue = 0;
$active_rentals_count = 0;
foreach ($rentals as $rental) {
    if (isset($rental['total_price'])) $total_revenue += $rental['total_price'];
    
    // Check active
    $is_returned = (isset($rental['status']) && ($rental['status'] === 'returned' || $rental['status'] === 'cancelled'));
    $is_expired = (strtotime($rental['end_date']) < strtotime(date('Y-m-d')));
    if (!$is_returned && !$is_expired) $active_rentals_count++;
}
?>
<!DOCTYPE html>
<html class="light" lang="en">
<head>
    <meta charset="utf-8"/>
    <meta content="width=device-width, initial-scale=1.0" name="viewport"/>
    <title>RendeX Admin Dashboard</title>
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
              "background-dark": "#1a1a1a",
              "surface-dark": "#2a2a2a",
              "accent-blue": "#3b82f6",
            },
            fontFamily: {
              "display": ["Spline Sans", "sans-serif"],
              "body": ["Noto Sans", "sans-serif"],
            },
          },
        },
      }

      function searchFilter(inputId, containerId, itemSelector) {
          const input = document.getElementById(inputId);
          const filter = input.value.toLowerCase();
          const container = document.getElementById(containerId);
          const items = container.querySelectorAll(itemSelector);

          items.forEach(item => {
              const text = item.textContent || item.innerText;
              if (text.toLowerCase().indexOf(filter) > -1) {
                  item.style.display = "";
                  // Also handle parent grid layout
                  if(item.parentElement.classList.contains('col-span-full')) return; // skip no results msg
              } else {
                  item.style.display = "none";
              }
          });
      }
      
      function filterRentals(status) {
          const all = document.querySelectorAll('.rental-card');
          const defaultTab = document.getElementById('tab-all');
          const activeTab = document.getElementById('tab-active');
          const pastTab = document.getElementById('tab-past');
          
          // Reset styles
          [defaultTab, activeTab, pastTab].forEach(t => {
              if (t) {
                t.classList.remove('text-black', 'border-b-2', 'border-black');
                t.classList.add('text-gray-400', 'font-medium', 'cursor-pointer');
              }
          });
          
          // Set Active Style
          let target = status === 'all' ? defaultTab : (status === 'active' ? activeTab : pastTab);
          if (target) {
            target.classList.add('text-black', 'border-b-2', 'border-black');
            target.classList.remove('text-gray-400');
          }
          
          if(status === 'all') {
              all.forEach(el => el.style.display = '');
          } else if (status === 'active') {
              all.forEach(el => {
                  el.style.display = el.dataset.status === 'active' ? '' : 'none';
              });
          } else if (status === 'past') {
              all.forEach(el => {
                  el.style.display = el.dataset.status !== 'active' ? '' : 'none';
              });
          }
      }
    </script>
</head>
<body class="bg-gray-100 text-gray-900 flex h-screen overflow-hidden font-display">

    <!-- Sidebar -->
    <aside class="w-64 bg-[#1a1a1a] text-white flex flex-col shrink-0 transition-all duration-300">
        <div class="p-6 border-b border-gray-800 flex items-center gap-2">
            <div class="size-8 text-primary">
                <svg class="w-full h-full" fill="none" viewbox="0 0 48 48" xmlns="http://www.w3.org/2000/svg">
                    <path d="M36.7273 44C33.9891 44 31.6043 39.8386 30.3636 33.69C29.123 39.8386 26.7382 44 24 44C21.2618 44 18.877 39.8386 17.6364 33.69C16.3957 39.8386 14.0109 44 11.2727 44C7.25611 44 4 35.0457 4 24C4 12.9543 7.25611 4 11.2727 4C14.0109 4 16.3957 8.16144 17.6364 14.31C18.877 8.16144 21.2618 4 24 4C26.7382 4 29.123 8.16144 30.3636 14.31C31.6043 8.16144 33.9891 4 36.7273 4C40.7439 4 44 12.9543 44 24C44 35.0457 40.7439 44 36.7273 44Z" fill="currentColor"></path>
                </svg>
            </div>
            <span class="font-bold text-xl tracking-tight">Admin<span class="text-primary">Panel</span></span>
        </div>
        
        <nav class="flex-1 p-4 space-y-2 overflow-y-auto">
            <a href="?tab=overview" class="flex items-center gap-3 px-4 py-3 rounded-xl transition-colors <?php echo $tab == 'overview' ? 'bg-gray-800 text-primary font-bold' : 'text-gray-400 hover:bg-gray-800 hover:text-white'; ?>">
                <span class="material-symbols-outlined">dashboard</span> Dashboard
                <?php if (count($pending_partners) + count($pending_owners) > 0): ?>
                <span class="ml-auto bg-red-500 text-white text-xs font-bold px-2 py-0.5 rounded-full animate-pulse"><?php echo count($pending_partners) + count($pending_owners); ?></span>
                <?php endif; ?>
            </a>
            <a href="?tab=users" class="flex items-center gap-3 px-4 py-3 rounded-xl transition-colors <?php echo $tab == 'users' ? 'bg-gray-800 text-primary font-bold' : 'text-gray-400 hover:bg-gray-800 hover:text-white'; ?>">
                <span class="material-symbols-outlined">group</span> Users & Groups
                <?php if (count($pending_partners) + count($pending_owners) > 0): ?>
                <span class="ml-auto bg-orange-500 text-white text-xs font-bold px-2 py-0.5 rounded-full"><?php echo count($pending_partners) + count($pending_owners); ?></span>
                <?php endif; ?>
            </a>
            <a href="?tab=rentals" class="flex items-center gap-3 px-4 py-3 rounded-xl transition-colors <?php echo $tab == 'rentals' ? 'bg-gray-800 text-primary font-bold' : 'text-gray-400 hover:bg-gray-800 hover:text-white'; ?>">
                <span class="material-symbols-outlined">receipt_long</span> Rentals Mgmt
            </a>
            <a href="?tab=products" class="flex items-center gap-3 px-4 py-3 rounded-xl transition-colors <?php echo $tab == 'products' ? 'bg-gray-800 text-primary font-bold' : 'text-gray-400 hover:bg-gray-800 hover:text-white'; ?>">
                <span class="material-symbols-outlined">inventory_2</span> Product Mgmt
                <?php 
                $pending_items_count = 0;
                if ($use_database && isset($pending_items_db)) $pending_items_count = count($pending_items_db);
                else {
                    foreach($items as $i) if (isset($i['status']) && $i['status'] === 'Pending Approval') $pending_items_count++;
                }
                if ($pending_items_count > 0): ?>
                <span class="ml-auto bg-yellow-500 text-black text-xs font-bold px-2 py-0.5 rounded-full"><?php echo $pending_items_count; ?></span>
                <?php endif; ?>
            </a>
            <a href="?tab=deliveries" class="flex items-center gap-3 px-4 py-3 rounded-xl transition-colors <?php echo $tab == 'deliveries' ? 'bg-gray-800 text-primary font-bold' : 'text-gray-400 hover:bg-gray-800 hover:text-white'; ?>">
                <span class="material-symbols-outlined">local_shipping</span> Deliveries
            </a>
            <a href="?tab=system" class="flex items-center gap-3 px-4 py-3 rounded-xl transition-colors <?php echo $tab == 'system' ? 'bg-gray-800 text-primary font-bold' : 'text-gray-400 hover:bg-gray-800 hover:text-white'; ?>">
                <span class="material-symbols-outlined">settings_suggest</span> System
            </a>
        </nav>

        <!-- Pending Approvals Quick Stats in Sidebar -->
        <?php if (count($pending_partners) > 0 || count($pending_owners) > 0): ?>
        <div class="p-4 border-t border-gray-800">
            <p class="text-xs text-gray-500 font-bold uppercase mb-3">Pending Approvals</p>
            <?php if (count($pending_partners) > 0): ?>
            <a href="?tab=users" class="flex items-center justify-between p-2 rounded-lg bg-blue-500/10 hover:bg-blue-500/20 text-blue-400 mb-2 transition-colors">
                <span class="flex items-center gap-2 text-xs">
                    <span class="material-symbols-outlined text-sm">two_wheeler</span>
                    Drivers
                </span>
                <span class="bg-blue-500 text-white text-xs font-bold px-2 py-0.5 rounded-full"><?php echo count($pending_partners); ?></span>
            </a>
            <?php endif; ?>
            <?php if (count($pending_owners) > 0): ?>
            <a href="?tab=users" class="flex items-center justify-between p-2 rounded-lg bg-orange-500/10 hover:bg-orange-500/20 text-orange-400 transition-colors">
                <span class="flex items-center gap-2 text-xs">
                    <span class="material-symbols-outlined text-sm">storefront</span>
                    Owners
                </span>
                <span class="bg-orange-500 text-white text-xs font-bold px-2 py-0.5 rounded-full"><?php echo count($pending_owners); ?></span>
            </a>
            <?php endif; ?>
        </div>
        <?php endif; ?>

        <div class="p-4 border-t border-gray-800">
            <div class="flex items-center gap-3 mb-4 px-2">
                <div class="w-10 h-10 rounded-full bg-primary/20 text-primary flex items-center justify-center font-bold">
                    A
                </div>
                <div>
                    <p class="text-sm font-bold">Anna Christina</p>
                    <p class="text-xs text-gray-500">Super Admin</p>
                </div>
            </div>
            <a href="logout.php" class="flex items-center justify-center w-full gap-2 bg-red-500/10 hover:bg-red-500/20 text-red-500 px-4 py-2 rounded-lg font-bold text-sm transition-colors">
                <span class="material-symbols-outlined text-sm">logout</span> Logout
            </a>
        </div>
    </aside>

    <!-- Main Content -->
    <main class="flex-1 overflow-y-auto p-8 relative">
        <header class="flex justify-between items-center mb-8">
            <div>
                <h1 class="text-3xl font-black text-gray-800 capitalize"><?php echo str_replace('-', ' ', $tab); ?></h1>
                <p class="text-sm text-gray-500 font-medium">Manage your platform efficiently.</p>
            </div>
            <div class="flex items-center gap-4">
                <!-- Notification Bell -->
                <?php 
                $pending_items_count = 0;
                if ($use_database && isset($pending_items_db)) $pending_items_count = count($pending_items_db);
                else foreach($items as $i) if (isset($i['status']) && $i['status'] === 'Pending Approval') $pending_items_count++;
                $total_pending = count($pending_partners) + count($pending_owners) + $pending_items_count; 
                ?>
                <a href="?tab=overview" class="relative p-2 bg-white rounded-full border border-gray-200 shadow-sm hover:bg-gray-50 transition-colors" title="Pending Approvals">
                    <span class="material-symbols-outlined text-gray-600">notifications</span>
                    <?php if ($total_pending > 0): ?>
                    <span class="absolute -top-1 -right-1 bg-red-500 text-white text-xs font-bold w-5 h-5 rounded-full flex items-center justify-center animate-pulse"><?php echo $total_pending; ?></span>
                    <?php endif; ?>
                </a>
                <div class="text-sm text-gray-500 font-bold bg-white px-4 py-2 rounded-full border border-gray-200 shadow-sm">
                    <?php echo date('l, F j, Y'); ?>
                </div>
            </div>
        </header>

        <?php switch($tab): 
            case 'users': ?>
                <!-- USERS VIEW -->
                <div class="space-y-10">
                    
                    <!-- Quick Stats Summary -->
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                        <div class="bg-white p-6 rounded-2xl border border-gray-100 shadow-sm flex items-center gap-4">
                            <div class="w-12 h-12 rounded-full bg-green-100 flex items-center justify-center text-green-600">
                                <span class="material-symbols-outlined">shopping_bag</span>
                            </div>
                            <div>
                                <p class="text-sm text-gray-500 font-bold">Total Renters</p>
                                <p class="text-3xl font-black text-gray-900"><?php echo count($renters); ?></p>
                            </div>
                        </div>
                        <div class="bg-white p-6 rounded-2xl border border-gray-100 shadow-sm flex items-center gap-4">
                            <div class="w-12 h-12 rounded-full bg-blue-100 flex items-center justify-center text-blue-600">
                                <span class="material-symbols-outlined">real_estate_agent</span>
                            </div>
                            <div>
                                <p class="text-sm text-gray-500 font-bold">Active Owners</p>
                                <p class="text-3xl font-black text-gray-900"><?php echo count($owners); ?></p>
                            </div>
                        </div>
                        <div class="bg-white p-6 rounded-2xl border border-gray-100 shadow-sm flex items-center gap-4">
                            <div class="w-12 h-12 rounded-full bg-purple-100 flex items-center justify-center text-purple-600">
                                <span class="material-symbols-outlined">local_shipping</span>
                            </div>
                            <div>
                                <p class="text-sm text-gray-500 font-bold">Delivery Partners</p>
                                <p class="text-3xl font-black text-gray-900"><?php echo count($delivery_partners); ?></p>
                            </div>
                        </div>
                    </div>

                     <!-- Pending Approvals -->
                    <?php if (!empty($pending_owners)): ?>
                    <section class="bg-orange-50 border border-orange-200 rounded-2xl p-6 relative overflow-hidden">
                        <div class="absolute top-0 right-0 p-4 opacity-10">
                            <span class="material-symbols-outlined text-9xl text-orange-500">pending_actions</span>
                        </div>
                        <h2 class="text-xl font-bold flex items-center gap-2 mb-6 text-orange-800 relative z-10">
                            <span class="material-symbols-outlined">notification_important</span> 
                            Pending Owner Requests <span class="text-xs bg-orange-600 text-white px-2 py-1 rounded-full"><?php echo count($pending_owners); ?></span>
                        </h2>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 relative z-10 w-full lg:w-3/4">
                            <?php foreach($pending_owners as $u): ?>
                            <div class="bg-white p-4 rounded-xl shadow-sm border border-orange-100 flex items-center justify-between">
                                <div>
                                    <h3 class="font-bold"><?php echo htmlspecialchars($u['name']); ?></h3>
                                    <p class="text-sm text-gray-500"><?php echo htmlspecialchars($u['email']); ?></p>
                                    <p class="text-xs text-gray-400">Phone: <?php echo htmlspecialchars($u['phone']); ?></p>
                                </div>
                                <div class="flex gap-2">
                                    <form method="POST">
                                        <input type="hidden" name="approve_user_id" value="<?php echo $u['id']; ?>">
                                        <button class="bg-green-500 text-white px-3 py-1.5 rounded-lg text-xs font-bold hover:bg-green-600">Approve</button>
                                    </form>
                                    <form method="POST" onsubmit="return confirm('Reject and delete request?');">
                                        <input type="hidden" name="reject_user_id" value="<?php echo $u['id']; ?>">
                                        <button class="bg-red-500 text-white px-3 py-1.5 rounded-lg text-xs font-bold hover:bg-red-600">Reject</button>
                                    </form>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    </section>
                    <?php endif; ?>

                    <!-- Pending Delivery Partner Approvals -->
                    <?php if (!empty($pending_partners)): ?>
                    <section class="bg-blue-50 border border-blue-200 rounded-2xl p-6 relative overflow-hidden">
                        <div class="absolute top-0 right-0 p-4 opacity-10">
                            <span class="material-symbols-outlined text-9xl text-blue-500">local_shipping</span>
                        </div>
                        <h2 class="text-xl font-bold flex items-center gap-2 mb-6 text-blue-800 relative z-10">
                            <span class="material-symbols-outlined">notification_important</span> 
                            Pending Delivery Partner Requests <span class="text-xs bg-blue-600 text-white px-2 py-1 rounded-full"><?php echo count($pending_partners); ?></span>
                        </h2>
                        <div class="grid grid-cols-1 lg:grid-cols-2 gap-4 relative z-10">
                            <?php foreach($pending_partners as $u): 
                                $app = isset($u['delivery_application']) ? $u['delivery_application'] : null;
                                // Use application data if available, otherwise fall back to account data
                                $display_name = $app ? $app['full_name'] : $u['name'];
                                $display_email = $app ? $app['email'] : $u['email'];
                                $display_phone = $app ? $app['phone'] : ($u['phone'] ?? 'N/A');
                            ?>
                            <div class="bg-white p-5 rounded-xl shadow-sm border border-blue-100">
                                <div class="flex items-start justify-between mb-4">
                                    <div class="flex items-center gap-3">
                                        <div class="w-12 h-12 rounded-full bg-blue-100 text-blue-600 flex items-center justify-center font-bold text-lg">
                                            <?php echo strtoupper(substr($display_name, 0, 1)); ?>
                                        </div>
                                        <div>
                                            <h3 class="font-bold text-gray-900"><?php echo htmlspecialchars($display_name); ?></h3>
                                            <p class="text-xs text-gray-500"><?php echo htmlspecialchars($display_email); ?></p>
                                            <p class="text-xs text-gray-400"><?php echo htmlspecialchars($display_phone); ?></p>
                                        </div>
                                    </div>
                                    <div class="flex gap-2">
                                        <form method="POST">
                                            <input type="hidden" name="approve_partner_id" value="<?php echo $u['id']; ?>">
                                            <button class="bg-green-500 text-white px-4 py-2 rounded-lg text-xs font-bold hover:bg-green-600 flex items-center gap-1">
                                                <span class="material-symbols-outlined text-sm">check</span> Approve
                                            </button>
                                        </form>
                                        <form method="POST" onsubmit="return confirm('Reject and delete this application?');">
                                            <input type="hidden" name="reject_partner_id" value="<?php echo $u['id']; ?>">
                                            <button class="bg-red-500 text-white px-4 py-2 rounded-lg text-xs font-bold hover:bg-red-600 flex items-center gap-1">
                                                <span class="material-symbols-outlined text-sm">close</span> Reject
                                            </button>
                                        </form>
                                    </div>
                                </div>
                                
                                <?php if ($app): ?>
                                <!-- Application Details -->
                                <div class="bg-gray-50 rounded-lg p-4 space-y-3">
                                    <div class="grid grid-cols-2 gap-4 text-xs">
                                        <div>
                                            <span class="text-gray-400 block mb-1">Vehicle Type</span>
                                            <span class="font-bold capitalize flex items-center gap-1">
                                                <span class="material-symbols-outlined text-sm text-blue-500">
                                                    <?php 
                                                        $icon = 'directions_car';
                                                        if ($app['vehicle_type'] === 'bicycle') $icon = 'pedal_bike';
                                                        elseif ($app['vehicle_type'] === 'scooter' || $app['vehicle_type'] === 'motorcycle') $icon = 'two_wheeler';
                                                        elseif ($app['vehicle_type'] === 'van') $icon = 'local_shipping';
                                                        echo $icon;
                                                    ?>
                                                </span>
                                                <?php echo str_replace('_', ' ', $app['vehicle_type']); ?>
                                            </span>
                                        </div>
                                        <div>
                                            <span class="text-gray-400 block mb-1">Vehicle Number</span>
                                            <span class="font-bold uppercase"><?php echo htmlspecialchars($app['vehicle_number']); ?></span>
                                        </div>
                                        <div>
                                            <span class="text-gray-400 block mb-1">License Number</span>
                                            <span class="font-bold uppercase"><?php echo htmlspecialchars($app['license_number']); ?></span>
                                        </div>
                                        <div>
                                            <span class="text-gray-400 block mb-1">License Expiry</span>
                                            <span class="font-bold <?php echo strtotime($app['license_expiry']) < strtotime('+30 days') ? 'text-red-600' : 'text-green-600'; ?>">
                                                <?php echo date('M j, Y', strtotime($app['license_expiry'])); ?>
                                            </span>
                                        </div>
                                        <?php if (!empty($app['license_photo'])): ?>
                                        <div class="col-span-2 mt-2">
                                            <span class="text-gray-400 block mb-2">Driving License Document</span>
                                            <div class="relative group/doc max-w-xs">
                                                <a href="<?php echo htmlspecialchars($app['license_photo']); ?>" target="_blank" class="block">
                                                    <?php if (strtolower(pathinfo($app['license_photo'], PATHINFO_EXTENSION)) === 'pdf'): ?>
                                                        <div class="flex items-center gap-3 p-3 bg-red-50 border border-red-100 rounded-xl text-red-600 hover:bg-red-100 transition-colors">
                                                            <span class="material-symbols-outlined text-3xl">picture_as_pdf</span>
                                                            <span class="text-xs font-bold uppercase">View License PDF</span>
                                                        </div>
                                                    <?php else: ?>
                                                        <div class="aspect-[16/10] bg-gray-100 rounded-xl overflow-hidden border border-gray-200 group-hover/doc:border-blue-300 transition-all">
                                                            <img src="<?php echo htmlspecialchars($app['license_photo']); ?>" class="w-full h-full object-cover group-hover/doc:scale-105 transition-transform duration-500" alt="Driving License">
                                                            <div class="absolute inset-0 bg-black/0 group-hover/doc:bg-black/20 flex items-center justify-center transition-colors">
                                                                <span class="material-symbols-outlined text-white opacity-0 group-hover/doc:opacity-100 transition-opacity">visibility</span>
                                                            </div>
                                                        </div>
                                                    <?php endif; ?>
                                                </a>
                                            </div>
                                        </div>
                                        <?php endif; ?>
                                    </div>
                                    </div>
                                    
                                    <div class="border-t border-gray-200 pt-3">
                                        <span class="text-gray-400 text-xs block mb-2">Service Areas</span>
                                        <div class="flex flex-wrap gap-1">
                                            <?php foreach($app['service_areas'] as $area): ?>
                                            <span class="bg-blue-100 text-blue-700 text-xs px-2 py-1 rounded"><?php echo htmlspecialchars($area); ?></span>
                                            <?php endforeach; ?>
                                        </div>
                                    </div>
                                    
                                    <div class="border-t border-gray-200 pt-3 flex justify-between items-center">
                                        <div>
                                            <span class="text-gray-400 text-xs block mb-1">Availability</span>
                                            <span class="font-bold text-xs capitalize"><?php echo str_replace('_', ' ', $app['availability_hours']); ?></span>
                                        </div>
                                        <div>
                                            <span class="text-gray-400 text-xs block mb-1">Experience</span>
                                            <span class="font-bold text-xs capitalize"><?php echo str_replace('_', ' ', $app['experience']); ?></span>
                                        </div>
                                        <div>
                                            <span class="text-gray-400 text-xs block mb-1">Applied On</span>
                                            <span class="font-bold text-xs"><?php echo date('M j, Y', strtotime($app['applied_at'])); ?></span>
                                        </div>
                                    </div>
                                    
                                    <div class="border-t border-gray-200 pt-3">
                                        <span class="text-gray-400 text-xs block mb-1">Address</span>
                                        <span class="text-xs"><?php echo htmlspecialchars($app['address'] . ', ' . $app['city'] . ' - ' . $app['pincode']); ?></span>
                                    </div>
                                </div>
                                <?php else: ?>
                                <div class="bg-gray-50 rounded-lg p-4 text-center text-gray-400 text-sm">
                                    <span class="material-symbols-outlined">info</span>
                                    <p class="mt-1">Legacy application - no detailed information available</p>
                                </div>
                                <?php endif; ?>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    </section>
                    <?php endif; ?>

                    <!-- Owners -->
                    <section>
                        <div class="flex items-center justify-between mb-4">
                            <h2 class="text-xl font-bold flex items-center gap-2">
                                <span class="material-symbols-outlined text-blue-600">real_estate_agent</span> 
                                Owners List <span class="text-xs bg-blue-100 text-blue-800 px-2 py-1 rounded-full"><?php echo count($owners); ?></span>
                            </h2>
                            <input type="text" id="owner-search" onkeyup="searchFilter('owner-search', 'owners-list', '.user-card')" placeholder="Search owners..." class="border border-gray-200 rounded-lg px-3 py-1 text-sm focus:ring-blue-500 focus:border-blue-500">
                        </div>
                         <div id="owners-list" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                            <?php foreach($owners as $u): ?>
                            <div class="user-card bg-white p-5 rounded-2xl border border-gray-200 shadow-sm flex items-center justify-between gap-4">
                                <div class="flex items-center gap-4">
                                    <div class="w-12 h-12 rounded-full bg-blue-50 text-blue-600 flex items-center justify-center font-bold text-lg shrink-0">
                                        <?php echo strtoupper(substr($u['name'], 0, 1)); ?>
                                    </div>
                                    <div class="overflow-hidden">
                                        <h3 class="font-bold text-gray-900 truncate"><?php echo htmlspecialchars($u['name']); ?></h3>
                                        <p class="text-xs text-gray-500 truncate"><?php echo htmlspecialchars($u['email']); ?></p>
                                        <p class="text-xs text-gray-400 mt-1">ID: <?php echo substr($u['id'], -6); ?></p>
                                    </div>
                                </div>
                                <form method="POST" onsubmit="return confirm('Delete user <?php echo $u['name']; ?>? This is irreversible.');">
                                    <input type="hidden" name="delete_user_id" value="<?php echo $u['id']; ?>">
                                    <button class="text-gray-400 hover:text-red-500 p-2 rounded-full hover:bg-red-50 transition-colors">
                                        <span class="material-symbols-outlined">delete</span>
                                    </button>
                                </form>
                            </div>
                            <?php endforeach; ?>
                            <?php if(empty($owners)) echo '<p class="text-gray-500 italic col-span-full">No active owners found.</p>'; ?>
                        </div>
                    </section>
                    
                    <!-- Renters -->
                    <section>
                        <div class="flex items-center justify-between mb-4">
                            <h2 class="text-xl font-bold flex items-center gap-2">
                                <span class="material-symbols-outlined text-green-600">shopping_bag</span> 
                                Renters List <span class="text-xs bg-green-100 text-green-800 px-2 py-1 rounded-full"><?php echo count($renters); ?></span>
                            </h2>
                            <input type="text" id="renter-search" onkeyup="searchFilter('renter-search', 'renters-list', '.user-card')" placeholder="Search renters..." class="border border-gray-200 rounded-lg px-3 py-1 text-sm focus:ring-green-500 focus:border-green-500">
                        </div>
                         <div id="renters-list" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                            <?php foreach($renters as $u): ?>
                            <div class="user-card bg-white p-5 rounded-2xl border border-gray-200 shadow-sm flex items-center justify-between gap-4">
                                <div class="flex items-center gap-4">
                                    <div class="w-12 h-12 rounded-full bg-green-50 text-green-600 flex items-center justify-center font-bold text-lg shrink-0">
                                        <?php echo strtoupper(substr($u['name'], 0, 1)); ?>
                                    </div>
                                    <div class="overflow-hidden">
                                        <h3 class="font-bold text-gray-900 truncate"><?php echo htmlspecialchars($u['name']); ?></h3>
                                        <p class="text-xs text-gray-500 truncate"><?php echo htmlspecialchars($u['email']); ?></p>
                                        <p class="text-xs text-gray-400 mt-1">Joined: <?php echo date('M d', strtotime($u['created_at'])); ?></p>
                                    </div>
                                </div>
                                <form method="POST" onsubmit="return confirm('Delete user <?php echo $u['name']; ?>?');">
                                    <input type="hidden" name="delete_user_id" value="<?php echo $u['id']; ?>">
                                    <button class="text-gray-400 hover:text-red-500 p-2 rounded-full hover:bg-red-50 transition-colors">
                                        <span class="material-symbols-outlined">delete</span>
                                    </button>
                                </form>
                            </div>
                            <?php endforeach; ?>
                            <?php if(empty($renters)) echo '<p class="text-gray-500 italic col-span-full">No active renters found.</p>'; ?>
                        </div>
                    </section>

                    <!-- Delivery Partners -->
                    <section>
                        <div class="flex items-center justify-between mb-4">
                            <h2 class="text-xl font-bold flex items-center gap-2">
                                <span class="material-symbols-outlined text-purple-600">local_shipping</span> 
                                Delivery Partners List <span class="text-xs bg-purple-100 text-purple-800 px-2 py-1 rounded-full"><?php echo count($delivery_partners); ?></span>
                            </h2>
                            <input type="text" id="partner-search" onkeyup="searchFilter('partner-search', 'partners-list', '.user-card')" placeholder="Search partners..." class="border border-gray-200 rounded-lg px-3 py-1 text-sm focus:ring-purple-500 focus:border-purple-500">
                        </div>
                         <div id="partners-list" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                            <?php foreach($delivery_partners as $u): ?>
                            <div class="user-card bg-white p-5 rounded-2xl border border-gray-200 shadow-sm flex items-center justify-between gap-4">
                                <div class="flex items-center gap-4">
                                    <div class="w-12 h-12 rounded-full bg-purple-50 text-purple-600 flex items-center justify-center font-bold text-lg shrink-0">
                                        <?php echo strtoupper(substr($u['name'], 0, 1)); ?>
                                    </div>
                                    <div class="overflow-hidden">
                                        <h3 class="font-bold text-gray-900 truncate"><?php echo htmlspecialchars($u['name']); ?></h3>
                                        <p class="text-xs text-gray-500 truncate"><?php echo htmlspecialchars($u['email']); ?></p>
                                        <p class="text-xs text-gray-400 mt-1">ID: <?php echo substr($u['id'], -6); ?></p>
                                    </div>
                                </div>
                                <form method="POST" onsubmit="return confirm('Delete user <?php echo $u['name']; ?>? This is irreversible.');">
                                    <input type="hidden" name="delete_user_id" value="<?php echo $u['id']; ?>">
                                    <button class="text-gray-400 hover:text-red-500 p-2 rounded-full hover:bg-red-50 transition-colors">
                                        <span class="material-symbols-outlined">delete</span>
                                    </button>
                                </form>
                            </div>
                            <?php endforeach; ?>
                            <?php if(empty($delivery_partners)) echo '<p class="text-gray-500 italic col-span-full">No active delivery partners found.</p>'; ?>
                        </div>
                    </section>
                </div>
            <?php break;

            case 'deliveries': 
                // Filter Active Rentals
                $active_rentals = array_filter($rentals, function($r) {
                    $is_returned = (isset($r['status']) && ($r['status'] === 'returned' || $r['status'] === 'cancelled'));
                    $is_expired = (strtotime($r['end_date']) < strtotime(date('Y-m-d')));
                    return !$is_returned && !$is_expired;
                });
                
                // Map delivery info
                $delivery_map = []; // rental_id -> delivery
                foreach ($deliveries as $d) {
                    $delivery_map[$d['rental_id']] = $d;
                }
                
                // Identify Unassigned
                $unassigned_rentals = [];
                foreach ($active_rentals as $r) {
                    if (!isset($delivery_map[$r['id']])) {
                        $unassigned_rentals[] = $r;
                    }
                }
            ?>
                <!-- DELIVERIES VIEW -->
                <div class="space-y-10">
                    <!-- Unassigned Section -->
                    <section>
                        <h2 class="text-xl font-bold flex items-center gap-2 mb-4">
                            <span class="material-symbols-outlined text-orange-500">pending</span> 
                            Pending Assignments <span class="text-xs bg-orange-100 text-orange-800 px-2 py-1 rounded-full"><?php echo count($unassigned_rentals); ?></span>
                        </h2>
                        
                        <?php if (empty($delivery_partners)): ?>
                            <div class="bg-red-50 text-red-600 p-4 rounded-xl text-sm mb-4 border border-red-200">
                                Result: No active delivery partners found. Please approve partners in 'Users' tab first.
                            </div>
                        <?php endif; ?>

                        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                            <?php foreach($unassigned_rentals as $r): ?>
                            <div class="bg-white p-5 rounded-2xl border border-gray-200 shadow-sm relative group">
                                <span class="absolute top-4 right-4 text-xs font-bold bg-gray-100 text-gray-500 px-2 py-1 rounded">
                                    <?php echo $r['id']; ?>
                                </span>
                                <h3 class="font-bold text-gray-900 mb-1"><?php echo htmlspecialchars($r['item']['name']); ?></h3>
                                <p class="text-xs text-gray-500 mb-3">
                                    <span class="material-symbols-outlined text-[14px] align-middle">location_on</span> 
                                    <?php echo isset($r['item']['address']) ? htmlspecialchars($r['item']['address']) : 'N/A'; ?>
                                </p>
                                
                                <div class="bg-gray-50 p-3 rounded-lg text-xs space-y-1 mb-4">
                                    <div class="flex justify-between">
                                        <span class="text-gray-400">Owner:</span>
                                        <span class="font-bold"><?php echo isset($user_names[$r['item']['address']]) ? 'Owner' : 'Owner'; ?></span>
                                    </div>
                                    <div class="flex justify-between">
                                        <span class="text-gray-400">Renter:</span>
                                        <span class="font-bold"><?php echo isset($user_names[$r['user_id']]) ? htmlspecialchars($user_names[$r['user_id']]) : 'Unknown'; ?></span>
                                    </div>
                                </div>

                                <form method="POST" class="mt-4">
                                    <input type="hidden" name="assign_delivery" value="1">
                                    <input type="hidden" name="rental_id" value="<?php echo $r['id']; ?>">
                                    <label class="block text-xs font-bold text-gray-500 mb-2">Assign Partner:</label>
                                    <div class="flex gap-2">
                                        <select name="partner_id" required class="flex-1 bg-gray-50 border-gray-200 rounded-lg text-sm py-2 px-3 focus:ring-black focus:border-black">
                                            <option value="">Select...</option>
                                            <?php foreach($delivery_partners as $dp): ?>
                                            <option value="<?php echo $dp['id']; ?>"><?php echo htmlspecialchars($dp['name']); ?> </option>
                                            <?php endforeach; ?>
                                        </select>
                                        <button type="submit" class="bg-black text-white px-3 py-2 rounded-lg text-xs font-bold hover:bg-gray-800">Assign</button>
                                    </div>
                                </form>
                            </div>
                            <?php endforeach; ?>
                            <?php if(empty($unassigned_rentals)) echo '<p class="text-gray-500 italic col-span-full">No pending deliveries.</p>'; ?>
                        </div>
                    </section>
                    
                    <!-- Assigned Section -->
                    <section>
                         <h2 class="text-xl font-bold flex items-center gap-2 mb-4">
                            <span class="material-symbols-outlined text-blue-500">local_shipping</span> 
                            Ongoing Deliveries <span class="text-xs bg-blue-100 text-blue-800 px-2 py-1 rounded-full"><?php echo count($deliveries); ?></span>
                        </h2>
                        
                        <div class="bg-white rounded-2xl shadow-sm border border-gray-200 overflow-hidden">
                            <table class="w-full text-left text-sm">
                                <thead class="bg-gray-50 text-gray-500 font-bold border-b border-gray-100">
                                    <tr>
                                        <th class="p-4">ID</th>
                                        <th class="p-4">Rental Item</th>
                                        <th class="p-4">Partner</th>
                                        <th class="p-4">Status</th>
                                        <th class="p-4">Assigned At</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-100">
                                    <?php foreach(array_reverse($deliveries) as $d): 
                                        $r = null; 
                                        foreach($rentals as $rent) { if($rent['id'] === $d['rental_id']) { $r = $rent; break; } }
                                        $p_name = 'Unknown';
                                        foreach($delivery_partners as $dp) { if($dp['id'] === $d['partner_id']) { $p_name = $dp['name']; break; } }
                                    ?>
                                    <tr class="hover:bg-gray-50">
                                        <td class="p-4 font-mono text-xs text-gray-400"><?php echo $d['id']; ?></td>
                                        <td class="p-4 font-bold"><?php echo $r ? htmlspecialchars($r['item']['name']) : 'N/A'; ?></td>
                                        <td class="p-4"><?php echo htmlspecialchars($p_name); ?></td>
                                        <td class="p-4">
                                            <span class="px-2 py-1 rounded text-xs font-bold uppercase
                                                <?php 
                                                    echo ($d['status'] == 'assigned') ? 'bg-yellow-100 text-yellow-800' : 
                                                         (($d['status'] == 'delivered') ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-800'); 
                                                ?>">
                                                <?php echo $d['status']; ?>
                                            </span>
                                        </td>
                                        <td class="p-4 text-gray-400 text-xs"><?php echo $d['assigned_at']; ?></td>
                                    </tr>
                                    <?php endforeach; ?>
                                    <?php if(empty($deliveries)): ?>
                                    <tr><td colspan="5" class="p-8 text-center text-gray-400 italic">No assigned deliveries yet.</td></tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </section>
                </div>

            <?php break; 
            
            case 'rentals': ?>
                <!-- RENTALS MANAGEMENT VIEW -->
                <div class="space-y-6">
                    <div class="bg-white rounded-2xl shadow-sm border border-gray-200 overflow-hidden">
                         <!-- Tabs/Filters Mock -->
                          <div class="p-6 border-b border-gray-100 flex gap-4">
                             <span id="tab-all" onclick="filterRentals('all')" class="font-bold text-black border-b-2 border-black pb-1 cursor-pointer">All Rentals</span>
                             <span id="tab-active" onclick="filterRentals('active')" class="font-medium text-gray-400 cursor-pointer hover:text-black">Active</span>
                             <span id="tab-past" onclick="filterRentals('past')" class="font-medium text-gray-400 cursor-pointer hover:text-black">Past</span>
                         </div>
                         
                         <div class="p-6 grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                            <?php foreach(array_reverse($rentals) as $r): 
                                $is_returned = (isset($r['status']) && ($r['status'] === 'returned' || $r['status'] === 'cancelled'));
                                $is_expired = (strtotime($r['end_date']) < strtotime(date('Y-m-d')));
                                $active = !$is_returned && !$is_expired;
                            ?>
                            <div data-status="<?php echo $active ? 'active' : 'past'; ?>" class="rental-card border border-gray-200 rounded-xl p-4 flex flex-col gap-3 relative <?php echo $active ? 'bg-green-50/30 border-green-200' : 'bg-gray-50'; ?>">
                                <div class="flex items-start gap-4">
                                     <div class="w-16 h-16 bg-gray-200 rounded-lg overflow-hidden shrink-0">
                                         <img src="<?php echo (strpos($r['item']['img'], 'uploads/') === 0) ? $r['item']['img'] : 'https://source.unsplash.com/random/100x100?' . urlencode($r['item']['name']); ?>" class="w-full h-full object-cover">
                                     </div>
                                     <div>
                                         <h4 class="font-bold text-sm line-clamp-1"><?php echo htmlspecialchars($r['item']['name']); ?></h4>
                                         <p class="text-xs text-gray-500">Renter: <?php echo isset($user_names[$r['user_id']]) ? htmlspecialchars($user_names[$r['user_id']]) : 'Unknown'; ?></p>
                                         <p class="text-xs text-gray-500">End: <?php echo $r['end_date']; ?></p>
                                     </div>
                                </div>
                                <div class="flex items-center justify-between border-t border-gray-100 pt-3 mt-auto">
                                    <span class="font-bold text-sm <?php echo $active ? 'text-green-600' : 'text-gray-500'; ?>">
                                        <?php if(isset($r['status']) && $r['status']=='cancelled') echo 'Cancelled';
                                              elseif($is_returned) echo 'Returned';
                                              elseif($is_expired) echo 'Expired';
                                              else echo 'Active'; ?>
                                    </span>
                                    <?php if($active): ?>
                                    <form method="POST" onsubmit="return confirm('Force cancel this rental?');">
                                        <input type="hidden" name="cancel_rental_id" value="<?php echo $r['id']; ?>">
                                        <button class="text-xs font-bold text-red-600 hover:text-red-700 hover:underline">Cancel</button>
                                    </form>
                                    <?php else: ?>
                                    <span class="text-xs text-gray-300">Closed</span>
                                    <?php endif; ?>
                                </div>
                            </div>
                            <?php endforeach; ?>
                         </div>
                    </div>
                </div>
            <?php break;

            case 'products': 
                $pending_items_list = [];
                if ($use_database) {
                    $pending_items_list = $pending_items_db;
                } else {
                    foreach ($items as $item) {
                        if (isset($item['status']) && ($item['status'] === 'Pending Approval' || $item['status'] === 'pending')) {
                            $pending_items_list[] = $item;
                        }
                    }
                }
                ?>
                <!-- PRODUCTS VIEW -->
                <div class="space-y-10">
                    <!-- Pending Items Section -->
                    <?php if (!empty($pending_items_list)): ?>
                    <section class="bg-yellow-50 border border-yellow-200 rounded-2xl p-6 relative overflow-hidden">
                        <div class="absolute top-0 right-0 p-4 opacity-10">
                            <span class="material-symbols-outlined text-9xl text-yellow-500">inventory_2</span>
                        </div>
                        <h2 class="text-xl font-bold flex items-center gap-2 mb-6 text-yellow-800 relative z-10">
                            <span class="material-symbols-outlined">notification_important</span> 
                            Pending Item Listings <span class="text-xs bg-yellow-600 text-white px-2 py-1 rounded-full"><?php echo count($pending_items_list); ?></span>
                        </h2>
                        
                        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6 relative z-10">
                            <?php foreach($pending_items_list as $item): 
                                $handover_arr = is_array($item['handover_methods'] ?? []) ? $item['handover_methods'] : json_decode($item['handover_methods'] ?? '[]', true);
                                $images_arr = is_array($item['images'] ?? []) ? $item['images'] : json_decode($item['images'] ?? '[]', true);
                                
                                // Prepare detailed data for modal
                                $item_json = json_encode([
                                    'id' => $item['id'],
                                    'title' => $item['title'] ?? $item['name'],
                                    'category' => $item['category'],
                                    'price' => $item['price_per_day'] ?? $item['price'],
                                    'deposit' => $item['security_deposit'] ?? 0,
                                    'description' => $item['description'] ?? 'No description provided.',
                                    'location' => $item['location'] ?? $item['address'] ?? 'Not specified',
                                    'handover' => $handover_arr,
                                    'owner' => $item['owner_name'] ?? 'User',
                                    'images' => $images_arr
                                ]);
                            ?>
                            <div class="bg-white p-5 rounded-xl shadow-sm border border-yellow-100">
                                <div onclick='openReviewModal(<?php echo htmlspecialchars($item_json, ENT_QUOTES, 'UTF-8'); ?>)' class="block group cursor-pointer">
                                    <div class="aspect-video bg-gray-100 rounded-lg overflow-hidden mb-4 relative">
                                        <?php 
                                        $img_src = 'https://source.unsplash.com/random/300x200?' . urlencode($item['category']);
                                        if (isset($item['images']) && is_array($item['images']) && !empty($item['images'])) {
                                            $img_src = 'uploads/' . $item['images'][0];
                                        } elseif (isset($item['images']) && !empty($item['images'])) {
                                            $decoded_imgs = json_decode($item['images'], true);
                                            if ($decoded_imgs && !empty($decoded_imgs)) {
                                                $img_src = 'uploads/' . $decoded_imgs[0];
                                            }
                                        }
                                        ?>
                                        <img src="<?php echo $img_src; ?>" class="w-full h-full object-cover group-hover:scale-110 transition-transform duration-500">
                                        <div class="absolute inset-0 bg-black/0 group-hover:bg-black/10 transition-colors flex items-center justify-center">
                                            <span class="material-symbols-outlined text-white opacity-0 group-hover:opacity-100 transition-opacity">description</span>
                                        </div>
                                    </div>
                                    <div class="mb-4">
                                        <div class="flex justify-between items-start mb-1">
                                            <h3 class="font-bold text-gray-900 group-hover:text-amber-600 transition-colors"><?php echo htmlspecialchars($item['title'] ?? $item['name']); ?></h3>
                                            <span class="text-[10px] bg-yellow-100 text-yellow-700 px-2 py-0.5 rounded-full font-bold uppercase">Pending</span>
                                        </div>
                                        <p class="text-xs text-gray-500 mb-1 capitalize"><?php echo htmlspecialchars($item['category']); ?> • ₹<?php echo $item['price_per_day'] ?? $item['price']; ?>/day</p>
                                        <p class="text-xs text-gray-400">Owner: <?php echo htmlspecialchars($item['owner_name'] ?? 'User'); ?></p>
                                    </div>
                                </div>
                                <div class="flex gap-2">
                                    <form method="POST" class="flex-1">
                                        <input type="hidden" name="approve_item_id" value="<?php echo $item['id']; ?>">
                                        <button class="w-full bg-green-500 text-white px-3 py-2 rounded-lg text-xs font-bold hover:bg-green-600 flex items-center justify-center gap-1">
                                            <span class="material-symbols-outlined text-sm">check</span> Approve
                                        </button>
                                    </form>
                                    <form method="POST" class="flex-1" onsubmit="return confirm('Reject this item?');">
                                        <input type="hidden" name="reject_item_id" value="<?php echo $item['id']; ?>">
                                        <button class="w-full bg-red-500 text-white px-3 py-2 rounded-lg text-xs font-bold hover:bg-red-600 flex items-center justify-center gap-1">
                                            <span class="material-symbols-outlined text-sm">close</span> Reject
                                        </button>
                                    </form>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>

                    <!-- Item Detail Review Modal -->
                    <div id="itemReviewModal" class="fixed inset-0 z-[100] hidden items-center justify-center p-4">
                        <div class="absolute inset-0 bg-black/60 backdrop-blur-sm" onclick="closeReviewModal()"></div>
                        <div class="relative bg-white w-full max-w-2xl rounded-3xl overflow-hidden shadow-2xl border border-gray-200 transform transition-all max-h-[90vh] flex flex-col">
                            <!-- Modal Header -->
                            <div class="p-6 border-b flex justify-between items-center bg-gray-50">
                                <div>
                                    <h3 class="text-xl font-bold text-gray-900" id="modal-title">Item Review</h3>
                                    <p class="text-xs text-gray-500" id="modal-owner">Owner Info</p>
                                </div>
                                <button onclick="closeReviewModal()" class="p-2 hover:bg-gray-200 rounded-full transition-colors">
                                    <span class="material-symbols-outlined">close</span>
                                </button>
                            </div>
                            
                            <!-- Modal Body -->
                            <div class="p-8 overflow-y-auto flex-1 custom-scrollbar">
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-8 mb-8">
                                    <!-- Image Preview -->
                                    <div class="space-y-3">
                                        <div class="aspect-square bg-gray-100 rounded-2xl overflow-hidden border">
                                            <img id="modal-image" src="" class="w-full h-full object-cover">
                                        </div>
                                        <div id="modal-thumbnails" class="flex gap-2 overflow-x-auto pb-2"></div>
                                    </div>
                                    
                                    <!-- Quick Stats -->
                                    <div class="space-y-5">
                                        <div>
                                            <label class="text-[10px] font-black text-gray-400 uppercase tracking-widest block mb-1">Pricing & Category</label>
                                            <div class="flex flex-wrap gap-2">
                                                <span class="bg-green-100 text-green-700 font-bold px-3 py-1 rounded-lg text-sm" id="modal-price">₹0/day</span>
                                                <span class="bg-amber-100 text-amber-700 font-bold px-3 py-1 rounded-lg text-sm" id="modal-deposit">Deposit: ₹0</span>
                                                <span class="bg-blue-100 text-blue-700 font-bold px-3 py-1 rounded-lg text-sm capitalize" id="modal-category">Category</span>
                                            </div>
                                        </div>
                                        
                                        <div>
                                            <label class="text-[10px] font-black text-gray-400 uppercase tracking-widest block mb-1">Handover Methods</label>
                                            <div id="modal-handover" class="flex flex-wrap gap-2"></div>
                                        </div>
                                        
                                        <div>
                                            <label class="text-[10px] font-black text-gray-400 uppercase tracking-widest block mb-1">Pickup Location</label>
                                            <p class="text-sm font-medium flex items-center gap-1 text-gray-700" id="modal-location">
                                                <span class="material-symbols-outlined text-sm">location_on</span> Location
                                            </p>
                                        </div>
                                    </div>
                                </div>
                                
                                <div>
                                    <label class="text-[10px] font-black text-gray-400 uppercase tracking-widest block mb-2">Item Description</label>
                                    <div class="bg-gray-50 p-4 rounded-xl border border-gray-100 text-sm text-gray-600 leading-relaxed italic" id="modal-description">
                                        Description text...
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Modal Footer -->
                            <div class="p-6 border-t bg-gray-50 flex gap-4">
                                <button onclick="submitApprovalFromModal()" class="flex-1 bg-green-600 hover:bg-green-700 text-white font-bold py-3 rounded-xl transition-all flex items-center justify-center gap-2">
                                    <span class="material-symbols-outlined text-sm">check_circle</span> Approve Listing
                                </button>
                                <button onclick="submitRejectionFromModal()" class="flex-1 bg-white border border-red-200 text-red-600 hover:bg-red-50 font-bold py-3 rounded-xl transition-all flex items-center justify-center gap-2">
                                    <span class="material-symbols-outlined text-sm">cancel</span> Reject
                                </button>
                            </div>
                        </div>
                    </div>

                    <script>
                        window.currentReviewId = null;
                        
                        function openReviewModal(data) {
                            window.currentReviewId = data.id;
                            document.getElementById('modal-title').textContent = data.title;
                            document.getElementById('modal-owner').textContent = 'Listed by ' + data.owner;
                            document.getElementById('modal-price').textContent = '₹' + data.price + '/day';
                            document.getElementById('modal-deposit').textContent = 'Deposit: ₹' + data.deposit;
                            document.getElementById('modal-category').textContent = data.category;
                            document.getElementById('modal-description').textContent = '"' + data.description + '"';
                            document.getElementById('modal-location').textContent = data.location;
                            
                            // Handover
                            const handoverContainer = document.getElementById('modal-handover');
                            handoverContainer.innerHTML = '';
                            if (data.handover && data.handover.length > 0) {
                                data.handover.forEach(m => {
                                    const span = document.createElement('span');
                                    span.className = 'bg-gray-200 text-gray-700 font-bold px-2 py-0.5 rounded text-[10px] uppercase';
                                    span.textContent = m;
                                    handoverContainer.appendChild(span);
                                });
                            } else {
                                handoverContainer.innerHTML = '<span class="text-xs text-gray-400">Not specified</span>';
                            }
                            
                            // Images
                            const mainImg = document.getElementById('modal-image');
                            const thumbContainer = document.getElementById('modal-thumbnails');
                            thumbContainer.innerHTML = '';
                            
                            if (data.images && data.images.length > 0) {
                                mainImg.src = 'uploads/' + data.images[0];
                                data.images.forEach((img, idx) => {
                                    const thumb = document.createElement('div');
                                    thumb.className = `w-12 h-12 rounded-lg border-2 cursor-pointer overflow-hidden flex-shrink-0 ${idx === 0 ? 'border-amber-500' : 'border-transparent'}`;
                                    thumb.innerHTML = `<img src="uploads/${img}" class="w-full h-full object-cover">`;
                                    thumb.onclick = () => {
                                        mainImg.src = `uploads/${img}`;
                                        Array.from(thumbContainer.children).forEach(c => c.classList.remove('border-amber-500'));
                                        thumb.classList.add('border-amber-500');
                                    };
                                    thumbContainer.appendChild(thumb);
                                });
                            } else {
                                mainImg.src = 'https://source.unsplash.com/random/300x200?' + data.category;
                            }
                            
                            document.getElementById('itemReviewModal').classList.remove('hidden');
                            document.getElementById('itemReviewModal').classList.add('flex');
                            document.body.style.overflow = 'hidden';
                        }
                        
                        function closeReviewModal() {
                            document.getElementById('itemReviewModal').classList.add('hidden');
                            document.getElementById('itemReviewModal').classList.remove('flex');
                            document.body.style.overflow = 'auto';
                        }

                        function submitApprovalFromModal() {
                            if (!window.currentReviewId) return;
                            const form = document.createElement('form');
                            form.method = 'POST';
                            form.innerHTML = `<input type="hidden" name="approve_item_id" value="${window.currentReviewId}">`;
                            document.body.appendChild(form);
                            form.submit();
                        }

                        function submitRejectionFromModal() {
                            if (!window.currentReviewId || !confirm('Reject this listing?')) return;
                            const form = document.createElement('form');
                            form.method = 'POST';
                            form.innerHTML = `<input type="hidden" name="reject_item_id" value="${window.currentReviewId}">`;
                            document.body.appendChild(form);
                            form.submit();
                        }
                    </script>

                    <div class="bg-white rounded-2xl shadow-sm border border-gray-200 p-6">
                        <div class="flex justify-between items-center mb-6">
                            <h2 class="text-xl font-bold">Product Management</h2>
                            <input type="text" id="product-search" onkeyup="searchFilter('product-search', 'products-list', '.product-card')" placeholder="Search items..." class="border border-gray-200 rounded-lg px-3 py-1 text-sm focus:ring-gray-500 focus:border-gray-500 w-64">
                        </div>
                        <div id="products-list" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6">
                            <?php foreach($items as $item): ?>
                            <div class="product-card group relative bg-gray-50 rounded-xl p-3 border border-gray-200 hover:shadow-md transition-all">
                                <div class="aspect-video bg-gray-200 rounded-lg overflow-hidden mb-3">
                                     <img src="<?php echo (strpos($item['images'][0], 'uploads/') === 0) ? $item['images'][0] : 'https://source.unsplash.com/random/300x200?' . urlencode($item['category']); ?>" class="w-full h-full object-cover">
                                </div>
                                <h3 class="font-bold text-sm truncate"><?php echo htmlspecialchars($item['title']); ?></h3>
                                <p class="text-xs text-gray-500 mb-2"><?php echo htmlspecialchars($item['category']); ?></p>
                                <div class="flex justify-between items-center">
                                    <span class="font-bold text-green-600">₹<?php echo $item['price']; ?></span>
                                    <form method="POST" onsubmit="return confirm('Delete this item permanently?');">
                                        <input type="hidden" name="delete_item_id" value="<?php echo $item['id']; ?>">
                                        <button type="submit" class="text-red-500 hover:bg-red-50 p-1.5 rounded-full transition-colors" title="Delete Item">
                                            <span class="material-symbols-outlined text-[20px]">delete</span>
                                        </button>
                                    </form>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
            <?php break; 
            
            case 'system': ?>
                <!-- SYSTEM VIEW -->
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div class="bg-white p-6 rounded-2xl shadow-sm border border-gray-200">
                        <div class="flex justify-between items-center mb-6">
                            <h2 class="text-xl font-bold">Data Storage Status</h2>
                            <form method="POST">
                                <input type="hidden" name="backup_now" value="1">
                                <button type="submit" class="bg-black text-white px-4 py-2 rounded-lg text-sm font-bold flex items-center gap-2 hover:bg-gray-800 transition-colors">
                                    <span class="material-symbols-outlined text-sm">backup</span> Backup Data
                                </button>
                            </form>
                        </div>
                         <ul class="space-y-3 text-sm">
                            <li class="flex justify-between border-b pb-2">
                                <span class="text-gray-500">users.json</span>
                                <div class="text-right">
                                    <span class="block font-bold <?php echo file_exists('users.json') ? 'text-green-600' : 'text-red-600'; ?>">
                                        <?php echo file_exists('users.json') ? round(filesize('users.json')/1024, 2) . ' KB' : 'Missing'; ?>
                                    </span>
                                    <?php if(file_exists('users.json.bak')): ?>
                                        <span class="text-xs text-gray-400">Last Backup: Verified</span>
                                    <?php endif; ?>
                                </div>
                            </li>
                            <li class="flex justify-between border-b pb-2">
                                <span class="text-gray-500">items.json</span>
                                <div class="text-right">
                                    <span class="block font-bold <?php echo file_exists('items.json') ? 'text-green-600' : 'text-red-600'; ?>">
                                        <?php echo file_exists('items.json') ? round(filesize('items.json')/1024, 2) . ' KB' : 'Missing'; ?>
                                    </span>
                                </div>
                            </li>
                            <li class="flex justify-between border-b pb-2">
                                <span class="text-gray-500">rentals.json</span>
                                <div class="text-right">
                                    <span class="block font-bold <?php echo file_exists('rentals.json') ? 'text-green-600' : 'text-red-600'; ?>">
                                        <?php echo file_exists('rentals.json') ? round(filesize('rentals.json')/1024, 2) . ' KB' : 'Missing'; ?>
                                    </span>
                                </div>
                            </li>
                        </ul>
                    </div>

                    <!-- Report Generation -->
                    <div class="bg-white p-8 rounded-2xl shadow-sm border border-gray-200">
                        <div class="flex items-center gap-3 mb-6">
                            <span class="material-symbols-outlined text-green-600 text-3xl">summarize</span>
                            <div>
                                <h3 class="font-bold text-lg text-gray-900">Financial & Activity Reports</h3>
                                <p class="text-xs text-gray-400">Download CSV reports for analysis.</p>
                            </div>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                            <!-- Owner Revenue -->
                            <a href="download_report.php?type=owners" class="group border border-gray-200 rounded-xl p-4 hover:border-blue-500 hover:bg-blue-50 transition-all flex flex-col justify-between h-32">
                                <div class="w-10 h-10 rounded-full bg-blue-100 flex items-center justify-center text-blue-600 mb-2 group-hover:bg-blue-600 group-hover:text-white transition-colors">
                                    <span class="material-symbols-outlined">payments</span>
                                </div>
                                <div>
                                    <h4 class="font-bold text-gray-800">Owner Revenue</h4>
                                    <p class="text-xs text-gray-400 mt-1">Monthly earnings per owner.</p>
                                </div>
                            </a>

                            <!-- Renter Spending -->
                            <a href="download_report.php?type=renters" class="group border border-gray-200 rounded-xl p-4 hover:border-green-500 hover:bg-green-50 transition-all flex flex-col justify-between h-32">
                                <div class="w-10 h-10 rounded-full bg-green-100 flex items-center justify-center text-green-600 mb-2 group-hover:bg-green-600 group-hover:text-white transition-colors">
                                    <span class="material-symbols-outlined">shopping_cart</span>
                                </div>
                                <div>
                                    <h4 class="font-bold text-gray-800">Renter Spending</h4>
                                    <p class="text-xs text-gray-400 mt-1">Spending analysis by renter.</p>
                                </div>
                            </a>

                            <!-- Full Rentals Log -->
                             <a href="download_report.php?type=rentals" class="group border border-gray-200 rounded-xl p-4 hover:border-purple-500 hover:bg-purple-50 transition-all flex flex-col justify-between h-32">
                                <div class="w-10 h-10 rounded-full bg-purple-100 flex items-center justify-center text-purple-600 mb-2 group-hover:bg-purple-600 group-hover:text-white transition-colors">
                                    <span class="material-symbols-outlined">receipt_long</span>
                                </div>
                                <div>
                                    <h4 class="font-bold text-gray-800">Full Rental Logs</h4>
                                    <p class="text-xs text-gray-400 mt-1">Detailed list of all transactions.</p>
                                </div>
                            </a>
                        </div>
                    </div>
            <?php break; 
            
            default: ?>
                <!-- OVERVIEW VIEW (Default) -->
                
                <!-- Pending Approvals Alert Banner -->
                <?php if (count($pending_partners) > 0 || count($pending_owners) > 0): ?>
                <div class="mb-8 bg-gradient-to-r from-amber-50 to-orange-50 border border-amber-200 rounded-2xl p-6 relative overflow-hidden">
                    <div class="absolute top-0 right-0 w-40 h-40 bg-amber-100/50 rounded-full -translate-y-1/2 translate-x-1/2"></div>
                    <div class="relative z-10 flex flex-col md:flex-row items-start md:items-center justify-between gap-4">
                        <div class="flex items-start gap-4">
                            <div class="p-3 bg-amber-500 text-white rounded-xl animate-pulse">
                                <span class="material-symbols-outlined text-2xl">notification_important</span>
                            </div>
                            <div>
                                <h3 class="text-lg font-bold text-amber-900">Action Required: Pending Approvals</h3>
                                <p class="text-sm text-amber-700 mt-1">
                                    You have 
                                    <?php 
                                        $parts = [];
                                        if (count($pending_partners) > 0) {
                                            $parts[] = '<span class="font-bold">' . count($pending_partners) . ' delivery partner' . (count($pending_partners) > 1 ? 's' : '') . '</span>';
                                        }
                                        if (count($pending_owners) > 0) {
                                            $parts[] = '<span class="font-bold">' . count($pending_owners) . ' owner' . (count($pending_owners) > 1 ? 's' : '') . '</span>';
                                        }
                                        if ($pending_items_count > 0) {
                                            $parts[] = '<span class="font-bold">' . $pending_items_count . ' item listing' . ($pending_items_count > 1 ? 's' : '') . '</span>';
                                        }
                                        echo implode(', ', array_slice($parts, 0, -1)) . (count($parts) > 1 ? ' and ' : '') . end($parts);
                                    ?> 
                                    waiting for approval.
                                </p>
                            </div>
                        </div>
                        <div class="flex gap-3">
                            <a href="?tab=users" class="bg-amber-500 hover:bg-amber-600 text-white font-bold px-6 py-2.5 rounded-xl transition-colors flex items-center gap-2 shadow-sm">
                                <span class="material-symbols-outlined text-sm">visibility</span>
                                Review Now
                            </a>
                        </div>
                    </div>
                    
                    <!-- Quick Preview of Pending -->
                    <div class="mt-4 pt-4 border-t border-amber-200 flex flex-wrap gap-3">
                        <?php 
                        $preview_count = 0;
                        foreach ($pending_partners as $pp): 
                            if ($preview_count >= 4) break;
                            $preview_count++;
                        ?>
                        <div class="flex items-center gap-2 bg-white/70 rounded-full px-3 py-1.5 border border-amber-100">
                            <div class="w-6 h-6 rounded-full bg-blue-100 text-blue-600 flex items-center justify-center text-xs font-bold">
                                <?php echo strtoupper(substr($pp['name'], 0, 1)); ?>
                            </div>
                            <span class="text-xs font-medium text-gray-700"><?php echo htmlspecialchars($pp['name']); ?></span>
                            <span class="text-xs bg-blue-100 text-blue-700 px-1.5 py-0.5 rounded">Driver</span>
                        </div>
                        <?php endforeach; ?>
                        <?php foreach ($pending_owners as $po): 
                            if ($preview_count >= 4) break;
                            $preview_count++;
                        ?>
                        <div class="flex items-center gap-2 bg-white/70 rounded-full px-3 py-1.5 border border-amber-100">
                            <div class="w-6 h-6 rounded-full bg-orange-100 text-orange-600 flex items-center justify-center text-xs font-bold">
                                <?php echo strtoupper(substr($po['name'], 0, 1)); ?>
                            </div>
                            <span class="text-xs font-medium text-gray-700"><?php echo htmlspecialchars($po['name']); ?></span>
                            <span class="text-xs bg-orange-100 text-orange-700 px-1.5 py-0.5 rounded">Owner</span>
                        </div>
                        <?php endforeach; ?>
                        <?php if (count($pending_partners) + count($pending_owners) > 4): ?>
                        <div class="flex items-center gap-2 bg-gray-100 rounded-full px-3 py-1.5">
                            <span class="text-xs font-medium text-gray-500">+<?php echo (count($pending_partners) + count($pending_owners) - 4); ?> more</span>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
                <?php endif; ?>
                
                <!-- Stats Grid -->
                <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-10">
                    <!-- Stat Card 1 -->
                    <div class="bg-white p-6 rounded-2xl shadow-sm border border-gray-200">
                        <div class="flex items-start justify-between mb-4">
                            <div class="p-3 bg-blue-50 text-blue-600 rounded-xl">
                                <span class="material-symbols-outlined block text-2xl">group</span>
                            </div>
                            <span class="text-green-600 bg-green-50 text-xs font-bold px-2 py-1 rounded">+5%</span>
                        </div>
                        <h3 class="text-gray-500 text-sm font-bold uppercase tracking-wider">Total Users</h3>
                        <p class="text-3xl font-black text-gray-800 mt-1"><?php echo $total_users; ?></p>
                    </div>

                    <!-- Stat Card 2 -->
                    <div class="bg-white p-6 rounded-2xl shadow-sm border border-gray-200">
                        <div class="flex items-start justify-between mb-4">
                            <div class="p-3 bg-purple-50 text-purple-600 rounded-xl">
                                <span class="material-symbols-outlined block text-2xl">inventory_2</span>
                            </div>
                            <span class="text-green-600 bg-green-50 text-xs font-bold px-2 py-1 rounded">+12 new</span>
                        </div>
                        <h3 class="text-gray-500 text-sm font-bold uppercase tracking-wider">Active Listings</h3>
                        <p class="text-3xl font-black text-gray-800 mt-1"><?php echo $total_items; ?></p>
                    </div>

                    <!-- Stat Card 3 -->
                    <div class="bg-white p-6 rounded-2xl shadow-sm border border-gray-200">
                         <div class="flex items-start justify-between mb-4">
                            <div class="p-3 bg-orange-50 text-orange-600 rounded-xl">
                                <span class="material-symbols-outlined block text-2xl">receipt_long</span>
                            </div>
                         </div>
                        <h3 class="text-gray-500 text-sm font-bold uppercase tracking-wider">Active Rentals</h3>
                        <p class="text-3xl font-black text-gray-800 mt-1"><?php echo $active_rentals_count; ?></p>
                    </div>

                    <!-- Stat Card 4 -->
                    <div class="bg-white p-6 rounded-2xl shadow-sm border border-gray-200">
                         <div class="flex items-start justify-between mb-4">
                            <div class="p-3 bg-green-50 text-green-600 rounded-xl">
                                <span class="material-symbols-outlined block text-2xl">payments</span>
                            </div>
                         </div>
                        <h3 class="text-gray-500 text-sm font-bold uppercase tracking-wider">Total Revenue</h3>
                        <p class="text-3xl font-black text-gray-800 mt-1">₹<?php echo number_format($total_revenue); ?></p>
                    </div>
                </div>
            <?php break; ?> 
        <?php endswitch; ?>

    </main>

    </main>
