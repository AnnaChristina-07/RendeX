<?php
session_start();
?>
<!DOCTYPE html>
<html class="light" lang="en">
<head>
    <meta charset="utf-8"/>
    <meta content="width=device-width, initial-scale=1.0" name="viewport"/>
    <title>Terms & Conditions - RendeX</title>
    <meta name="description" content="Read RendeX's Terms and Conditions before using our peer-to-peer rental platform."/>
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
                    <span class="material-symbols-outlined text-xs">gavel</span> Legal
                </div>
                <h1 class="text-3xl md:text-4xl font-black tracking-tight mb-1">Terms & Conditions</h1>
                <p class="text-text-muted dark:text-gray-400 text-sm">Effective March 2026 · Please read carefully before using RendeX.</p>
            </div>
            <a href="index.php" class="inline-flex items-center gap-2 text-sm font-bold text-text-muted hover:text-primary transition-colors shrink-0">
                <span class="material-symbols-outlined text-sm">arrow_back</span> Return Home
            </a>
        </div>

        <!-- BANNER 1: Acceptance, Accounts & Rental Transactions -->
        <div class="bg-black rounded-2xl overflow-hidden relative">
            <div class="absolute top-0 right-0 w-72 h-72 bg-primary/8 rounded-full blur-3xl pointer-events-none"></div>

            <!-- Banner header -->
            <div class="px-8 pt-8 pb-5 border-b border-white/10 relative z-10">
                <div class="flex items-center gap-3">
                    <span class="w-8 h-8 rounded-full bg-primary flex items-center justify-center shrink-0">
                        <span class="material-symbols-outlined text-black text-base">description</span>
                    </span>
                    <div>
                        <p class="text-[10px] font-black text-primary uppercase tracking-widest">Part 1 of 2</p>
                        <h2 class="text-lg font-black text-white">Platform Rules & Responsibilities</h2>
                    </div>
                </div>
            </div>

            <!-- 2-column body -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-0 relative z-10">

                <!-- Left: § 1 + § 2 -->
                <div class="p-8 md:border-r border-white/10 space-y-6">
                    <!-- Section 1 -->
                    <div>
                        <div class="flex items-center gap-2 mb-3">
                            <span class="w-6 h-6 rounded-full bg-primary text-black text-[10px] font-black flex items-center justify-center shrink-0">1</span>
                            <h3 class="text-white text-sm font-black">Acceptance of Terms</h3>
                        </div>
                        <p class="text-gray-400 text-xs leading-relaxed">
                            By accessing or using the RendeX platform, you agree to be bound by these Terms and Conditions. If you disagree with any part of these terms, you may not access our services.
                        </p>
                    </div>
                    <!-- Section 2 -->
                    <div>
                        <div class="flex items-center gap-2 mb-3">
                            <span class="w-6 h-6 rounded-full bg-primary text-black text-[10px] font-black flex items-center justify-center shrink-0">2</span>
                            <h3 class="text-white text-sm font-black">User Accounts & Responsibilities</h3>
                        </div>
                        <p class="text-gray-400 text-xs leading-relaxed">
                            You are responsible for maintaining the confidentiality of your account credentials. You must provide accurate, current, and complete information during registration. Fraudulent or deceptive behavior will result in immediate account termination.
                        </p>
                    </div>
                    <!-- Quick rules -->
                    <div class="flex flex-col gap-2 mt-2">
                        <div class="flex items-center gap-2">
                            <span class="material-symbols-outlined text-primary text-base shrink-0">check_circle</span>
                            <p class="text-gray-300 text-xs">Keep your login credentials private and secure.</p>
                        </div>
                        <div class="flex items-center gap-2">
                            <span class="material-symbols-outlined text-primary text-base shrink-0">check_circle</span>
                            <p class="text-gray-300 text-xs">Provide truthful registration information at all times.</p>
                        </div>
                        <div class="flex items-center gap-2">
                            <span class="material-symbols-outlined text-primary text-base shrink-0">check_circle</span>
                            <p class="text-gray-300 text-xs">Report any unauthorized account access immediately.</p>
                        </div>
                    </div>
                </div>

                <!-- Right: § 3 -->
                <div class="p-8">
                    <div class="flex items-center gap-2 mb-4">
                        <span class="w-6 h-6 rounded-full bg-primary text-black text-[10px] font-black flex items-center justify-center shrink-0">3</span>
                        <h3 class="text-white text-sm font-black">Rental Transactions</h3>
                    </div>
                    <p class="text-gray-400 text-xs leading-relaxed mb-5">
                        RendeX acts as a marketplace to facilitate transactions between Renters and Owners. We do not own any of the items listed on the platform.
                    </p>
                    <div class="space-y-3">
                        <div class="flex items-start gap-3 p-3 bg-white/5 border border-white/10 rounded-xl">
                            <span class="material-symbols-outlined text-primary text-base shrink-0 mt-0.5">storefront</span>
                            <div>
                                <p class="text-white text-xs font-bold">Owners</p>
                                <p class="text-gray-400 text-xs mt-0.5">Must ensure items match listing descriptions accurately.</p>
                            </div>
                        </div>
                        <div class="flex items-start gap-3 p-3 bg-white/5 border border-white/10 rounded-xl">
                            <span class="material-symbols-outlined text-primary text-base shrink-0 mt-0.5">shopping_bag</span>
                            <div>
                                <p class="text-white text-xs font-bold">Renters</p>
                                <p class="text-gray-400 text-xs mt-0.5">Must return items in identical condition at rental end.</p>
                            </div>
                        </div>
                        <div class="flex items-start gap-3 p-3 bg-white/5 border border-white/10 rounded-xl">
                            <span class="material-symbols-outlined text-primary text-base shrink-0 mt-0.5">local_shipping</span>
                            <div>
                                <p class="text-white text-xs font-bold">Delivery Partners</p>
                                <p class="text-gray-400 text-xs mt-0.5">Verified and bound by separate logistics terms.</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- BANNER 2: Liability & Modifications -->
        <div class="bg-surface-light dark:bg-surface-dark border border-[#e9e8ce] dark:border-[#3e3d2a] rounded-2xl overflow-hidden shadow-sm">

            <!-- Banner header -->
            <div class="bg-primary px-8 py-5 flex items-center gap-3">
                <span class="w-8 h-8 rounded-full bg-black flex items-center justify-center shrink-0">
                    <span class="material-symbols-outlined text-primary text-base">balance</span>
                </span>
                <div>
                    <p class="text-[10px] font-black text-black/60 uppercase tracking-widest">Part 2 of 2</p>
                    <h2 class="text-lg font-black text-black">Liability, Insurance & Policy Changes</h2>
                </div>
            </div>

            <!-- 2-column body -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-0">

                <!-- Left: § 4 -->
                <div class="p-8 md:border-r border-[#e9e8ce] dark:border-[#3e3d2a]">
                    <div class="flex items-center gap-2 mb-4">
                        <span class="w-6 h-6 rounded-full bg-black text-primary text-[10px] font-black flex items-center justify-center shrink-0">4</span>
                        <h3 class="text-text-main dark:text-white text-sm font-black">Liability & Insurance</h3>
                    </div>
                    <p class="text-text-muted dark:text-gray-400 text-xs leading-relaxed mb-5">
                        While RendeX facilitates transactions, we limit our liability for damage, loss, or theft of items. Users agree to resolve disputes amicably. In cases where Delivery Partners handle logistics, they are bound by a separate set of verification terms to ensure item safety during transit.
                    </p>
                    <div class="grid grid-cols-2 gap-3">
                        <div class="text-center p-3 bg-background-light dark:bg-background-dark rounded-xl border border-[#e9e8ce] dark:border-[#3e3d2a]">
                            <span class="material-symbols-outlined text-primary text-xl mb-1">shield</span>
                            <p class="text-xs font-black text-text-main dark:text-white">Item Safety</p>
                            <p class="text-[10px] text-text-muted dark:text-gray-400 mt-0.5">Verified delivery handling</p>
                        </div>
                        <div class="text-center p-3 bg-background-light dark:bg-background-dark rounded-xl border border-[#e9e8ce] dark:border-[#3e3d2a]">
                            <span class="material-symbols-outlined text-primary text-xl mb-1">handshake</span>
                            <p class="text-xs font-black text-text-main dark:text-white">Disputes</p>
                            <p class="text-[10px] text-text-muted dark:text-gray-400 mt-0.5">Resolved amicably</p>
                        </div>
                    </div>
                </div>

                <!-- Right: § 5 + agree CTA -->
                <div class="p-8 flex flex-col justify-between">
                    <div>
                        <div class="flex items-center gap-2 mb-4">
                            <span class="w-6 h-6 rounded-full bg-black text-primary text-[10px] font-black flex items-center justify-center shrink-0">5</span>
                            <h3 class="text-text-main dark:text-white text-sm font-black">Modifications to Terms</h3>
                        </div>
                        <p class="text-text-muted dark:text-gray-400 text-xs leading-relaxed mb-5">
                            We reserve the right to modify or replace these Terms at any time. Material changes will be communicated via email or through the platform interface at least <strong class="text-text-main dark:text-white">30 days</strong> before taking effect. Continued use of RendeX after changes constitutes acceptance.
                        </p>
                        <div class="flex items-center gap-2 bg-primary/10 border border-primary/20 px-4 py-2 rounded-full w-fit">
                            <span class="material-symbols-outlined text-primary text-sm">notifications</span>
                            <span class="text-text-main dark:text-primary text-xs font-black">30-day advance notice for changes</span>
                        </div>
                    </div>
                    <div class="flex gap-3 mt-6">
                        <a href="index.php" class="flex-1 flex items-center justify-center gap-2 bg-black text-white font-bold px-5 py-3 rounded-full hover:bg-gray-900 transition-colors text-sm">
                            <span class="material-symbols-outlined text-primary text-[18px]">check_circle</span>
                            I Agree — Continue
                        </a>
                        <a href="contact.php" class="inline-flex items-center justify-center gap-2 border border-[#e9e8ce] dark:border-[#3e3d2a] text-text-main dark:text-white font-bold px-5 py-3 rounded-full hover:border-primary hover:text-primary transition-colors text-sm">
                            Questions?
                        </a>
                    </div>
                </div>
            </div>
        </div>

        <!-- Bottom note -->
        <p class="text-center text-xs text-text-muted dark:text-gray-500 pb-4">
            © 2026 RendeX Inc. All rights reserved. ·
            <a href="privacy.php" class="hover:text-primary transition-colors">Privacy Policy</a> ·
            <a href="contact.php" class="hover:text-primary transition-colors">Contact Us</a>
        </p>

    </div>
</div>
</body>
</html>
