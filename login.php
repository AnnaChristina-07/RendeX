<?php
ob_start();
session_start();

// Include database connection
require_once 'config/database.php';

$error = '';
$success_message = '';

// Check if user just signed up
if (isset($_SESSION['signup_success'])) {
    $success_message = "Account created successfully! Please log in.";
    unset($_SESSION['signup_success']);
}

if (isset($_GET['error'])) {
    $error = htmlspecialchars($_GET['error']);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && !isset($_POST['credential'])) {
    $email = trim($_POST['email']);
    $password = $_POST['password'];
    
    // Validation
    if (empty($email) || empty($password)) {
        $error = "Please enter both email and password.";
    } else {
        $user_found = false;
        $user = null;
        
        // Try database first
        try {
            $pdo = getDBConnection();
            if ($pdo) {
                $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ?");
                $stmt->execute([$email]);
                $user = $stmt->fetch();
                
                if ($user && password_verify($password, $user['password_hash'])) {
                    $user_found = true;
                } elseif ($user) {
                    $error = "Invalid email or password.";
                }
            }
        } catch (PDOException $e) {
            // Fallback to JSON
        }
        
        // Fallback: Check JSON file
        if (!$user_found) {
            // Reset error so we can check JSON without being blocked by previous DB failure message
            $error = ''; 
            $users_file = 'users.json';
            if (file_exists($users_file)) {
                $json_data = file_get_contents($users_file);
                $users = json_decode($json_data, true) ?: [];
                
                foreach ($users as $u) {
                    if ($u['email'] === $email) {
                        if (password_verify($password, $u['password_hash'])) {
                            $user_found = true;
                            $user = $u;
                            break;
                        } else {
                            $error = "Invalid email or password.";
                            break;
                        }
                    }
                }
                
                if (!$user_found && empty($error)) {
                    $error = "User not found. Please sign up.";
                }
            } else {
                // Database failed and no JSON, set error
                if (empty($error)) {
                    $error = "User not found. Please sign up.";
                }
            }
        }
        
        // Successful login
        if ($user_found && $user) {
            session_regenerate_id(true);
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['user_email'] = $user['email'];
            $_SESSION['user_name'] = ucwords($user['name']);
            
            // Role-based Redirection
            if ($user['email'] === 'annachristina2005@gmail.com') {
                header("Location: admin_dashboard.php");
                exit();
            } else {
                header("Location: dashboard.php");
                exit();
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
    <title>RendeX - Login</title>
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
              "primary": "#dfff00", /* Neon/Lime Yellow as per image */
              "background-light": "#f8f8f5",
              "background-dark": "#23220f",
              "surface-light": "#ffffff",
              "surface-dark": "#2d2c18",
              "text-main": "#1c1c0d",
              "text-muted": "#5e5e4a",
              "card-dark": "#1e2019" /* Dark/Olive tone from image */
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
    <div class="bg-white rounded-[2rem] shadow-2xl overflow-hidden w-full max-w-[1100px] min-h-[600px] flex flex-col md:flex-row">
        
        <!-- Left Side: Form -->
        <div class="w-full md:w-1/2 p-8 md:p-12 lg:p-16 flex flex-col justify-center relative">
            <!-- Logo -->
            <div class="flex items-center gap-3 mb-12">
                <a href="index.php" class="flex items-center gap-3">
                    <div class="w-10 h-10 bg-black rounded-full flex items-center justify-center text-[#dfff00] font-bold text-xl">R</div>
                    <h2 class="text-2xl font-bold tracking-tight text-black">RendeX</h2>
                </a>
            </div>

            <div class="max-w-md w-full">
                <h1 class="text-3xl font-bold text-black mb-2">Welcome Back</h1>
                <p class="text-[#8e9080] mb-8 text-sm">Enter your credentials to access your account.</p>

                <?php if ($success_message): ?>
                    <div class="bg-green-50 text-green-600 p-4 rounded-xl text-sm mb-6">
                        <?php echo htmlspecialchars($success_message); ?>
                    </div>
                <?php endif; ?>

                <?php if ($error): ?>
                    <div class="bg-[#fff1f2] text-[#e11d48] p-5 rounded-2xl text-sm mb-6">
                        <?php echo htmlspecialchars($error); ?>
                    </div>
                <?php endif; ?>

                <form autocomplete="off" method="POST">
                    <!-- Email Input -->
                    <div class="mb-5">
                        <label class="block text-xs font-bold text-black mb-2 ml-1">Email or Username</label>
                        <div class="relative">
                            <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none">
                                <span class="material-symbols-outlined text-gray-400 text-[20px]">person</span>
                            </div>
                            <input type="email" name="email" autocomplete="new-password" class="w-full bg-[#f6f7f2] border-none rounded-xl py-3.5 pl-11 pr-4 text-sm focus:ring-2 focus:ring-[#dfff00] placeholder-gray-400 transition-all" placeholder="">
                        </div>
                    </div>

                    <!-- Password Input -->
                    <div class="mb-6">
                        <label class="block text-xs font-bold text-black mb-2 ml-1">Password</label>
                        <div class="relative">
                            <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none">
                                <span class="material-symbols-outlined text-gray-400 text-[20px]">lock</span>
                            </div>
                            <input type="password" name="password" autocomplete="new-password" class="w-full bg-[#f6f7f2] border-none rounded-xl py-3.5 pl-11 pr-4 text-sm focus:ring-2 focus:ring-[#dfff00] placeholder-gray-400 transition-all" placeholder="">
                        </div>
                        <div class="mt-2 text-right">
                             <a href="forgot-password.php" class="text-xs font-bold text-[#8e9080] hover:text-black">Forgot Password?</a>
                        </div>
                    </div>

                    <!-- Remember Me -->
                    <div class="flex items-center mb-8">
                        <input id="remember" type="checkbox" class="w-4 h-4 text-black border-gray-300 rounded focus:ring-[#dfff00]">
                        <label for="remember" class="ml-2 block text-xs font-bold text-[#8e9080]">Remember me for 30 days</label>
                    </div>

                    <!-- Submit Button -->
                    <button type="submit" class="w-full bg-black text-white hover:bg-gray-800 font-bold py-4 rounded-full transition-colors shadow-lg shadow-black/20 text-sm tracking-wide">
                        LOG IN
                    </button>

                    <!-- Divider -->
                    <div class="relative flex py-6 items-center">
                        <div class="flex-grow border-t border-gray-200"></div>
                        <span class="flex-shrink-0 mx-4 text-gray-400 text-xs">OR</span>
                        <div class="flex-grow border-t border-gray-200"></div>
                    </div>

                    <!-- Custom Google Sign In Button -->
                    <button onclick="handleGoogleSignIn()" type="button" class="w-full bg-white border-2 border-gray-300 hover:border-gray-400 text-gray-700 font-semibold py-4 rounded-full transition-all flex items-center justify-center gap-3 text-sm shadow-sm hover:shadow-md">
                        <svg width="20" height="20" viewBox="0 0 20 20" fill="none" xmlns="http://www.w3.org/2000/svg">
                            <path d="M19.9895 10.1871C19.9895 9.36767 19.9214 8.76973 19.7742 8.14966H10.1992V11.848H15.8195C15.7062 12.7671 15.0943 14.1512 13.7346 15.0813L13.7155 15.2051L16.7429 17.4969L16.9527 17.5174C18.879 15.7789 19.9895 13.221 19.9895 10.1871Z" fill="#4285F4"/>
                            <path d="M10.1993 19.9313C12.9527 19.9313 15.2643 19.0454 16.9527 17.5174L13.7346 15.0813C12.8734 15.6682 11.7176 16.0779 10.1993 16.0779C7.50243 16.0779 5.21352 14.3395 4.39759 11.9366L4.27799 11.9466L1.13003 14.3273L1.08887 14.4391C2.76588 17.6945 6.21061 19.9313 10.1993 19.9313Z" fill="#34A853"/>
                            <path d="M4.39748 11.9366C4.18219 11.3166 4.05759 10.6521 4.05759 9.96565C4.05759 9.27909 4.18219 8.61473 4.38615 7.99466L4.38045 7.8626L1.19304 5.44366L1.08875 5.49214C0.397576 6.84305 0.000976562 8.36008 0.000976562 9.96565C0.000976562 11.5712 0.397576 13.0882 1.08875 14.4391L4.39748 11.9366Z" fill="#FBBC05"/>
                            <path d="M10.1993 3.85336C12.1142 3.85336 13.406 4.66168 14.1425 5.33717L17.0207 2.59107C15.253 0.985496 12.9527 0 10.1993 0C6.2106 0 2.76588 2.23672 1.08887 5.49214L4.38626 7.99466C5.21352 5.59183 7.50242 3.85336 10.1993 3.85336Z" fill="#EB4335"/>
                        </svg>
                        Sign in with Google
                    </button>
                </form>

                <div id="g_id_onload"
                     data-client_id="6682512066-vgnsqcpb1p7ff5bfv78kp4c0mv5pu9tv.apps.googleusercontent.com"
                     data-callback="handleCredentialResponse"
                     data-auto_prompt="false">
                </div>

                <!-- Google Sign In Script -->
                <script src="https://accounts.google.com/gsi/client" async defer></script>
                <script>
                    function handleGoogleSignIn() {
                        const client = google.accounts.oauth2.initTokenClient({
                            client_id: '6682512066-vgnsqcpb1p7ff5bfv78kp4c0mv5pu9tv.apps.googleusercontent.com',
                            scope: 'email profile openid',
                            callback: (response) => {
                                if (response.access_token) {
                                    fetch('https://www.googleapis.com/oauth2/v3/userinfo', {
                                        headers: { 'Authorization': 'Bearer ' + response.access_token }
                                    })
                                    .then(res => res.json())
                                    .then(data => {
                                        const form = document.createElement('form');
                                        form.method = 'POST';
                                        form.action = 'google-callback.php';

                                        const inputName = document.createElement('input');
                                        inputName.type = 'hidden';
                                        inputName.name = 'google_name';
                                        inputName.value = data.name;

                                        const inputEmail = document.createElement('input');
                                        inputEmail.type = 'hidden';
                                        inputEmail.name = 'google_email';
                                        inputEmail.value = data.email;

                                        const inputId = document.createElement('input');
                                        inputId.type = 'hidden';
                                        inputId.name = 'google_id';
                                        inputId.value = data.sub;

                                        const inputPic = document.createElement('input');
                                        inputPic.type = 'hidden';
                                        inputPic.name = 'google_picture';
                                        inputPic.value = data.picture;

                                        form.appendChild(inputName);
                                        form.appendChild(inputEmail);
                                        form.appendChild(inputId);
                                        form.appendChild(inputPic);

                                        document.body.appendChild(form);
                                        form.submit();
                                    });
                                }
                            },
                        });
                        client.requestAccessToken();
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
                    
                    <!-- Register Link -->
                    <p class="mt-8 text-center text-xs text-[#8e9080]">
                        Don't have an account? <a href="signup.php" class="text-black font-bold hover:underline">Register New Account</a>
                    </p>
                </form>
            </div>
        </div>

        <!-- Right Side: Visual -->
        <div class="hidden md:flex w-1/2 bg-[#1e2015] relative overflow-hidden flex-col justify-center p-12 text-white">
            <!-- Background Image Overlay -->
            <div class="absolute inset-0 z-0 opacity-20">
                <img src="https://images.unsplash.com/photo-1516035069371-29a1b244cc32?q=80&w=1000&auto=format&fit=crop" class="w-full h-full object-cover grayscale mix-blend-luminosity">
            </div>
            


            <!-- Content -->
            <div class="relative z-10 max-w-sm mt-auto mb-20">
                <!-- Icon Circle -->
                <div class="w-16 h-16 rounded-full bg-[#3d402b]/80 flex items-center justify-center mb-8 backdrop-blur-sm border border-white/5">
                    <span class="material-symbols-outlined text-[#dfff00] text-3xl">key</span>
                </div>

                <h2 class="text-4xl font-bold leading-tight mb-4 text-white">
                    Seamless Rental<br>
                    <span class="text-[#dfff00]">Management.</span>
                </h2>
                <p class="text-gray-400 text-sm leading-relaxed mb-8">
                    Connect with renters, manage your inventory, and track your earnings securely in one unified platform.
                </p>

                <!-- Users -->
                <div class="flex items-center gap-4">
                    <div class="flex -space-x-3">
                        <img class="w-8 h-8 rounded-full border-2 border-[#1e2015]" src="https://i.pravatar.cc/100?img=33" alt="User">
                        <img class="w-8 h-8 rounded-full border-2 border-[#1e2015]" src="https://i.pravatar.cc/100?img=47" alt="User">
                        <img class="w-8 h-8 rounded-full border-2 border-[#1e2015]" src="https://i.pravatar.cc/100?img=12" alt="User">
                         <div class="w-8 h-8 rounded-full border-2 border-[#1e2015] bg-[#dfff00] flex items-center justify-center text-[10px] font-bold text-black">
                            +2k
                        </div>
                    </div>
                    <span class="text-xs font-bold text-white">2,000+ <span class="font-normal text-gray-400">Active users</span></span>
                </div>
            </div>

            <!-- Decorative Circles -->
            <div class="absolute -bottom-20 -right-20 w-80 h-80 rounded-full border-[30px] border-[#dfff00]/5 blur-3xl"></div>
        </div>

    </div>

</body>
</html>
