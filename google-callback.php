<?php
ob_start();
session_start();

function get_local_name_if_exists($email, $default_name) {
    $users_file = 'users.json';
    if (file_exists($users_file)) {
        $users = json_decode(file_get_contents($users_file), true) ?: [];
        foreach ($users as $user) {
            if (strcasecmp($user['email'], $email) === 0) {
                return $user['name']; // Return name from signup
            }
        }
    }
    return $default_name; // Fallback to Google name
}

// Handle Manual Profile Post (Implicit Flow from client)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['google_name'])) {
    $email = $_POST['google_email'];
    $google_name = $_POST['google_name'];
    $google_id = $_POST['google_id'];
    $pic = $_POST['google_picture'] ?? '';
    
    // ENSURE USER EXISTS IN OUR SYSTEM
    $users_file = 'users.json';
    $users = file_exists($users_file) ? json_decode(file_get_contents($users_file), true) : [];
    $exists = false;
    $existing_user = null;
    foreach ($users as $u) {
        if ($u['email'] === $email) {
            $exists = true;
            $existing_user = $u;
            break;
        }
    }
    
    if (!$exists) {
        // Register new Google user
        $new_user = [
            'id' => $google_id,
            'name' => $google_name,
            'email' => $email,
            'phone' => '',
            'role' => 'user',
            'password_hash' => 'google_oauth_no_password',
            'created_at' => date('Y-m-d H:i:s'),
            'profile_picture' => $pic
        ];
        $users[] = $new_user;
        file_put_contents($users_file, json_encode($users, JSON_PRETTY_PRINT));
        $existing_user = $new_user;
        
        // Also add to Database
        try {
            require_once 'config/database.php';
            $pdo = getDBConnection();
            if ($pdo) {
                $stmt = $pdo->prepare("INSERT IGNORE INTO users (id, name, email, role, password_hash, profile_picture, created_at) VALUES (?, ?, ?, ?, ?, ?, ?)");
                $stmt->execute([$google_id, $google_name, $email, 'user', 'google_oauth_no_password', $pic, date('Y-m-d H:i:s')]);
            }
        } catch (Exception $e) {}
    } else {
        // Update user ID in session to match what's in our records if it differs
        // (Though usually sub should be the same)
        $google_id = $existing_user['id']; 
    }

    $_SESSION['user_id'] = $google_id;
    $_SESSION['user_email'] = $email;
    $_SESSION['user_name'] = $existing_user['name'];
    $_SESSION['user_picture'] = $pic;
    
    if ($email === 'annachristina2005@gmail.com') {
        header("Location: admin_dashboard.php");
    } elseif ($email === 'owner@gmail.com' || (isset($existing_user['role']) && $existing_user['role'] === 'owner')) {
        header("Location: owner_dashboard.php");
    } elseif (isset($existing_user['role']) && $existing_user['role'] === 'owner_pending') {
        session_unset();
        session_destroy();
        header("Location: login.php?error=" . urlencode("Your owner account is pending approval."));
    } else {
        header("Location: dashboard.php");
    }
    exit();
}

// Handle OAuth authorization code (Legacy)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['code'])) {
     // Legacy flow ignored as we prefer the Implicit/Credential flows for now
}

// Handle JWT ID token (from One Tap flow)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['credential'])) {
    $id_token = $_POST['credential'];
    $client_id = "6682512066-vgnsqcpb1p7ff5bfv78kp4c0mv5pu9tv.apps.googleusercontent.com";

    // Verify the token using Google's tokeninfo endpoint
    $url = "https://oauth2.googleapis.com/tokeninfo?id_token=" . $id_token;
    $response = file_get_contents($url);
    
    if ($response) {
        $payload = json_decode($response, true);
        
        // Verify Client ID matches
        if (isset($payload['aud']) && $payload['aud'] === $client_id) {
            
            $email = $payload['email'];
            $google_name = $payload['name'];
            $google_id = $payload['sub'];
            $pic = $payload['picture'] ?? '';
            
            // ENSURE USER EXISTS
            require_once 'config/database.php';
            $users_file = 'users.json';
            $users = file_exists($users_file) ? json_decode(file_get_contents($users_file), true) : [];
            $exists = false;
            $existing_user = null;
            foreach ($users as $u) {
                if ($u['email'] === $email) {
                    $exists = true;
                    $existing_user = $u;
                    break;
                }
            }
            
            if (!$exists) {
                $new_user = [
                    'id' => $google_id,
                    'name' => $google_name,
                    'email' => $email,
                    'phone' => '',
                    'role' => 'user',
                    'password_hash' => 'google_oauth_no_password',
                    'created_at' => date('Y-m-d H:i:s'),
                    'profile_picture' => $pic
                ];
                $users[] = $new_user;
                file_put_contents($users_file, json_encode($users, JSON_PRETTY_PRINT));
                $existing_user = $new_user;
                
                try {
                    $pdo = getDBConnection();
                    if ($pdo) {
                        $stmt = $pdo->prepare("INSERT IGNORE INTO users (id, name, email, role, password_hash, profile_picture, created_at) VALUES (?, ?, ?, ?, ?, ?, ?)");
                        $stmt->execute([$google_id, $google_name, $email, 'user', 'google_oauth_no_password', $pic, date('Y-m-d H:i:s')]);
                    }
                } catch (Exception $e) {}
            }
            
            // Token is valid. Set session variables.
            $_SESSION['user_id'] = $existing_user['id'];
            $_SESSION['user_email'] = $email;
            $_SESSION['user_name'] = $existing_user['name'];
            $_SESSION['picture'] = $pic;
            
            // Redirect to dashboard
            if ($email === 'annachristina2005@gmail.com') {
                header("Location: admin_dashboard.php");
            } elseif ($email === 'owner@gmail.com' || (isset($existing_user['role']) && $existing_user['role'] === 'owner')) {
                header("Location: owner_dashboard.php");
            } elseif (isset($existing_user['role']) && $existing_user['role'] === 'owner_pending') {
                session_unset();
                session_destroy();
                header("Location: login.php?error=" . urlencode("Your owner account is pending approval."));
            } else {
                header("Location: dashboard.php");
            }
            exit();
        } else {
            echo "Error: Invalid Client ID.";
        }
    } else {
        echo "Error: Failed to verify token with Google.";
    }
} else {
    // Direct access or missing credential
    if (!isset($_POST['google_name'])) {
        header("Location: login.php");
        exit();
    }
}
?>
