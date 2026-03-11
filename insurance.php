<?php
session_start();
?>
<!DOCTYPE html>
<html class="light" lang="en">
<head>
    <meta charset="utf-8"/>
    <meta content="width=device-width, initial-scale=1.0" name="viewport"/>
    <title>Insurance - RendeX</title>
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
<div class="relative flex min-h-screen w-full flex-col overflow-x-hidden">
    <!-- Header Placeholder -->
    <header class="sticky top-0 z-50 flex items-center justify-between border-b border-[#e9e8ce] dark:border-[#3e3d2a] bg-background-light/95 dark:bg-background-dark/95 backdrop-blur-sm px-6 py-4 lg:px-10">
        <div class="flex items-center gap-8 w-full max-w-[1400px] mx-auto">
            <a href="index.php" class="flex items-center gap-2 text-text-main dark:text-white">
                <div class="size-8 text-primary">
                    <svg class="w-full h-full" fill="none" viewBox="0 0 48 48" xmlns="http://www.w3.org/2000/svg">
                        <ellipse cx="14" cy="24" rx="10" ry="20" fill="currentColor" />
                        <ellipse cx="24" cy="24" rx="10" ry="20" fill="currentColor" />
                        <ellipse cx="34" cy="24" rx="10" ry="20" fill="currentColor" />
                    </svg>
                </div>
                <h2 class="text-xl font-bold tracking-tight">RendeX</h2>
            </a>
            <div class="hidden lg:flex items-center gap-6 ml-auto">
                <nav class="flex gap-6">
                    <a class="text-sm font-medium hover:text-primary transition-colors" href="index.php#how-it-works">How it Works</a>
                    <?php if (isset($_SESSION['user_id'])): ?>
                        <a class="text-sm font-medium hover:text-primary transition-colors" href="dashboard.php">Dashboard</a>
                    <?php else: ?>
                        <a class="text-sm font-medium hover:text-primary transition-colors" href="login.php">Login</a>
                        <a class="text-sm font-medium hover:text-primary transition-colors" href="signup.php">Sign Up</a>
                    <?php endif; ?>
                </nav>
            </div>
        </div>
    </header>

    <main class="flex-1 w-full max-w-[1400px] mx-auto px-4 md:px-10 py-12">
        <div class="bg-surface-light dark:bg-surface-dark border border-[#e9e8ce] dark:border-[#3e3d2a] rounded-2xl p-8 md:p-12 shadow-sm">
            <h1 class="text-3xl md:text-5xl font-black mb-6">Insurance</h1>
            <div class="prose dark:prose-invert max-w-none text-text-muted dark:text-gray-300">
                <p class="text-lg">This page is currently under construction. Stay tuned for updates regarding <strong>Insurance</strong>.</p>
                <div class="mt-8">
                    <a href="index.php" class="inline-block bg-primary text-black font-bold px-6 py-3 rounded-full hover:bg-yellow-400 transition-colors">Return Home</a>
                </div>
            </div>
        </div>
    </main>

    <!-- Footer -->
    <footer class="bg-surface-light dark:bg-surface-dark border-t border-[#e9e8ce] dark:border-[#3e3d2a] pt-16 pb-8 px-4 md:px-10 mt-auto">
        <div class="max-w-[1400px] mx-auto">
            <div class="grid grid-cols-1 md:grid-cols-4 gap-12 mb-12">
                <div class="col-span-1 md:col-span-1">
                    <div class="flex items-center gap-2 text-text-main dark:text-white mb-6">
                        <div class="size-6 text-primary">
                            <svg class="w-full h-full" fill="none" viewBox="0 0 48 48" xmlns="http://www.w3.org/2000/svg">
                                <ellipse cx="14" cy="24" rx="10" ry="20" fill="currentColor" />
                                <ellipse cx="24" cy="24" rx="10" ry="20" fill="currentColor" />
                                <ellipse cx="34" cy="24" rx="10" ry="20" fill="currentColor" />
                            </svg>
                        </div>
                        <h2 class="text-lg font-bold tracking-tight">RendeX</h2>
                    </div>
                    <p class="text-sm text-text-muted dark:text-gray-400">The safest peer-to-peer rental marketplace. Own less, experience more.</p>
                </div>
                <div>
                    <h4 class="font-bold mb-4">RendeX</h4>
                    <ul class="space-y-3 text-sm text-text-muted dark:text-gray-400">
                        <li><a class="hover:text-primary transition-colors" href="about.php">About Us</a></li>
                        <li><a class="hover:text-primary transition-colors" href="careers.php">Careers</a></li>
                        <li><a class="hover:text-primary transition-colors" href="signup.php">Become a Partner</a></li>
                        <li><a class="hover:text-primary transition-colors" href="press.php">Press</a></li>
                        <li><a class="hover:text-primary transition-colors" href="blog.php">Blog</a></li>
                    </ul>
                </div>
                <div>
                    <h4 class="font-bold mb-4">Support</h4>
                    <ul class="space-y-3 text-sm text-text-muted dark:text-gray-400">
                        <li><a class="hover:text-primary transition-colors" href="help.php">Help Center</a></li>
                        <li><a class="hover:text-primary transition-colors" href="safety.php">Safety &amp; Trust</a></li>
                        <li><a class="hover:text-primary transition-colors" href="insurance.php">Insurance</a></li>
                        <li><a class="hover:text-primary transition-colors" href="disputes.php">Dispute Resolution</a></li>
                    </ul>
                </div>
                <div>
                    <h4 class="font-bold mb-4">Legal</h4>
                    <ul class="space-y-3 text-sm text-text-muted dark:text-gray-400">
                        <li><a class="hover:text-primary transition-colors" href="terms.php">Terms of Service</a></li>
                        <li><a class="hover:text-primary transition-colors" href="privacy.php">Privacy Policy</a></li>
                        <li><a class="hover:text-primary transition-colors" href="cookie.php">Cookie Policy</a></li>
                    </ul>
                </div>
            </div>
            <div class="flex flex-col md:flex-row justify-between items-center pt-8 border-t border-[#e9e8ce] dark:border-[#3e3d2a] gap-4">
                <p class="text-sm text-text-muted dark:text-gray-500">© 2024 RendeX Inc. All rights reserved.</p>
                <div class="flex gap-4">
                    <a class="text-text-muted hover:text-primary transition-colors" href="index.php"><span class="material-symbols-outlined">public</span></a>
                    <a class="text-text-muted hover:text-primary transition-colors" href="mailto:support@rendex.com"><span class="material-symbols-outlined">alternate_email</span></a>
                </div>
            </div>
        </div>
    </footer>
</div>
</body>
</html>
