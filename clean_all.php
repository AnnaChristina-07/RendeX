<?php
// Debug rentals structure
$r_data = json_decode(file_get_contents('rentals.json'), true);
if (!empty($r_data)) {
    echo "First Rental: " . print_r($r_data[0], true) . "\n";
}

// Clean Items
$i_data = json_decode(file_get_contents('items.json'), true);
echo "Items Count: " . count($i_data) . "\n";

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
    'Generic Wheelchair' // Be specific
];

$new_items = [];
$removed_items = 0;
foreach ($i_data as $item) {
    $title = $item['title'] ?? $item['name'] ?? '';
    $should_remove = false;
    foreach ($targets as $t) {
        if (stripos($title, $t) !== false) {
            $should_remove = true;
            break;
        }
    }
    if ($should_remove) {
        echo "Removing Item: $title\n";
        $removed_items++;
    } else {
        $new_items[] = $item;
    }
}
file_put_contents('items.json', json_encode($new_items, JSON_PRETTY_PRINT));
echo "Removed $removed_items items from items.json\n";

?>
