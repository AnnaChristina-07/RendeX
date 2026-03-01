<?php
/**
 * api_availability.php
 * Returns booked & pre-booked date ranges for an item (JSON).
 * Called via AJAX from item-details.php calendar widget.
 */
header('Content-Type: application/json');
session_start();

$item_id = isset($_GET['item_id']) ? trim($_GET['item_id']) : null;
if (!$item_id) {
    echo json_encode(['error' => 'Missing item_id']);
    exit();
}

require_once 'config/database.php';

$booked_ranges   = [];
$available_from  = null; // earliest free date

// ─── 1. Ranges from rentals.json (JSON renters) ───────────────────────────
$rentals_file = 'rentals.json';
if (file_exists($rentals_file)) {
    $rentals = json_decode(file_get_contents($rentals_file), true) ?: [];
    foreach ($rentals as $r) {
        // Match item id (string or int)
        $rid = $r['item']['id'] ?? null;
        if ($rid != $item_id) continue;
        $status = $r['status'] ?? 'active';
        if (in_array($status, ['active', 'confirmed', 'pending_inspection'])) {
            $s = $r['start_date'] ?? null;
            $e = $r['end_date']   ?? null;
            if ($s && $e) {
                $booked_ranges[] = ['start' => $s, 'end' => $e, 'type' => 'rented'];
            }
        }
    }
}

// ─── 2. Ranges from DB (rentals table + pre_bookings table) ──────────────
try {
    $pdo = getDBConnection();
    if ($pdo) {
        // Active rentals from DB
        $stmt = $pdo->prepare("
            SELECT start_date, end_date FROM rentals
            WHERE item_id = ? AND status NOT IN ('cancelled','returned','dispute')
        ");
        $stmt->execute([$item_id]);
        foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
            $booked_ranges[] = ['start' => $row['start_date'], 'end' => $row['end_date'], 'type' => 'rented'];
        }

        // Confirmed pre-bookings (block those dates too)
        $has_pb = $pdo->query("SHOW TABLES LIKE 'pre_bookings'")->rowCount() > 0;
        if ($has_pb) {
            $stmt = $pdo->prepare("
                SELECT start_date, end_date FROM pre_bookings
                WHERE item_id = ? AND status IN ('pending','confirmed','active')
            ");
            $stmt->execute([$item_id]);
            foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
                $booked_ranges[] = ['start' => $row['start_date'], 'end' => $row['end_date'], 'type' => 'prebooked'];
            }
        }
    }
} catch (Exception $e) {
    // Non-fatal
}

// ─── 3. Deduplicate & sort ─────────────────────────────────────────────────
usort($booked_ranges, fn($a, $b) => strcmp($a['start'], $b['start']));

// ─── 4. Compute next available date ───────────────────────────────────────
$today     = date('Y-m-d');
$candidate = $today;
$max_tries = 120; // search up to 4 months ahead
$i = 0;
while ($i < $max_tries) {
    $blocked = false;
    foreach ($booked_ranges as $r) {
        if ($candidate >= $r['start'] && $candidate <= $r['end']) {
            // Jump to day after end of this range
            $candidate = date('Y-m-d', strtotime($r['end'] . ' +1 day'));
            $blocked = true;
            break;
        }
    }
    if (!$blocked) {
        $available_from = $candidate;
        break;
    }
    $i++;
}

echo json_encode([
    'item_id'        => $item_id,
    'booked_ranges'  => $booked_ranges,
    'available_from' => $available_from ?? $today,
    'today'          => $today,
]);
