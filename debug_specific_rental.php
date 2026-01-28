<?php
$data = json_decode(file_get_contents('rentals.json'), true);
if (isset($data[5])) {
    echo "Item 5 Keys: " . implode(', ', array_keys($data[5])) . "\n";
    echo "Item 5 Content: " . print_r($data[5], true);
} else {
    echo "Item 5 not found\n";
}
?>
