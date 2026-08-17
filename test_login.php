<?php
require 'config/database.php';
$stmt = $pdo->query("SELECT * FROM users WHERE role='student' LIMIT 1");
$user = $stmt->fetch(PDO::FETCH_ASSOC);
print_r($user);
if($user) {
    $name = explode('@', $user['email'])[0];
    $name = preg_replace('/[0-9]+/', '', $name);
    echo 'Expected pass: ' . $name . '123' . PHP_EOL;
    echo 'Verifies? ' . (password_verify($name . '123', $user['password_hash']) ? 'YES' : 'NO') . PHP_EOL;
}
