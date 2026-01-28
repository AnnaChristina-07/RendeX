<?php
$files = ['items.json', 'rentals.json'];
foreach ($files as $f) {
    if (file_exists($f)) {
        echo "--- $f ---\n";
        $data = json_decode(file_get_contents($f), true);
        if (is_array($data)) {
            foreach ($data as $i) {
                $id = $i['id'] ?? 'N/A';
                $title = $i['title'] ?? $i['item']['name'] ?? 'N/A';
                echo "ID: $id | Title: $title\n";
            }
        }
    } else {
        echo "$f not found\n";
    }
}
?>
