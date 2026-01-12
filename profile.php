<?php
ob_start();
session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

require_once 'config/database.php';

$users_file = 'users.json';
$users = file_exists($users_file) ? json_decode(file_get_contents($users_file), true) : [];

$current_user = null;
foreach ($users as $u) {
    if ($u['id'] === $_SESSION['user_id']) {
        $current_user = $u;
        break;
    }
}

// Fallback to DB if not in JSON (unlikely but possible)
if (!$current_user) {
    try {
        $pdo = getDBConnection();
        if ($pdo) {
            $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
            $stmt->execute([$_SESSION['user_id']]);
            $current_user = $stmt->fetch();
        }
    } catch (Exception $e) {}
}

// Fallback to Session Data if no record exists (Prevents logging out valid session users)
if (!$current_user) {
    if (isset($_SESSION['user_id'])) {
        $current_user = [
            'id' => $_SESSION['user_id'],
            'name' => $_SESSION['user_name'] ?? 'New User',
            'email' => $_SESSION['user_email'] ?? '',
            'phone' => '',
            'created_at' => date('Y-m-d H:i:s'),
            'bio' => '',
            'address' => '',
            'city' => '',
            'state' => '',
            'pincode' => ''
        ];
    } else {
        header("Location: logout.php");
        exit();
    }
}

$success_msg = "";
$error_msg = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_profile'])) {
    $name = $_POST['name'];
    $email = $_POST['email'];
    $phone = $_POST['phone'];
    $address = $_POST['address'] ?? '';
    $city = $_POST['city'] ?? '';
    $state = $_POST['state'] ?? '';
    $pincode = $_POST['pincode'] ?? '';
    $gender = $_POST['gender'] ?? '';
    $dob = $_POST['dob'] ?? '';
    $bio = $_POST['bio'] ?? '';
    
    // Update JSON
    foreach ($users as &$u) {
        if ($u['id'] === $_SESSION['user_id']) {
            $u['name'] = $name;
            $u['email'] = $email;
            $u['phone'] = $phone;
            $u['address'] = $address;
            $u['city'] = $city;
            $u['state'] = $state;
            $u['pincode'] = $pincode;
            $u['gender'] = $gender;
            $u['dob'] = $dob;
            $u['bio'] = $bio;
            break;
        }
    }
    file_put_contents($users_file, json_encode($users, JSON_PRETTY_PRINT));
    
    // Update Database
    try {
        $pdo = getDBConnection();
        if ($pdo) {
            // Check if columns exist or use a flexible update
            $stmt = $pdo->prepare("UPDATE users SET name = ?, email = ?, phone = ?, address = ?, city = ?, state = ?, pincode = ? WHERE id = ?");
            $stmt->execute([$name, $email, $phone, $address, $city, $state, $pincode, $_SESSION['user_id']]);
            
            // Note: If gender, dob, bio aren't in the schema yet, we might need to add them or skip
            // For now, we update the primary fields we confirmed in the SQL file.
        }
    } catch (Exception $e) {
        $error_msg = "Database update failed: " . $e->getMessage();
    }
    
    // Update Session
    $_SESSION['user_name'] = $name;
    $_SESSION['user_email'] = $email;
    
    // Update local variable for display
    $current_user['name'] = $name;
    $current_user['email'] = $email;
    $current_user['phone'] = $phone;
    $current_user['address'] = $address;
    $current_user['city'] = $city;
    $current_user['state'] = $state;
    $current_user['pincode'] = $pincode;
    $current_user['gender'] = $gender;
    $current_user['dob'] = $dob;
    $current_user['bio'] = $bio;
    
    $success_msg = "Profile updated successfully!";
}

?>
<!DOCTYPE html>
<html class="light" lang="en">
<head>
    <meta charset="utf-8"/>
    <meta content="width=device-width, initial-scale=1.0" name="viewport"/>
    <title>My Profile - RendeX</title>
    <link href="https://fonts.googleapis.com" rel="preconnect"/>
    <link crossorigin="" href="https://fonts.gstatic.com" rel="preconnect"/>
    <link href="https://fonts.googleapis.com/css2?family=Spline+Sans:wght@300;400;500;600;700;800&display=swap" rel="stylesheet"/>
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&display=swap" rel="stylesheet"/>
    <script src="https://cdn.tailwindcss.com?plugins=forms"></script>
    <script>
      tailwind.config = {
        darkMode: "class",
        theme: {
          extend: {
            colors: {
              "primary": "#f9f506",
              "background-light": "#f8f8f5",
              "background-dark": "#1e2019",
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
    <style>
        body { font-family: "Spline Sans", sans-serif; }
    </style>
</head>
<body class="bg-background-light dark:bg-background-dark text-text-main dark:text-white transition-colors duration-200 min-h-screen">
    <header class="sticky top-0 z-50 border-b border-[#e9e8ce] dark:border-[#3e3d2a] bg-white/80 dark:bg-background-dark/80 backdrop-blur-md px-6 py-4">
        <div class="max-w-[1400px] mx-auto flex items-center justify-between">
            <a href="dashboard.php" class="flex items-center gap-2">
                <div class="size-8 text-primary">
                    <svg class="w-full h-full" fill="none" viewbox="0 0 48 48" xmlns="http://www.w3.org/2000/svg">
                        <path d="M36.7273 44C33.9891 44 31.6043 39.8386 30.3636 33.69C29.123 39.8386 26.7382 44 24 44C21.2618 44 18.877 39.8386 17.6364 33.69C16.3957 39.8386 14.0109 44 11.2727 44C7.25611 44 4 35.0457 4 24C4 12.9543 7.25611 4 11.2727 4C14.0109 4 16.3957 8.16144 17.6364 14.31C18.877 8.16144 21.2618 4 24 4C26.7382 4 29.123 8.16144 30.3636 14.31C31.6043 8.16144 33.9891 4 36.7273 4C40.7439 4 44 12.9543 44 24C44 35.0457 40.7439 44 36.7273 44Z" fill="currentColor"></path>
                    </svg>
                </div>
                <h2 class="text-xl font-bold tracking-tight">RendeX</h2>
            </a>
            <div class="flex items-center gap-6">
                <a href="dashboard.php" class="text-sm font-bold hover:text-primary transition-colors">Home</a>
                <a href="rentals.php" class="text-sm font-bold hover:text-primary transition-colors">My Rentals</a>
                <a href="logout.php" class="text-sm font-bold text-red-500 flex items-center gap-1">
                    <span class="material-symbols-outlined text-lg">logout</span> Logout
                </a>
            </div>
        </div>
    </header>

    <main class="max-w-[800px] mx-auto px-4 py-16">
        <div class="flex flex-col items-center mb-12">
            <div class="w-24 h-24 rounded-full bg-primary flex items-center justify-center text-black text-4xl font-black border-4 border-white shadow-xl mb-4">
                <?php echo strtoupper(substr($current_user['name'], 0, 1)); ?>
            </div>
            <h1 class="text-3xl font-black"><?php echo htmlspecialchars($current_user['name']); ?></h1>
            <p class="text-text-muted">Member since <?php echo date('M Y', strtotime($current_user['created_at'])); ?></p>
        </div>

        <?php if ($success_msg): ?>
        <div class="bg-green-100 border border-green-200 text-green-700 px-6 py-4 rounded-2xl mb-8 flex items-center gap-3">
            <span class="material-symbols-outlined">check_circle</span>
            <span class="font-bold"><?php echo $success_msg; ?></span>
        </div>
        <?php endif; ?>

        <?php if ($error_msg): ?>
        <div class="bg-red-100 border border-red-200 text-red-700 px-6 py-4 rounded-2xl mb-8 flex items-center gap-3">
            <span class="material-symbols-outlined">error</span>
            <span class="font-bold"><?php echo $error_msg; ?></span>
        </div>
        <?php endif; ?>

        <div class="bg-white dark:bg-surface-dark rounded-3xl p-8 border border-[#e9e8ce] dark:border-[#3e3d2a] shadow-sm">
            <form method="POST" class="space-y-6">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div class="md:col-span-2">
                        <label class="block text-xs font-black text-text-muted uppercase tracking-widest mb-2 ml-1">About You (Bio)</label>
                        <textarea name="bio" rows="3" placeholder="Tell us a bit about yourself..."
                                  class="w-full px-6 py-4 rounded-2xl border-none bg-gray-50 dark:bg-[#1e2019] focus:ring-2 focus:ring-primary font-medium resize-none"><?php echo htmlspecialchars($current_user['bio'] ?? ''); ?></textarea>
                    </div>

                    <div>
                        <label class="block text-xs font-black text-text-muted uppercase tracking-widest mb-2 ml-1">Full Name</label>
                        <input type="text" name="name" value="<?php echo htmlspecialchars($current_user['name']); ?>" required
                               class="w-full px-6 py-4 rounded-2xl border-none bg-gray-50 dark:bg-[#1e2019] focus:ring-2 focus:ring-primary font-bold">
                    </div>
                    
                    <div>
                        <label class="block text-xs font-black text-text-muted uppercase tracking-widest mb-2 ml-1">Email Address</label>
                        <input type="email" name="email" value="<?php echo htmlspecialchars($current_user['email']); ?>" required
                               class="w-full px-6 py-4 rounded-2xl border-none bg-gray-50 dark:bg-[#1e2019] focus:ring-2 focus:ring-primary font-bold">
                    </div>

                    <div>
                        <label class="block text-xs font-black text-text-muted uppercase tracking-widest mb-2 ml-1">Phone Number</label>
                        <input type="text" name="phone" value="<?php echo htmlspecialchars($current_user['phone']); ?>" required
                               class="w-full px-6 py-4 rounded-2xl border-none bg-gray-50 dark:bg-[#1e2019] focus:ring-2 focus:ring-primary font-bold">
                    </div>

                    <div>
                        <label class="block text-xs font-black text-text-muted uppercase tracking-widest mb-2 ml-1">Date of Birth</label>
                        <input type="date" name="dob" value="<?php echo htmlspecialchars($current_user['dob'] ?? ''); ?>"
                               class="w-full px-6 py-4 rounded-2xl border-none bg-gray-50 dark:bg-[#1e2019] focus:ring-2 focus:ring-primary font-bold">
                    </div>

                    <div>
                        <label class="block text-xs font-black text-text-muted uppercase tracking-widest mb-2 ml-1">Gender</label>
                        <select name="gender" class="w-full px-6 py-4 rounded-2xl border-none bg-gray-50 dark:bg-[#1e2019] focus:ring-2 focus:ring-primary font-bold">
                            <option value="">Select Gender</option>
                            <option value="male" <?php echo ($current_user['gender'] ?? '') === 'male' ? 'selected' : ''; ?>>Male</option>
                            <option value="female" <?php echo ($current_user['gender'] ?? '') === 'female' ? 'selected' : ''; ?>>Female</option>
                            <option value="other" <?php echo ($current_user['gender'] ?? '') === 'other' ? 'selected' : ''; ?>>Other</option>
                        </select>
                    </div>

                    <div>
                        <label class="block text-xs font-black text-text-muted uppercase tracking-widest mb-2 ml-1">User ID (Public)</label>
                        <div class="w-full px-6 py-4 rounded-2xl bg-gray-100 dark:bg-gray-800 font-mono text-xs text-text-muted">
                            <?php echo $current_user['id']; ?>
                        </div>
                    </div>

                    <!-- Address Section -->
                    <div class="md:col-span-2 pt-4">
                        <h3 class="text-sm font-black uppercase tracking-widest text-primary mb-6 flex items-center gap-2">
                            <span class="material-symbols-outlined">location_on</span> Address Details
                        </h3>
                    </div>

                    <div class="md:col-span-2">
                        <label class="block text-xs font-black text-text-muted uppercase tracking-widest mb-2 ml-1">Street Address</label>
                        <input type="text" name="address" value="<?php echo htmlspecialchars($current_user['address'] ?? ''); ?>" placeholder="Apt, Suite, Street"
                               class="w-full px-6 py-4 rounded-2xl border-none bg-gray-50 dark:bg-[#1e2019] focus:ring-2 focus:ring-primary font-bold">
                    </div>

                    <div>
                        <label class="block text-xs font-black text-text-muted uppercase tracking-widest mb-2 ml-1">City</label>
                        <input type="text" name="city" value="<?php echo htmlspecialchars($current_user['city'] ?? ''); ?>"
                               class="w-full px-6 py-4 rounded-2xl border-none bg-gray-50 dark:bg-[#1e2019] focus:ring-2 focus:ring-primary font-bold">
                    </div>

                    <div>
                        <label class="block text-xs font-black text-text-muted uppercase tracking-widest mb-2 ml-1">State</label>
                        <input type="text" name="state" value="<?php echo htmlspecialchars($current_user['state'] ?? ''); ?>"
                               class="w-full px-6 py-4 rounded-2xl border-none bg-gray-50 dark:bg-[#1e2019] focus:ring-2 focus:ring-primary font-bold">
                    </div>

                    <div>
                        <label class="block text-xs font-black text-text-muted uppercase tracking-widest mb-2 ml-1">Pincode</label>
                        <input type="text" name="pincode" value="<?php echo htmlspecialchars($current_user['pincode'] ?? ''); ?>"
                               class="w-full px-6 py-4 rounded-2xl border-none bg-gray-50 dark:bg-[#1e2019] focus:ring-2 focus:ring-primary font-bold">
                    </div>
                </div>

                <div class="pt-6">
                    <button type="submit" name="save_profile" class="w-full bg-black text-white dark:bg-primary dark:text-black font-black py-5 rounded-2xl text-lg transition-all hover:scale-[1.02] active:scale-95 shadow-xl">
                        Save Changes
                    </button>
                </div>
            </form>
        </div>

        <div class="mt-12 text-center text-sm text-text-muted">
            <p>To change your password or delete your account, please contact support.</p>
        </div>
    </main>
</body>
</html>
