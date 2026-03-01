<?php
ob_start();
session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$item_id = $_GET['id'] ?? null;
if (!$item_id) { header("Location: dashboard.php"); exit(); }

require_once 'config/database.php';
require_once 'config/mail.php';

// ─── Load Item ─────────────────────────────────────────────────────────────
$item = null;
$pdo  = null;
try {
    $pdo = getDBConnection();
    if ($pdo) {
        $stmt = $pdo->prepare("SELECT * FROM items WHERE id = ?");
        $stmt->execute([$item_id]);
        $db = $stmt->fetch();
        if ($db) {
            $item = $db;
            $item['name'] = $db['title'];
            $item['price'] = $db['price_per_day'];
            $item['listing_type'] = $db['listing_type'] ?? 'rent';
            $item['allow_prebooking'] = $db['allow_prebooking'] ?? 1;
            $item['max_advance_days'] = $db['max_advance_days'] ?? 60;
            $item['address'] = $db['location'];
            $images = json_decode($db['images'] ?? '[]', true) ?: [];
            $item['img'] = !empty($images) ? 'uploads/' . $images[0] : '';
            $item['user_id'] = $db['owner_id'];
        }
    }
} catch (Exception $e) {}

// JSON fallback
if (!$item) {
    $all = file_exists('items.json') ? json_decode(file_get_contents('items.json'), true) : [];
    foreach ((array)$all as $d) {
        if ($d['id'] == $item_id) {
            $item = $d;
            $item['name'] = $d['title'];
            $item['price'] = $d['price'] ?? $d['price_per_day'] ?? 0;
            $item['listing_type'] = $d['listing_type'] ?? 'rent';
            $item['allow_prebooking'] = $d['allow_prebooking'] ?? 1;
            $item['max_advance_days'] = $d['max_advance_days'] ?? 60;
            $item['img'] = !empty($d['images']) ? 'uploads/' . $d['images'][0] : '';
            break;
        }
    }
}

if (!$item || !($item['allow_prebooking'] ?? 1)) {
    header("Location: item-details.php?id=" . urlencode($item_id));
    exit();
}

// Guard: not own item
$owner_id = $item['user_id'] ?? $item['owner_id'] ?? null;
if ($owner_id == $_SESSION['user_id']) {
    header("Location: item-details.php?id=" . urlencode($item_id) . "&msg=own_item");
    exit();
}

// Owner name
$owner_name = $item['owner_name'] ?? 'Verified Owner';
if ($owner_id) {
    try {
        if ($pdo) {
            $u = $pdo->prepare("SELECT name FROM users WHERE id = ?");
            $u->execute([$owner_id]);
            $r = $u->fetch();
            if ($r) $owner_name = $r['name'];
        }
    } catch (Exception $e) {}
}

// ─── Conflict checker ──────────────────────────────────────────────────────
function hasConflict($pdo, $item_id, $start, $end) {
    // Check DB rentals
    try {
        if ($pdo) {
            $s = $pdo->prepare("
                SELECT id FROM rentals
                WHERE item_id = ? AND status NOT IN ('cancelled','returned')
                AND NOT (end_date < ? OR start_date > ?)
                LIMIT 1
            ");
            $s->execute([$item_id, $start, $end]);
            if ($s->fetch()) return 'rented';

            $has_pb = $pdo->query("SHOW TABLES LIKE 'pre_bookings'")->rowCount() > 0;
            if ($has_pb) {
                $s = $pdo->prepare("
                    SELECT id FROM pre_bookings
                    WHERE item_id = ? AND status IN ('pending','confirmed','active')
                    AND NOT (end_date < ? OR start_date > ?)
                    LIMIT 1
                ");
                $s->execute([$item_id, $start, $end]);
                if ($s->fetch()) return 'prebooked';
            }
        }
    } catch (Exception $e) {}

    // Check rentals.json
    $rentals = file_exists('rentals.json') ? json_decode(file_get_contents('rentals.json'), true) : [];
    foreach ((array)$rentals as $r) {
        if (($r['item']['id'] ?? null) != $item_id) continue;
        if (in_array($r['status'] ?? '', ['active','confirmed','pending_inspection'])) {
            if (!($r['end_date'] < $start || $r['start_date'] > $end)) return 'rented';
        }
    }
    return false;
}

// ─── Handle POST ──────────────────────────────────────────────────────────
$error = null;
$success = null;
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $start    = $_POST['start_date'] ?? '';
    $end      = $_POST['end_date'] ?? '';
    $method   = $_POST['delivery_method'] ?? 'pickup';
    $address  = trim($_POST['delivery_address'] ?? '');
    $notes    = trim($_POST['notes'] ?? '');
    $today    = date('Y-m-d');
    $max_date = date('Y-m-d', strtotime('+' . ($item['max_advance_days']) . ' days'));

    if (!$start || !$end || $start > $end) {
        $error = "Please choose valid start and end dates.";
    } elseif ($start < $today) {
        $error = "Start date cannot be in the past.";
    } elseif ($start > $max_date) {
        $error = "Cannot pre-book more than {$item['max_advance_days']} days in advance.";
    } else {
        $conflict = hasConflict($pdo, is_numeric($item_id) ? $item_id : 0, $start, $end);
        if ($conflict === 'rented') {
            $error = "Those dates overlap with an active rental. Please choose different dates.";
        } elseif ($conflict === 'prebooked') {
            $error = "Someone else has already reserved those dates. Please pick a different window.";
        } else {
            // Calculate
            $days = max(1, (int)round((strtotime($end) - strtotime($start)) / 86400) + 1);
            $rate = $item['price'];
            $total = $days * $rate;
            $ref = 'PB_' . strtoupper(substr(uniqid(), -8));
            $expires = date('Y-m-d H:i:s', strtotime('+48 hours'));

            // Save to DB
            $saved = false;
            try {
                if ($pdo) {
                    $stmt = $pdo->prepare("
                        INSERT INTO pre_bookings
                        (booking_ref, item_id, user_id, owner_id, start_date, end_date,
                         total_days, daily_rate, total_amount, status, delivery_method,
                         delivery_address, notes, expires_at, created_at)
                        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 'pending', ?, ?, ?, ?, NOW())
                    ");
                    $stmt->execute([
                        $ref,
                        is_numeric($item_id) ? $item_id : 0,
                        $_SESSION['user_id'],
                        $owner_id,
                        $start, $end, $days, $rate, $total,
                        $method,
                        ($method === 'delivery') ? $address : null,
                        $notes ?: null,
                        $expires
                    ]);
                    $saved = true;

                    // Notify owner
                    $pdo->prepare("
                        INSERT INTO notifications (user_id, title, message, type, created_at)
                        VALUES (?, 'New Pre-Booking Request', ?, 'info', NOW())
                    ")->execute([
                        $owner_id,
                        "{$_SESSION['user_name']} wants to pre-book \"{$item['name']}\" from {$start} to {$end}."
                    ]);
                }
            } catch (Exception $e) {}

            // Send email to owner
            try {
                $owner_email = '';
                if ($pdo) {
                    $oe = $pdo->prepare("SELECT email FROM users WHERE id = ?");
                    $oe->execute([$owner_id]);
                    $oe = $oe->fetch();
                    $owner_email = $oe['email'] ?? '';
                }
                if ($owner_email) {
                    $body = "<div style='font-family:sans-serif;max-width:580px;margin:auto;padding:24px;border:1px solid #e9e8ce;border-radius:20px'>
                        <h2 style='color:#1c1c0d'>📅 New Pre-Booking Request</h2>
                        <p>Hi <b>" . htmlspecialchars($owner_name) . "</b>,</p>
                        <p><b>" . htmlspecialchars($_SESSION['user_name']) . "</b> wants to pre-book <b>" . htmlspecialchars($item['name']) . "</b>.</p>
                        <div style='background:#f8f8f5;padding:16px;border-radius:12px;margin:16px 0'>
                            <p><b>Dates:</b> {$start} → {$end} ({$days} days)</p>
                            <p><b>Estimated Earnings:</b> ₹" . number_format($total, 2) . "</p>
                            <p><b>Ref:</b> {$ref}</p>
                        </div>
                        <p>Please <b>approve or decline</b> this request from your <a href='http://{$_SERVER['HTTP_HOST']}/RendeX/owner_dashboard.php?tab=prebookings' style='color:#000;font-weight:bold'>Owner Dashboard</a> within 48 hours.</p>
                        <p style='color:#888;font-size:12px'>This request expires on {$expires} if not reviewed.</p>
                    </div>";
                    send_smtp_email($owner_email, "Pre-Booking Request — {$ref}", $body);
                }
            } catch (Exception $e) {}

            // Email to renter
            $renter_email = $_SESSION['user_email'] ?? '';
            if ($renter_email) {
                $body = "<div style='font-family:sans-serif;max-width:580px;margin:auto;padding:24px;border:1px solid #e9e8ce;border-radius:20px'>
                    <h2 style='color:#1c1c0d'>📅 Pre-Booking Submitted!</h2>
                    <p>Hi <b>" . htmlspecialchars($_SESSION['user_name']) . "</b>,</p>
                    <p>Your pre-booking request for <b>" . htmlspecialchars($item['name']) . "</b> has been submitted.</p>
                    <div style='background:#f8f8f5;padding:16px;border-radius:12px;margin:16px 0'>
                        <p><b>Ref:</b> {$ref}</p>
                        <p><b>Dates:</b> {$start} → {$end} ({$days} days)</p>
                        <p><b>Status:</b> Pending Owner Approval</p>
                    </div>
                    <p>The owner has 48 hours to approve. You'll be notified by email once they respond.</p>
                </div>";
                send_smtp_email($renter_email, "Pre-Booking Submitted — {$ref}", $body);
            }

            $success = [
                'ref'   => $ref,
                'item'  => $item['name'],
                'start' => $start,
                'end'   => $end,
                'days'  => $days,
                'total' => $total,
            ];
        }
    }
}

$today_str    = date('Y-m-d');
$max_date_str = date('Y-m-d', strtotime('+' . ($item['max_advance_days'] ?? 60) . ' days'));
?>
<!DOCTYPE html>
<html class="light" lang="en">
<head>
    <meta charset="utf-8"/>
    <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
    <title>Pre-Book: <?= htmlspecialchars($item['name']) ?> — RendeX</title>
    <link href="https://fonts.googleapis.com" rel="preconnect"/>
    <link crossorigin href="https://fonts.gstatic.com" rel="preconnect"/>
    <link href="https://fonts.googleapis.com/css2?family=Spline+Sans:wght@300;400;500;600;700;800&display=swap" rel="stylesheet"/>
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&display=swap" rel="stylesheet"/>
    <script src="https://cdn.tailwindcss.com?plugins=forms"></script>
    <script>
      tailwind.config = {
        darkMode: "class",
        theme: { extend: {
            colors: { "primary":"#f9f506","background-light":"#f8f8f5","background-dark":"#1e2019","surface-dark":"#2d2c18","text-main":"#1c1c0d","text-muted":"#5e5e4a" },
            fontFamily: { "display":["Spline Sans","sans-serif"] }
        }}
      }
    </script>
    <style>
        body { font-family: "Spline Sans", sans-serif; }
        .material-symbols-outlined { font-variation-settings: 'FILL' 0, 'wght' 400, 'GRAD' 0, 'opsz' 24 }
        .success-overlay { position:fixed;inset:0;background:rgba(0,0,0,0.8);backdrop-filter:blur(10px);z-index:9999;display:flex;align-items:center;justify-content:center;padding:20px; }
        @keyframes popIn { from{transform:scale(0.7);opacity:0} to{transform:scale(1);opacity:1} }
        .animate-pop { animation: popIn 0.4s cubic-bezier(0.34,1.56,0.64,1); }
    </style>
</head>
<body class="bg-background-light text-text-main min-h-screen">

<?php if ($success): ?>
<div class="success-overlay">
    <div class="bg-white max-w-md w-full rounded-[40px] p-10 text-center shadow-2xl relative overflow-hidden animate-pop">
        <div class="absolute top-0 left-0 w-full h-2 bg-primary"></div>
        <div class="w-24 h-24 bg-yellow-100 rounded-full flex items-center justify-center mx-auto mb-6">
            <span class="material-symbols-outlined text-yellow-600 text-5xl">event_available</span>
        </div>
        <h2 class="text-3xl font-black mb-2">Pre-Booking Submitted!</h2>
        <p class="text-text-muted mb-6">Ref: <strong class="text-black">#<?= $success['ref'] ?></strong></p>
        <div class="bg-gray-50 rounded-3xl p-6 text-left space-y-3 mb-8">
            <div class="flex justify-between text-sm"><span class="text-text-muted">Item</span><span class="font-black"><?= htmlspecialchars($success['item']) ?></span></div>
            <div class="flex justify-between text-sm"><span class="text-text-muted">Dates</span><span class="font-black"><?= $success['start'] ?> → <?= $success['end'] ?></span></div>
            <div class="flex justify-between text-sm"><span class="text-text-muted">Duration</span><span class="font-black"><?= $success['days'] ?> days</span></div>
            <div class="flex justify-between text-sm border-t pt-3"><span class="text-text-muted font-bold">Estimated Total</span><span class="font-black text-yellow-700">₹<?= number_format($success['total'], 2) ?></span></div>
        </div>
        <p class="text-xs text-text-muted mb-6">The owner will review within 48 hours. You'll be notified by email.</p>
        <div class="space-y-3">
            <a href="my-prebookings.php" class="block w-full bg-black text-primary py-4 rounded-2xl font-black hover:bg-gray-900 transition-all">View My Pre-Bookings</a>
            <a href="dashboard.php" class="block w-full border-2 border-gray-100 py-4 rounded-2xl font-bold text-text-muted hover:bg-gray-50 transition-all">Return Home</a>
        </div>
    </div>
</div>
<?php endif; ?>

<!-- Navbar -->
<header class="sticky top-0 z-50 border-b border-[#e9e8ce] bg-white/80 backdrop-blur-md px-6 py-4">
    <div class="max-w-[1100px] mx-auto flex items-center justify-between">
        <a href="dashboard.php" class="flex items-center gap-2">
            <div class="size-8 text-primary">
                <svg class="w-full h-full" fill="none" viewBox="0 0 48 48" xmlns="http://www.w3.org/2000/svg">
                    <path d="M36.7273 44C33.9891 44 31.6043 39.8386 30.3636 33.69C29.123 39.8386 26.7382 44 24 44C21.2618 44 18.877 39.8386 17.6364 33.69C16.3957 39.8386 14.0109 44 11.2727 44C7.25611 44 4 35.0457 4 24C4 12.9543 7.25611 4 11.2727 4C14.0109 4 16.3957 8.16144 17.6364 14.31C18.877 8.16144 21.2618 4 24 4C26.7382 4 29.123 8.16144 30.3636 14.31C31.6043 8.16144 33.9891 4 36.7273 4C40.7439 4 44 12.9543 44 24C44 35.0457 40.7439 44 36.7273 44Z" fill="currentColor"/>
                </svg>
            </div>
            <h2 class="text-xl font-bold tracking-tight">RendeX</h2>
        </a>
        <div class="flex items-center gap-4">
            <a href="profile.php" class="w-9 h-9 rounded-full bg-primary flex items-center justify-center text-black font-black shadow-sm">
                <?= strtoupper(substr($_SESSION['user_name'], 0, 1)) ?>
            </a>
            <a href="item-details.php?id=<?= urlencode($item_id) ?>" class="text-sm font-bold text-text-muted hover:text-black flex items-center gap-1">
                <span class="material-symbols-outlined text-lg">close</span> Cancel
            </a>
        </div>
    </div>
</header>

<main class="max-w-[1100px] mx-auto px-4 py-12">
    <div class="mb-8">
        <div class="inline-flex items-center gap-2 bg-yellow-100 text-yellow-800 text-xs font-bold px-3 py-1.5 rounded-full mb-4">
            <span class="material-symbols-outlined text-sm">calendar_add_on</span> Pre-Schedule
        </div>
        <h1 class="text-4xl font-black mb-2">Schedule a Future Date</h1>
        <p class="text-text-muted">Reserve this item for upcoming dates. The owner will confirm within 48 hrs.</p>
    </div>

    <?php if ($error): ?>
    <div class="mb-6 bg-red-50 border border-red-200 text-red-700 p-4 rounded-2xl font-bold flex items-center gap-3">
        <span class="material-symbols-outlined">error</span> <?= htmlspecialchars($error) ?>
    </div>
    <?php endif; ?>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-10 items-start">
        <!-- Left: Form -->
        <div class="lg:col-span-2 space-y-8">
            <!-- Item Card -->
            <div class="bg-white rounded-3xl p-6 border border-[#e9e8ce] shadow-sm flex gap-5 items-center">
                <?php if ($item['img']): ?>
                <div class="w-24 h-24 rounded-2xl overflow-hidden bg-gray-100 shrink-0">
                    <img src="<?= htmlspecialchars($item['img']) ?>" class="w-full h-full object-cover" onerror="this.src='https://placehold.co/200x200?text=Item'">
                </div>
                <?php endif; ?>
                <div>
                    <p class="text-[10px] font-black text-text-muted uppercase tracking-widest"><?= htmlspecialchars(ucwords($item['category'] ?? 'Item')) ?></p>
                    <h2 class="text-2xl font-black"><?= htmlspecialchars($item['name']) ?></h2>
                    <p class="text-primary font-black text-xl">₹<?= number_format($item['price'], 0) ?><span class="text-text-muted font-normal text-sm">/day</span></p>
                </div>
            </div>

            <!-- Booking Form -->
            <form method="POST" id="pb-form">
                <!-- Step 1: Dates -->
                <div class="bg-white rounded-3xl p-8 border border-[#e9e8ce] shadow-sm mb-6">
                    <div class="flex items-center gap-4 mb-6">
                        <div class="w-10 h-10 rounded-full bg-black text-white flex items-center justify-center font-bold">1</div>
                        <h3 class="text-2xl font-black">Select Dates</h3>
                        <span id="days-badge" class="ml-auto text-xs font-bold bg-yellow-100 text-yellow-700 px-3 py-1 rounded-full"></span>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div>
                            <label class="block text-xs font-bold text-text-muted uppercase tracking-widest mb-2">Start Date <span class="text-red-500">*</span></label>
                            <div class="relative">
                                <span class="absolute left-4 top-1/2 -translate-y-1/2 material-symbols-outlined text-gray-400">calendar_today</span>
                                <input type="date" name="start_date" id="start_date" required
                                    value="<?= isset($_POST['start_date']) ? htmlspecialchars($_POST['start_date']) : '' ?>"
                                    min="<?= $today_str ?>" max="<?= $max_date_str ?>"
                                    class="w-full pl-12 pr-4 py-4 rounded-2xl border-none bg-gray-50 focus:ring-2 focus:ring-primary font-bold"
                                    onchange="updateDays()">
                            </div>
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-text-muted uppercase tracking-widest mb-2">End Date <span class="text-red-500">*</span></label>
                            <div class="relative">
                                <span class="absolute left-4 top-1/2 -translate-y-1/2 material-symbols-outlined text-gray-400">calendar_month</span>
                                <input type="date" name="end_date" id="end_date" required
                                    value="<?= isset($_POST['end_date']) ? htmlspecialchars($_POST['end_date']) : '' ?>"
                                    min="<?= $today_str ?>" max="<?= $max_date_str ?>"
                                    class="w-full pl-12 pr-4 py-4 rounded-2xl border-none bg-gray-50 focus:ring-2 focus:ring-primary font-bold"
                                    onchange="updateDays()">
                            </div>
                        </div>
                    </div>

                    <div id="conflict-msg" class="hidden mt-4 bg-red-50 border border-red-200 text-red-700 text-sm font-bold p-3 rounded-xl flex items-center gap-2">
                        <span class="material-symbols-outlined text-sm">warning</span>
                        <span id="conflict-text">Checking availability…</span>
                    </div>
                </div>

                <!-- Step 2: Delivery -->
                <div class="bg-white rounded-3xl p-8 border border-[#e9e8ce] shadow-sm mb-6">
                    <div class="flex items-center gap-4 mb-6">
                        <div class="w-10 h-10 rounded-full bg-black text-white flex items-center justify-center font-bold">2</div>
                        <h3 class="text-2xl font-black">Pickup or Delivery?</h3>
                    </div>

                    <div class="grid grid-cols-2 gap-3 mb-6 p-1 bg-gray-100 rounded-2xl">
                        <button type="button" id="btn-pickup" onclick="setMethod('pickup')"
                            class="py-4 rounded-xl font-bold flex items-center justify-center gap-2 transition-all bg-black text-white shadow-lg">
                            <span class="material-symbols-outlined">storefront</span> Self-Pickup
                        </button>
                        <button type="button" id="btn-delivery" onclick="setMethod('delivery')"
                            class="py-4 rounded-xl font-bold flex items-center justify-center gap-2 transition-all text-text-muted">
                            <span class="material-symbols-outlined">local_shipping</span> Delivery
                        </button>
                        <input type="hidden" name="delivery_method" id="delivery_method" value="pickup">
                    </div>

                    <div id="address-section" class="hidden space-y-4">
                        <div>
                            <label class="block text-xs font-bold text-text-muted uppercase tracking-widest mb-2">Delivery Address <span class="text-red-500">*</span></label>
                            <input type="text" name="delivery_address" placeholder="Street, City, State, Pincode"
                                class="w-full px-5 py-4 rounded-2xl border-none bg-gray-50 focus:ring-2 focus:ring-primary font-medium">
                        </div>
                    </div>
                </div>

                <!-- Step 3: Notes -->
                <div class="bg-white rounded-3xl p-8 border border-[#e9e8ce] shadow-sm mb-6">
                    <div class="flex items-center gap-4 mb-4">
                        <div class="w-10 h-10 rounded-full bg-black text-white flex items-center justify-center font-bold">3</div>
                        <h3 class="text-2xl font-black">Notes <span class="text-text-muted font-normal text-base">(optional)</span></h3>
                    </div>
                    <textarea name="notes" rows="3" placeholder="e.g. I need it for a wedding on March 15th…"
                        class="w-full px-5 py-4 rounded-2xl border-none bg-gray-50 focus:ring-2 focus:ring-primary font-medium resize-none"
                    ><?= htmlspecialchars($_POST['notes'] ?? '') ?></textarea>
                </div>
            </form>
        </div>

        <!-- Right: Summary -->
        <div class="lg:sticky lg:top-32 space-y-6">
            <div class="bg-white rounded-3xl border border-[#e9e8ce] shadow-xl overflow-hidden">
                <div class="p-8 border-b border-gray-100">
                    <h3 class="text-xl font-black mb-6">Booking Summary</h3>
                    <div class="space-y-3 mb-6">
                        <div class="flex justify-between text-sm">
                            <span class="text-text-muted">Daily Rate</span>
                            <span class="font-bold">₹<?= number_format($item['price'], 0) ?></span>
                        </div>
                        <div class="flex justify-between text-sm">
                            <span class="text-text-muted">Duration</span>
                            <span class="font-bold" id="days-display">— days</span>
                        </div>
                    </div>
                    <div class="flex justify-between pt-4 border-t border-dashed border-gray-300">
                        <span class="text-lg font-black">Estimated Total</span>
                        <span class="text-2xl font-black text-yellow-700" id="total-display">₹0</span>
                    </div>
                    <p class="text-[10px] text-text-muted mt-2">* No payment now. Charged only after owner approval.</p>
                </div>
                <div class="p-8 bg-gray-50">
                    <div class="p-4 bg-yellow-50 border border-yellow-200 rounded-xl mb-5 text-xs font-medium text-yellow-800 leading-relaxed">
                        <strong>How it works:</strong> Submit request → Owner approves within 48 hrs → You pay → Rental is confirmed.
                    </div>
                    <button type="submit" form="pb-form"
                        class="w-full bg-primary hover:bg-yellow-300 text-black font-black py-5 rounded-2xl text-lg flex items-center justify-center gap-3 shadow-lg shadow-primary/20 transition-all hover:scale-[1.02] active:scale-95">
                        <span class="material-symbols-outlined">event_available</span>
                        Submit Pre-Booking
                    </button>
                </div>
            </div>

            <div class="bg-blue-50 border border-blue-100 rounded-2xl p-5">
                <div class="flex gap-3">
                    <span class="material-symbols-outlined text-blue-600 text-xl shrink-0">info</span>
                    <p class="text-xs font-medium text-blue-900 leading-relaxed">
                        You can pre-book up to <strong><?= $item['max_advance_days'] ?> days</strong> in advance for this item. Bookings auto-expire if the owner doesn't respond within 48 hours.
                    </p>
                </div>
            </div>
        </div>
    </div>
</main>

<script>
const pricePerDay = <?= (float)$item['price'] ?>;
const ITEM_ID = <?= json_encode($item_id) ?>;

function updateDays() {
    const s = document.getElementById('start_date').value;
    const e = document.getElementById('end_date').value;
    const badge = document.getElementById('days-badge');

    if (!s || !e) { badge.textContent = ''; return; }
    if (e < s) { document.getElementById('end_date').value = s; return; }

    const days = Math.ceil((new Date(e) - new Date(s)) / 86400000) + 1;
    const total = days * pricePerDay;

    badge.textContent = days + (days === 1 ? ' day' : ' days');
    document.getElementById('days-display').textContent = days + (days === 1 ? ' day' : ' days');
    document.getElementById('total-display').textContent = '₹' + total.toLocaleString('en-IN');

    // Live conflict check
    fetch(`api_availability.php?item_id=${encodeURIComponent(ITEM_ID)}`)
        .then(r => r.json())
        .then(data => {
            const ranges = data.booked_ranges || [];
            let conflict = null;
            for (const r of ranges) {
                if (!(e < r.start || s > r.end)) { conflict = r.type; break; }
            }
            const msg = document.getElementById('conflict-msg');
            const txt = document.getElementById('conflict-text');
            if (conflict) {
                txt.textContent = conflict === 'rented'
                    ? '⛔ These dates are already rented. Pick different dates.'
                    : '⚠️ Someone already reserved these dates.';
                msg.classList.remove('hidden');
            } else {
                msg.classList.add('hidden');
            }
        });
}

function setMethod(m) {
    document.getElementById('delivery_method').value = m;
    const section = document.getElementById('address-section');
    const bp = document.getElementById('btn-pickup');
    const bd = document.getElementById('btn-delivery');
    if (m === 'pickup') {
        section.classList.add('hidden');
        bp.className = 'py-4 rounded-xl font-bold flex items-center justify-center gap-2 transition-all bg-black text-white shadow-lg';
        bd.className = 'py-4 rounded-xl font-bold flex items-center justify-center gap-2 transition-all text-text-muted hover:bg-white/50';
    } else {
        section.classList.remove('hidden');
        bd.className = 'py-4 rounded-xl font-bold flex items-center justify-center gap-2 transition-all bg-black text-white shadow-lg';
        bp.className = 'py-4 rounded-xl font-bold flex items-center justify-center gap-2 transition-all text-text-muted hover:bg-white/50';
    }
}
</script>
</body>
</html>
