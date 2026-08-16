<?php
require_once 'config/database.php';

try {
    $stmt = $pdo->query("SELECT id, email FROM users WHERE role = 'student'");
    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $count = 0;
    foreach ($users as $u) {
        if (strpos($u['email'], 'student') === 0) continue; // Skip generic student
        
        $new_email = preg_replace('/[0-9]+@nova\.edu/', '65@nova.edu', $u['email']);
        if ($new_email !== $u['email']) {
            try {
                $update = $pdo->prepare("UPDATE users SET email = :email WHERE id = :id");
                $update->execute(['email' => $new_email, 'id' => $u['id']]);
                $count++;
            } catch (Exception $e) {
                // Ignore duplicates
            }
        }
    }
    echo "Updated $count students' emails to match credentials.txt (65@nova.edu).\n";
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage();
}
