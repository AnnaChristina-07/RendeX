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
                    <div id="request-card-<?php echo $req['id']; ?>" class="group bg-white dark:bg-surface-dark p-8 rounded-[2.5rem] shadow-sm hover:shadow-2xl hover:shadow-primary/10 transition-all duration-500 border border-[#e9e8ce] dark:border-[#3e3d2a] flex flex-col h-full relative overflow-hidden">
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
                                <button disabled class="flex-1 bg-gray-100 dark:bg-gray-800 text-gray-400 py-4 rounded-2xl font-black text-xs uppercase tracking-widest cursor-not-allowed">
                                    Your Request
                                </button>
                            <?php endif; ?>
                            <button type="button" onclick="openShareModal('<?php echo $req['id']; ?>', '<?php echo htmlspecialchars(addslashes($req['item_name'])); ?>', '<?php echo htmlspecialchars(addslashes($req['renter_name'])); ?>')" class="w-auto bg-gray-100 dark:bg-gray-800 hover:bg-gray-200 dark:hover:bg-gray-700 text-black dark:text-white px-5 rounded-2xl font-black transition-all flex items-center justify-center" title="Share this request">
                                <span class="material-symbols-outlined text-lg">share</span>
                            </button>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </main>

    <footer class="mt-20 py-10 text-center text-text-muted text-sm border-t border-[#e9e8ce] dark:border-[#3e3d2a]">
        &copy; 2026 RendeX - Sharing is Caring.
    </footer>
    <!-- Share Modal -->
    <div id="shareModal" class="fixed inset-0 z-[100] hidden items-center justify-center bg-black/50 backdrop-blur-sm transition-opacity duration-300 opacity-0">
        <div class="bg-white dark:bg-[#1e1e1e] rounded-[2rem] p-8 max-w-sm w-full mx-4 shadow-2xl border border-gray-100 dark:border-gray-800 transform transition-transform duration-300 scale-95" id="shareModalContent">
            <div class="flex justify-between items-center mb-6">
                <h3 class="text-2xl font-black text-gray-900 dark:text-white">Share Request</h3>
                <button onclick="closeShareModal()" class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-200 transition-colors">
                    <span class="material-symbols-outlined">close</span>
                </button>
            </div>
            <p class="text-sm text-gray-500 mb-6 text-center">Help fulfill this request faster by sharing it with your network!</p>
            <div class="flex flex-col gap-4">
                <button onclick="shareToWhatsApp()" class="flex items-center gap-3 w-full bg-[#25D366] hover:bg-[#20bd5a] text-white px-6 py-4 rounded-xl font-bold transition-colors">
                    <svg viewBox="0 0 24 24" class="w-6 h-6 fill-current"><path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51a12.8 12.8 0 0 0-.57-.01c-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 0 1-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 0 1-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.82 9.82 0 0 1 2.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0 0 12.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.88 11.88 0 0 0 5.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.82 11.82 0 0 0-3.48-8.413Z"/></svg>
                    WhatsApp
                </button>
                <button onclick="shareToFacebook()" class="flex items-center gap-3 w-full bg-[#1877F2] hover:bg-[#166fe5] text-white px-6 py-4 rounded-xl font-bold transition-colors">
                    <svg viewBox="0 0 24 24" class="w-6 h-6 fill-current"><path d="M24 12.073c0-6.627-5.373-12-12-12s-12 5.373-12 12c0 5.99 4.388 10.954 10.125 11.854v-8.385H7.078v-3.469h3.047V9.43c0-3.007 1.792-4.669 4.533-4.669 1.312 0 2.686.235 2.686.235v2.953H15.83c-1.491 0-1.956.925-1.956 1.874v2.25h3.328l-.532 3.469h-2.796v8.385C19.612 23.027 24 18.062 24 12.073z"/></svg>
                    Facebook
                </button>
                <button onclick="shareToX()" class="flex items-center gap-3 w-full bg-black hover:bg-gray-900 text-white px-6 py-4 rounded-xl font-bold transition-colors">
                    <svg viewBox="0 0 24 24" class="w-5 h-5 fill-current"><path d="M18.244 2.25h3.308l-7.227 8.26 8.502 11.24H16.17l-5.214-6.817L4.99 21.75H1.68l7.73-8.835L1.254 2.25H8.08l4.713 6.231zm-1.161 17.52h1.833L7.084 4.126H5.117z"/></svg>
                    Twitter (X)
                </button>
                <button onclick="copyShareLink()" id="copyLinkBtn" class="flex items-center justify-center gap-3 w-full bg-gray-100 dark:bg-gray-800 hover:bg-gray-200 dark:hover:bg-gray-700 text-black dark:text-white px-6 py-4 rounded-xl font-bold transition-colors">
                    <span class="material-symbols-outlined" id="copyIcon">link</span>
                    <span id="copyText">Copy Link</span>
                </button>
            </div>
        </div>
    </div>

    <script>
        let currentShareReqId = null;
        let currentShareItemName = '';
        let currentShareRenterName = '';
        let currentShortUrl = '';

        function getShareUrl(reqId) {
            // Note: Plan requires sharing links to point to browse_requests.php
            let baseUrl = window.location.origin + window.location.pathname.replace(/\/[^\/]+$/, '/browse_requests.php');
            baseUrl = baseUrl.replace('localhost', '127.0.0.1'); 
            return baseUrl + '?highlight=' + reqId;
        }

        async function prefetchShortUrl(reqId) {
            const longUrl = getShareUrl(reqId);
            currentShortUrl = longUrl; // Fallback immediately
            try {
                const response = await fetch(`https://tinyurl.com/api-create.php?url=${encodeURIComponent(longUrl)}`);
                if (response.ok) {
                    currentShortUrl = await response.text();
                }
            } catch (e) {
                console.error("Short URL failed", e);
            }
        }

        function openShareModal(reqId, itemName, renterName) {
            currentShareReqId = reqId;
            currentShareItemName = itemName;
            currentShareRenterName = renterName;
            
            // Generate the TinyURL in the background so it's ready when they click share
            prefetchShortUrl(reqId);
            
            const modal = document.getElementById('shareModal');
            const content = document.getElementById('shareModalContent');
            modal.classList.remove('hidden');
            modal.classList.add('flex');
            setTimeout(() => {
                modal.classList.remove('opacity-0');
                content.classList.remove('scale-95');
            }, 10);
        }

        function closeShareModal() {
            const modal = document.getElementById('shareModal');
            const content = document.getElementById('shareModalContent');
            modal.classList.add('opacity-0');
            content.classList.add('scale-95');
            setTimeout(() => {
                modal.classList.add('hidden');
                modal.classList.remove('flex');
            }, 300);
        }

        function shareToWhatsApp() {
            const urlToShare = currentShortUrl || getShareUrl(currentShareReqId);
            const text = `Hey! Is anyone renting out a ${currentShareItemName}?\n\n${currentShareRenterName} is looking for one on RendeX right now! Check it out here:\n${urlToShare}\n\n📸🚀`;
            window.open(`https://wa.me/?text=${encodeURIComponent(text)}`, '_blank');
        }

        function shareToFacebook() {
            const urlToShare = currentShortUrl || getShareUrl(currentShareReqId);
            const url = encodeURIComponent(urlToShare);
            window.open(`https://www.facebook.com/sharer/sharer.php?u=${url}`, '_blank', 'width=600,height=400');
        }

        function shareToX() {
            const urlToShare = currentShortUrl || getShareUrl(currentShareReqId);
            const url = encodeURIComponent(urlToShare);
            const text = encodeURIComponent(`Could you help out? ${currentShareRenterName} is searching for a ${currentShareItemName} to rent on RendeX. Do you have one lying around? Let them know!\n\n`);
            window.open(`https://twitter.com/intent/tweet?text=${text}&url=${url}`, '_blank', 'width=600,height=400');
        }

        function copyShareLink() {
            const urlToShare = currentShortUrl || getShareUrl(currentShareReqId);
            navigator.clipboard.writeText(urlToShare).then(() => {
                const btn = document.getElementById('copyLinkBtn');
                const icon = document.getElementById('copyIcon');
                const text = document.getElementById('copyText');
                
                icon.textContent = 'check';
                text.textContent = 'Copied!';
                btn.classList.add('text-green-600', 'dark:text-green-400');
                
                setTimeout(() => {
                    icon.textContent = 'link';
                    text.textContent = 'Copy Link';
                    btn.classList.remove('text-green-600', 'dark:text-green-400');
                }, 2000);
            });
        }

        document.addEventListener('DOMContentLoaded', () => {
            const urlParams = new URLSearchParams(window.location.search);
            const highlightId = urlParams.get('highlight');
            if (highlightId) {
                const card = document.getElementById('request-card-' + highlightId);
                if (card) {
                    setTimeout(() => {
                        card.scrollIntoView({ behavior: 'smooth', block: 'center' });
                        card.classList.add('ring-4', 'ring-primary', 'ring-opacity-50', 'animate-pulse');
                        setTimeout(() => {
                            card.classList.remove('ring-4', 'ring-primary', 'ring-opacity-50', 'animate-pulse');
                        }, 3000);
                    }, 500);
                }
            }

            document.getElementById('shareModal').addEventListener('click', (e) => {
                if (e.target === document.getElementById('shareModal')) {
                    closeShareModal();
                }
            });
        });
    </script>
</body>
</html>
