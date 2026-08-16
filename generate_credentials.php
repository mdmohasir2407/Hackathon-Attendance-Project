<?php
require_once 'config/database.php';

$output = "CAMPUS NOVA - ALL CREDENTIALS\n=============================\n\n";

// Admins
$output .= "ADMINS\n------\n";
$output .= "Email: admin@nova.edu\nPassword: password123\n\n";

// Teachers
$output .= "TEACHERS\n--------\n";
$stmt = $pdo->query("SELECT u.email, t.first_name FROM users u JOIN teachers t ON u.id = t.id");
while ($row = $stmt->fetch()) {
    $pass = strtolower($row['first_name']) . "123";
    $output .= "Email: " . str_pad($row['email'], 25) . " | Password: {$pass}\n";
}
$output .= "\n";

// Students
$output .= "STUDENTS\n--------\n";
$stmt = $pdo->query("SELECT u.email, s.first_name, s.roll_number, d.code as dept FROM users u JOIN students s ON u.id = s.id JOIN enrollments e ON s.id = e.student_id JOIN classes c ON e.class_id = c.id JOIN departments d ON c.department_id = d.id ORDER BY d.code, s.first_name");
while ($row = $stmt->fetch()) {
    $pass = strtolower($row['first_name']) . "123";
    $output .= "Dept: " . str_pad($row['dept'], 5) . "| Roll: " . str_pad($row['roll_number'], 10) . " | Email: " . str_pad($row['email'], 25) . " | Password: {$pass}\n";
}

file_put_contents('credentials.txt', $output);
echo "Credentials generated.";
?>
