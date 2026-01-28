<?php
$data = json_decode(file_get_contents('rentals.json'), true);
echo "Count: " . count($data) . "\n";
foreach ($data as $i => $r) {
    if ($i < 15) {
        $name = $r['item']['name'] ?? 'N/A';
        echo "[$i] $name\n";
    }
}
?>
