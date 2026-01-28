<?php
$data = json_decode(file_get_contents('items.json'), true);
foreach ($data as $item) {
    echo "Item: " . ($item['title'] ?? $item['name'] ?? 'NoName') . "\n";
}
?>
