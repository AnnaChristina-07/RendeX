<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$rental_id = $_GET['id'] ?? null;
if (!$rental_id) {
    header("Location: rentals.php");
    exit();
}

// Load Rental Data
$rentals_file = 'rentals.json';
$rentals = file_exists($rentals_file) ? json_decode(file_get_contents($rentals_file), true) : [];
$rental = null;
foreach ($rentals as $r) {
    if ($r['id'] === $rental_id && $r['user_id'] === $_SESSION['user_id']) {
        $rental = $r;
        break;
    }
}

if (!$rental) {
    // Rental not found or access denied
    header("Location: rentals.php");
    exit();
}

$item = $rental['item'];
$owner_name = $item['owner_name'] ?? null;

if (!$owner_name) {
    // If owner_name is missing, try to find it via owner_id/user_id from users.json
    $owner_id = $item['user_id'] ?? $item['owner_id'] ?? null;
    if ($owner_id) {
        $users_file = 'users.json';
        if (file_exists($users_file)) {
            $users = json_decode(file_get_contents($users_file), true) ?: [];
            foreach ($users as $u) {
                if ($u['id'] === $owner_id) {
                    $owner_name = $u['name'];
                    break;
                }
            }
        }
    }
}
$owner_name = $owner_name ?? 'Owner'; 
$img_src = (strpos($item['img'], 'uploads/') === 0) ? $item['img'] : 'https://source.unsplash.com/random/400x400?' . urlencode($item['img']);

// Handle Form Submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['confirm_return'])) {
    
    // 1. Update Rental Status
    $updated_rentals = [];
    $item_id = $rental['item']['id'] ?? null;
    
    foreach ($rentals as $r) {
        if ($r['id'] === $rental_id) {
            $r['status'] = 'returned';
            $r['actual_end_date'] = date('Y-m-d');
            $r['return_method'] = $_POST['return_method']; // pickup or dropoff
            $r['return_date'] = $_POST['return_date'];
            $r['return_time'] = $_POST['return_time'];
            $r['condition_note'] = $_POST['condition_note'];
            // In a real app, handle file uploads for photos here
        }
        $updated_rentals[] = $r;
    }
    file_put_contents($rentals_file, json_encode($updated_rentals, JSON_PRETTY_PRINT));

    // 2. Update Item Status (Make it available again)
    if ($item_id) {
        $items_file = 'items.json';
        if (file_exists($items_file)) {
            $items = json_decode(file_get_contents($items_file), true) ?: [];
            foreach ($items as &$i) {
                if ($i['id'] === $item_id) {
                    $i['status'] = 'Active';
                    $i['availability_status'] = 'available';
                }
            }
            file_put_contents($items_file, json_encode($items, JSON_PRETTY_PRINT));
        }

        // 3. Update Database (if applicable)
        require_once 'config/database.php'; // Ensure this path is correct relative to this file
        try {
            $pdo = getDBConnection();
            if ($pdo) {
                // Update rentals table
                $stmt = $pdo->prepare("UPDATE rentals SET status = 'returned', end_date = NOW() WHERE id = ?");
                $stmt->execute([$rental_id]);

                // Update items table
                $stmt = $pdo->prepare("UPDATE items SET availability_status = 'available' WHERE id = ?");
                $stmt->execute([$item_id]);
            }
        } catch (Exception $e) {
            // Ignore DB errors if file update succeeded, strictly following "based on my folder" but keeping DB sync if present
        }
    }

    // Redirect to success/history
    header("Location: rentals.php?msg=returned_success");
    exit();
}

?>
<!DOCTYPE html>
<html class="light" lang="en">
<head>
    <meta charset="utf-8"/>
    <meta content="width=device-width, initial-scale=1.0" name="viewport"/>
    <title>Schedule Return - RendeX</title>
    <link href="https://fonts.googleapis.com" rel="preconnect"/>
    <link crossorigin="" href="https://fonts.gstatic.com" rel="preconnect"/>
    <link href="https://fonts.googleapis.com/css2?family=Spline+Sans:wght@300;400;500;600;700;800&display=swap" rel="stylesheet"/>
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&display=swap" rel="stylesheet"/>
    <script src="https://cdn.tailwindcss.com?plugins=forms"></script>
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
            },
            fontFamily: {
              "display": ["Spline Sans", "sans-serif"],
            },
            borderRadius: { "DEFAULT": "1rem", "lg": "2rem", "xl": "3rem" }
          }
        }
      }
    </script>
    <style>
        body { font-family: "Spline Sans", sans-serif; }
        .step-active { display: block; }
        .step-inactive { display: none; }
        /* Custom Radio Button styling */
        .logistics-card.selected { border-color: #dfff00; background-color: rgba(223, 255, 0, 0.1); }
        .time-slot.selected { background-color: #dfff00; color: #000; border-color: #dfff00; }
    </style>
</head>
<body class="bg-background-light dark:bg-background-dark text-text-main dark:text-white min-h-screen">
    
    <header class="sticky top-0 z-50 flex items-center justify-between border-b border-gray-800 bg-black/95 backdrop-blur-sm px-6 py-4 lg:px-10 shadow-lg">
        <div class="flex items-center gap-8 w-full max-w-[1200px] mx-auto">
            <a href="rentals.php" class="flex items-center gap-2 text-white">
                <div class="size-8 text-primary bg-primary rounded-full flex items-center justify-center text-black font-bold">
                    R
                </div>
                <h2 class="text-xl font-bold tracking-tight text-white"><span class="text-primary">Rende</span>X</h2>
            </a>
            <div class="ml-auto">
                <a href="rentals.php" class="text-sm font-bold flex items-center gap-1 hover:text-primary text-gray-400 transition-colors">
                    <span class="material-symbols-outlined text-lg">close</span> Cancel
                </a>
            </div>
        </div>
    </header>

    <main class="max-w-[1200px] mx-auto px-4 py-8 lg:py-12">
        <div class="mb-10">
            <h1 class="text-4xl font-black mb-3">Schedule Your Return</h1>
            <p class="text-text-muted">Coordinate logistics and document condition to ensure a smooth hand-off.</p>
        </div>

        <form method="POST" id="return-form" class="grid grid-cols-1 lg:grid-cols-3 gap-8 lg:gap-12 items-start">
            
            <!-- Left: Rental Summary -->
            <div class="lg:col-span-1 order-2 lg:order-1">
                <div class="bg-white dark:bg-surface-dark rounded-3xl p-6 border border-[#e9e8ce] dark:border-[#3e3d2a] sticky top-32">
                    <div class="text-xs font-bold uppercase tracking-widest text-text-muted mb-4">Active Rental Summary</div>
                    
                    <div class="aspect-video rounded-2xl overflow-hidden bg-gray-100 mb-6">
                        <img src="<?php echo htmlspecialchars($img_src); ?>" class="w-full h-full object-cover">
                    </div>
                    
                    <h2 class="text-2xl font-extrabold mb-2"><?php echo htmlspecialchars($item['name']); ?></h2>
                    
                    <div class="flex items-center gap-3 pt-4 mb-4 border-t border-gray-100 dark:border-gray-800">
                         <div class="w-10 h-10 rounded-full bg-primary flex items-center justify-center text-black text-xs font-bold border border-white">
                            <?php echo strtoupper(substr($owner_name, 0, 1)); ?>
                        </div>
                        <div>
                            <p class="text-xs text-text-muted font-bold uppercase">Owner</p>
                            <p class="text-sm font-bold"><?php echo htmlspecialchars($owner_name); ?></p>
                        </div>
                    </div>

                    <div class="p-4 bg-yellow-50 dark:bg-yellow-900/10 rounded-2xl border border-yellow-100 dark:border-yellow-900/20">
                        <div class="flex items-start gap-3">
                            <span class="material-symbols-outlined text-yellow-700">calendar_clock</span>
                            <div>
                                <p class="text-[10px] font-black uppercase text-yellow-800 tracking-wider">Due By</p>
                                <p class="font-bold text-yellow-900 text-sm"><?php echo date('M d, Y', strtotime($rental['end_date'])); ?></p>
                                <p class="text-xs text-yellow-700 mt-1">Make sure to return by 7:00 PM</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Right: Return Process -->
            <div class="lg:col-span-2 order-1 lg:order-2 space-y-8">
                
                <!-- Step 1: Logistics -->
                <section id="step-1" class="step-active">
                    <div class="flex items-center gap-4 mb-6">
                        <div class="w-10 h-10 rounded-full bg-primary text-black flex items-center justify-center font-bold shadow-lg shadow-yellow-200/50">1</div>
                        <h3 class="text-2xl font-black">Logistics: Method & Date</h3>
                    </div>

                    <div class="bg-white dark:bg-surface-dark rounded-3xl p-8 border border-[#e9e8ce] dark:border-[#3e3d2a] mb-8">
                        <input type="hidden" name="return_method" id="return_method" value="pickup">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-8">
                            <div class="logistics-card selected cursor-pointer border-2 border-[#e9e8ce] dark:border-[#3e3d2a] rounded-2xl p-6 text-center transition-all" onclick="selectMethod('pickup')">
                                <span class="material-symbols-outlined text-4xl mb-3 text-primary">local_shipping</span>
                                <h4 class="font-bold text-lg">Schedule Pickup</h4>
                                <p class="text-xs text-text-muted mt-2">A delivery partner will come to collect the item.</p>
                            </div>
                            <div class="logistics-card cursor-pointer border-2 border-[#e9e8ce] dark:border-[#3e3d2a] rounded-2xl p-6 text-center transition-all" onclick="selectMethod('dropoff')">
                                <span class="material-symbols-outlined text-4xl mb-3 text-text-muted">storefront</span>
                                <h4 class="font-bold text-lg">Self Drop-off</h4>
                                <p class="text-xs text-text-muted mt-2">Return the item directly to the owner's location.</p>
                            </div>
                        </div>

                        <div class="mb-6">
                            <label class="block text-xs font-bold text-text-muted uppercase tracking-widest mb-3 ml-1">Select Date</label>
                            <input type="date" name="return_date" id="return_date" required 
                                   class="w-full px-6 py-4 rounded-2xl border-none bg-gray-50 dark:bg-[#1e2019] focus:ring-2 focus:ring-primary font-medium"
                                   min="<?php echo date('Y-m-d'); ?>" value="<?php echo date('Y-m-d'); ?>">
                        </div>

                        <div>
                            <label class="block text-xs font-bold text-text-muted uppercase tracking-widest mb-3 ml-1">Preferred Time Window</label>
                            <input type="hidden" name="return_time" id="return_time" value="09:00 - 11:00 AM">
                            <div class="grid grid-cols-1 md:grid-cols-3 gap-3">
                                <button type="button" class="time-slot selected px-4 py-3 rounded-xl border-2 border-transparent font-bold text-sm bg-gray-50 dark:bg-[#1e2019] transition-all" onclick="selectTime(this, '09:00 - 11:00 AM')">09:00 - 11:00 AM</button>
                                <button type="button" class="time-slot px-4 py-3 rounded-xl border-2 border-transparent font-bold text-sm bg-gray-50 dark:bg-[#1e2019] transition-all" onclick="selectTime(this, '01:00 - 03:00 PM')">01:00 - 03:00 PM</button>
                                <button type="button" class="time-slot px-4 py-3 rounded-xl border-2 border-transparent font-bold text-sm bg-gray-50 dark:bg-[#1e2019] transition-all" onclick="selectTime(this, '05:00 - 07:00 PM')">05:00 - 07:00 PM</button>
                            </div>
                        </div>
                    </div>

                    <button type="button" onclick="nextStep(2)" class="bg-primary hover:bg-[#ccee00] text-black font-bold py-4 px-8 rounded-full text-lg w-full md:w-auto shadow-xl shadow-yellow-200/50 transition-all hover:scale-[1.02]">
                        Continue to Condition Report
                    </button>
                </section>

                <!-- Step 2: Condition -->
                <section id="step-2" class="step-inactive">
                    <div class="flex items-center gap-4 mb-6">
                        <div class="w-10 h-10 rounded-full bg-primary text-black flex items-center justify-center font-bold shadow-lg shadow-yellow-200/50">2</div>
                        <h3 class="text-2xl font-black">Condition Report</h3>
                    </div>

                    <div class="bg-white dark:bg-surface-dark rounded-3xl p-8 border border-[#e9e8ce] dark:border-[#3e3d2a] mb-8">
                         <div class="mb-6">
                            <label class="block text-xs font-bold text-text-muted uppercase tracking-widest mb-3 ml-1">Description of Current Status <span class="text-red-500">*</span></label>
                            <textarea id="condition_note" name="condition_note" placeholder="Any minor wear, issues, or confirming it's in perfect shape..." rows="4" 
                                class="w-full px-6 py-4 rounded-2xl border-none bg-gray-50 dark:bg-[#1e2019] focus:ring-2 focus:ring-primary font-medium"></textarea>
                        </div>

                        <div>
                            <label class="block text-xs font-bold text-text-muted uppercase tracking-widest mb-3 ml-1">Proof of Condition (Photos) <span class="text-red-500">*</span></label>
                            <div id="photo_dropzone" class="border-2 border-dashed border-gray-300 dark:border-gray-700 rounded-2xl p-8 text-center bg-gray-50 dark:bg-[#1e2019]/50 hover:bg-gray-100 transition-colors cursor-pointer relative" onclick="document.getElementById('condition_photos').click()">
                                <input type="file" multiple accept="image/*" id="condition_photos" name="condition_photos[]" class="absolute inset-0 opacity-0 cursor-pointer w-full h-full" onchange="handleFileSelect(this)">
                                <span class="material-symbols-outlined text-4xl text-gray-400 mb-2">add_a_photo</span>
                                <p id="photo_text" class="font-bold text-sm">Click or drag photos to upload</p>
                                <p class="text-xs text-text-muted mt-1">Please provide clear photos of the front, back, and any sensitive parts.</p>
                            </div>
                        </div>
                    </div>

                    <div class="flex gap-4">
                        <button type="button" onclick="nextStep(1)" class="bg-black text-white font-bold py-4 px-8 rounded-full text-lg hover:bg-gray-800 transition-all">
                            Back
                        </button>
                        <button type="button" onclick="showConfirmation()" class="bg-primary hover:bg-[#ccee00] text-black font-bold py-4 px-8 rounded-full text-lg flex-1 md:flex-none shadow-xl shadow-yellow-200/50 transition-all hover:scale-[1.02]">
                            Review & Confirm
                        </button>
                    </div>
                </section>

                <!-- Confirmation Modal (Hidden initially) -->
                <div id="confirm-modal" class="hidden fixed inset-0 z-[100] flex items-center justify-center p-4">
                    <div class="absolute inset-0 bg-black/60 backdrop-blur-sm" onclick="hideConfirmation()"></div>
                    <div class="relative bg-white dark:bg-surface-dark rounded-[2rem] p-8 max-w-md w-full shadow-2xl transform transition-all">
                        <h3 class="text-2xl font-black mb-4">Everything ready?</h3>
                        <p class="text-text-muted mb-8">By confirming, you begin the return process. The owner will be notified to expect the item.</p>
                        
                        <div class="flex gap-4">
                            <button type="button" onclick="hideConfirmation()" class="flex-1 py-4 font-bold text-text-muted hover:text-black">
                                Cancel
                            </button>
                            <button type="submit" name="confirm_return" class="flex-1 bg-primary hover:bg-yellow-300 text-black font-bold py-4 rounded-xl shadow-lg shadow-primary/20 flex items-center justify-center gap-2">
                                Confirm Return <span class="material-symbols-outlined text-lg font-black">check</span>
                            </button>
                        </div>
                    </div>
                </div>

            </div>
        </form>
    </main>

    <div id="error-toast" class="hidden fixed top-24 left-1/2 -translate-x-1/2 bg-red-500 text-white px-6 py-4 rounded-2xl shadow-2xl font-bold flex items-center gap-3 z-[110] transition-all">
        <span class="material-symbols-outlined">error</span>
        <span id="error-text">Please fill all fields</span>
    </div>

    <script>
        function selectMethod(method) {
            document.getElementById('return_method').value = method;
            document.querySelectorAll('.logistics-card').forEach(el => {
                el.classList.remove('selected');
                el.querySelector('.material-symbols-outlined').classList.remove('text-primary');
                el.querySelector('.material-symbols-outlined').classList.add('text-text-muted');
                el.style.borderColor = '';
            });
            const selected = event.currentTarget;
            selected.classList.add('selected');
            selected.querySelector('.material-symbols-outlined').classList.remove('text-text-muted');
            selected.querySelector('.material-symbols-outlined').classList.add('text-primary');
        }

        function selectTime(btn, time) {
            document.getElementById('return_time').value = time;
            document.querySelectorAll('.time-slot').forEach(el => el.classList.remove('selected'));
            btn.classList.add('selected');
        }

        function nextStep(step) {
            if (step === 2) {
                // Validate Step 1
                const date = document.getElementById('return_date').value;
                const time = document.getElementById('return_time').value;
                const method = document.getElementById('return_method').value;

                if (!date || !time || !method) {
                    showError('Please complete all logistics details first.');
                    return;
                }

                document.getElementById('step-1').classList.remove('step-active');
                document.getElementById('step-1').classList.add('step-inactive');
                document.getElementById('step-2').classList.remove('step-inactive');
                document.getElementById('step-2').classList.add('step-active');
            } else {
                document.getElementById('step-2').classList.remove('step-active');
                document.getElementById('step-2').classList.add('step-inactive');
                document.getElementById('step-1').classList.remove('step-inactive');
                document.getElementById('step-1').classList.add('step-active');
            }
            window.scrollTo(0, 0);
        }

        function handleFileSelect(input) {
            const dropzone = document.getElementById('photo_dropzone');
            const text = document.getElementById('photo_text');
            if (input.files && input.files.length > 0) {
                text.textContent = input.files.length + " file(s) selected";
                dropzone.classList.remove('border-gray-300', 'dark:border-gray-700');
                dropzone.classList.add('border-green-500', 'bg-green-50');
            }
        }

        function showConfirmation() {
            // Validate Step 2
            const note = document.getElementById('condition_note').value.trim();
            const photos = document.getElementById('condition_photos').files;

            if (!note) {
                showError('Please provide a description of the condition.');
                return;
            }
            if (photos.length === 0) {
                 showError('Please upload at least one photo of the item.');
                 return;
            }

            document.getElementById('confirm-modal').classList.remove('hidden');
        }

        function hideConfirmation() {
            document.getElementById('confirm-modal').classList.add('hidden');
        }

        function showError(msg) {
            const toast = document.getElementById('error-toast');
            document.getElementById('error-text').textContent = msg;
            toast.classList.remove('hidden');
            setTimeout(() => {
                toast.classList.add('hidden');
            }, 3000);
        }
    </script>
</body>
</html>
