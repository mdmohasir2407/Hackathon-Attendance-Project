<?php
require_once 'config/database.php';

try {
    $stmt = $pdo->query("SELECT id, email, role FROM users");
    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $count = 0;
    foreach ($users as $u) {
        if ($u['role'] === 'admin') continue;
        
        // Extract the name part before numbers and @
        preg_match('/^([a-zA-Z]+)/', $u['email'], $matches);
        if (!empty($matches[1])) {
            $name = strtolower($matches[1]);
            if ($u['role'] === 'teacher') {
                // e.g. MCAmanimozhi -> manimozhi
                // Remove the first 3 letters for department like MCA, MBA, CSE, IT
                $name = substr($name, 3);
            }
            $password = $name . '123';
            $hash = password_hash($password, PASSWORD_DEFAULT);
            $update = $pdo->prepare("UPDATE users SET password_hash = :hash WHERE id = :id");
            $update->execute(['hash' => $hash, 'id' => $u['id']]);
            $count++;
        }
    }
    echo "Updated $count passwords to match credentials.txt.\n";
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage();
}
