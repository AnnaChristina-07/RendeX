<?php
$files = ['items.json', 'rentals.json', 'deliveries.json'];

$dummy_titles = [
    'Folding Dining Table (6 Seater)',
    'Bosch Drill Machine Kit',
    'Large Bean Bag Chair',
    'Wooden Bookshelf',
    'Epson 4K Home Projector',
    '4-Person Camping Tent',
    'Sleeping Bag (-5°C)',
    'Urban Commuter Backpack',
    'Sony Noise Cancelling Headphones',
    'Canon EOS 1500D Kit',
    'Decathlon 2-Person Tent',
    'Generic Wheelchair',
    'Drill Machine',
    'Camping Tent',
    'DSLR Camera' // just in case
];

// Helper to check if item is dummy
function isDummy($item, $dummy_titles) {
    // Check ID - if simple integer < 1000, likely dummy (unless user app uses simple IDs, but it uses uniqid item_...)
    if (isset($item['id']) && is_numeric($item['id']) && $item['id'] < 1000) return true;
    
    // Check Title
    $title = $item['title'] ?? $item['item']['name'] ?? '';
    if (in_array($title, $dummy_titles)) return true;
    
    return false;
}

foreach ($files as $f) {
    if (!file_exists($f)) continue;
    
    $data = json_decode(file_get_contents($f), true);
    if (!is_array($data)) continue;
    
    $initial_count = count($data);
    $new_data = [];
    foreach ($data as $item) {
        if (!isDummy($item, $dummy_titles)) {
            $new_data[] = $item;
        }
    }
    
    file_put_contents($f, json_encode($new_data, JSON_PRETTY_PRINT));
    echo "Cleaned $f: $initial_count -> " . count($new_data) . " items.\n";
}
?>
