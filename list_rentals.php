<?php
$data = json_decode(file_get_contents('rentals.json'), true);
echo "Rentals Count: " . count($data) . "\n";
foreach ($data as $i => $r) {
    echo "[$i] ID: " . ($r['id']??'N/A') . " | Name: " . ($r['item']['name']??'N/A') . "\n";
}
?>
