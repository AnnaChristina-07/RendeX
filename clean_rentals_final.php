<?php
$f = 'rentals.json';
$data = json_decode(file_get_contents($f), true);
$new_data = [];
$removed = 0;

foreach ($data as $r) {
    // Remove if ID starts with RENT_ (uppercase), keep rent_ (lowercase)
    if (isset($r['id']) && strpos($r['id'], 'RENT_') === 0) {
        $removed++;
        continue; // Skip
    }
    
    // Also remove if title matches dummy list (just in case)
    $json = json_encode($r);
    $targets = ['Folding Dining Table', 'Bosch Drill', 'Bean Bag', 'Bookshelf', 'Projector', 'Camping Tent'];
    $skip = false;
    foreach($targets as $t) {
        if (stripos($json, $t) !== false) {
             $skip = true;
             $removed++; 
             break;
        }
    }
    if ($skip) continue;

    $new_data[] = $r;
}

file_put_contents($f, json_encode($new_data, JSON_PRETTY_PRINT));
echo "Removed $removed dummy rentals (uppercase RENT_).\n";
echo "Kept " . count($new_data) . " rentals.\n";
?>
