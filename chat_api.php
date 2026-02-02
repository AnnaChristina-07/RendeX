<?php
session_start();
header('Content-Type: application/json');

// Get the raw POST data
$input = json_decode(file_get_contents('php://input'), true);
$userMessage = trim($input['message'] ?? '');

if (empty($userMessage)) {
    echo json_encode(['reply' => 'I am here to help!']);
    exit;
}

// Configuration: Set this to true and add your key to use real OpenAI
$useOpenAI = false; 
$apiKey = 'YOUR_OPENAI_API_KEY_HERE';

// If configured, try OpenAI
if ($useOpenAI && $apiKey !== 'YOUR_OPENAI_API_KEY_HERE') {
    $reply = callOpenAI($userMessage, $apiKey);
    if ($reply) {
        echo json_encode(['reply' => $reply]);
        exit;
    }
}

// FALLBACK: Robust Local Pattern Matching
// This simulates an intelligent agent for common queries without needing an API key.
$reply = getLocalResponse($userMessage);
echo json_encode(['reply' => $reply]);

/**
 * Local Rule-Based Response Logic
 */
function getLocalResponse($msg) {
    $m = strtolower($msg);
    
    // 1. Greetings & Small Talk
    if (preg_match('/\b(hi|hello|hey|yo|greetings|sup|hai)\b/', $m)) return "Hello! Welcome to RendeX. How can I assist you with renting or lending today?";
    if (preg_match('/\b(how are you|how is it going)\b/', $m)) return "I'm doing great, thanks for asking! Ready to help you find some cool items.";
    if (preg_match('/\b(who are you|what are you)\b/', $m)) return "I'm the RendeX Assistant, your personal guide to our peer-to-peer rental marketplace.";
    if (preg_match('/\b(thank|thanks)\b/', $m)) return "You're helpful! Happy to help.";
    if (preg_match('/\b(bye|goodbye|see ya)\b/', $m)) return "Goodbye! Happy renting!";

    // 2. Core RendeX Functionality
    // Renting
    if (strpos($m, 'rent') !== false || strpos($m, 'book') !== false || strpos($m, 'find') !== false) {
        if (strpos($m, 'how') !== false) return "To rent an item, simply browse the categories on the dashboard. Click on an item, select your dates, and hit 'Request Rental'.";
        if (strpos($m, 'cancel') !== false) return "You can cancel a booking from your 'My Rentals' page. Note that cancellation fees may apply depending on how close to the date it is.";
        return "You can find thousands of items to rent here. Try searching for 'camera', 'tent', or 'drill' in the search bar!";
    }

    // Lending/Listing
    if (strpos($m, 'lend') !== false || strpos($m, 'list') !== false || strpos($m, 'sell') !== false || strpos($m, 'post') !== false) {
        if (strpos($m, 'how') !== false) return "Listing is easy! Click 'Lend Items' in the menu, upload photos, set a description and price, and publish.";
        if (strpos($m, 'price') !== false || strpos($m, 'cost') !== false) return "It's free to list! We only take a small commission (10%) when you successfully rent out an item.";
        return "Turn your idle items into cash! Click 'Lend Items' to get started.";
    }

    // Delivery
    if (strpos($m, 'delivery') !== false || strpos($m, 'driver') !== false || strpos($m, 'ship') !== false) {
        if (strpos($m, 'join') !== false || strpos($m, 'job') !== false) return "Want to earn extra money? Go to the 'Earn on the go' section on the dashboard and apply to be a Delivery Partner.";
        return "We offer flexible delivery. Handover directly with the owner, or choose a RendeX Delivery Partner for door-to-door service.";
    }

    // Account & Security
    if (strpos($m, 'password') !== false || strpos($m, 'reset') !== false) return "You can reset your password via the 'Forgot Password' link on the login page.";
    if (strpos($m, 'safe') !== false || strpos($m, 'trust') !== false || strpos($m, 'scam') !== false) return "We verify all users and hold payments securely until the rental is complete to ensure safety.";

    // 3. Fun / Random Fallbacks (To feel more "AI-like")
    if (strpos($m, 'joke') !== false) return "Why did the scarecrow win an award? Because he was outstanding in his field! (We have rentals for fields too!)";
    if (strpos($m, 'weather') !== false) return "I can't check the weather, but RendeX is great for both sunny days (rent a bike!) and rainy ones (rent a gaming console!).";

    // 4. Specific Category Helpers
    if (strpos($m, 'camera') !== false || strpos($m, 'lens') !== false) return "Looking for photography gear? Check our 'Electronic Devices' category for cameras and lenses.";
    if (strpos($m, 'tent') !== false || strpos($m, 'camp') !== false) return "Adventure awaits! Our 'Travel/Outdoor Gear' category has plenty of tents and camping equipment.";

    // Default Fallback
    return "I'm not exactly sure about that, but I'm learning every day! Try asking about renting, listing items, or our delivery services.";
}

/**
 * Call OpenAI API
 */
function callOpenAI($message, $key) {
    $url = 'https://api.openai.com/v1/chat/completions';
    $data = [
        'model' => 'gpt-3.5-turbo',
        'messages' => [
            ['role' => 'system', 'content' => 'You are RendeX Assistant, a helpful support bot for a rental marketplace.'],
            ['role' => 'user', 'content' => $message]
        ],
        'max_tokens' => 150
    ];

    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/json',
        'Authorization: Bearer ' . $key
    ]);

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($httpCode === 200) {
        $json = json_decode($response, true);
        return $json['choices'][0]['message']['content'] ?? null;
    }
    return null;
}
?>
