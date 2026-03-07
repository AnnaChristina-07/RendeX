<?php
require 'c:\xamppp\htdocs\RendeX\config\database.php';
$pdo = getDBConnection();
echo "--- DB Items ---\n";
print_r($pdo->query("SELECT id, title FROM items ORDER BY id DESC LIMIT 5")->fetchAll(PDO::FETCH_ASSOC));

echo "\n--- JSON Items ---\n";
$json_data = file_get_contents('c:\xamppp\htdocs\RendeX\items.json');
$items = json_decode($json_data, true);
$recent = array_slice($items, -5);
foreach ($recent as $item) {
    echo $item['id'] . " : " . $item['title'] . "\n";
}
?>
