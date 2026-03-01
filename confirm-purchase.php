<?php
ob_start();
session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$item_id = isset($_GET['id']) ? $_GET['id'] : null;
if (!$item_id) { header("Location: dashboard.php"); exit(); }

require_once 'config/database.php';
require_once 'config/mail.php';

// Load Item (DB first, then JSON)
$item = null;
try {
    $pdo = getDBConnection();
    if ($pdo) {
        $stmt = $pdo->prepare("SELECT * FROM items WHERE id = ?");
        $stmt->execute([$item_id]);
        $db_item = $stmt->fetch();
        if ($db_item) {
            $item = $db_item;
            $item['name'] = $db_item['title'];
            $item['price'] = $db_item['price_per_day'];
            $item['selling_price'] = $db_item['selling_price'] ?? 0;
            $item['listing_type'] = $db_item['listing_type'] ?? 'rent';
            $item['address'] = $db_item['location'];
            $images = !empty($db_item['images']) ? json_decode($db_item['images'], true) : [];
            $item['all_images'] = array_map(fn($i) => 'uploads/' . $i, (array)$images);
            $item['img'] = !empty($item['all_images']) ? $item['all_images'][0] : '';
            $item['user_id'] = $db_item['owner_id'];
        }
    }
} catch (Exception $e) {}

// JSON fallback
if (!$item) {
    $items_file = 'items.json';
    $all_items = file_exists($items_file) ? json_decode(file_get_contents($items_file), true) : [];
    foreach ($all_items as $d) {
        if ($d['id'] == $item_id) {
            $item = $d;
            $item['name'] = $d['title'];
            $item['selling_price'] = $d['selling_price'] ?? 0;
            $item['listing_type'] = $d['listing_type'] ?? 'rent';
            $item['all_images'] = !empty($d['images']) ? array_map(fn($i) => 'uploads/'.$i, $d['images']) : [];
            $item['img'] = !empty($item['all_images']) ? $item['all_images'][0] : '';
            break;
        }
    }
}

if (!$item) { header("Location: dashboard.php"); exit(); }

// Guard: must be sellable
$listing_type  = $item['listing_type'] ?? 'rent';
$selling_price = floatval($item['selling_price'] ?? 0);
if (!in_array($listing_type, ['sell','both']) || $selling_price <= 0) {
    header("Location: item-details.php?id=" . $item_id);
    exit();
}

// Prevent buying own item
$owner_id = $item['user_id'] ?? $item['owner_id'] ?? null;
if ($owner_id == $_SESSION['user_id']) {
    header("Location: item-details.php?id=" . $item_id . "?msg=own_item");
    exit();
}

// Fetch Owner
$owner_name = $item['owner_name'] ?? 'Verified Owner';
if ($owner_id) {
    try {
        if ($pdo) {
            $stmt = $pdo->prepare("SELECT name, email FROM users WHERE id = ?");
            $stmt->execute([$owner_id]);
            $u = $stmt->fetch();
            if ($u) $owner_name = $u['name'];
        }
    } catch (Exception $e) {}
}

// Platform fee
$platform_fee = 50;
$total_amount = $selling_price + $platform_fee;

// Handle POST — Confirm purchase
$success_purchase = null;
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['confirm_purchase'])) {
    $delivery_address = trim($_POST['delivery_address'] ?? '');
    $delivery_city    = trim($_POST['delivery_city'] ?? '');
    $delivery_state   = trim($_POST['delivery_state'] ?? '');
    $delivery_zip     = trim($_POST['delivery_zip'] ?? '');
    $delivery_phone   = trim($_POST['delivery_phone'] ?? '');
    $payment_ref      = $_POST['payment_ref'] ?? ('DEMO_' . uniqid());

    $purchase_ref = 'PUR_' . strtoupper(uniqid());

    try {
        if ($pdo) {
            // 1. Insert purchase record
            $stmt = $pdo->prepare("INSERT INTO purchases 
                (purchase_ref, item_id, buyer_id, seller_id, amount, platform_fee, payment_status, payment_id,
                 delivery_address, delivery_city, delivery_state, delivery_zip, delivery_phone, status, created_at)
                VALUES (?, ?, ?, ?, ?, ?, 'paid', ?, ?, ?, ?, ?, ?, 'confirmed', NOW())");
            $stmt->execute([
                $purchase_ref, is_numeric($item_id) ? $item_id : 0,
                $_SESSION['user_id'], $owner_id,
                $selling_price, $platform_fee, $payment_ref,
                $delivery_address, $delivery_city, $delivery_state, $delivery_zip, $delivery_phone
            ]);

            // 2. Mark item as sold
            $pdo->prepare("UPDATE items SET sold_to = ?, sold_at = NOW(), availability_status = 'unavailable', is_active = 0 WHERE id = ?")
                ->execute([$_SESSION['user_id'], $item_id]);

            // 3. Notify seller
            $pdo->prepare("INSERT INTO notifications (user_id, title, message, type, created_at) VALUES (?, ?, ?, 'success', NOW())")
                ->execute([$owner_id, 'Your item was sold!', $item['name'] . ' was purchased by ' . ($_SESSION['user_name'] ?? 'a user') . ' for ₹' . number_format($selling_price, 2) . '.']);

        }
    } catch (Exception $e) {
        // Silent — JSON fallback below
    }

    // JSON fallback: update item status
    $items_file = 'items.json';
    if (file_exists($items_file)) {
        $all_json = json_decode(file_get_contents($items_file), true) ?: [];
        foreach ($all_json as &$ji) {
            if ($ji['id'] == $item_id) {
                $ji['sold_to'] = $_SESSION['user_id'];
                $ji['sold_at'] = date('Y-m-d H:i:s');
                $ji['availability_status'] = 'sold';
                $ji['status'] = 'Sold';
            }
        }
        file_put_contents($items_file, json_encode($all_json, JSON_PRETTY_PRINT));
    }

    // Send confirmation email to buyer
    $buyer_email = $_SESSION['user_email'] ?? '';
    if ($buyer_email) {
        $email_body = "
        <div style='font-family:sans-serif;max-width:600px;margin:auto;padding:20px;border:1px solid #e9e8ce;border-radius:20px;background:#fff'>
            <div style='background:#22c55e;padding:20px;border-radius:15px;text-align:center;margin-bottom:24px'>
                <h1 style='margin:0;color:#fff;font-size:22px'>Purchase Confirmed! 🎉</h1>
            </div>
            <p>Hi <strong>" . htmlspecialchars($_SESSION['user_name']) . "</strong>,</p>
            <p>You've successfully purchased <strong>" . htmlspecialchars($item['name']) . "</strong>.</p>
            <div style='background:#f8f8f5;padding:16px;border-radius:12px;margin:20px 0'>
                <table style='width:100%;border-collapse:collapse'>
                    <tr><td style='padding:8px 0;color:#555'>Order Ref</td><td style='text-align:right;font-weight:bold'>{$purchase_ref}</td></tr>
                    <tr><td style='padding:8px 0;color:#555'>Item</td><td style='text-align:right;font-weight:bold'>" . htmlspecialchars($item['name']) . "</td></tr>
                    <tr><td style='padding:8px 0;color:#555'>Selling Price</td><td style='text-align:right;font-weight:bold'>₹" . number_format($selling_price, 2) . "</td></tr>
                    <tr><td style='padding:8px 0;color:#555'>Platform Fee</td><td style='text-align:right;font-weight:bold'>₹" . number_format($platform_fee, 2) . "</td></tr>
                    <tr><td style='padding:8px 0;font-weight:bold'>Total Paid</td><td style='text-align:right;font-weight:bold;color:#16a34a'>₹" . number_format($total_amount, 2) . "</td></tr>
                </table>
            </div>
            <p style='text-align:center;font-size:13px;color:#888'>The seller will contact you for delivery. <a href='http://{$_SERVER['HTTP_HOST']}/RendeX/my-purchases.php' style='color:#000;font-weight:bold'>View My Purchases</a></p>
        </div>";
        send_smtp_email($buyer_email, "Purchase Confirmed — {$purchase_ref}", $email_body);
    }

    $success_purchase = [
        'ref'    => $purchase_ref,
        'item'   => $item['name'],
        'img'    => $item['img'],
        'amount' => $total_amount,
        'buyer'  => $_SESSION['user_name'],
    ];
}
?>
<!DOCTYPE html>
<html class="light" lang="en">
<head>
    <meta charset="utf-8"/>
    <meta content="width=device-width, initial-scale=1.0" name="viewport"/>
    <title>RendeX - Buy <?php echo htmlspecialchars($item['name']); ?></title>
    <link href="https://fonts.googleapis.com" rel="preconnect"/>
    <link crossorigin="" href="https://fonts.gstatic.com" rel="preconnect"/>
    <link href="https://fonts.googleapis.com/css2?family=Spline+Sans:wght@300;400;500;600;700;800&display=swap" rel="stylesheet"/>
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&display=swap" rel="stylesheet"/>
    <script src="https://cdn.tailwindcss.com?plugins=forms"></script>
    <script src="https://checkout.razorpay.com/v1/checkout.js"></script>
    <script>
      tailwind.config = {
        darkMode: "class",
        theme: {
          extend: {
            colors: {
              "primary": "#f9f506",
              "background-light": "#f8f8f5",
              "background-dark": "#1e2019",
              "surface-light": "#ffffff",
              "surface-dark": "#2d2c18",
              "text-main": "#1c1c0d",
              "text-muted": "#5e5e4a",
            },
            fontFamily: { "display": ["Spline Sans", "sans-serif"] },
          },
        },
      }
    </script>
    <style>
        body { font-family: "Spline Sans", sans-serif; }
        .material-symbols-outlined { font-variation-settings: 'FILL' 0, 'wght' 400, 'GRAD' 0, 'opsz' 24 }
        .success-overlay { position:fixed;inset:0;background:rgba(0,0,0,0.8);backdrop-filter:blur(10px);z-index:9999;display:flex;align-items:center;justify-content:center;padding:20px; }
        .animate-pop { animation: popIn 0.4s cubic-bezier(0.34, 1.56, 0.64, 1); }
        @keyframes popIn { from { transform: scale(0.7); opacity: 0; } to { transform: scale(1); opacity: 1; } }
    </style>
</head>
<body class="bg-background-light text-text-main min-h-screen">

<?php if ($success_purchase): ?>
<!-- ===== SUCCESS OVERLAY ===== -->
<div class="success-overlay">
    <div class="bg-white max-w-md w-full rounded-[40px] p-10 text-center shadow-2xl relative overflow-hidden animate-pop">
        <div class="absolute top-0 left-0 w-full h-2 bg-green-500"></div>

        <div class="w-24 h-24 bg-green-100 rounded-full flex items-center justify-center mx-auto mb-6">
            <span class="material-symbols-outlined text-green-500 text-5xl">check_circle</span>
        </div>

        <h2 class="text-3xl font-black mb-2">Purchase Complete!</h2>
        <p class="text-text-muted mb-6">Order <span class="font-black text-black">#<?= $success_purchase['ref'] ?></span> confirmed.</p>

        <div class="bg-gray-50 rounded-3xl p-6 text-left space-y-3 mb-8">
            <div class="flex items-center gap-4 mb-4">
                <?php if ($success_purchase['img']): ?>
                <img src="<?= htmlspecialchars($success_purchase['img']) ?>" class="w-16 h-16 rounded-xl object-cover bg-white">
                <?php endif; ?>
                <div>
                    <h4 class="font-black"><?= htmlspecialchars($success_purchase['item']) ?></h4>
                    <p class="text-xs text-text-muted">Purchased by <?= htmlspecialchars($success_purchase['buyer']) ?></p>
                </div>
            </div>
            <div class="flex justify-between border-t pt-3 text-sm">
                <span class="text-text-muted">Total Paid</span>
                <span class="font-black text-green-600">₹<?= number_format($success_purchase['amount'], 2) ?></span>
            </div>
        </div>

        <div class="space-y-3">
            <a href="my-purchases.php" class="block w-full bg-green-500 text-white py-4 rounded-2xl font-black hover:bg-green-600 transition-all shadow-lg">
                View My Purchases
            </a>
            <a href="dashboard.php" class="block w-full border-2 border-gray-100 py-4 rounded-2xl font-bold text-text-muted hover:bg-gray-50 transition-all">
                Return Home
            </a>
        </div>
    </div>
</div>
<?php endif; ?>

<!-- Navbar -->
<header class="sticky top-0 z-50 border-b border-[#e9e8ce] bg-white/80 backdrop-blur-md px-6 py-4">
    <div class="max-w-[1200px] mx-auto flex items-center justify-between">
        <a href="dashboard.php" class="flex items-center gap-2">
            <div class="size-8 text-primary">
                <svg class="w-full h-full" fill="none" viewBox="0 0 48 48" xmlns="http://www.w3.org/2000/svg">
                    <path d="M36.7273 44C33.9891 44 31.6043 39.8386 30.3636 33.69C29.123 39.8386 26.7382 44 24 44C21.2618 44 18.877 39.8386 17.6364 33.69C16.3957 39.8386 14.0109 44 11.2727 44C7.25611 44 4 35.0457 4 24C4 12.9543 7.25611 4 11.2727 4C14.0109 4 16.3957 8.16144 17.6364 14.31C18.877 8.16144 21.2618 4 24 4C26.7382 4 29.123 8.16144 30.3636 14.31C31.6043 8.16144 33.9891 4 36.7273 4C40.7439 4 44 12.9543 44 24C44 35.0457 40.7439 44 36.7273 44Z" fill="currentColor"></path>
                </svg>
            </div>
            <h2 class="text-xl font-bold tracking-tight">RendeX</h2>
        </a>
        <div class="flex items-center gap-4">
            <a href="profile.php" class="w-9 h-9 rounded-full bg-primary flex items-center justify-center text-black text-sm font-black shadow-sm">
                <?php echo strtoupper(substr($_SESSION['user_name'], 0, 1)); ?>
            </a>
            <a href="item-details.php?id=<?= urlencode($item_id) ?>" class="text-sm font-bold text-text-muted hover:text-black flex items-center gap-1">
                <span class="material-symbols-outlined text-lg">close</span> Cancel
            </a>
        </div>
    </div>
</header>

<main class="max-w-[1100px] mx-auto px-4 py-12">
    <div class="mb-10">
        <div class="inline-flex items-center gap-2 bg-green-100 text-green-800 text-xs font-bold px-3 py-1.5 rounded-full mb-4">
            <span class="material-symbols-outlined text-sm">sell</span> One-Time Purchase
        </div>
        <h1 class="text-4xl font-black mb-2">Confirm Your Purchase</h1>
        <p class="text-text-muted">Review the details and pay once — the item is yours forever.</p>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-10 items-start">
        <!-- Left: Delivery Form -->
        <div class="lg:col-span-2 space-y-8">
            <!-- Item Summary Card -->
            <div class="bg-white rounded-3xl p-6 border border-[#e9e8ce] shadow-sm flex flex-col md:flex-row gap-6">
                <?php if (!empty($item['img'])): ?>
                <div class="w-full md:w-40 aspect-square rounded-2xl overflow-hidden bg-gray-100 shrink-0">
                    <img src="<?= htmlspecialchars($item['img']) ?>" class="w-full h-full object-cover"
                         onerror="this.src='https://placehold.co/400x400?text=No+Image'">
                </div>
                <?php endif; ?>
                <div class="flex-1">
                    <span class="text-xs font-bold uppercase tracking-wider text-text-muted bg-gray-100 px-2 py-1 rounded">
                        <?= htmlspecialchars(ucwords(str_replace('-',' ',$item['category'] ?? 'Item'))) ?>
                    </span>
                    <h2 class="text-2xl font-black mt-2 mb-1"><?= htmlspecialchars($item['name']) ?></h2>
                    <p class="text-text-muted text-sm line-clamp-2 mb-4"><?= htmlspecialchars($item['description'] ?? '') ?></p>
                    <div class="flex items-center gap-3 border-t border-gray-100 pt-4">
                        <div class="w-9 h-9 rounded-full bg-primary flex items-center justify-center text-black font-bold text-sm">
                            <?= strtoupper(substr($owner_name, 0, 1)) ?>
                        </div>
                        <div>
                            <p class="text-[10px] text-text-muted font-bold uppercase">Sold by</p>
                            <p class="text-sm font-bold"><?= htmlspecialchars($owner_name) ?></p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Delivery Address -->
            <form method="POST" id="purchase-form">
                <div class="bg-white rounded-3xl p-8 border border-[#e9e8ce] shadow-sm space-y-6">
                    <div class="flex items-center gap-4 mb-2">
                        <div class="w-10 h-10 rounded-full bg-black text-white flex items-center justify-center font-bold">1</div>
                        <h3 class="text-2xl font-black">Delivery Address</h3>
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-text-muted uppercase tracking-widest mb-2">Street Address <span class="text-red-500">*</span></label>
                        <input type="text" name="delivery_address" id="delivery_address" required
                            placeholder="e.g. 42B MG Road, Indiranagar"
                            class="w-full px-5 py-4 rounded-2xl border-none bg-gray-50 focus:ring-2 focus:ring-green-400 font-medium">
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                        <div>
                            <label class="block text-xs font-bold text-text-muted uppercase tracking-widest mb-2">City <span class="text-red-500">*</span></label>
                            <input type="text" name="delivery_city" id="delivery_city" required placeholder="Bangalore"
                                class="w-full px-5 py-4 rounded-2xl border-none bg-gray-50 focus:ring-2 focus:ring-green-400 font-medium">
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-text-muted uppercase tracking-widest mb-2">State <span class="text-red-500">*</span></label>
                            <input type="text" name="delivery_state" id="delivery_state" required placeholder="Karnataka"
                                class="w-full px-5 py-4 rounded-2xl border-none bg-gray-50 focus:ring-2 focus:ring-green-400 font-medium">
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-text-muted uppercase tracking-widest mb-2">Pincode <span class="text-red-500">*</span></label>
                            <input type="text" name="delivery_zip" id="delivery_zip" required placeholder="560038" maxlength="6"
                                class="w-full px-5 py-4 rounded-2xl border-none bg-gray-50 focus:ring-2 focus:ring-green-400 font-medium">
                        </div>
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-text-muted uppercase tracking-widest mb-2">Phone <span class="text-red-500">*</span></label>
                        <div class="relative">
                            <span class="absolute left-5 top-1/2 -translate-y-1/2 material-symbols-outlined text-gray-400">call</span>
                            <input type="tel" name="delivery_phone" id="delivery_phone" required placeholder="+91 98765 43210"
                                class="w-full pl-14 pr-5 py-4 rounded-2xl border-none bg-gray-50 focus:ring-2 focus:ring-green-400 font-medium">
                        </div>
                    </div>
                </div>

                <!-- Hidden field for payment ref -->
                <input type="hidden" name="payment_ref" id="payment_ref_input" value="">
                <input type="hidden" name="confirm_purchase" value="1">
            </form>
        </div>

        <!-- Right: Order Summary -->
        <div class="lg:sticky lg:top-32 space-y-6">
            <div class="bg-white rounded-3xl border border-[#e9e8ce] shadow-xl overflow-hidden">
                <div class="p-8 border-b border-gray-100">
                    <h3 class="text-xl font-black mb-6">Order Summary</h3>

                    <div class="space-y-3 mb-6">
                        <div class="flex justify-between text-sm">
                            <span class="text-text-muted">Item Price</span>
                            <span class="font-bold">₹<?= number_format($selling_price, 2) ?></span>
                        </div>
                        <div class="flex justify-between text-sm">
                            <span class="text-text-muted">Platform Fee</span>
                            <span class="font-bold">₹<?= number_format($platform_fee, 2) ?></span>
                        </div>
                    </div>

                    <div class="flex justify-between pt-4 border-t border-dashed border-gray-300 items-center">
                        <span class="text-lg font-black">Total</span>
                        <span class="text-2xl font-black text-green-600">₹<?= number_format($total_amount, 2) ?></span>
                    </div>
                </div>

                <!-- Pay Button -->
                <div class="p-8 bg-gray-50">
                    <button type="button" onclick="startPurchasePayment()"
                        class="w-full bg-green-500 hover:bg-green-400 text-white font-black py-5 rounded-2xl text-lg flex items-center justify-center gap-3 shadow-lg shadow-green-200 transition-all hover:scale-[1.02] active:scale-95">
                        <span class="material-symbols-outlined">lock</span>
                        Pay ₹<?= number_format($total_amount, 2) ?>
                    </button>
                    <p class="text-center text-[10px] font-bold text-text-muted mt-4 uppercase tracking-widest">
                        <span class="material-symbols-outlined text-[14px] align-middle">verified_user</span>
                        Secure via Razorpay
                    </p>
                </div>
            </div>

            <!-- Trust Badge -->
            <div class="bg-green-50 border border-green-100 rounded-2xl p-5">
                <div class="flex gap-3">
                    <span class="material-symbols-outlined text-green-600 text-xl shrink-0">info</span>
                    <p class="text-xs font-medium text-green-900 leading-relaxed">
                        <strong>RendeX Buyer Protection</strong> — If the item is not as described, you're entitled to a full refund within 48 hours of delivery.
                    </p>
                </div>
            </div>
        </div>
    </div>
</main>

<script>
function startPurchasePayment() {
    // Validate delivery form
    const fields = ['delivery_address','delivery_city','delivery_state','delivery_zip','delivery_phone'];
    let valid = true;
    fields.forEach(id => {
        const el = document.getElementById(id);
        if (!el.value.trim()) {
            el.classList.add('ring-2','ring-red-400');
            valid = false;
        } else {
            el.classList.remove('ring-2','ring-red-400');
        }
    });
    if (!valid) {
        alert('Please fill in all delivery details before proceeding.');
        return;
    }

    const totalPaise = <?= $total_amount * 100 ?>;

    const options = {
        key: 'rzp_test_YourKeyHere', // Replace with your Razorpay test key
        amount: totalPaise,
        currency: 'INR',
        name: 'RendeX',
        description: 'Purchase: <?= addslashes($item['name']) ?>',
        image: '<?= !empty($item['img']) ? $item['img'] : '' ?>',
        handler: function(response) {
            document.getElementById('payment_ref_input').value = response.razorpay_payment_id;
            document.getElementById('purchase-form').submit();
        },
        prefill: {
            name: '<?= addslashes($_SESSION['user_name']) ?>',
            email: '<?= addslashes($_SESSION['user_email'] ?? '') ?>',
        },
        theme: { color: '#22c55e' },
        modal: {
            ondismiss: function() {
                // If user closes without paying, allow via demo fallback
                if (confirm('Payment cancelled. Submit as demo purchase (for testing)?')) {
                    document.getElementById('payment_ref_input').value = 'DEMO_' + Date.now();
                    document.getElementById('purchase-form').submit();
                }
            }
        }
    };

    try {
        const rzp = new Razorpay(options);
        rzp.open();
    } catch(e) {
        // Razorpay unavailable — demo mode
        if (confirm('Razorpay unavailable. Confirm as demo purchase?')) {
            document.getElementById('payment_ref_input').value = 'DEMO_' + Date.now();
            document.getElementById('purchase-form').submit();
        }
    }
}
</script>
</body>
</html>
