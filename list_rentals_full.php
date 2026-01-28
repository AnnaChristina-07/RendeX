<?php
$data = json_decode(file_get_contents('rentals.json'), true);
foreach ($data as $r) {
    echo "ID: " . ($r['id'] ?? 'NoID') . " | Name: " . ($r['item']['name'] ?? 'NoName') . "\n";
}
?>
