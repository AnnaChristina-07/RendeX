<?php
$f = 'rentals.json';
$data = json_decode(file_get_contents($f), true);
$new_data = [];
$removed = 0;

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
    'Noise Cancelling',
    'Wheelchair', // Careful! User has "wheelchair". Dummy is "Generic Wheelchair"?
    'Mountain Bike'
];

foreach ($data as $r) {
    $json = json_encode($r);
    $remove = false;
    foreach ($targets as $t) {
        // Special case for Wheelchair to avoid removing valid one if it's just "wheelchair"
        if ($t === 'Wheelchair') {
             if (stripos($json, 'Generic Wheelchair') !== false) $remove = true;
        } else {
             if (stripos($json, $t) !== false) $remove = true;
        }
    }
    
    // Also remove items with numeric ID < 1000
    if (isset($r['id']) && is_numeric($r['id']) && $r['id'] < 1000) $remove = true;
    
    if ($remove) {
        $removed++;
    } else {
        $new_data[] = $r;
    }
}

file_put_contents($f, json_encode($new_data, JSON_PRETTY_PRINT));
echo "Removed $removed dummy rentals.\n";
echo "Kept " . count($new_data) . " rentals.\n";
?>
