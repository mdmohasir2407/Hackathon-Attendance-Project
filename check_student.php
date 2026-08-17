<?php
require_once 'config/database.php';
$stmt = $pdo->query("SELECT s.id, s.first_name, s.last_name, e.class_id FROM students s LEFT JOIN enrollments e ON s.id = e.student_id");
print_r($stmt->fetchAll(PDO::FETCH_ASSOC));
