<?php
require 'config/database.php';
$stmt = $pdo->query("SELECT email FROM users WHERE role='student'");
$emails = $stmt->fetchAll(PDO::FETCH_COLUMN);
print_r(array_slice($emails, 0, 15));
if (in_array('karthik65@nova.edu', $emails)) {
    echo "karthik65@nova.edu is in DB\n";
} else {
    echo "karthik65@nova.edu is NOT in DB\n";
}
