<?php
ob_start();
session_start();
require_once 'config/database.php';

// Check if user is logged in (requests are usually for members)
if (!isset($_SESSION['user_id'])) {
    $_SESSION['redirect_after_login'] = $_SERVER['REQUEST_URI'];
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$msg = '';
$error = '';

// Handle Request Acceptance (Same logic as browse_requests)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['accept_request'])) {
    $request_id = $_POST['request_id'];
    
    try {
        $pdo = getDBConnection();
        // Update request status to fulfilled
        $stmt = $pdo->prepare("UPDATE item_requests SET status = 'fulfilled' WHERE id = ?");
        $stmt->execute([$request_id]);
        
        // Notify Renter
        $rStmt = $pdo->prepare("SELECT renter_id, item_name FROM item_requests WHERE id = ?");
        $rStmt->execute([$request_id]);
        $reqData = $rStmt->fetch(PDO::FETCH_ASSOC);
        
        if ($reqData) {
            $notifTitle = "Request Accepted!";
            $notifMsg = "Your request for '" . $reqData['item_name'] . "' has been accepted by an owner.";
            $notifLink = "chat.php?recipient_id=" . $user_id; 
            
            $nStmt = $pdo->prepare("INSERT INTO notifications (user_id, title, message, type, link, created_at) VALUES (?, ?, ?, 'info', ?, NOW())");
            $nStmt->execute([$reqData['renter_id'], $notifTitle, $notifMsg, $notifLink]);
        }
        
        header("Location: lend-item.php?request_id=" . $request_id . "&alert=accepted");
        exit();
        
    } catch (PDOException $e) {
        $error = "Database Error: " . $e->getMessage();
    }
}

// Fetch ALL Requests (Active) - "All" here typically means global community requests
try {
    $pdo = getDBConnection();
    $stmt = $pdo->prepare("
        SELECT r.*, u.name as renter_name, u.profile_picture 
        FROM item_requests r 
        JOIN users u ON r.renter_id = u.id 
        WHERE r.status = 'active' 
        ORDER BY r.created_at DESC
    ");
    $stmt->execute();
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
    <title>RendeX - All Rental Requests</title>
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
    </style>
</head>
<body class="bg-background-light dark:bg-background-dark text-text-main dark:text-white transition-colors duration-200">
    <!-- Navbar -->
    <header class="sticky top-0 z-50 flex items-center justify-between border-b border-[#e9e8ce] dark:border-[#3e3d2a] bg-background-light/95 dark:bg-background-dark/95 backdrop-blur-sm px-6 py-4 lg:px-10">
        <div class="flex items-center gap-8 w-full max-w-[1400px] mx-auto">
            <a href="index.php" class="flex items-center gap-2 text-text-main dark:text-white">
                <div class="size-8 text-primary">
                    <svg class="w-full h-full" fill="none" viewbox="0 0 48 48" xmlns="http://www.w3.org/2000/svg">
                        <path d="M36.7273 44C33.9891 44 31.6043 39.8386 30.3636 33.69C29.123 39.8386 26.7382 44 24 44C21.2618 44 18.877 39.8386 17.6364 33.69C16.3957 39.8386 14.0109 44 11.2727 44C7.25611 44 4 35.0457 4 24C4 12.9543 7.25611 4 11.2727 4C14.0109 4 16.3957 8.16144 17.6364 14.31C18.877 8.16144 21.2618 4 24 4C26.7382 4 29.123 8.16144 30.3636 14.31C31.6043 8.16144 33.9891 4 36.7273 4C40.7439 4 44 12.9543 44 24C44 35.0457 40.7439 44 36.7273 44Z" fill="currentColor"></path>
                    </svg>
                </div>
                <h2 class="text-xl font-bold tracking-tight">RendeX</h2>
            </a>
            <div class="hidden lg:flex items-center gap-6 ml-auto">
                <a href="index.php" class="bg-white border border-[#e9e8ce] hover:bg-gray-50 text-black text-sm font-bold px-6 py-2.5 rounded-full transition-all flex items-center gap-2">
                    <span class="material-symbols-outlined text-[18px]">home</span>
                    Home
                </a>
            </div>
        </div>
    </header>

    <main class="w-full max-w-[1400px] mx-auto px-6 py-10">
        <div class="flex flex-col md:flex-row items-center justify-between mb-12 gap-6 bg-white dark:bg-surface-dark p-8 rounded-[2rem] border border-[#e9e8ce] dark:border-[#3e3d2a] shadow-sm">
            <div>
                <h1 class="text-4xl font-black text-gray-900 dark:text-white leading-tight">All Rental Requests</h1>
                <p class="text-gray-500 dark:text-gray-400 mt-2 text-lg">Explore what the community needs and help them experience more.</p>
            </div>
            <a href="request-item.php" class="bg-primary px-8 py-4 rounded-2xl font-black text-black hover:bg-yellow-400 transition-all shadow-lg shadow-yellow-200/50 dark:shadow-none flex items-center gap-2 hover:scale-105 active:scale-95">
                <span class="material-symbols-outlined">add_circle</span>
                Post Your Request
            </a>
        </div>

        <?php if ($msg): ?>
            <div class="bg-green-50 border border-green-200 text-green-700 px-6 py-4 rounded-2xl mb-8 flex items-center gap-3 animate-in fade-in slide-in-from-top-4">
                <span class="material-symbols-outlined text-green-500">check_circle</span>
                <p class="font-bold"><?php echo htmlspecialchars($msg); ?></p>
            </div>
        <?php endif; ?>
        <?php if ($error): ?>
            <div class="bg-red-50 border border-red-200 text-red-700 px-6 py-4 rounded-2xl mb-8 flex items-center gap-3 animate-in fade-in slide-in-from-top-4">
                <span class="material-symbols-outlined text-red-500">error</span>
                <p class="font-bold"><?php echo htmlspecialchars($error); ?></p>
            </div>
        <?php endif; ?>

        <?php if (empty($requests)): ?>
            <div class="text-center py-24 bg-white dark:bg-surface-dark rounded-[3rem] border border-[#e9e8ce] dark:border-[#3e3d2a] shadow-inner">
                <div class="w-24 h-24 bg-gray-50 dark:bg-black/20 rounded-full flex items-center justify-center mx-auto mb-6">
                    <span class="material-symbols-outlined text-5xl text-gray-300">sentiment_dissatisfied</span>
                </div>
                <h3 class="text-2xl font-black text-gray-900 dark:text-white">No requests found</h3>
                <p class="text-gray-500 mt-2 max-w-sm mx-auto">It looks like all needs are currently met! Check back later or post your own request.</p>
                <a href="request-item.php" class="inline-block mt-8 text-primary font-bold hover:underline">Want to ask for something?</a>
            </div>
        <?php else: ?>
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">
                <?php foreach ($requests as $req): ?>
                    <div class="group bg-white dark:bg-surface-dark p-8 rounded-[2.5rem] shadow-sm hover:shadow-2xl hover:shadow-primary/10 transition-all duration-500 border border-[#e9e8ce] dark:border-[#3e3d2a] flex flex-col h-full relative overflow-hidden">
                        <!-- Decorative element -->
                        <div class="absolute top-0 right-0 w-32 h-32 bg-primary/5 rounded-bl-full -mr-10 -mt-10 group-hover:scale-150 transition-transform duration-700"></div>

                        <div class="flex items-center gap-4 mb-8">
                            <?php if (!empty($req['profile_picture'])): ?>
                                <img src="<?php echo htmlspecialchars($req['profile_picture']); ?>" class="w-14 h-14 rounded-full object-cover border-4 border-background-light dark:border-background-dark shadow-sm">
                            <?php else: ?>
                                <div class="w-14 h-14 rounded-2xl bg-primary/20 flex items-center justify-center text-yellow-700 font-black text-xl shadow-inner">
                                    <?php echo strtoupper(substr($req['renter_name'], 0, 1)); ?>
                                </div>
                            <?php endif; ?>
                            <div class="flex-1 min-w-0">
                                <h4 class="font-bold text-gray-900 dark:text-white text-lg truncate"><?php echo htmlspecialchars($req['renter_name']); ?></h4>
                                <div class="flex items-center gap-1.5 text-xs text-text-muted font-bold tracking-wide">
                                    <span class="bg-gray-100 dark:bg-gray-800 px-2 py-0.5 rounded uppercase"><?php echo htmlspecialchars($req['category']); ?></span>
                                </div>
                            </div>
                        </div>

                        <div class="mb-4">
                            <h3 class="text-2xl font-black text-gray-900 dark:text-white mb-2 leading-tight group-hover:text-primary transition-colors"><?php echo htmlspecialchars($req['item_name']); ?></h3>
                            <?php if (!empty($req['location'])): ?>
                                <div class="flex items-center gap-1 text-sm text-red-500 font-bold">
                                    <span class="material-symbols-outlined text-lg">location_on</span>
                                    <span><?php echo htmlspecialchars($req['location']); ?></span>
                                </div>
                            <?php endif; ?>
                        </div>

                        <p class="text-gray-600 dark:text-gray-300 text-base mb-8 line-clamp-3 leading-relaxed flex-grow italic">
                            "<?php echo htmlspecialchars($req['description']); ?>"
                        </p>

                        <div class="grid grid-cols-2 gap-4 mb-8">
                            <div class="bg-gray-50 dark:bg-black/20 p-4 rounded-2xl flex flex-col gap-1 border border-transparent hover:border-green-200 transition-colors">
                                <span class="text-[10px] font-black text-gray-400 uppercase tracking-widest">Budget</span>
                                <span class="text-sm font-black text-gray-900 dark:text-white">
                                    <?php if ($req['min_price'] || $req['max_price']): ?>
                                        ₹<?php echo $req['max_price'] ?: $req['min_price']; ?>
                                    <?php else: ?>
                                        Flexible
                                    <?php endif; ?>
                                </span>
                            </div>
                            <div class="bg-gray-50 dark:bg-black/20 p-4 rounded-2xl flex flex-col gap-1 border border-transparent hover:border-blue-200 transition-colors">
                                <span class="text-[10px] font-black text-gray-400 uppercase tracking-widest">Needed</span>
                                <span class="text-sm font-black text-gray-900 dark:text-white">
                                    <?php echo $req['needed_by'] ? date('M d', strtotime($req['needed_by'])) : 'ASAP'; ?>
                                </span>
                            </div>
                        </div>

                        <div class="flex gap-3 pt-2">
                             <?php if ($req['renter_id'] != $user_id): ?>
                                <form method="POST" class="flex-1">
                                    <input type="hidden" name="request_id" value="<?php echo $req['id']; ?>">
                                    <button type="submit" name="accept_request" class="w-full bg-black text-white dark:bg-white dark:text-black py-4 rounded-2xl font-black text-xs uppercase tracking-widest shadow-xl hover:bg-primary hover:text-black dark:hover:bg-primary transition-all flex items-center justify-center gap-2">
                                        Accept Request
                                        <span class="material-symbols-outlined text-lg">arrow_forward</span>
                                    </button>
                                </form>
                            <?php else: ?>
                                <button disabled class="flex-1 bg-gray-100 text-gray-400 py-4 rounded-2xl font-black text-xs uppercase tracking-widest cursor-not-allowed">
                                    Your Request
                                </button>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </main>

    <footer class="mt-20 py-10 text-center text-text-muted text-sm border-t border-[#e9e8ce] dark:border-[#3e3d2a]">
        &copy; 2026 RendeX - Sharing is Caring.
    </footer>
</body>
</html>
