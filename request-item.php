<?php
ob_start();
session_start();
require_once 'config/database.php';
require_once 'config/mail.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$title = 'Request an Item';
$msg = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $item_name = trim($_POST['item_name']);
    $category = trim($_POST['category']);
    $description = trim($_POST['description']);
    $min_price = !empty($_POST['min_price']) ? $_POST['min_price'] : NULL;
    $max_price = !empty($_POST['max_price']) ? $_POST['max_price'] : NULL;
    $needed_by = !empty($_POST['needed_by']) ? $_POST['needed_by'] : NULL;
    $location = trim($_POST['location']);
    
    if (empty($item_name) || empty($category) || empty($description) || empty($location)) {
        $error = "Please fill in all required fields.";
    } else {
        try {
            $pdo = getDBConnection();
            $stmt = $pdo->prepare("INSERT INTO item_requests (renter_id, item_name, category, description, min_price, max_price, needed_by, location, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW())");
            $stmt->execute([
                $_SESSION['user_id'],
                $item_name,
                $category,
                $description,
                $min_price,
                $max_price,
                $needed_by,
                $location
            ]);
            $request_id = $pdo->lastInsertId();

            // --- NOTIFICATION & EMAIL LOGIC (Restored) ---
            $ownerStmt = $pdo->prepare("SELECT DISTINCT owner_id, u.email, u.name as owner_name FROM items i JOIN users u ON i.owner_id = u.id WHERE i.category = ? AND i.owner_id != ?");
            $ownerStmt->execute([$category, $_SESSION['user_id']]);
            $owners = $ownerStmt->fetchAll(PDO::FETCH_ASSOC);

            if (!empty($owners)) {
                $notifSql = "INSERT INTO notifications (user_id, title, message, type, link, created_at) VALUES (?, ?, ?, 'info', ?, NOW())";
                $notifStmt = $pdo->prepare($notifSql);
                
                foreach ($owners as $owner) {
                    try {
                        $title = "New Request: " . $item_name;
                        $message = "A renter in $location is looking for '$item_name' ($category).";
                        $link = "browse_requests.php?highlight=" . $request_id;
                        $notifStmt->execute([$owner['owner_id'], $title, $message, $link]);
                        
                        if (function_exists('send_smtp_email') && !empty($owner['email'])) {
                             $subject = "RandeX Opportunity: Needed in $location - " . $item_name;
                             $body = "
                                <div style='font-family: Arial, sans-serif; padding: 20px; background-color: #f8f8f5;'>
                                    <div style='background-color: white; padding: 20px; border-radius: 10px; border: 1px solid #e9e8ce;'>
                                        <h2 style='color: #000;'>New Rental Request!</h2>
                                        <p>Hi " . htmlspecialchars($owner['owner_name']) . ",</p>
                                        <p>Someone in <b>" . htmlspecialchars($location) . "</b> is looking for an item matching your category (<b>" . htmlspecialchars($category) . "</b>).</p>
                                        <p style='background-color: #f9f506; padding: 10px; border-radius: 5px; font-weight: bold;'>
                                            Looking for: " . htmlspecialchars($item_name) . "
                                        </p>
                                        <p>" . htmlspecialchars($description) . "</p>
                                        <p><a href='http://localhost/RendeX/browse_requests.php' style='display: inline-block; background-color: #000; color: #fff; padding: 10px 20px; text-decoration: none; border-radius: 5px; font-weight: bold;'>View Request & Make Offer</a></p>
                                    </div>
                                </div>
                             ";
                             @send_smtp_email($owner['email'], $subject, $body);
                        }
                    } catch (Exception $e) {}
                }
            }
            
            header("Location: dashboard.php?msg=request_submitted");
            exit();
        } catch (PDOException $e) {
            $error = "Database Error: " . $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html class="light" lang="en">
<head>
    <meta charset="utf-8"/>
    <meta content="width=device-width, initial-scale=1.0" name="viewport"/>
    <title>RendeX - Request an Item</title>
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
                    <span class="material-symbols-outlined text-[18px]">close</span>
                    Cancel
                </a>
            </div>
        </div>
    </header>

    <main class="w-full max-w-2xl mx-auto px-6 py-16">
        <div class="mb-12">
            <h1 class="text-4xl font-black mb-3 tracking-tight text-gray-900 dark:text-white">Request an Item</h1>
            <p class="text-lg text-gray-500 dark:text-gray-400">Can't find what you're looking for? Let owners know what you need.</p>
        </div>

        <?php if ($error): ?>
        <div class="bg-red-50 border-l-4 border-red-500 p-4 mb-8 rounded-lg">
            <div class="flex">
                <div class="flex-shrink-0">
                    <span class="material-symbols-outlined text-red-500">error</span>
                </div>
                <div class="ml-3">
                    <p class="text-sm text-red-700 font-bold"><?php echo htmlspecialchars($error); ?></p>
                </div>
            </div>
        </div>
        <?php endif; ?>

        <form class="space-y-10" method="POST">
            
            <div class="bg-white dark:bg-[#1e1e1e] p-8 md:p-10 rounded-[2.5rem] shadow-xl shadow-gray-200/50 dark:shadow-none space-y-6">
                <div>
                    <label class="block text-sm font-bold mb-2 ml-1 text-gray-700 dark:text-gray-300">What are you looking for? <span class="text-red-500">*</span></label>
                    <input type="text" name="item_name" placeholder="e.g. DSLR Camera for 2 days" class="w-full bg-gray-50 dark:bg-gray-800 border-none focus:bg-white dark:focus:bg-gray-700 focus:ring-2 focus:ring-yellow-400 rounded-2xl px-5 py-4 font-medium transition-all" required>
                </div>

                <div>
                    <label class="block text-sm font-bold mb-2 ml-1 text-gray-700 dark:text-gray-300">Location <span class="text-red-500">*</span></label>
                    <input type="text" name="location" placeholder="e.g. Downtown, near University" class="w-full bg-gray-50 dark:bg-gray-800 border-none focus:bg-white dark:focus:bg-gray-700 focus:ring-2 focus:ring-yellow-400 rounded-2xl px-5 py-4 font-medium transition-all" required>
                </div>

                <div>
                    <label class="block text-sm font-bold mb-2 ml-1 text-gray-700 dark:text-gray-300">Category <span class="text-red-500">*</span></label>
                    <div class="relative">
                        <select name="category" class="w-full bg-gray-50 dark:bg-gray-800 border-none focus:bg-white dark:focus:bg-gray-700 focus:ring-2 focus:ring-yellow-400 rounded-2xl px-5 py-4 font-medium appearance-none transition-all cursor-pointer" required>
                            <option value="">Select Category</option>
                            <option value="student-essentials">Student Essentials</option>
                            <option value="clothing">Clothing</option>
                            <option value="electronics">Electronic Devices</option>
                            <option value="outdoor-gear">Travel/Outdoor Gear</option>
                            <option value="home-essentials">Home-Daily Essentials</option>
                            <option value="furniture">Furniture</option>
                            <option value="vintage">Vintage Collections</option>
                            <option value="fitness">Fitness Equipment</option>
                            <option value="agriculture">Agricultural Tools</option>
                            <option value="medical">Medical Items</option>
                        </select>
                        <span class="absolute right-5 top-1/2 -translate-y-1/2 pointer-events-none text-gray-400 material-symbols-outlined">expand_more</span>
                    </div>
                </div>

                <div>
                    <label class="block text-sm font-bold mb-2 ml-1 text-gray-700 dark:text-gray-300">Description <span class="text-red-500">*</span></label>
                    <textarea name="description" rows="4" placeholder="Describe the item, specifications, purpose, etc..." class="w-full bg-gray-50 dark:bg-gray-800 border-none focus:bg-white dark:focus:bg-gray-700 focus:ring-2 focus:ring-yellow-400 rounded-2xl px-5 py-4 font-medium transition-all resize-none" required></textarea>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <label class="block text-sm font-bold mb-2 ml-1 text-gray-700 dark:text-gray-300">Budget Range (₹)</label>
                        <div class="flex gap-2">
                            <input type="number" name="min_price" placeholder="Min" class="w-full bg-gray-50 dark:bg-gray-800 border-none focus:bg-white dark:focus:bg-gray-700 focus:ring-2 focus:ring-yellow-400 rounded-2xl px-5 py-4 font-medium transition-all">
                            <input type="number" name="max_price" placeholder="Max" class="w-full bg-gray-50 dark:bg-gray-800 border-none focus:bg-white dark:focus:bg-gray-700 focus:ring-2 focus:ring-yellow-400 rounded-2xl px-5 py-4 font-medium transition-all">
                        </div>
                    </div>
                    <div>
                        <label class="block text-sm font-bold mb-2 ml-1 text-gray-700 dark:text-gray-300">Needed By (Optional)</label>
                        <input type="date" name="needed_by" class="w-full bg-gray-50 dark:bg-gray-800 border-none focus:bg-white dark:focus:bg-gray-700 focus:ring-2 focus:ring-yellow-400 rounded-2xl px-5 py-4 font-medium transition-all">
                    </div>
                </div>
            </div>

            <div class="flex flex-col md:flex-row items-center gap-4 pt-6">
                <a href="dashboard.php" class="w-full md:w-1/3 bg-white dark:bg-gray-800 border-2 border-gray-100 dark:border-gray-700 hover:bg-gray-50 text-gray-700 dark:text-gray-300 font-bold py-5 rounded-2xl text-center transition-all">
                    Back
                </a>
                <button type="submit" class="w-full md:w-2/3 bg-primary hover:bg-[#e6e200] text-black font-black text-lg py-5 rounded-2xl shadow-xl shadow-yellow-200/50 dark:shadow-none transition-all hover:scale-[1.02] active:scale-[0.98] flex items-center justify-center gap-2">
                    <span>Submit Request</span>
                    <span class="material-symbols-outlined">send</span>
                </button>
            </div>

        </form>
    </main>
</body>
</html>
