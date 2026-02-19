<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}
$recipient_id = $_GET['recipient_id'] ?? null;
?>
<!DOCTYPE html>
<html class="light" lang="en">
<head>
    <meta charset="utf-8"/>
    <meta content="width=device-width, initial-scale=1.0" name="viewport"/>
    <title>Messages - RendeX</title>
    <link href="https://fonts.googleapis.com" rel="preconnect"/>
    <link crossorigin="" href="https://fonts.gstatic.com" rel="preconnect"/>
    <link href="https://fonts.googleapis.com/css2?family=Spline+Sans:wght@300;400;500;600;700&amp;display=swap" rel="stylesheet"/>
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&amp;display=swap" rel="stylesheet"/>
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
            fontFamily: {
              "display": ["Spline Sans", "sans-serif"],
            },
          },
        },
      }
    </script>
    <style>
        body { font-family: "Spline Sans", sans-serif; }
        .scrollbar-hide::-webkit-scrollbar { display: none; }
    </style>
</head>
<body class="bg-background-light dark:bg-background-dark text-text-main dark:text-white transition-colors duration-200 h-screen flex flex-col overflow-hidden">
    <!-- Navbar -->
    <header class="flex-none border-b border-[#e9e8ce] dark:border-[#3e3d2a] bg-background-light dark:bg-background-dark px-6 py-4">
        <div class="max-w-[1400px] mx-auto flex items-center justify-between">
            <a href="dashboard.php" class="flex items-center gap-2">
                <div class="size-8 text-primary">
                    <svg class="w-full h-full" fill="none" viewbox="0 0 48 48" xmlns="http://www.w3.org/2000/svg">
                        <path d="M36.7273 44C33.9891 44 31.6043 39.8386 30.3636 33.69C29.123 39.8386 26.7382 44 24 44C21.2618 44 18.877 39.8386 17.6364 33.69C16.3957 39.8386 14.0109 44 11.2727 44C7.25611 44 4 35.0457 4 24C4 12.9543 7.25611 4 11.2727 4C14.0109 4 16.3957 8.16144 17.6364 14.31C18.877 8.16144 21.2618 4 24 4C26.7382 4 29.123 8.16144 30.3636 14.31C31.6043 8.16144 33.9891 4 36.7273 4C40.7439 4 44 12.9543 44 24C44 35.0457 40.7439 44 36.7273 44Z" fill="currentColor"></path>
                    </svg>
                </div>
                <h2 class="text-xl font-bold tracking-tight">RendeX</h2>
            </a>
            <div class="flex items-center gap-4">
                <a href="dashboard.php" class="text-sm font-bold hover:text-primary transition-colors">Home</a>
                <div class="w-10 h-10 rounded-full bg-primary flex items-center justify-center text-black text-sm font-black">
                    <?php echo strtoupper(substr($_SESSION['user_name'] ?? 'U', 0, 1)); ?>
                </div>
            </div>
        </div>
    </header>

    <main class="flex-1 flex overflow-hidden max-w-[1400px] mx-auto w-full">
        <!-- Sidebar: Conversation List -->
        <aside class="w-full md:w-80 border-r border-[#e9e8ce] dark:border-[#3e3d2a] bg-surface-light dark:bg-surface-dark flex flex-col transition-all" id="sidebar">
            <div class="p-4 border-b border-[#e9e8ce] dark:border-[#3e3d2a]">
                <h2 class="text-xl font-black">Messages</h2>
            </div>
            <div class="flex-1 overflow-y-auto p-2 space-y-2" id="conversation-list">
                <!-- Conversations populate here -->
                <div class="p-4 text-center text-text-muted">Loading...</div>
            </div>
        </aside>

        <!-- Main Chat Area -->
        <section class="flex-1 flex flex-col bg-white dark:bg-[#1e2019] relative" id="chat-area">
            <!-- Chat Header -->
            <div class="p-4 border-b border-[#e9e8ce] dark:border-[#3e3d2a] flex items-center gap-4 bg-surface-light dark:bg-surface-dark z-10 hidden" id="chat-header">
                <button onclick="toggleSidebar()" class="md:hidden p-2 rounded-full hover:bg-gray-100 dark:hover:bg-gray-800">
                    <span class="material-symbols-outlined">arrow_back</span>
                </button>
                <div class="w-10 h-10 rounded-full bg-gray-200 flex items-center justify-center text-black font-bold" id="chat-avatar">
                    ?
                </div>
                <div>
                    <h3 class="font-bold text-lg leading-none" id="chat-name">Select a conversation</h3>
                    <p class="text-xs text-green-500 font-bold uppercase tracking-wider mt-1">Online</p>
                </div>
            </div>

            <!-- Messages Container -->
            <div class="flex-1 overflow-y-auto p-4 space-y-4 flex flex-col" id="messages-container">
                <div class="flex flex-col items-center justify-center h-full text-text-muted">
                    <span class="material-symbols-outlined text-6xl mb-4 opacity-50">forum</span>
                    <p class="text-lg">Select a conversation to start chatting</p>
                </div>
            </div>

            <!-- Input Area -->
            <div class="p-4 bg-surface-light dark:bg-surface-dark border-t border-[#e9e8ce] dark:border-[#3e3d2a] hidden" id="chat-input-area">
                <form onsubmit="sendMessage(event)" class="flex gap-4">
                    <input type="text" id="message-input" placeholder="Type a message..." class="flex-1 bg-gray-100 dark:bg-[#1e2019] border-none rounded-2xl px-6 py-4 focus:ring-2 focus:ring-primary outline-none text-text-main dark:text-white">
                    <button type="submit" class="bg-black text-white dark:bg-white dark:text-black rounded-2xl px-6 font-bold hover:scale-105 active:scale-95 transition-transform flex items-center justify-center">
                        <span class="material-symbols-outlined">send</span>
                    </button>
                </form>
            </div>
        </section>
    </main>

    <script>
        const currentUserId = '<?php echo $_SESSION['user_id']; ?>';
        let activePartnerId = '<?php echo $recipient_id; ?>';
        let conversations = [];

        // Fetch Conversations
        async function loadConversations() {
            const res = await fetch('api_chat.php?action=list');
            const json = await res.json();
            
            if (json.status === 'success') {
                conversations = json.data || []; // Assuming DB query is correct now. 
                 // If data structure is different, adjust.
                 // Currently expecting: [{partner_id, partner_name, last_message, ...}]
                renderConversations();
                
                // If specific recipient requested and not in list (new chat), handle it
                if (activePartnerId && !conversations.find(c => c.partner_id == activePartnerId)) {
                    // We might need to fetch partner details separately if not in list
                    // For MVP, just assume we can start chatting if we know ID
                    // Or fetch partner name via another API call? 
                    // Let's create a temp conversation entry
                    createTempConversation(activePartnerId);
                } else if (activePartnerId) {
                    openChat(activePartnerId);
                }
            } else {
                 console.error('Failed to load conversations');
            }
        }
        
        function createTempConversation(partnerId) {
            // Check if we can get name from somewhere? 
            // Maybe just show "New Chat" until message sent.
             const temp = {
                partner_id: partnerId,
                partner_name: 'User ' + partnerId.substring(0, 5), // Placeholder
                last_message: 'Start a conversation',
                last_time: new Date().toISOString()
            };
            conversations.unshift(temp);
            renderConversations();
            openChat(partnerId);
        }

        function renderConversations() {
            const list = document.getElementById('conversation-list');
            list.innerHTML = '';
            
            conversations.forEach(c => {
                const div = document.createElement('div');
                div.className = `p-4 rounded-2xl cursor-pointer hover:bg-gray-100 dark:hover:bg-[#2d2c18] transition-colors flex items-center gap-4 ${c.partner_id == activePartnerId ? 'bg-gray-100 dark:bg-[#2d2c18]' : ''}`;
                div.onclick = () => openChat(c.partner_id, c.partner_name);
                
                div.innerHTML = `
                    <div class="w-12 h-12 rounded-full bg-primary flex items-center justify-center text-black font-bold shrink-0">
                        ${c.partner_name ? c.partner_name.charAt(0).toUpperCase() : '?'}
                    </div>
                    <div class="flex-1 min-w-0">
                        <div class="flex justify-between items-baseline mb-1">
                            <h4 class="font-bold truncate">${c.partner_name || 'Unknown'}</h4>
                            <span class="text-[10px] text-text-muted">${new Date(c.last_time).toLocaleDateString()}</span>
                        </div>
                        <p class="text-sm text-text-muted truncate">${c.last_message || ''}</p>
                    </div>
                `;
                list.appendChild(div);
            });
        }

        async function openChat(partnerId, partnerName) {
            activePartnerId = partnerId;
            renderConversations(); // Update active state
            
            // UI Updates
            document.getElementById('chat-header').classList.remove('hidden');
            document.getElementById('chat-input-area').classList.remove('hidden');
            document.getElementById('messages-container').innerHTML = '<div class="loader m-auto">Loading...</div>'; // Simple loader
            document.getElementById('messages-container').className = 'flex-1 overflow-y-auto p-4 space-y-4 flex flex-col bg-gray-50 dark:bg-[#151510]';
            
            // Update Header info
            // Find partner name from list if not passed
            if (!partnerName) {
                const c = conversations.find(x => x.partner_id == partnerId);
                partnerName = c ? c.partner_name : 'User';
            }
            document.getElementById('chat-name').textContent = partnerName;
            document.getElementById('chat-avatar').textContent = partnerName.charAt(0).toUpperCase();

            // Fetch Messages
            const res = await fetch(`api_chat.php?action=get_messages&partner_id=${partnerId}`);
            const json = await res.json();
            
            if (json.status === 'success') {
                renderMessages(json.data);
            }
            
            // On mobile, hide sidebar
            if (window.innerWidth < 768) {
                document.getElementById('sidebar').classList.add('hidden');
                document.getElementById('chat-area').classList.remove('hidden');
            }
        }
        
        function renderMessages(messages) {
            const container = document.getElementById('messages-container');
            container.innerHTML = '';
            
            if (messages.length === 0) {
                 container.innerHTML = '<div class="m-auto text-text-muted">No messages yet. Say hello!</div>';
                 return;
            }

            messages.forEach(m => {
                const isMe = m.sender_id == currentUserId;
                const div = document.createElement('div');
                div.className = `flex ${isMe ? 'justify-end' : 'justify-start'}`;
                
                div.innerHTML = `
                    <div class="max-w-[70%] px-5 py-3 rounded-2xl text-sm ${isMe ? 'bg-black text-white dark:bg-white dark:text-black rounded-br-none' : 'bg-white dark:bg-[#2d2c18] border border-gray-200 dark:border-gray-700 rounded-bl-none'} shadow-sm">
                        <p>${m.message_text}</p>
                        <span class="text-[10px] opacity-60 mt-1 block text-right">${new Date(m.created_at).toLocaleTimeString([], {hour: '2-digit', minute:'2-digit'})}</span>
                    </div>
                `;
                container.appendChild(div);
            });
            
            // Scroll to bottom
            container.scrollTop = container.scrollHeight;
        }

        async function sendMessage(e) {
            e.preventDefault();
            const input = document.getElementById('message-input');
            const text = input.value.trim();
            if (!text || !activePartnerId) return;

            // Optimistic Append (optional, but good for UX)
            // ...

            input.value = '';
            
            const res = await fetch('api_chat.php?action=send', {
                method: 'POST',
                headers: {'Content-Type': 'application/json'},
                body: JSON.stringify({
                    receiver_id: activePartnerId,
                    message: text
                })
            });
            
            const json = await res.json();
            if (json.status === 'success') {
                // Reload messages to get full sync
                openChat(activePartnerId); 
                // Refresh list to update "last message" snippet
                loadConversations();
            }
        }
        
        function toggleSidebar() {
             document.getElementById('sidebar').classList.remove('hidden');
             // document.getElementById('chat-area').classList.add('hidden');
        }

        // Init
        loadConversations();

        // Polling for new messages every 5 seconds
        setInterval(() => {
            if (activePartnerId) {
                // Ideally only fetch if new messages
                // openChat(activePartnerId); // This might be too heavy doing full render
                // For MVP, maybe just refresh list
                // loadConversations();
            }
        }, 5000);
    </script>
</body>
</html>
