<?php
ob_start();
session_start();
require_once 'config/database.php';
require_once 'config/mail.php';

$success = false;
$error = '';
$redirect = false;
$show_otp_form = false;

// Handle OTP Verification
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['otp'])) {
    if (!isset($_SESSION['signup_otp']) || !isset($_SESSION['pending_user'])) {
        $error = "Session expired. Please try signing up again.";
    } else {
        $entered_otp = trim($_POST['otp']);
        if ($entered_otp == $_SESSION['signup_otp']) {
            $user_data = $_SESSION['pending_user'];
            $user_id = $user_data['id'];
            $saved = false;

            // Save to Database
            try {
                $pdo = getDBConnection();
                if ($pdo) {
                    $stmt = $pdo->prepare("
                        INSERT INTO users (id, name, email, phone, role, password_hash, created_at)
                        VALUES (?, ?, ?, ?, 'renter', ?, NOW())
                    ");
                    $stmt->execute([
                        $user_id, 
                        $user_data['name'], 
                        $user_data['email'], 
                        $user_data['phone'], 
                        $user_data['password_hash']
                    ]);
                    $saved = true;
                }
            } catch (PDOException $e) {
                $saved = false;
            }

            // Fallback to JSON
            if (!$saved) {
                $users_file = 'users.json';
                $users = file_exists($users_file) ? json_decode(file_get_contents($users_file), true) ?: [] : [];
                $users[] = [
                    'id' => $user_id,
                    'name' => $user_data['name'],
                    'email' => $user_data['email'],
                    'phone' => $user_data['phone'],
                    'role' => 'renter',
                    'password_hash' => $user_data['password_hash'],
                    'created_at' => date('Y-m-d H:i:s')
                ];
                file_put_contents($users_file, json_encode($users, JSON_PRETTY_PRINT));
                $saved = true;
            }

            if ($saved) {
                unset($_SESSION['signup_otp']);
                unset($_SESSION['pending_user']);
                $_SESSION['signup_success'] = true;
                header("Location: login.php");
                exit();
            } else {
                $error = "An error occurred while creating your account.";
            }
        } else {
            $error = "Invalid OTP. Please try again.";
            $show_otp_form = true;
        }
    }
}

// Handle Initial Signup Form
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['email']) && !isset($_POST['otp']) && !isset($_POST['credential'])) {
    $name = trim($_POST['name']);
    $email = trim($_POST['email']);
    $phone = trim($_POST['phone']);
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];
    
    // Validation
    if (empty($name) || empty($email) || empty($phone) || empty($password)) {
        $error = "All fields are required.";
    } elseif (!preg_match('/^[0-9]{10}$/', $phone)) {
        $error = "Please enter a valid 10-digit phone number.";
    } elseif ($password !== $confirm_password) {
        $error = "Passwords do not match.";
    } elseif (strlen($password) < 8) {
        $error = "Password must be at least 8 characters.";
    } elseif (!isset($_POST['terms'])) {
        $error = "You must agree to the Terms of Service.";
    } else {
        // Check for duplicate email
        $duplicate = false;
        try {
            $pdo = getDBConnection();
            if ($pdo) {
                $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
                $stmt->execute([$email]);
                if ($stmt->fetch()) $duplicate = true;
            }
        } catch (PDOException $e) {}

        if (!$duplicate) {
            $users_file = 'users.json';
            if (file_exists($users_file)) {
                $users = json_decode(file_get_contents($users_file), true) ?: [];
                foreach ($users as $u) {
                    if ($u['email'] === $email) { $duplicate = true; break; }
                }
            }
        }

        if ($duplicate) {
            $error = "Email already registered.";
        } else {
            // Generate 4-digit OTP
            $otp = rand(1000, 9999);
            $_SESSION['signup_otp'] = $otp;
            $_SESSION['pending_user'] = [
                'id' => uniqid(),
                'name' => $name,
                'email' => $email,
                'phone' => $phone,
                'password_hash' => password_hash($password, PASSWORD_DEFAULT)
            ];

            // Send Email
            $subject = "RendeX - Email Verification Code";
            $message = "
                <div style='font-family: sans-serif; padding: 20px; text-align: center; background: #f8f8f5;'>
                    <h2 style='color: #1c1c0d;'>Welcome to RendeX!</h2>
                    <p>Use the code below to verify your email address and complete your registration:</p>
                    <h1 style='color: #000; font-size: 40px; letter-spacing: 12px; background: #dfff00; padding: 20px; display: inline-block; border-radius: 12px;'>$otp</h1>
                    <p style='margin-top: 20px; color: #5e5e4a;'>This code will expire in 10 minutes.</p>
                </div>
            ";

            $res = send_smtp_email($email, $subject, $message);
            if ($res === true) {
                $show_otp_form = true;
            } else {
                $error = "Failed to send verification email. Please try again later.";
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
    <title>RendeX - Sign Up</title>
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com" rel="preconnect"/>
    <link crossorigin="" href="https://fonts.gstatic.com" rel="preconnect"/>
    <link href="https://fonts.googleapis.com/css2?family=Spline+Sans:wght@300;400;500;600;700&amp;display=swap" rel="stylesheet"/>
    <link href="https://fonts.googleapis.com/css2?family=Noto+Sans:wght@400;500;700&amp;display=swap" rel="stylesheet"/>
    <!-- Material Symbols -->
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&amp;display=swap" rel="stylesheet"/>
    <!-- Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com?plugins=forms,container-queries"></script>
    <script src="https://accounts.google.com/gsi/client" async defer></script>
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
    <?php if ($redirect): ?>
    <script>
        window.location.href = 'login.php';
    </script>
    <?php endif; ?>
    <style>
        body { font-family: "Spline Sans", sans-serif; }
        .material-symbols-outlined { font-variation-settings: 'FILL' 1, 'wght' 400, 'GRAD' 0, 'opsz' 24 }
    </style>
</head>
<body class="bg-[#f0f2eb] min-h-screen flex items-center justify-center p-4">

    <!-- Main Card -->
    <div class="bg-white rounded-[2rem] shadow-2xl overflow-hidden w-full max-w-[1100px] min-h-[700px] flex flex-col md:flex-row">
        
        <!-- Left Side: Form -->
        <div class="w-full md:w-1/2 p-8 md:p-12 lg:p-16 flex flex-col justify-center relative">
            <!-- Logo -->
            <div class="flex items-center gap-3 mb-10">
                <a href="index.php" class="flex items-center gap-3">
                    <div class="w-10 h-10 bg-black rounded-full flex items-center justify-center text-[#dfff00] font-bold text-xl">R</div>
                    <h2 class="text-2xl font-bold tracking-tight text-black">RendeX</h2>
                </a>
            </div>

            <div class="max-w-md w-full">
                <?php if ($error): ?>
                    <div class="bg-[#fff1f2] text-[#e11d48] p-5 rounded-2xl text-sm mb-6 border border-rose-100 flex items-center gap-3">
                        <span class="material-symbols-outlined text-lg">error</span>
                        <?php echo htmlspecialchars($error); ?>
                    </div>
                <?php endif; ?>

                <?php if ($show_otp_form): ?>
                    <!-- OTP Verification Form -->
                    <div class="text-center">
                        <div class="w-16 h-16 bg-[#dfff00]/20 rounded-full flex items-center justify-center mx-auto mb-6">
                             <span class="material-symbols-outlined text-[#dfff00] text-3xl">mail_lock</span>
                        </div>
                        <h1 class="text-3xl font-bold text-black mb-2">Verify Email</h1>
                        <p class="text-[#8e9080] mb-8 text-sm">We've sent a 4-digit code to <strong><?php echo htmlspecialchars($_SESSION['pending_user']['email']); ?></strong></p>

                        <form method="POST" autocomplete="off">
                            <div class="mb-8">
                                <label class="block text-xs font-bold text-black mb-2 uppercase tracking-widest">Enter Verification Code</label>
                                <input type="text" name="otp" maxlength="4" required
                                       class="w-full bg-[#f6f7f2] border-none rounded-2xl py-5 text-center text-4xl font-black tracking-[0.5em] focus:ring-2 focus:ring-[#dfff00] transition-all"
                                       placeholder="----" oninput="this.value = this.value.replace(/[^0-9]/g, '')">
                            </div>

                            <button type="submit" class="w-full bg-black text-white hover:bg-gray-800 font-bold py-5 rounded-full transition-all shadow-xl shadow-black/20 text-sm tracking-widest uppercase">
                                COMPLETE REGISTRATION
                            </button>

                            <p class="mt-8 text-center text-xs text-[#8e9080]">
                                Didn't receive the code? 
                                <a href="signup.php" class="text-black font-bold hover:underline">Start Over</a>
                            </p>
                        </form>
                    </div>
                <?php else: ?>
                    <h1 class="text-3xl font-bold text-black mb-2">Create Account</h1>
                    <p class="text-[#8e9080] mb-8 text-sm">Join the community and start experiencing more.</p>

                    <form id="signupForm" autocomplete="off" method="POST" novalidate>
                        <!-- Name Input -->
                        <div class="mb-5">
                            <label class="block text-xs font-bold text-black mb-2 ml-1">Full Name</label>
                            <div class="relative">
                                <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none">
                                    <span class="material-symbols-outlined text-gray-400 text-[20px]">badge</span>
                                </div>
                                <input type="text" name="name" id="name" class="w-full bg-[#f6f7f2] border-none rounded-xl py-3.5 pl-11 pr-4 text-sm focus:ring-2 focus:ring-[#dfff00] placeholder-gray-400 transition-all" placeholder="">
                            </div>
                            <p id="name-error" class="text-red-500 text-xs mt-1 hidden font-medium pl-1"></p>
                        </div>

                        <!-- Email Input -->
                        <div class="mb-5">
                            <label class="block text-xs font-bold text-black mb-2 ml-1">Email Address</label>
                            <div class="relative">
                                <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none">
                                    <span class="material-symbols-outlined text-gray-400 text-[20px]">mail</span>
                                </div>
                                <input type="email" name="email" id="email" class="w-full bg-[#f6f7f2] border-none rounded-xl py-3.5 pl-11 pr-4 text-sm focus:ring-2 focus:ring-[#dfff00] placeholder-gray-400 transition-all" placeholder="">
                            </div>
                            <p id="email-error" class="text-red-500 text-xs mt-1 hidden font-medium pl-1"></p>
                        </div>

                        <!-- Phone Input -->
                        <div class="mb-5">
                            <label class="block text-xs font-bold text-black mb-2 ml-1">Phone Number</label>
                            <div class="relative">
                                <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none">
                                    <span class="material-symbols-outlined text-gray-400 text-[20px]">call</span>
                                </div>
                                <input type="tel" name="phone" id="phone" class="w-full bg-[#f6f7f2] border-none rounded-xl py-3.5 pl-11 pr-4 text-sm focus:ring-2 focus:ring-[#dfff00] placeholder-gray-400 transition-all" placeholder="10-digit number" maxlength="10">
                            </div>
                            <p id="phone-error" class="text-red-500 text-xs mt-1 hidden font-medium pl-1"></p>
                        </div>

                        <!-- Password Inputs -->
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-8">
                            <div>
                                <label class="block text-xs font-bold text-black mb-2 ml-1">Password</label>
                                <div class="relative">
                                    <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none">
                                        <span class="material-symbols-outlined text-gray-400 text-[20px]">lock</span>
                                    </div>
                                    <input type="password" name="password" id="password" class="w-full bg-[#f6f7f2] border-none rounded-xl py-3.5 pl-11 pr-12 text-sm focus:ring-2 focus:ring-[#dfff00] placeholder-gray-400 transition-all" placeholder="">
                                    <button type="button" onclick="togglePassword('password', this)" class="absolute inset-y-0 right-0 pr-4 flex items-center text-gray-400 hover:text-black focus:outline-none">
                                        <span class="material-symbols-outlined text-[20px]">visibility</span>
                                    </button>
                                </div>
                                <p id="password-error" class="text-red-500 text-xs mt-1 hidden font-medium pl-1"></p>
                            </div>
                            <div>
                                <label class="block text-xs font-bold text-black mb-2 ml-1">Confirm</label>
                                <div class="relative">
                                    <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none">
                                        <span class="material-symbols-outlined text-gray-400 text-[20px]">lock_reset</span>
                                    </div>
                                    <input type="password" name="confirm_password" id="confirm_password" class="w-full bg-[#f6f7f2] border-none rounded-xl py-3.5 pl-11 pr-12 text-sm focus:ring-2 focus:ring-[#dfff00] placeholder-gray-400 transition-all" placeholder="">
                                    <button type="button" onclick="togglePassword('confirm_password', this)" class="absolute inset-y-0 right-0 pr-4 flex items-center text-gray-400 hover:text-black focus:outline-none">
                                        <span class="material-symbols-outlined text-[20px]">visibility</span>
                                    </button>
                                </div>
                                <p id="confirm_password-error" class="text-red-500 text-xs mt-1 hidden font-medium pl-1"></p>
                            </div>
                        </div>

                        <!-- Terms -->
                        <div class="mb-8">
                            <div class="flex items-center">
                                <input id="terms" name="terms" type="checkbox" class="w-4 h-4 text-black border-gray-300 rounded focus:ring-[#dfff00]">
                                <label for="terms" class="ml-2 block text-xs font-bold text-[#8e9080]">I agree to the <a href="#" class="underline hover:text-black">Terms of Service</a> and <a href="#" class="underline hover:text-black">Privacy Policy</a></label>
                            </div>
                            <p id="terms-error" class="text-red-500 text-xs mt-1 hidden font-medium pl-1"></p>
                        </div>

                        <!-- Submit Button -->
                        <button type="submit" class="w-full bg-black text-white hover:bg-gray-800 font-bold py-4 rounded-full transition-colors shadow-lg shadow-black/20 text-sm tracking-wide">
                            CREATE ACCOUNT
                        </button>
                    </form>
                <?php endif; ?>

                    <!-- Divider -->
                    <div class="relative flex py-6 items-center">
                        <div class="flex-grow border-t border-gray-200"></div>
                        <span class="flex-shrink-0 mx-4 text-gray-400 text-xs">OR</span>
                        <div class="flex-grow border-t border-gray-200"></div>
                    </div>

                    <!-- Custom Google Sign Up Button -->
                    <button onclick="handleGoogleSignIn()" type="button" class="w-full bg-white border-2 border-gray-300 hover:border-gray-400 text-gray-700 font-semibold py-4 rounded-full transition-all flex items-center justify-center gap-3 text-sm shadow-sm hover:shadow-md">
                        <svg width="20" height="20" viewBox="0 0 20 20" fill="none" xmlns="http://www.w3.org/2000/svg">
                            <path d="M19.9895 10.1871C19.9895 9.36767 19.9214 8.76973 19.7742 8.14966H10.1992V11.848H15.8195C15.7062 12.7671 15.0943 14.1512 13.7346 15.0813L13.7155 15.2051L16.7429 17.4969L16.9527 17.5174C18.879 15.7789 19.9895 13.221 19.9895 10.1871Z" fill="#4285F4"/>
                            <path d="M10.1993 19.9313C12.9527 19.9313 15.2643 19.0454 16.9527 17.5174L13.7346 15.0813C12.8734 15.6682 11.7176 16.0779 10.1993 16.0779C7.50243 16.0779 5.21352 14.3395 4.39759 11.9366L4.27799 11.9466L1.13003 14.3273L1.08887 14.4391C2.76588 17.6945 6.21061 19.9313 10.1993 19.9313Z" fill="#34A853"/>
                            <path d="M4.39748 11.9366C4.18219 11.3166 4.05759 10.6521 4.05759 9.96565C4.05759 9.27909 4.18219 8.61473 4.38615 7.99466L4.38045 7.8626L1.19304 5.44366L1.08875 5.49214C0.397576 6.84305 0.000976562 8.36008 0.000976562 9.96565C0.000976562 11.5712 0.397576 13.0882 1.08875 14.4391L4.39748 11.9366Z" fill="#FBBC05"/>
                            <path d="M10.1993 3.85336C12.1142 3.85336 13.406 4.66168 14.1425 5.33717L17.0207 2.59107C15.253 0.985496 12.9527 0 10.1993 0C6.2106 0 2.76588 2.23672 1.08887 5.49214L4.38626 7.99466C5.21352 5.59183 7.50242 3.85336 10.1993 3.85336Z" fill="#EB4335"/>
                        </svg>
                        Sign up with Google
                    </button>

                    <!-- Login Link -->
                    <p class="mt-8 text-center text-xs text-[#8e9080]">
                        Already have an account? <a href="login.php" class="text-black font-bold hover:underline">Log In</a>
                    </p>
                </form>

                    <div id="g_id_onload"
                         data-client_id="6682512066-vgnsqcpb1p7ff5bfv78kp4c0mv5pu9tv.apps.googleusercontent.com"
                         data-callback="handleCredentialResponse"
                         data-auto_prompt="false">
                    </div>

                    <!-- Google Sign In Script -->
                    <script src="https://accounts.google.com/gsi/client" async defer></script>
                    <script>
                        function togglePassword(inputId, btn) {
                            const input = document.getElementById(inputId);
                            const icon = btn.querySelector('.material-symbols-outlined');
                            
                            if (input.type === 'password') {
                                input.type = 'text';
                                icon.textContent = 'visibility_off';
                            } else {
                                input.type = 'password';
                                icon.textContent = 'visibility';
                            }
                        }

                        function handleGoogleSignIn() {
                            const client = google.accounts.oauth2.initCodeClient({
                                client_id: '6682512066-vgnsqcpb1p7ff5bfv78kp4c0mv5pu9tv.apps.googleusercontent.com',
                                scope: 'email profile openid',
                                ux_mode: 'popup',
                                callback: (response) => {
                                    // Send the authorization code to backend
                                    const form = document.createElement('form');
                                    form.method = 'POST';
                                    form.action = 'google-callback.php';

                                    const codeField = document.createElement('input');
                                    codeField.type = 'hidden';
                                    codeField.name = 'code';
                                    codeField.value = response.code;

                                    const sourceField = document.createElement('input');
                                    sourceField.type = 'hidden';
                                    sourceField.name = 'source';
                                    sourceField.value = 'signup';

                                    form.appendChild(codeField);
                                    form.appendChild(sourceField);
                                    document.body.appendChild(form);
                                    form.submit();
                                },
                            });
                            client.requestCode();
                        }

                        function handleCredentialResponse(response) {
                            const form = document.createElement('form');
                            form.method = 'POST';
                            form.action = 'google-callback.php';

                            const credentialField = document.createElement('input');
                            credentialField.type = 'hidden';
                            credentialField.name = 'credential';
                            credentialField.value = response.credential;

                            form.appendChild(credentialField);
                            document.body.appendChild(form);
                            form.submit();
                        }
                    </script>
            </div>
        </div>

        <!-- Right Side: Visual -->
        <div class="hidden md:flex w-1/2 bg-[#1e2015] relative overflow-hidden flex-col justify-center p-12 text-white">
            <!-- Background Image Overlay -->
            <div class="absolute inset-0 z-0 opacity-20">
                <img src="https://images.unsplash.com/photo-1504280390367-361c6d9f38f4?q=80&w=1000&auto=format&fit=crop" class="w-full h-full object-cover grayscale mix-blend-luminosity">
            </div>
            
            <!-- Content -->
            <div class="relative z-10 max-w-sm mt-auto mb-20">
                <!-- Icon Circle -->
                <div class="w-16 h-16 rounded-full bg-[#3d402b]/80 flex items-center justify-center mb-8 backdrop-blur-sm border border-white/5">
                    <span class="material-symbols-outlined text-[#dfff00] text-3xl">rocket_launch</span>
                </div>

                <h2 class="text-4xl font-bold leading-tight mb-4 text-white">
                    Start Your<br>
                    <span class="text-[#dfff00]">Journey.</span>
                </h2>
                <p class="text-gray-400 text-sm leading-relaxed mb-8">
                    Whether you are looking to rent the perfect gear or monetize your own, RendeX is your secure platform.
                </p>

                <!-- Features List -->
                 <ul class="space-y-3">
                    <li class="flex items-center gap-3 text-sm text-gray-300">
                        <span class="material-symbols-outlined text-[#dfff00] text-lg">check_circle</span>
                        Verified Users & Secure Payments
                    </li>
                    <li class="flex items-center gap-3 text-sm text-gray-300">
                        <span class="material-symbols-outlined text-[#dfff00] text-lg">check_circle</span>
                        Insurance Protection Included
                    </li>
                    <li class="flex items-center gap-3 text-sm text-gray-300">
                        <span class="material-symbols-outlined text-[#dfff00] text-lg">check_circle</span>
                        24/7 Customer Support
                    </li>
                </ul>
            </div>

            <!-- Decorative Circles -->
            <div class="absolute -top-20 -right-20 w-80 h-80 rounded-full border-[30px] border-[#dfff00]/5 blur-3xl"></div>
        </div>

    </div>


    <!-- Live Validation Script -->
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const form = document.getElementById('signupForm');
            const inputs = {
                name: document.getElementById('name'),
                email: document.getElementById('email'),
                phone: document.getElementById('phone'),
                password: document.getElementById('password'),
                confirm_password: document.getElementById('confirm_password'),
                terms: document.getElementById('terms')
            };

            // Validators
            const validators = {
                name: (val) => {
                    if (!val.trim()) return 'Name is required';
                    if (!/^[a-zA-Z\s]+$/.test(val)) return 'Only alphabets are allowed';
                    return '';
                },
                email: (val) => {
                    if (!val.trim()) return 'Email is required';
                    const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
                    if (!emailRegex.test(val)) return 'Enter a valid email address';
                    return '';
                },
                phone: (val) => {
                    if (!val) return 'Phone number is required';
                    if (val.length !== 10) return 'Phone number must be exactly 10 digits';
                    return '';
                },
                password: (val) => {
                    if (!val) return 'Password is required';
                    if (val.length < 8) return 'Password must be at least 8 characters';
                    return '';
                },
                confirm_password: (val) => {
                    if (!val) return 'Confirm Password is required';
                    if (val !== inputs.password.value) return 'Passwords do not match';
                    return '';
                },
                terms: (checked) => checked ? '' : 'You must agree to the Terms of Service'
            };

            function showError(input, message) {
                const errorId = input.id + '-error';
                const errorEl = document.getElementById(errorId);
                if (errorEl) {
                    errorEl.textContent = message;
                    errorEl.classList.remove('hidden');
                }
                
                if (input.type !== 'checkbox') {
                    input.classList.add('ring-2', 'ring-red-500', 'focus:ring-red-500');
                    input.classList.remove('focus:ring-[#dfff00]');
                }
            }

            function clearError(input) {
                const errorId = input.id + '-error';
                const errorEl = document.getElementById(errorId);
                if (errorEl) {
                    errorEl.classList.add('hidden');
                    errorEl.textContent = '';
                }

                if (input.type !== 'checkbox') {
                    input.classList.remove('ring-2', 'ring-red-500', 'focus:ring-red-500');
                    input.classList.add('focus:ring-[#dfff00]');
                }
            }

            function validateInput(input, key) {
                const val = input.type === 'checkbox' ? input.checked : input.value;
                const error = validators[key](val);
                
                if (error) {
                    showError(input, error);
                    return false;
                } else {
                    clearError(input);
                    return true;
                }
            }

            // --- Event Listeners ---

            // Name: Input masking (Alphabets only) + Validation
            inputs.name.addEventListener('input', function() {
                this.value = this.value.replace(/[^a-zA-Z\s]/g, ''); // Remove non-alphabets
                validateInput(this, 'name');
            });
            inputs.name.addEventListener('blur', function() { validateInput(this, 'name'); });

            // Phone: Input masking (Numbers only) + Validation
            inputs.phone.addEventListener('input', function() {
                this.value = this.value.replace(/[^0-9]/g, ''); // Remove non-numbers
                if (this.value.length > 10) this.value = this.value.slice(0, 10); // Limit to 10
                validateInput(this, 'phone');
            });
            inputs.phone.addEventListener('blur', function() { validateInput(this, 'phone'); });

            // Email, Password, Confirm Password: Real-time validation
            ['email', 'password', 'confirm_password'].forEach(key => {
                const input = inputs[key];
                input.addEventListener('input', () => validateInput(input, key));
                input.addEventListener('blur', () => validateInput(input, key));
            });

            // Terms
            inputs.terms.addEventListener('change', () => validateInput(inputs.terms, 'terms'));

            // Re-validate confirm password when password changes
            inputs.password.addEventListener('input', () => {
                if (inputs.confirm_password.value) {
                    validateInput(inputs.confirm_password, 'confirm_password');
                }
            });

            // Form Submit Logic
            form.addEventListener('submit', (e) => {
                let isValid = true;
                Object.keys(inputs).forEach(key => {
                    const valid = validateInput(inputs[key], key);
                    if (!valid) isValid = false;
                });

                if (!isValid) {
                    e.preventDefault();
                }
            });
        });
    </script>
</body>
</html>
