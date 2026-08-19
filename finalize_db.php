<?php
require 'config/database.php';

try {
    $pdo->exec("ALTER TABLE users ADD COLUMN profile_pic VARCHAR(255) DEFAULT 'assets/images/default-avatar.png' AFTER role");
    echo "Added profile_pic column\n";
} catch (Exception $e) {
    echo "Notice: " . $e->getMessage() . "\n";
}

require 'seed_database.php';
