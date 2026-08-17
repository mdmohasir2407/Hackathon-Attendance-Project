<?php
require 'config/database.php';
$stmt = $pdo->query('SELECT COUNT(*) FROM students');
echo "Total students: " . $stmt->fetchColumn();
