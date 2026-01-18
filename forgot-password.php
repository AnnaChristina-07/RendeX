<?php 
            ob_start();
            session_start();
            require_once 'config/mail.php';

            $step = isset($_SESSION['reset_step']) ? $_SESSION['reset_step'] : 'email';
            $error_message = '';
            $success_message = '';

            if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                if (isset($_POST['email'])) {
                    // Step 1: Handle Email Submission
                    $email = trim($_POST['email']);
                    
                    // Basic validtion
                    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                        $error_message = "Please enter a valid email address.";
                    } else {
                        // Check if email exists in Database first
                        require_once 'config/database.php';
                        $email_exists = false;
                        
                        try {
                            $pdo = getDBConnection();
                            if ($pdo) {
                                $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
                                $stmt->execute([$email]);
                                if ($stmt->fetch()) {
                                    $email_exists = true;
                                }
                            }
                        } catch (Exception $e) {
                            // Ignore DB error and fall back to file
                        }

                        // Check JSON if not found in DB
                        if (!$email_exists) {
                            $users_file = 'users.json';
                            if (file_exists($users_file)) {
                                $users = json_decode(file_get_contents($users_file), true) ?: [];
                                foreach ($users as $user) {
                                    if (strtolower(trim($user['email'])) === strtolower($email)) {
                                        $email_exists = true;
                                        break;
                                    }
                                }
                            }
                        }

                        if ($email_exists) {
                            $otp = rand(100000, 999999);
                            $_SESSION['reset_otp'] = $otp;
                            $_SESSION['reset_email'] = $email;
                            $_SESSION['reset_step'] = 'otp'; // Move to next step
                            
                            $subject = "RendeX Password Reset OTP";
                            $message_body = "
                            <div style='font-family: sans-serif; padding: 20px; background-color: #f8f8f5;'>
                                <h2 style='color: #1c1c0d;'>Password Reset Verification</h2>
                                <p>You requested to reset your password. Use the code below to verify your identity:</p>
                                <h1 style='color: #000; letter-spacing: 5px;'>$otp</h1>
                                <p style='margin-top: 20px; color: #5e5e4a; font-size: 12px;'>If you didn't ask for this, ignore this email.</p>
                            </div>";

                            $result = send_smtp_email($email, $subject, $message_body);
                            if ($result === true) {
                                $step = 'otp';
                            } else {
                                $error_message = "Failed to send email: " . $result;
                                unset($_SESSION['reset_step']); // Revert
                            }
                        } else {
                            $error_message = "We couldn't find an account with that email.";
                        }
                    }
                } elseif (isset($_POST['otp'])) {
                    // Step 2: Handle OTP Submission
                    if (!isset($_SESSION['reset_otp'])) {
                         $error_message = "Session expired. Please try again.";
                         $step = 'email'; // Fallback
                    } else {
                        $entered_otp = trim($_POST['otp']);
                        if ($entered_otp == $_SESSION['reset_otp']) {
                            $_SESSION['otp_verified'] = true;
                            unset($_SESSION['reset_otp']); // Clear OTP for security
                            unset($_SESSION['reset_step']);
                            header("Location: reset-password.php");
                            exit();
                        } else {
                            $error_message = "The OTP is not correct.";
                            $step = 'otp'; // Ensure we stay on OTP step to show error
                        }
                    }
                }
            }
            ?>
<!DOCTYPE html>
<html class="light" lang="en">
<head>
    <meta charset="utf-8"/>
    <meta content="width=device-width, initial-scale=1.0" name="viewport"/>
    <title>RendeX - Forgot Password</title>
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

            <?php if ($step === 'otp'): ?>
                <!-- OTP Entry Form -->
                <div class="w-full text-center">
                    <div class="w-16 h-16 bg-[#dfff00]/20 rounded-full flex items-center justify-center mx-auto mb-6">
                         <span class="material-symbols-outlined text-[#dfff00] text-3xl">sms</span>
                    </div>
                    
                    <h1 class="text-3xl font-bold text-black mb-2">Enter Verification Code</h1>
                    <p class="text-[#8e9080] mb-8 text-sm max-w-sm mx-auto">
                        We sent a 6-digit code to <strong><?php echo htmlspecialchars($_SESSION['reset_email']); ?></strong>
                    </p>

                    <?php if ($error_message): ?>
                        <div class="bg-red-50 text-red-500 p-4 rounded-xl text-sm mb-6">
                            <?php echo $error_message; ?>
                        </div>
                    <?php endif; ?>

                    <form autocomplete="off" method="POST">
                        <div class="mb-5 text-left">
                            <label class="block text-xs font-bold text-black mb-2 ml-1">6-Digit OTP</label>
                            <input type="text" name="otp" required maxlength="6" class="w-full bg-[#f6f7f2] border-none rounded-xl py-3.5 px-4 text-center text-2xl tracking-[0.5em] font-bold focus:ring-2 focus:ring-[#dfff00] placeholder-gray-300 transition-all" placeholder="000000">
                        </div>

                        <button type="submit" class="w-full bg-black text-white hover:bg-gray-800 font-bold py-4 rounded-full transition-colors shadow-lg shadow-black/20 text-sm tracking-wide">
                            Verify Code
                        </button>
                        
                        <p class="mt-8 text-center text-xs text-[#8e9080]">
                            Didn't receive code? 
                            <a href="forgot-password.php?retry=1" onclick="<?php unset($_SESSION['reset_step']); ?>" class="font-bold text-black hover:underline">Try Again</a>
                        </p>
                    </form>
                </div>
            <?php else: ?>
                <!-- Request Form (Email) -->
                <div class="w-full text-center">
                    <div class="w-16 h-16 bg-[#f6f7f2] rounded-full flex items-center justify-center mx-auto mb-6">
                         <span class="material-symbols-outlined text-black text-3xl">lock_reset</span>
                    </div>
                    
                    <h1 class="text-3xl font-bold text-black mb-2">Forgot Password?</h1>
                    <p class="text-[#8e9080] mb-8 text-sm max-w-sm mx-auto">No worries, we'll send you a verification code.</p>
                    
                    <?php if ($error_message): ?>
                        <div class="bg-red-50 text-red-500 p-4 rounded-xl text-sm mb-6">
                            <?php echo $error_message; ?>
                        </div>
                    <?php endif; ?>

                    <form autocomplete="off" method="POST">
                        <!-- Email Input -->
                        <div class="mb-5 text-left">
                            <label class="block text-xs font-bold text-black mb-2 ml-1">Email</label>
                            <div class="relative">
                                <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none">
                                    <span class="material-symbols-outlined text-gray-400 text-[20px]">mail</span>
                                </div>
                                <input type="email" name="email" required autocomplete="off" class="w-full bg-[#f6f7f2] border-none rounded-xl py-3.5 pl-11 pr-4 text-sm focus:ring-2 focus:ring-[#dfff00] placeholder-gray-400 transition-all" placeholder="">
                            </div>
                        </div>

                        <!-- Submit Button -->
                        <button type="submit" class="w-full bg-black text-white hover:bg-gray-800 font-bold py-4 rounded-full transition-colors shadow-lg shadow-black/20 text-sm tracking-wide">
                            Send Code
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
