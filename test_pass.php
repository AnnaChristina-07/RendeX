<?php
$password = 'anna@2005';
$hash = '$2y$10$hGVUNpeLl8bbPNWC7oFONeB6c2ijfWZb2MLjmfBjvaF70PQRFm4NO';
if (password_verify($password, $hash)) {
    echo "Password MATCHES!";
} else {
    echo "Password DOES NOT match.";
}
?>
