<?php
ob_start();
session_start();
require_once 'config/database.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$msg = $_GET['msg'] ?? '';

// Handle Actions (Cancel Request, etc.)
if (isset($_POST['cancel_request'])) {
    try {
        $pdo = getDBConnection();
        $stmt = $pdo->prepare("UPDATE item_requests SET status = 'cancelled' WHERE id = ? AND renter_id = ?");
        $stmt->execute([$_POST['request_id'], $user_id]);
        header("Location: my_requests.php?msg=cancelled");
        exit();
    } catch (Exception $e) {
        $error = "Error cancelling request.";
    }
}

// Fetch My Requests
try {
    $pdo = getDBConnection();
    // Get requests
    $stmt = $pdo->prepare("SELECT * FROM item_requests WHERE renter_id = ? ORDER BY created_at DESC");
    $stmt->execute([$user_id]);
    $requests = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Offers fetching removed as per user request
    $offers_by_request = []; // Kept empty array to avoid undefined variable errors in view if any remain
    
} catch (PDOException $e) {
    die("Error: " . $e->getMessage());
}
    

?>
<!DOCTYPE html>
<html class="light" lang="en">
<head>
    <meta charset="utf-8"/>
    <meta content="width=device-width, initial-scale=1.0" name="viewport"/>
    <title>RendeX - My Requests</title>
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com" rel="preconnect"/>
    <link crossorigin="" href="https://fonts.gstatic.com" rel="preconnect"/>
    <link href="https://fonts.googleapis.com/css2?family=Spline+Sans:wght@300;400;500;600;700&display=swap" rel="stylesheet"/>
    <link href="https://fonts.googleapis.com/css2?family=Noto+Sans:wght@400;500;700&display=swap" rel="stylesheet"/>
    <!-- Material Symbols -->
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&display=swap" rel="stylesheet"/>
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
    <style> body { font-family: "Spline Sans", sans-serif; } </style>
</head>
<body class="bg-background-light dark:bg-background-dark text-text-main dark:text-white transition-colors duration-200">
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
            <div class="hidden lg:flex items-center gap-6 ml-auto">
                <a href="dashboard.php" class="bg-white border border-[#e9e8ce] hover:bg-gray-50 text-black text-sm font-bold px-6 py-2.5 rounded-full transition-all flex items-center gap-2">
                    <span class="material-symbols-outlined text-[18px]">arrow_back</span>
                    Back to Dashboard
                </a>
            </div>
        </div>
    </header>

    <main class="w-full max-w-[1400px] mx-auto px-6 py-10">
        <div class="flex items-center justify-between mb-8">
            <h1 class="text-3xl font-black text-gray-900 dark:text-white">My Requests</h1>
            <a href="request-item.php" class="bg-primary px-6 py-3 rounded-xl font-bold text-black hover:bg-yellow-400 transition-colors flex items-center gap-2">
                <span class="material-symbols-outlined">add</span>
                New Request
            </a>
        </div>

        <?php if ($msg === 'cancelled'): ?>
            <div class="bg-green-100 border-l-4 border-green-500 text-green-700 p-4 mb-6">Request cancelled successfully.</div>
        <?php endif; ?>

        <?php if (empty($requests)): ?>
            <div class="text-center py-20 bg-white dark:bg-[#1e1e1e] rounded-3xl">
                <span class="material-symbols-outlined text-gray-300 text-6xl mb-4">folder_open</span>
                <h3 class="text-xl font-bold text-gray-900 dark:text-white">No requests yet</h3>
                <p class="text-gray-500 mt-2">Create a request to let owners know what you need.</p>
            </div>
        <?php else: ?>
            <div class="space-y-8">
                <?php foreach ($requests as $req): 
                    $req_offers = $offers_by_request[$req['id']] ?? [];
                    $status_colors = [
                        'active' => 'bg-green-100 text-green-800',
                        'fulfilled' => 'bg-blue-100 text-blue-800',
                        'cancelled' => 'bg-red-100 text-red-800',
                        'expired' => 'bg-gray-100 text-gray-800'
                    ];
                    $status_class = $status_colors[$req['status']] ?? 'bg-gray-100 text-gray-800';
                ?>
                    <div class="bg-white dark:bg-[#1e1e1e] rounded-[2rem] p-8 border border-gray-100 dark:border-[#333] shadow-lg shadow-gray-200/50 dark:shadow-none hover:shadow-xl transition-shadow duration-300">
                        <div class="flex flex-col md:flex-row justify-between gap-6 mb-8">
                            <div class="flex-1">
                                <div class="flex items-center gap-4 mb-4">
                                    <span class="px-4 py-1.5 rounded-full text-xs font-black tracking-wide uppercase <?php echo $status_class; ?>">
                                        <?php echo ucfirst($req['status']); ?>
                                    </span>
                                    <span class="text-xs text-gray-400 font-medium tracking-wide flex items-center gap-1">
                                        <span class="material-symbols-outlined text-[16px]">calendar_today</span>
                                        <?php echo date('M d, Y', strtotime($req['created_at'])); ?>
                                    </span>
                                </div>
                                
                                <h3 class="text-3xl font-black text-gray-900 dark:text-white tracking-tight mb-3"><?php echo htmlspecialchars($req['item_name']); ?></h3>
                                <p class="text-gray-600 dark:text-gray-300 text-base leading-relaxed max-w-3xl"><?php echo htmlspecialchars($req['description']); ?></p>
                                
                                <div class="flex flex-wrap gap-4 mt-6">
                                    <?php if ($req['min_price'] || $req['max_price']): ?>
                                        <div class="bg-gray-50 dark:bg-black/40 px-4 py-2 rounded-xl flex items-center gap-2 border border-gray-100 dark:border-gray-800">
                                            <span class="material-symbols-outlined text-green-600 dark:text-green-400">payments</span>
                                            <div>
                                                <p class="text-[10px] font-bold text-gray-400 uppercase leading-none">Budget</p>
                                                <p class="text-sm font-bold text-gray-900 dark:text-white leading-none mt-1">
                                                    <?php if ($req['min_price'] && $req['max_price']): ?>
                                                        ₹<?php echo $req['min_price']; ?> - ₹<?php echo $req['max_price']; ?>
                                                    <?php elseif ($req['min_price']): ?>
                                                        Min ₹<?php echo $req['min_price']; ?>
                                                    <?php elseif ($req['max_price']): ?>
                                                        Max ₹<?php echo $req['max_price']; ?>
                                                    <?php endif; ?>
                                                </p>
                                            </div>
                                        </div>
                                    <?php endif; ?>
                                    
                                    <?php if ($req['needed_by']): ?>
                                        <div class="bg-gray-50 dark:bg-black/40 px-4 py-2 rounded-xl flex items-center gap-2 border border-gray-100 dark:border-gray-800">
                                            <span class="material-symbols-outlined text-blue-500">event_upcoming</span>
                                            <div>
                                                <p class="text-[10px] font-bold text-gray-400 uppercase leading-none">Needed By</p>
                                                <p class="text-sm font-bold text-gray-900 dark:text-white leading-none mt-1"><?php echo date('M d, Y', strtotime($req['needed_by'])); ?></p>
                                            </div>
                                        </div>
                                    <?php endif; ?>

                                    <?php if (!empty($req['location'])): ?>
                                        <div class="bg-gray-50 dark:bg-black/40 px-4 py-2 rounded-xl flex items-center gap-2 border border-gray-100 dark:border-gray-800">
                                            <span class="material-symbols-outlined text-red-500">location_on</span>
                                            <div>
                                                <p class="text-[10px] font-bold text-gray-400 uppercase leading-none">Location</p>
                                                <p class="text-sm font-bold text-gray-900 dark:text-white leading-none mt-1"><?php echo htmlspecialchars($req['location']); ?></p>
                                            </div>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                            
                            <?php if ($req['status'] === 'active'): ?>
                            <div class="flex flex-col items-end gap-2">
                                <form method="POST" onsubmit="return confirm('Are you sure you want to cancel this request?');">
                                    <input type="hidden" name="request_id" value="<?php echo $req['id']; ?>">
                                    <button type="submit" name="cancel_request" class="px-5 py-2.5 bg-white dark:bg-transparent border border-gray-200 dark:border-gray-700 hover:border-red-200 hover:bg-red-50 hover:text-red-700 dark:hover:bg-red-900/20 dark:hover:border-red-800 rounded-xl text-xs font-bold text-gray-500 transition-all flex items-center gap-2">
                                        <span class="material-symbols-outlined text-[18px]">close</span>
                                        Cancel Request
                                    </button>
                                </form>
                            </div>
                            <?php endif; ?>
                        </div>


                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </main>
</body>
</html>
