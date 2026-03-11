<?php
session_start();
?>
<!DOCTYPE html>
<html class="light" lang="en">
<head>
    <meta charset="utf-8"/>
    <meta content="width=device-width, initial-scale=1.0" name="viewport"/>
    <title>About Us - RendeX</title>
    <meta name="description" content="Learn about RendeX — the safest peer-to-peer rental marketplace connecting Renters, Owners, and Delivery Partners."/>
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
                    <a href="signup.php" class="text-sm font-medium bg-primary text-black font-bold px-4 py-1.5 rounded-full hover:bg-yellow-400 transition-colors">Sign Up</a>
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
                    <span class="material-symbols-outlined text-xs">info</span> About
                </div>
                <h1 class="text-3xl md:text-4xl font-black tracking-tight mb-1">About RendeX</h1>
                <p class="text-text-muted dark:text-gray-400 text-sm">A unified rental ecosystem connecting people and communities.</p>
            </div>
            <a href="index.php" class="inline-flex items-center gap-2 text-sm font-bold text-text-muted hover:text-primary transition-colors shrink-0">
                <span class="material-symbols-outlined text-sm">arrow_back</span> Return Home
            </a>
        </div>

        <!-- BANNER 1: Who we are & our philosophy -->
        <div class="bg-black rounded-2xl overflow-hidden relative">
            <div class="absolute top-0 right-0 w-72 h-72 bg-primary/8 rounded-full blur-3xl pointer-events-none"></div>

            <!-- Banner header -->
            <div class="px-8 pt-8 pb-5 border-b border-white/10 relative z-10">
                <div class="flex items-center gap-3">
                    <span class="w-8 h-8 rounded-full bg-primary flex items-center justify-center shrink-0">
                        <span class="material-symbols-outlined text-black text-base">eco</span>
                    </span>
                    <div>
                        <p class="text-[10px] font-black text-primary uppercase tracking-widest">Part 1 of 2</p>
                        <h2 class="text-lg font-black text-white">Who We Are & Our Philosophy</h2>
                    </div>
                </div>
            </div>

            <!-- 2-column body -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-0 relative z-10">
                <!-- Left: Mission -->
                <div class="p-8 md:border-r border-white/10">
                    <p class="text-[10px] font-black text-primary uppercase tracking-widest mb-4">Our Mission</p>
                    <h3 class="text-white text-xl font-black mb-3">Own Less.<br>Experience More.</h3>
                    <p class="text-gray-400 text-sm leading-relaxed mb-5">
                        RendeX was built on the belief that <strong class="text-white">access is more important than ownership.</strong> We bridge the gap between people who have idle items and those who need them — fostering a sustainable, community-driven economy.
                    </p>
                    <div class="flex items-center gap-2 bg-primary/10 border border-primary/20 px-4 py-2 rounded-full w-fit">
                        <span class="material-symbols-outlined text-primary text-sm">handshake</span>
                        <span class="text-primary text-xs font-black">Community-Driven Platform</span>
                    </div>
                </div>

                <!-- Right: Core values -->
                <div class="p-8">
                    <p class="text-[10px] font-black text-primary uppercase tracking-widest mb-5">What We Stand For</p>
                    <div class="space-y-4">
                        <div class="flex items-start gap-3">
                            <span class="w-7 h-7 rounded-lg bg-white/8 flex items-center justify-center shrink-0">
                                <span class="material-symbols-outlined text-primary text-sm">recycling</span>
                            </span>
                            <div>
                                <p class="text-white text-sm font-bold">Sustainability</p>
                                <p class="text-gray-400 text-xs mt-0.5">Reduce waste by sharing what we already have.</p>
                            </div>
                        </div>
                        <div class="flex items-start gap-3">
                            <span class="w-7 h-7 rounded-lg bg-white/8 flex items-center justify-center shrink-0">
                                <span class="material-symbols-outlined text-primary text-sm">verified_user</span>
                            </span>
                            <div>
                                <p class="text-white text-sm font-bold">Trust & Safety</p>
                                <p class="text-gray-400 text-xs mt-0.5">Verified users, secure payments, and insured rentals.</p>
                            </div>
                        </div>
                        <div class="flex items-start gap-3">
                            <span class="w-7 h-7 rounded-lg bg-white/8 flex items-center justify-center shrink-0">
                                <span class="material-symbols-outlined text-primary text-sm">savings</span>
                            </span>
                            <div>
                                <p class="text-white text-sm font-bold">Affordability</p>
                                <p class="text-gray-400 text-xs mt-0.5">Save money — rent instead of buying things you rarely use.</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- BANNER 2: How it works & join CTA -->
        <div class="bg-surface-light dark:bg-surface-dark border border-[#e9e8ce] dark:border-[#3e3d2a] rounded-2xl overflow-hidden shadow-sm">

            <!-- Banner header -->
            <div class="bg-primary px-8 py-5 flex items-center gap-3">
                <span class="w-8 h-8 rounded-full bg-black flex items-center justify-center shrink-0">
                    <span class="material-symbols-outlined text-primary text-base">bolt</span>
                </span>
                <div>
                    <p class="text-[10px] font-black text-black/60 uppercase tracking-widest">Part 2 of 2</p>
                    <h2 class="text-lg font-black text-black">How the Platform Works</h2>
                </div>
            </div>

            <!-- 2-column body -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-0">

                <!-- Left: 3 steps -->
                <div class="p-8 md:border-r border-[#e9e8ce] dark:border-[#3e3d2a]">
                    <p class="text-[10px] font-black text-text-muted uppercase tracking-widest mb-5">3 Simple Steps</p>
                    <div class="space-y-5">
                        <div class="flex items-start gap-4">
                            <span class="w-8 h-8 rounded-full bg-background-light dark:bg-background-dark border border-[#e9e8ce] dark:border-[#3e3d2a] text-text-main dark:text-white text-sm font-black flex items-center justify-center shrink-0">1</span>
                            <div>
                                <p class="text-sm font-black text-text-main dark:text-white">List or Browse</p>
                                <p class="text-xs text-text-muted dark:text-gray-400 mt-0.5">Owners list idle gear. Renters browse items by category near them.</p>
                            </div>
                        </div>
                        <div class="flex items-start gap-4">
                            <span class="w-8 h-8 rounded-full bg-primary text-black text-sm font-black flex items-center justify-center shrink-0">2</span>
                            <div>
                                <p class="text-sm font-black text-text-main dark:text-white">Secure Booking</p>
                                <p class="text-xs text-text-muted dark:text-gray-400 mt-0.5">Renters book safely. Verified Delivery Partners handle logistics.</p>
                            </div>
                        </div>
                        <div class="flex items-start gap-4">
                            <span class="w-8 h-8 rounded-full bg-black dark:bg-white text-white dark:text-black text-sm font-black flex items-center justify-center shrink-0">3</span>
                            <div>
                                <p class="text-sm font-black text-text-main dark:text-white">Enjoy & Return</p>
                                <p class="text-xs text-text-muted dark:text-gray-400 mt-0.5">Enjoy your rental. Partners handle the smooth return flow.</p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Right: CTA -->
                <div class="p-8 flex flex-col justify-between">
                    <div>
                        <p class="text-[10px] font-black text-text-muted uppercase tracking-widest mb-4">Ready to Join?</p>
                        <p class="text-text-muted dark:text-gray-400 text-sm leading-relaxed mb-6">
                            Join thousands of users already sharing, renting, and saving through the RendeX community platform.
                        </p>
                        <div class="space-y-3">
                            <div class="flex items-center gap-2"><span class="material-symbols-outlined text-primary text-base">check_circle</span><p class="text-sm text-text-muted dark:text-gray-400">Free to sign up — no hidden fees</p></div>
                            <div class="flex items-center gap-2"><span class="material-symbols-outlined text-primary text-base">check_circle</span><p class="text-sm text-text-muted dark:text-gray-400">Verified renters and owners</p></div>
                            <div class="flex items-center gap-2"><span class="material-symbols-outlined text-primary text-base">check_circle</span><p class="text-sm text-text-muted dark:text-gray-400">Secure, insured transactions</p></div>
                        </div>
                    </div>
                    <div class="flex gap-3 mt-6">
                        <a href="signup.php" class="flex-1 flex items-center justify-center gap-2 bg-black text-white font-bold px-5 py-3 rounded-full hover:bg-gray-900 transition-colors text-sm">
                            <span class="material-symbols-outlined text-primary text-[18px]">person_add</span>
                            Create Account
                        </a>
                        <a href="index.php" class="flex items-center justify-center gap-2 border border-[#e9e8ce] dark:border-[#3e3d2a] text-text-main dark:text-white font-bold px-5 py-3 rounded-full hover:border-primary hover:text-primary transition-colors text-sm">
                            Browse
                        </a>
                    </div>
                </div>
            </div>
        </div>

        <!-- Bottom note -->
        <p class="text-center text-xs text-text-muted dark:text-gray-500 pb-4">
            © 2026 RendeX Inc. All rights reserved. ·
            <a href="privacy.php" class="hover:text-primary transition-colors">Privacy</a> ·
            <a href="terms.php" class="hover:text-primary transition-colors">Terms</a> ·
            <a href="contact.php" class="hover:text-primary transition-colors">Contact</a>
        </p>

    </div>
</div>
</body>
</html>
