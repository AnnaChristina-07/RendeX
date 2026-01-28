<?php
$data = json_decode(file_get_contents('rentals.json'), true);
foreach ($data as $i => $r) {
    if ($i > 5) break; 
    echo "Item $i: ";
    // Recursive search for 'Folding' in array
    array_walk_recursive($r, function($v, $k) {
        if (is_string($v) && stripos($v, 'Folding') !== false) {
            echo "Found 'Folding' in key '$k' => '$v'";
        }
    });
    echo "\n";
}
// Also just dump keys of one
print_r(array_keys($data[0]));
if (isset($data[0]['item'])) print_r(array_keys($data[0]['item']));
?>
