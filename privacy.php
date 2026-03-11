<?php
session_start();
?>
<!DOCTYPE html>
<html class="light" lang="en">
<head>
    <meta charset="utf-8"/>
    <meta content="width=device-width, initial-scale=1.0" name="viewport"/>
    <title>Privacy Policy - RendeX</title>
    <link href="https://fonts.googleapis.com" rel="preconnect"/>
    <link crossorigin="" href="https://fonts.gstatic.com" rel="preconnect"/>
    <link href="https://fonts.googleapis.com/css2?family=Spline+Sans:wght@300;400;500;600;700&display=swap" rel="stylesheet"/>
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
              "background-dark": "#23220f",
              "surface-light": "#ffffff",
              "surface-dark": "#2d2c18",
              "text-main": "#1c1c0d",
              "text-muted": "#5e5e4a",
            },
          },
        },
      }
    </script>
    <style>body { font-family: "Spline Sans", sans-serif; }</style>
</head>
<body class="bg-background-light dark:bg-background-dark text-text-main dark:text-white">
<div class="flex min-h-screen flex-col">

    <!-- Header -->
    <header class="sticky top-0 z-50 border-b border-[#e9e8ce] dark:border-[#3e3d2a] bg-background-light/95 dark:bg-background-dark/95 backdrop-blur-sm px-6 py-4 lg:px-10">
        <div class="flex items-center w-full max-w-[1100px] mx-auto">
            <a href="index.php" class="flex items-center gap-2 text-text-main dark:text-white">
                <div class="size-7 text-primary">
                    <svg class="w-full h-full" fill="none" viewBox="0 0 48 48" xmlns="http://www.w3.org/2000/svg">
                        <ellipse cx="14" cy="24" rx="10" ry="20" fill="currentColor"/>
                        <ellipse cx="24" cy="24" rx="10" ry="20" fill="currentColor"/>
                        <ellipse cx="34" cy="24" rx="10" ry="20" fill="currentColor"/>
                    </svg>
                </div>
                <span class="text-lg font-bold tracking-tight">RendeX</span>
            </a>
            <div class="ml-auto flex items-center gap-6">
                <a href="index.php" class="text-sm font-medium text-text-muted hover:text-primary transition-colors">Home</a>
                <?php if (isset($_SESSION['user_id'])): ?>
                    <a href="dashboard.php" class="text-sm font-medium hover:text-primary transition-colors">Dashboard</a>
                <?php else: ?>
                    <a href="login.php" class="text-sm font-medium hover:text-primary transition-colors">Login</a>
                <?php endif; ?>
            </div>
        </div>
    </header>

    <!-- Page Content -->
    <div class="flex-1 w-full max-w-[1100px] mx-auto px-6 lg:px-10 py-10 space-y-6">

        <!-- Compact Page Title -->
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
            <div>
                <div class="inline-flex items-center gap-1.5 bg-black text-primary text-[10px] font-black px-3 py-1 rounded-full mb-3 tracking-wider uppercase">
                    <span class="material-symbols-outlined text-xs">shield</span> Legal
                </div>
                <h1 class="text-3xl md:text-4xl font-black tracking-tight mb-1">Privacy Policy</h1>
                <p class="text-text-muted dark:text-gray-400 text-sm">Effective March 2026 · Last updated March 2026</p>
            </div>
            <a href="index.php" class="inline-flex items-center gap-2 text-sm font-bold text-text-muted hover:text-primary transition-colors shrink-0">
                <span class="material-symbols-outlined text-sm">arrow_back</span> Return Home
            </a>
        </div>

        <!-- BANNER 1: What we collect & how we use it -->
        <div class="bg-black rounded-2xl overflow-hidden relative">
            <div class="absolute top-0 right-0 w-72 h-72 bg-primary/8 rounded-full blur-3xl pointer-events-none"></div>

            <!-- Banner header -->
            <div class="px-8 pt-8 pb-5 border-b border-white/10 relative z-10">
                <div class="flex items-center gap-3">
                    <span class="w-8 h-8 rounded-full bg-primary flex items-center justify-center shrink-0">
                        <span class="material-symbols-outlined text-black text-base">privacy_tip</span>
                    </span>
                    <div>
                        <p class="text-[10px] font-black text-primary uppercase tracking-widest">Part 1 of 2</p>
                        <h2 class="text-lg font-black text-white">What We Collect & How We Use It</h2>
                    </div>
                </div>
            </div>

            <!-- Banner body: 2 columns -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-0 relative z-10">
                <!-- Left: What we collect -->
                <div class="p-8 md:border-r border-white/10">
                    <p class="text-[10px] font-black text-primary uppercase tracking-widest mb-5">Data We Collect</p>
                    <div class="space-y-4">
                        <div class="flex items-start gap-3">
                            <span class="w-7 h-7 rounded-lg bg-white/8 flex items-center justify-center shrink-0">
                                <span class="material-symbols-outlined text-primary text-sm">person</span>
                            </span>
                            <div>
                                <p class="text-white text-sm font-bold">Account Info</p>
                                <p class="text-gray-400 text-xs mt-0.5">Name, email, phone number, and password.</p>
                            </div>
                        </div>
                        <div class="flex items-start gap-3">
                            <span class="w-7 h-7 rounded-lg bg-white/8 flex items-center justify-center shrink-0">
                                <span class="material-symbols-outlined text-primary text-sm">receipt_long</span>
                            </span>
                            <div>
                                <p class="text-white text-sm font-bold">Transaction Data</p>
                                <p class="text-gray-400 text-xs mt-0.5">Items listed, rentals made, and payment status.</p>
                            </div>
                        </div>
                        <div class="flex items-start gap-3">
                            <span class="w-7 h-7 rounded-lg bg-white/8 flex items-center justify-center shrink-0">
                                <span class="material-symbols-outlined text-primary text-sm">location_on</span>
                            </span>
                            <div>
                                <p class="text-white text-sm font-bold">Location & Device</p>
                                <p class="text-gray-400 text-xs mt-0.5">Address for deliveries, browser & IP for security.</p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Right: How we use it -->
                <div class="p-8">
                    <p class="text-[10px] font-black text-primary uppercase tracking-widest mb-5">How We Use It</p>
                    <div class="space-y-3">
                        <div class="flex items-center gap-3">
                            <span class="material-symbols-outlined text-primary text-base shrink-0">check_circle</span>
                            <p class="text-gray-300 text-sm">Operate and improve the RendeX platform.</p>
                        </div>
                        <div class="flex items-center gap-3">
                            <span class="material-symbols-outlined text-primary text-base shrink-0">check_circle</span>
                            <p class="text-gray-300 text-sm">Facilitate secure rentals between users.</p>
                        </div>
                        <div class="flex items-center gap-3">
                            <span class="material-symbols-outlined text-primary text-base shrink-0">check_circle</span>
                            <p class="text-gray-300 text-sm">Send important service and support messages.</p>
                        </div>
                        <div class="flex items-center gap-3">
                            <span class="material-symbols-outlined text-primary text-base shrink-0">check_circle</span>
                            <p class="text-gray-300 text-sm">Monitor and prevent fraudulent activity.</p>
                        </div>
                    </div>
                    <!-- Guarantee pill -->
                    <div class="mt-6 flex items-center gap-2 bg-primary/10 border border-primary/20 px-4 py-2 rounded-full w-fit">
                        <span class="material-symbols-outlined text-primary text-sm">verified_user</span>
                        <span class="text-primary text-xs font-black">We never sell your data</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- BANNER 2: Your rights & how to contact us -->
        <div class="bg-surface-light dark:bg-surface-dark border border-[#e9e8ce] dark:border-[#3e3d2a] rounded-2xl overflow-hidden shadow-sm">

            <!-- Banner header -->
            <div class="bg-primary px-8 py-5 flex items-center gap-3">
                <span class="w-8 h-8 rounded-full bg-black flex items-center justify-center shrink-0">
                    <span class="material-symbols-outlined text-primary text-base">balance</span>
                </span>
                <div>
                    <p class="text-[10px] font-black text-black/60 uppercase tracking-widest">Part 2 of 2</p>
                    <h2 class="text-lg font-black text-black">Your Rights & Security</h2>
                </div>
            </div>

            <!-- Banner body: rights + contact -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-0">
                <!-- Left: Rights grid -->
                <div class="p-8 md:border-r border-[#e9e8ce] dark:border-[#3e3d2a]">
                    <p class="text-[10px] font-black text-text-muted uppercase tracking-widest mb-5">Your Data Rights</p>
                    <div class="grid grid-cols-2 gap-3">
                        <div class="p-3 bg-background-light dark:bg-background-dark rounded-xl text-center">
                            <span class="material-symbols-outlined text-primary text-xl mb-1">visibility</span>
                            <p class="text-xs font-black text-text-main dark:text-white">Access</p>
                            <p class="text-[10px] text-text-muted dark:text-gray-400">Request your data</p>
                        </div>
                        <div class="p-3 bg-background-light dark:bg-background-dark rounded-xl text-center">
                            <span class="material-symbols-outlined text-primary text-xl mb-1">edit</span>
                            <p class="text-xs font-black text-text-main dark:text-white">Correction</p>
                            <p class="text-[10px] text-text-muted dark:text-gray-400">Fix inaccuracies</p>
                        </div>
                        <div class="p-3 bg-background-light dark:bg-background-dark rounded-xl text-center">
                            <span class="material-symbols-outlined text-primary text-xl mb-1">delete</span>
                            <p class="text-xs font-black text-text-main dark:text-white">Deletion</p>
                            <p class="text-[10px] text-text-muted dark:text-gray-400">Delete your account</p>
                        </div>
                        <div class="p-3 bg-background-light dark:bg-background-dark rounded-xl text-center">
                            <span class="material-symbols-outlined text-primary text-xl mb-1">lock</span>
                            <p class="text-xs font-black text-text-main dark:text-white">Security</p>
                            <p class="text-[10px] text-text-muted dark:text-gray-400">Encrypted & safe</p>
                        </div>
                    </div>
                </div>

                <!-- Right: Contact us -->
                <div class="p-8 flex flex-col justify-between">
                    <div>
                        <p class="text-[10px] font-black text-text-muted uppercase tracking-widest mb-5">Privacy Questions?</p>
                        <p class="text-text-muted dark:text-gray-400 text-sm leading-relaxed mb-4">Have questions about your data or this policy? Our team responds within <strong class="text-text-main dark:text-white">24 hours</strong>.</p>
                        <a href="mailto:rendex857@gmail.com" class="flex items-center gap-2 text-sm font-bold text-text-main dark:text-white hover:text-primary transition-colors mb-6">
                            <span class="material-symbols-outlined text-primary text-base">alternate_email</span>
                            rendex857@gmail.com
                        </a>
                    </div>
                    <a href="contact.php" class="inline-flex items-center justify-center gap-2 bg-black text-white font-bold px-6 py-3 rounded-full hover:bg-gray-900 transition-colors text-sm">
                        <span class="material-symbols-outlined text-primary text-[18px]">support_agent</span>
                        Contact Privacy Team
                    </a>
                </div>
            </div>
        </div>

        <!-- Bottom note -->
        <p class="text-center text-xs text-text-muted dark:text-gray-500 pb-4">© 2026 RendeX Inc. All rights reserved. · <a href="terms.php" class="hover:text-primary transition-colors">Terms</a> · <a href="contact.php" class="hover:text-primary transition-colors">Contact</a></p>

    </div>
</div>
</body>
</html>
