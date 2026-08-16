<?php
require_once 'config/database.php';

$stmt = $pdo->prepare("SELECT * FROM users WHERE email = 'mohasir65@nova.edu'");
$stmt->execute();
$user = $stmt->fetch();
if ($user) {
    if (password_verify('mohasir123', $user['password_hash'])) {
        echo "Login SUCCESS for mohasir!\n";
    } else {
        echo "Wrong password.\n";
    }
} else {
    echo "User not found.\n";
}
