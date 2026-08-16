<?php
require_once 'config/database.php';

try {
    $stmt = $pdo->query("SELECT email, role FROM users LIMIT 100");
    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo "Users in database:\n";
    foreach ($users as $u) {
        echo "- " . $u['email'] . " (" . $u['role'] . ")\n";
    }
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage();
}
