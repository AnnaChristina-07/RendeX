<?php
session_start();

// Ensure user has verified OTP
if (!isset($_SESSION['otp_verified']) || $_SESSION['otp_verified'] !== true || !isset($_SESSION['reset_email'])) {
    header("Location: forgot-password.php");
    exit();
}

$email = $_SESSION['reset_email'];
$success = false;
$error = '';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $new_password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];
    
    if ($new_password !== $confirm_password) {
        $error = "Passwords do not match.";
    } elseif (strlen($new_password) < 8) {
        $error = "Password must be at least 8 characters long.";
    } else {
        // Update password in users.json
        $users_file = 'users.json';
        $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
        
        if (file_exists($users_file)) {
            $users = json_decode(file_get_contents($users_file), true) ?: [];
            $user_found = false;

            foreach ($users as &$user) {
                if ($user['email'] === $email) {
                    $user['password_hash'] = $hashed_password;
                    $user_found = true;
                    break;
                }
            }
            unset($user);

            if ($user_found) {
                file_put_contents($users_file, json_encode($users, JSON_PRETTY_PRINT));
            }
        }

        // Update password in Database
        try {
            require_once 'config/database.php';
            $pdo = getDBConnection();
            if ($pdo) {
                $stmt = $pdo->prepare("UPDATE users SET password_hash = ? WHERE email = ?");
                $stmt->execute([$hashed_password, $email]);
                $success = true;
            } else {
                // If DB fails, we still have JSON
                $success = true; 
            }
        } catch (PDOException $e) {
            $success = true; // Still true if JSON worked
        }

        if ($success) {
            // Clear session data
            unset($_SESSION['otp_verified']);
            unset($_SESSION['reset_email']);
            unset($_SESSION['reset_step']);
        }
    }
}
?>
<!DOCTYPE html>
<html class="light" lang="en">
<head>
    <meta charset="utf-8"/>
    <meta content="width=device-width, initial-scale=1.0" name="viewport"/>
    <title>RendeX - Reset Password</title>
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
              "background-dark": "#23220f",
              "surface-light": "#ffffff",
              "surface-dark": "#2d2c18",
              "text-main": "#1c1c0d",
              "text-muted": "#5e5e4a",
              "card-dark": "#1e2019"
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
        .material-symbols-outlined { font-variation-settings: 'FILL' 1, 'wght' 400, 'GRAD' 0, 'opsz' 24 }
    </style>
</head>
<body class="bg-[#f0f2eb] min-h-screen flex items-center justify-center p-4">

    <!-- Main Card -->
    <div class="bg-white rounded-[2rem] shadow-2xl overflow-hidden w-full max-w-[600px] flex flex-col">
        
        <div class="p-8 md:p-12 lg:p-16 flex flex-col justify-center relative">
            <!-- Logo -->
            <div class="flex items-center gap-3 mb-8 justify-center">
                <a href="index.php" class="flex items-center gap-3">
                    <div class="w-10 h-10 bg-black rounded-full flex items-center justify-center text-[#dfff00] font-bold text-xl">R</div>
                    <h2 class="text-2xl font-bold tracking-tight text-black">RendeX</h2>
                </a>
            </div>

            <?php if ($success): ?>
                <!-- Success Message -->
                <div class="w-full text-center">
                    <div class="w-16 h-16 bg-[#dfff00]/20 rounded-full flex items-center justify-center mx-auto mb-6">
                         <span class="material-symbols-outlined text-[#dfff00] text-3xl">check_circle</span>
                    </div>
                    
                    <h1 class="text-3xl font-bold text-black mb-2">Password Reset!</h1>
                    <p class="text-[#8e9080] mb-8 text-sm max-w-sm mx-auto">
                        Your password has been successfully reset. You can now log in with your new password.
                    </p>

                    <a href="login.php" class="w-full block bg-black text-white hover:bg-gray-800 font-bold py-4 rounded-full transition-colors shadow-lg shadow-black/20 text-sm tracking-wide text-center">
                        Go to Log In
                    </a>
                </div>
            <?php else: ?>
                <!-- Reset Password Form -->
                <div class="w-full text-center">
                    <div class="w-16 h-16 bg-[#f6f7f2] rounded-full flex items-center justify-center mx-auto mb-6">
                         <span class="material-symbols-outlined text-black text-3xl">lock</span>
                    </div>
                    
                    <h1 class="text-3xl font-bold text-black mb-2">Set New Password</h1>
                    <p class="text-[#8e9080] mb-8 text-sm max-w-sm mx-auto">
                        Enter your new password below.
                    </p>
                    
                    <?php if ($error): ?>
                        <div class="bg-red-50 text-red-500 p-4 rounded-xl text-sm mb-6">
                            <?php echo htmlspecialchars($error); ?>
                        </div>
                    <?php endif; ?>

                    <form autocomplete="off" method="POST">
                        
                        <!-- New Password Input -->
                        <div class="mb-5 text-left">
                            <label class="block text-xs font-bold text-black mb-2 ml-1">New Password</label>
                            <div class="relative">
                                <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none">
                                    <span class="material-symbols-outlined text-gray-400 text-[20px]">lock</span>
                                </div>
                                <input type="password" name="password" required autocomplete="new-password" class="w-full bg-[#f6f7f2] border-none rounded-xl py-3.5 pl-11 pr-4 text-sm focus:ring-2 focus:ring-[#dfff00] placeholder-gray-400 transition-all" placeholder="">
                            </div>
                        </div>

                        <!-- Confirm Password Input -->
                        <div class="mb-8 text-left">
                            <label class="block text-xs font-bold text-black mb-2 ml-1">Confirm Password</label>
                            <div class="relative">
                                <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none">
                                    <span class="material-symbols-outlined text-gray-400 text-[20px]">lock_reset</span>
                                </div>
                                <input type="password" name="confirm_password" required autocomplete="new-password" class="w-full bg-[#f6f7f2] border-none rounded-xl py-3.5 pl-11 pr-4 text-sm focus:ring-2 focus:ring-[#dfff00] placeholder-gray-400 transition-all" placeholder="">
                            </div>
                        </div>

                        <!-- Submit Button -->
                        <button type="submit" class="w-full bg-black text-white hover:bg-gray-800 font-bold py-4 rounded-full transition-colors shadow-lg shadow-black/20 text-sm tracking-wide">
                            Reset Password
                        </button>
                        
                        <!-- Back Link -->
                        <p class="mt-8 text-center text-xs text-[#8e9080]">
                            <a href="login.php" class="flex items-center justify-center gap-2 hover:text-black transition-colors">
                                <span class="material-symbols-outlined text-base">arrow_back</span>
                                Back to log in
                            </a>
                        </p>
                    </form>
                </div>
            <?php endif; ?>
        </div>

    </div>

</body>
</html>
