<?php
ob_start();
session_start();
require_once 'config/database.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$msg = '';
$error = '';

// Handle Request Acceptance
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['accept_request'])) {
    $request_id = $_POST['request_id'];
    
    try {
        $pdo = getDBConnection();
        // Update request status to fulfilled
        $stmt = $pdo->prepare("UPDATE item_requests SET status = 'fulfilled' WHERE id = ?");
        $stmt->execute([$request_id]);
        
        // Notify Renter
        // First get renter ID
        $rStmt = $pdo->prepare("SELECT renter_id, item_name FROM item_requests WHERE id = ?");
        $rStmt->execute([$request_id]);
        $reqData = $rStmt->fetch(PDO::FETCH_ASSOC);
        
        if ($reqData) {
            $notifTitle = "Request Accepted!";
            $notifMsg = "Your request for '" . $reqData['item_name'] . "' has been accepted by an owner.";
            // Link to chat with the owner who accepted it
            $notifLink = "chat.php?recipient_id=" . $user_id; 
            
            $nStmt = $pdo->prepare("INSERT INTO notifications (user_id, title, message, type, link, created_at) VALUES (?, ?, ?, 'info', ?, NOW())");
            $nStmt->execute([$reqData['renter_id'], $notifTitle, $notifMsg, $notifLink]);
        }
        
        // Redirect to Lend Item page so owner can list the item immediately
        header("Location: lend-item.php?request_id=" . $request_id . "&alert=accepted");
        exit();
        
    } catch (PDOException $e) {
        $error = "Database Error: " . $e->getMessage();
    }
}

// Fetch Active Requests
try {
    $pdo = getDBConnection();
    $stmt = $pdo->prepare("
        SELECT r.*, u.name as renter_name, u.profile_picture 
        FROM item_requests r 
        JOIN users u ON r.renter_id = u.id 
        WHERE r.status = 'active' 
        AND r.renter_id != ? 
        ORDER BY r.created_at DESC
    ");
    $stmt->execute([$user_id]);
    $requests = $stmt->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    die("Error fetching data: " . $e->getMessage());
}


?>
<!DOCTYPE html>
<html class="light" lang="en">
<head>
    <meta charset="utf-8"/>
    <meta content="width=device-width, initial-scale=1.0" name="viewport"/>
    <title>RendeX - Browse Requests</title>
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
    <style> 
        body { font-family: "Spline Sans", sans-serif; } 
        .modal { transition: opacity 0.25s ease; }
    </style>
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
            <div>
                <h1 class="text-3xl font-black text-gray-900 dark:text-white">Community Requests</h1>
                <p class="text-gray-500 dark:text-gray-400 mt-1">See what others are looking for and offer your items.</p>
            </div>
            <a href="request-item.php" class="bg-primary px-6 py-3 rounded-xl font-bold text-black hover:bg-yellow-400 transition-colors flex items-center gap-2">
                <span class="material-symbols-outlined">add</span>
                Post a Request
            </a>
        </div>

        <?php if ($msg): ?>
            <div class="bg-green-100 border-l-4 border-green-500 text-green-700 p-4 mb-6" role="alert">
                <p class="font-bold">Success</p>
                <p><?php echo htmlspecialchars($msg); ?></p>
            </div>
        <?php endif; ?>
        <?php if ($error): ?>
            <div class="bg-red-100 border-l-4 border-red-500 text-red-700 p-4 mb-6" role="alert">
                <p class="font-bold">Error</p>
                <p><?php echo htmlspecialchars($error); ?></p>
            </div>
        <?php endif; ?>

        <?php if (empty($requests)): ?>
            <div class="text-center py-20 bg-white dark:bg-[#1e1e1e] rounded-3xl">
                <span class="material-symbols-outlined text-6xl text-gray-300 mb-4">search_off</span>
                <h3 class="text-xl font-bold text-gray-900 dark:text-white">No active requests found</h3>
                <p class="text-gray-500 mt-2">Everyone seems to have what they need right now!</p>
            </div>
        <?php else: ?>
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                <?php foreach ($requests as $req): ?>
                    <div class="bg-white dark:bg-[#1e1e1e] p-7 rounded-[2rem] shadow-lg shadow-gray-200/50 dark:shadow-none border border-gray-100 dark:border-gray-800 flex flex-col h-full hover:scale-[1.01] transition-transform duration-300">
                        <div class="flex items-center gap-4 mb-5">
                            <?php if (!empty($req['profile_picture'])): ?>
                                <img src="<?php echo htmlspecialchars($req['profile_picture']); ?>" class="w-12 h-12 rounded-full object-cover border-2 border-white dark:border-gray-700 shadow-sm">
                            <?php else: ?>
                                <div class="w-12 h-12 rounded-full bg-primary/20 flex items-center justify-center text-yellow-700 font-black text-lg">
                                    <?php echo strtoupper(substr($req['renter_name'], 0, 1)); ?>
                                </div>
                            <?php endif; ?>
                            <div class="flex-1 min-w-0">
                                <h4 class="font-bold text-gray-900 dark:text-white text-base truncate"><?php echo htmlspecialchars($req['renter_name']); ?></h4>
                                <div class="flex items-center gap-1 text-xs text-gray-500 truncate">
                                    <span class="material-symbols-outlined text-[14px]">category</span>
                                    <span><?php echo htmlspecialchars($req['category']); ?></span>
                                    <?php if (!empty($req['location'])): ?>
                                        <span class="mx-1">•</span>
                                        <span class="material-symbols-outlined text-[14px] text-red-500">location_on</span>
                                        <span class="font-bold text-gray-700 dark:text-gray-300"><?php echo htmlspecialchars($req['location']); ?></span>
                                    <?php endif; ?>
                                </div>
                            </div>
                            <div class="flex flex-col items-end">
                                <span class="bg-gray-100 dark:bg-gray-800 text-[10px] uppercase font-bold px-2 py-1 rounded-md text-gray-500 mb-1">Posted</span>
                                <span class="text-xs font-mono text-gray-400 font-bold"><?php echo date('M d', strtotime($req['created_at'])); ?></span>
                            </div>
                        </div>

                        <h3 class="text-2xl font-black text-gray-900 dark:text-white mb-3 leading-tight"><?php echo htmlspecialchars($req['item_name']); ?></h3>
                        <p class="text-gray-600 dark:text-gray-300 text-sm mb-6 line-clamp-3 leading-relaxed flex-grow">
                            <?php echo htmlspecialchars($req['description']); ?>
                        </p>

                        <div class="space-y-3 mt-auto mb-6">
                            <?php if ($req['min_price'] || $req['max_price']): ?>
                                <div class="bg-gray-50 dark:bg-black/40 p-3 rounded-xl flex items-center gap-3">
                                    <div class="w-8 h-8 rounded-full bg-green-100 dark:bg-green-900/30 flex items-center justify-center text-green-600 dark:text-green-400">
                                        <span class="material-symbols-outlined text-lg">payments</span>
                                    </div>
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
                                <div class="bg-gray-50 dark:bg-black/40 p-3 rounded-xl flex items-center gap-3">
                                    <div class="w-8 h-8 rounded-full bg-blue-100 dark:bg-blue-900/30 flex items-center justify-center text-blue-600 dark:text-blue-400">
                                        <span class="material-symbols-outlined text-lg">event_upcoming</span>
                                    </div>
                                    <div>
                                        <p class="text-[10px] font-bold text-gray-400 uppercase leading-none">Needed By</p>
                                        <p class="text-sm font-bold text-gray-900 dark:text-white leading-none mt-1"><?php echo date('M d, Y', strtotime($req['needed_by'])); ?></p>
                                    </div>
                                </div>
                            <?php endif; ?>
                        </div>

                        <div class="pt-0 mt-0 flex gap-3">
                            <form method="POST" class="flex-1">
                                <input type="hidden" name="request_id" value="<?php echo $req['id']; ?>">
                                <button type="submit" name="accept_request" class="w-full bg-primary hover:bg-[#e6e200] text-black py-4 rounded-xl font-black text-sm uppercase tracking-wide shadow-xl shadow-yellow-200/50 dark:shadow-none hover:translate-y-[-2px] active:translate-y-[0px] transition-all flex items-center justify-center gap-2">
                                    <span>Accept Request</span>
                                    <span class="material-symbols-outlined">check_circle</span>
                                </button>
                            </form>
                            <a href="lend-item.php?request_id=<?php echo $req['id']; ?>" class="bg-gray-100 dark:bg-gray-800 hover:bg-gray-200 dark:hover:bg-gray-700 text-black dark:text-white px-4 rounded-xl font-bold flex items-center justify-center transition-colors border border-transparent hover:border-gray-300 dark:hover:border-gray-600" title="List item for rent">
                                <span class="material-symbols-outlined">add_box</span>
                            </a>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </main>
</body>
</html>
