<?php
session_start();
require_once __DIR__ . '/config/mail.php';

$success_msg = '';
$error_msg = '';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $name    = trim($_POST['name'] ?? '');
    $email   = trim($_POST['email'] ?? '');
    $subject = trim($_POST['subject'] ?? 'New Contact Form Message');
    $message = trim($_POST['message'] ?? '');

    if (empty($name) || empty($email) || empty($message)) {
        $error_msg = "Please fill in all required fields.";
    } else {
        $body = "<h2>New Contact Form Submission</h2>
                 <p><strong>Name:</strong> " . htmlspecialchars($name) . "</p>
                 <p><strong>Email:</strong> " . htmlspecialchars($email) . "</p>
                 <p><strong>Subject:</strong> " . htmlspecialchars($subject) . "</p>
                 <p><strong>Message:</strong><br>" . nl2br(htmlspecialchars($message)) . "</p>";
        $sent = send_smtp_email('rendex857@gmail.com', "Contact Form: " . $subject, $body);
        if ($sent === true) {
            $success_msg = "Your message has been sent! We'll get back to you within 24 hours.";
        } else {
            $error_msg = "Failed to send. Please try again or email us directly.";
        }
    }
}
?>
<!DOCTYPE html>
<html class="light" lang="en">
<head>
    <meta charset="utf-8"/>
    <meta content="width=device-width, initial-scale=1.0" name="viewport"/>
    <title>Contact Us - RendeX</title>
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
                <a href="index.php#how-it-works" class="text-sm font-medium text-text-muted hover:text-primary transition-colors">How it Works</a>
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

        <!-- Page Title -->
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
            <div>
                <div class="inline-flex items-center gap-1.5 bg-black text-primary text-[10px] font-black px-3 py-1 rounded-full mb-3 tracking-wider uppercase">
                    <span class="material-symbols-outlined text-xs">mail</span> Get in Touch
                </div>
                <h1 class="text-3xl md:text-4xl font-black tracking-tight mb-1">Contact Us</h1>
                <p class="text-text-muted dark:text-gray-400 text-sm">We typically respond within <strong class="text-text-main dark:text-white">24 hours</strong>.</p>
            </div>
            <a href="index.php" class="inline-flex items-center gap-2 text-sm font-bold text-text-muted hover:text-primary transition-colors shrink-0">
                <span class="material-symbols-outlined text-sm">arrow_back</span> Return Home
            </a>
        </div>

        <?php if (!empty($success_msg)): ?>
        <div class="p-4 bg-green-50 border border-green-200 text-green-800 rounded-xl flex items-center gap-3">
            <span class="material-symbols-outlined text-green-600">check_circle</span>
            <?php echo $success_msg; ?>
        </div>
        <?php endif; ?>
        <?php if (!empty($error_msg)): ?>
        <div class="p-4 bg-red-50 border border-red-200 text-red-800 rounded-xl flex items-center gap-3">
            <span class="material-symbols-outlined text-red-600">error</span>
            <?php echo $error_msg; ?>
        </div>
        <?php endif; ?>

        <!-- BANNER 1: Send us a message + Contact Info -->
        <div class="bg-black rounded-2xl overflow-hidden relative">
            <div class="absolute top-0 right-0 w-72 h-72 bg-primary/8 rounded-full blur-3xl pointer-events-none"></div>

            <!-- Banner header -->
            <div class="px-8 pt-8 pb-5 border-b border-white/10 relative z-10">
                <div class="flex items-center gap-3">
                    <span class="w-8 h-8 rounded-full bg-primary flex items-center justify-center shrink-0">
                        <span class="material-symbols-outlined text-black text-base">mail</span>
                    </span>
                    <div>
                        <p class="text-[10px] font-black text-primary uppercase tracking-widest">Part 1 of 2</p>
                        <h2 class="text-lg font-black text-white">Send Us a Message</h2>
                    </div>
                </div>
            </div>

            <!-- 2-column body -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-0 relative z-10">

                <!-- Left: Form (2/3 width) -->
                <div class="md:col-span-2 p-8 md:border-r border-white/10">
                    <form action="contact.php" method="POST" class="space-y-4">
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-[10px] font-black text-gray-400 uppercase tracking-wider mb-1.5">Name</label>
                                <input type="text" name="name" required placeholder="Your full name"
                                    class="w-full bg-white/8 border border-white/15 rounded-lg px-4 py-2.5 text-sm text-white placeholder-gray-500 focus:outline-none focus:ring-2 focus:ring-primary focus:border-transparent transition-all">
                            </div>
                            <div>
                                <label class="block text-[10px] font-black text-gray-400 uppercase tracking-wider mb-1.5">Email Address</label>
                                <input type="email" name="email" required placeholder="email@example.com"
                                    class="w-full bg-white/8 border border-white/15 rounded-lg px-4 py-2.5 text-sm text-white placeholder-gray-500 focus:outline-none focus:ring-2 focus:ring-primary focus:border-transparent transition-all">
                            </div>
                        </div>
                        <div>
                            <label class="block text-[10px] font-black text-gray-400 uppercase tracking-wider mb-1.5">Subject</label>
                            <select name="subject" class="w-full bg-white/8 border border-white/15 rounded-lg px-4 py-2.5 text-sm text-white focus:outline-none focus:ring-2 focus:ring-primary focus:border-transparent transition-all appearance-none">
                                <option value="" disabled selected class="bg-black">Select a category</option>
                                <option value="General Inquiry" class="bg-black">General Inquiry</option>
                                <option value="Listing Help" class="bg-black">Listing Help</option>
                                <option value="Payment Issue" class="bg-black">Payment Issue</option>
                                <option value="Delivery Support" class="bg-black">Delivery Support</option>
                                <option value="Account Issue" class="bg-black">Account Issue</option>
                                <option value="Other" class="bg-black">Other</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-[10px] font-black text-gray-400 uppercase tracking-wider mb-1.5">Message</label>
                            <textarea name="message" required rows="5" placeholder="Tell us how we can help..."
                                class="w-full bg-white/8 border border-white/15 rounded-lg px-4 py-2.5 text-sm text-white placeholder-gray-500 focus:outline-none focus:ring-2 focus:ring-primary focus:border-transparent transition-all resize-none"></textarea>
                        </div>
                        <button type="submit" class="inline-flex items-center gap-2 bg-primary text-black font-bold px-7 py-3 rounded-full hover:bg-yellow-400 transition-colors text-sm">
                            <span class="material-symbols-outlined text-[18px]">send</span>
                            Send Message
                        </button>
                    </form>
                </div>

                <!-- Right: Contact Info (1/3 width) -->
                <div class="p-8 flex flex-col gap-6">
                    <p class="text-[10px] font-black text-primary uppercase tracking-widest">Contact Information</p>
                    <div class="space-y-5">
                        <div class="flex items-start gap-3">
                            <span class="w-9 h-9 rounded-full bg-white/10 border border-white/10 flex items-center justify-center shrink-0">
                                <span class="material-symbols-outlined text-primary text-base">alternate_email</span>
                            </span>
                            <div>
                                <p class="text-[10px] text-gray-500 uppercase tracking-wider font-bold mb-0.5">Support Email</p>
                                <a href="mailto:rendex857@gmail.com" class="text-white text-sm font-semibold hover:text-primary transition-colors">rendex857@gmail.com</a>
                            </div>
                        </div>
                        <div class="flex items-start gap-3">
                            <span class="w-9 h-9 rounded-full bg-white/10 border border-white/10 flex items-center justify-center shrink-0">
                                <span class="material-symbols-outlined text-primary text-base">call</span>
                            </span>
                            <div>
                                <p class="text-[10px] text-gray-500 uppercase tracking-wider font-bold mb-0.5">Phone Support</p>
                                <a href="tel:7907397205" class="text-white text-sm font-semibold hover:text-primary transition-colors">+91 7907397205</a>
                                <p class="text-gray-500 text-xs mt-0.5">Mon–Sat, 9am – 6pm IST</p>
                            </div>
                        </div>
                        <div class="flex items-start gap-3">
                            <span class="w-9 h-9 rounded-full bg-white/10 border border-white/10 flex items-center justify-center shrink-0">
                                <span class="material-symbols-outlined text-primary text-base">location_on</span>
                            </span>
                            <div>
                                <p class="text-[10px] text-gray-500 uppercase tracking-wider font-bold mb-0.5">Headquarters</p>
                                <p class="text-white text-sm font-semibold">Kanjirappally, Kottayam</p>
                                <p class="text-gray-400 text-xs">Kerala, India</p>
                            </div>
                        </div>
                    </div>
                    <div class="mt-auto pt-5 border-t border-white/10 flex items-center gap-2">
                        <span class="w-2 h-2 rounded-full bg-primary animate-pulse shrink-0"></span>
                        <p class="text-gray-400 text-xs">We respond within <strong class="text-white">24 hours</strong>.</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- BANNER 2: AI Support -->
        <div class="bg-surface-light dark:bg-surface-dark border border-[#e9e8ce] dark:border-[#3e3d2a] rounded-2xl overflow-hidden shadow-sm">

            <!-- Banner header -->
            <div class="bg-primary px-8 py-5 flex items-center gap-3">
                <span class="w-8 h-8 rounded-full bg-black flex items-center justify-center shrink-0">
                    <span class="material-symbols-outlined text-primary text-base">smart_toy</span>
                </span>
                <div>
                    <p class="text-[10px] font-black text-black/60 uppercase tracking-widest">Part 2 of 2</p>
                    <h2 class="text-lg font-black text-black">Need Immediate Assistance?</h2>
                </div>
            </div>

            <!-- 2-column body -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-0">
                <!-- Left: AI info -->
                <div class="p-8 md:border-r border-[#e9e8ce] dark:border-[#3e3d2a]">
                    <p class="text-[10px] font-black text-text-muted uppercase tracking-widest mb-4">AI Support Bot</p>
                    <p class="text-text-muted dark:text-gray-400 text-sm leading-relaxed mb-5">
                        Chat instantly with our <strong class="text-text-main dark:text-white">RendeX AI Assistant</strong> — available 24/7 for quick answers about rentals, accounts, deliveries, and more.
                    </p>
                    <div class="space-y-2.5">
                        <div class="flex items-center gap-2"><span class="material-symbols-outlined text-primary text-base">check_circle</span><p class="text-sm text-text-muted dark:text-gray-400">Instant answers, no wait time</p></div>
                        <div class="flex items-center gap-2"><span class="material-symbols-outlined text-primary text-base">check_circle</span><p class="text-sm text-text-muted dark:text-gray-400">Available 24/7 every day</p></div>
                        <div class="flex items-center gap-2"><span class="material-symbols-outlined text-primary text-base">check_circle</span><p class="text-sm text-text-muted dark:text-gray-400">Handles rentals, billing & delivery queries</p></div>
                    </div>
                </div>
                <!-- Right: CTA -->
                <div class="p-8 flex flex-col justify-center items-start gap-4">
                    <p class="text-[10px] font-black text-text-muted uppercase tracking-widest">Start a Conversation</p>
                    <p class="text-sm text-text-muted dark:text-gray-400 leading-relaxed">Click below to open the chat and get help right away — no forms, no waiting.</p>
                    <button onclick="openChat()" class="inline-flex items-center gap-2 bg-black text-white font-bold px-7 py-3 rounded-full hover:bg-gray-900 transition-colors text-sm">
                        <span class="material-symbols-outlined text-primary text-[18px]">support_agent</span>
                        Contact Support Now
                    </button>
                </div>
            </div>
        </div>

        <!-- Bottom note -->
        <p class="text-center text-xs text-text-muted dark:text-gray-500 pb-4">
            © 2026 RendeX Inc. All rights reserved. ·
            <a href="privacy.php" class="hover:text-primary transition-colors">Privacy</a> ·
            <a href="terms.php" class="hover:text-primary transition-colors">Terms</a>
        </p>

    </div>
</div>

<!-- Chatbot Widget -->
<div id="chatbot-widget" class="fixed bottom-6 right-6 z-50 flex flex-col items-end gap-4">
    <div id="chat-window" class="hidden w-[350px] h-[500px] bg-white dark:bg-surface-dark rounded-2xl shadow-2xl border border-[#e9e8ce] dark:border-[#3e3d2a] flex flex-col overflow-hidden transition-all duration-300 origin-bottom-right transform scale-95 opacity-0">
        <div class="bg-primary p-4 flex justify-between items-center">
            <div class="flex items-center gap-2">
                <span class="material-symbols-outlined text-black">smart_toy</span>
                <h3 class="font-bold text-black">RendeX Assistant</h3>
            </div>
            <button onclick="toggleChat()" class="text-black hover:bg-black/10 rounded-full p-1 transition-colors">
                <span class="material-symbols-outlined">close</span>
            </button>
        </div>
        <div id="chat-messages" class="flex-1 p-4 overflow-y-auto space-y-4 bg-background-light dark:bg-zinc-900">
            <div class="flex items-start gap-2">
                <div class="w-8 h-8 rounded-full bg-primary flex items-center justify-center shrink-0">
                    <span class="material-symbols-outlined text-black text-sm">smart_toy</span>
                </div>
                <div class="bg-white dark:bg-surface-dark border border-[#e9e8ce] dark:border-[#3e3d2a] p-3 rounded-2xl rounded-tl-none shadow-sm max-w-[80%]">
                    <p class="text-sm">Hi! I'm the RendeX Support Assistant. How can I help you today?</p>
                </div>
            </div>
        </div>
        <div class="p-3 bg-white dark:bg-surface-dark border-t border-[#e9e8ce] dark:border-[#3e3d2a]">
            <form id="chat-form" onsubmit="handleChatSubmit(event)" class="flex gap-2">
                <input type="text" id="chat-input" placeholder="Type a message..." class="flex-1 bg-background-light dark:bg-zinc-900 border-none rounded-full px-4 py-2 text-sm focus:ring-2 focus:ring-primary outline-none text-text-main dark:text-white">
                <button type="submit" class="bg-primary text-black w-10 h-10 rounded-full flex items-center justify-center hover:bg-yellow-300 transition-colors shrink-0">
                    <span class="material-symbols-outlined text-xl">send</span>
                </button>
            </form>
        </div>
    </div>
    <button onclick="toggleChat()" id="chat-toggle-btn" class="w-14 h-14 bg-primary text-black rounded-full shadow-lg flex items-center justify-center hover:scale-110 transition-transform duration-200 border-2 border-white dark:border-zinc-800">
        <span class="material-symbols-outlined text-2xl">chat</span>
    </button>
</div>

<script>
    function toggleChat() {
        const chatWindow = document.getElementById('chat-window');
        const isHidden = chatWindow.classList.contains('hidden');
        if (isHidden) {
            chatWindow.classList.remove('hidden');
            setTimeout(() => { chatWindow.classList.remove('scale-95', 'opacity-0'); }, 10);
            setTimeout(() => { document.getElementById('chat-input').focus(); }, 300);
        } else {
            chatWindow.classList.add('scale-95', 'opacity-0');
            setTimeout(() => { chatWindow.classList.add('hidden'); }, 300);
        }
    }
    function openChat() {
        const chatWindow = document.getElementById('chat-window');
        if (chatWindow.classList.contains('hidden')) {
            chatWindow.classList.remove('hidden');
            setTimeout(() => { chatWindow.classList.remove('scale-95', 'opacity-0'); }, 10);
            setTimeout(() => { document.getElementById('chat-input').focus(); }, 300);
        }
    }
    async function handleChatSubmit(e) {
        e.preventDefault();
        const input = document.getElementById('chat-input');
        const message = input.value.trim();
        if (!message) return;
        addMessage(message, 'user');
        input.value = '';
        const typingId = showTypingIndicator();
        try {
            const response = await fetch('chat_api.php', { method: 'POST', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify({ message }) });
            if (!response.ok) throw new Error('Network error');
            const data = await response.json();
            removeTypingIndicator(typingId);
            addMessage(data.reply, 'bot');
        } catch {
            removeTypingIndicator(typingId);
            addMessage("I'm having trouble connecting right now. Please try again.", 'bot');
        }
    }
    function showTypingIndicator() {
        const id = 'typing-' + Date.now();
        const container = document.getElementById('chat-messages');
        const div = document.createElement('div');
        div.id = id; div.className = 'flex items-start gap-2';
        div.innerHTML = `<div class="w-8 h-8 rounded-full bg-primary flex items-center justify-center shrink-0"><span class="material-symbols-outlined text-black text-sm">smart_toy</span></div><div class="bg-white dark:bg-surface-dark border border-[#e9e8ce] dark:border-[#3e3d2a] p-3 rounded-2xl rounded-tl-none shadow-sm"><div class="flex gap-1"><span class="w-2 h-2 bg-gray-400 rounded-full animate-bounce"></span><span class="w-2 h-2 bg-gray-400 rounded-full animate-bounce" style="animation-delay:0.2s"></span><span class="w-2 h-2 bg-gray-400 rounded-full animate-bounce" style="animation-delay:0.4s"></span></div></div>`;
        container.appendChild(div); container.scrollTop = container.scrollHeight; return id;
    }
    function removeTypingIndicator(id) { const el = document.getElementById(id); if (el) el.remove(); }
    function addMessage(text, sender) {
        const container = document.getElementById('chat-messages');
        const div = document.createElement('div');
        div.className = sender === 'user' ? 'flex items-end justify-end gap-2' : 'flex items-start gap-2';
        const safe = text.replace(/&/g,"&amp;").replace(/</g,"&lt;").replace(/>/g,"&gt;");
        div.innerHTML = sender === 'bot'
            ? `<div class="w-8 h-8 rounded-full bg-primary flex items-center justify-center shrink-0"><span class="material-symbols-outlined text-black text-sm">smart_toy</span></div><div class="bg-white dark:bg-surface-dark border border-[#e9e8ce] dark:border-[#3e3d2a] p-3 rounded-2xl rounded-tl-none shadow-sm max-w-[80%]"><p class="text-sm">${safe}</p></div>`
            : `<div class="bg-black text-white p-3 rounded-2xl rounded-tr-none shadow-sm max-w-[80%]"><p class="text-sm">${safe}</p></div>`;
        container.appendChild(div);
        setTimeout(() => { container.scrollTo({ top: container.scrollHeight, behavior: 'smooth' }); }, 10);
    }
</script>
</body>
</html>
