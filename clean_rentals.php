<?php
$f = 'rentals.json';
$data = json_decode(file_get_contents($f), true);
$new_data = [];
$removed_count = 0;

$targets = [
    'Folding Dining Table',
    'Bosch Drill',
    'Bean Bag',
    'Wooden Bookshelf',
    'Home Projector',
    'Camping Tent',
    'Sleeping Bag',
    'Canon EOS',
    'Urban Commuter',
    'Noise Cancelling'
];

foreach ($data as $r) {
    echo "Checking: " . ($r['item']['name'] ?? 'Unknown') . "... ";
    $should_remove = false;
    foreach ($targets as $t) {
        if (stripos($r['item']['name'] ?? '', $t) !== false) {
            $should_remove = true;
            break;
        }
    }
    
    // Also remove items with numeric ID < 1000 just in case
    if (isset($r['id']) && is_numeric($r['id']) && $r['id'] < 1000) $should_remove = true;

    if ($should_remove) {
        echo "REMOVING.\n";
        $removed_count++;
    } else {
        echo "KEEPING.\n";
        $new_data[] = $r;
    }
}

file_put_contents($f, json_encode($new_data, JSON_PRETTY_PRINT));
echo "\nTotal Removed: $removed_count\n";
echo "Remaining: " . count($new_data) . "\n";
?>
