<?php
$data = json_decode(file_get_contents('rentals.json'), true);
if (isset($data[20])) {
    echo "Item 20 JSON: " . json_encode($data[20], JSON_PRETTY_PRINT);
} else {
    echo "Item 20 not found. Count: " . count($data);
}
?>
