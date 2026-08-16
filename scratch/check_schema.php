<?php
require_once '../config/database.php';
$stmt = $pdo->query('SHOW CREATE TABLE timetable');
$row = $stmt->fetch();
echo $row[1];
?>
