<?php
require 'c:\xamppp\htdocs\RendeX\config\database.php';
$pdo = getDBConnection();
$reqId = 1;

// Simulate form post
$_POST = [
    'title' => 'Test Item',
    'category' => 'electronics',
    'price' => '100',
    'description' => 'Test',
    'addr_house' => '1',
    'addr_street' => '2',
    'addr_city' => '3',
    'addr_state' => '4',
    'addr_pin' => '560000',
    'listing_type' => 'rent',
    'handover_methods' => ['pickup'],
    'fulfilled_request_id' => $reqId
];
$_FILES = []; // assume no files, it fails with "Please upload at least one photo"

// Or wait, let's just test what happens to the form rendering
$fulfilled_request_id = isset($_POST['fulfilled_request_id']) && !empty($_POST['fulfilled_request_id']) ? $_POST['fulfilled_request_id'] : null;
echo "fulfilled: " . var_export($fulfilled_request_id, true) . "\n";
?>
