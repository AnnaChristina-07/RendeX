<?php
session_start();

// Strict Admin Security Check
if (!isset($_SESSION['user_id']) || !isset($_SESSION['user_email']) || $_SESSION['user_email'] !== 'annachristina2005@gmail.com') {
    header("Location: index.php");
    exit();
}

$report_type = $_GET['type'] ?? 'all';

// Load Data
$users = json_decode(file_get_contents('users.json'), true);
$items = json_decode(file_get_contents('items.json'), true);
$rentals = json_decode(file_get_contents('rentals.json'), true);

// Map Helpers
$user_map = array_column($users, 'name', 'id');
$item_map = [];
foreach ($items as $i) $item_map[$i['id']] = $i;

// Prepare CSV Output
header('Content-Type: text/csv');
header('Content-Disposition: attachment; filename="rendex_report_' . $report_type . '_' . date('Y-m-d') . '.csv"');

$output = fopen('php://output', 'w');

if ($report_type === 'rentals') {
    // Columns: Date, Rental ID, Item Name, Owner Name, Renter Name, Price, Status
    fputcsv($output, ['Date', 'Rental ID', 'Item Name', 'Owner Name', 'Renter Name', 'Price (INR)', 'Status']);

    foreach ($rentals as $r) {
        $item = $item_map[$r['item_id']] ?? [];
        $owner_id = $item['user_id'] ?? 'Unknown';
        $owner_name = $user_map[$owner_id] ?? 'Unknown';
        $renter_name = $user_map[$r['user_id']] ?? 'Unknown';

        fputcsv($output, [
            $r['start_date'],
            $r['id'],
            $item['title'] ?? 'Unknown Item',
            $owner_name,
            $renter_name,
            $r['total_price'],
            $r['status']
        ]);
    }
} elseif ($report_type === 'owners') {
    // Output Owner Revenue Summary
    fputcsv($output, ['Owner Name', 'Email', 'Items Listed', 'Total Rentals', 'Total Revenue (INR)']);
    
    $owner_stats = [];

    // Initialize owners
    foreach ($users as $u) {
        // Simple check for potential owners
        $owner_stats[$u['id']] = [
            'name' => $u['name'], 
            'email' => $u['email'], 
            'items' => 0, 
            'rentals' => 0, 
            'revenue' => 0
        ];
    }

    // Count Items
    foreach ($items as $i) {
        if(isset($owner_stats[$i['user_id']])) {
            $owner_stats[$i['user_id']]['items']++;
        }
    }

    // Count Revenue
    foreach ($rentals as $r) {
        $item = $item_map[$r['item_id']] ?? null;
        if ($item && isset($owner_stats[$item['user_id']])) {
            $owner_stats[$item['user_id']]['rentals']++;
            $owner_stats[$item['user_id']]['revenue'] += (float)$r['total_price'];
        }
    }

    // Filter to only those with activity
    foreach ($owner_stats as $stat) {
        if ($stat['items'] > 0 || $stat['revenue'] > 0) {
            fputcsv($output, $stat);
        }
    }

} elseif ($report_type === 'renters') {
     // Output Renter Spending Summary
     fputcsv($output, ['Renter Name', 'Email', 'Total Rentals', 'Total Spent (INR)']);
     
     $renter_stats = [];
     foreach ($users as $u) {
         $renter_stats[$u['id']] = [
             'name' => $u['name'],
             'email' => $u['email'],
             'rentals' => 0,
             'spent' => 0
         ];
     }

     foreach ($rentals as $r) {
         if (isset($renter_stats[$r['user_id']])) {
             $renter_stats[$r['user_id']]['rentals']++;
             $renter_stats[$r['user_id']]['spent'] += (float)$r['total_price'];
         }
     }

     foreach ($renter_stats as $stat) {
         if ($stat['rentals'] > 0) {
             fputcsv($output, $stat);
         }
     }
}

fclose($output);
exit();
?>
