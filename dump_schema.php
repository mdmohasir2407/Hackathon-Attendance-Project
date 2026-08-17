<?php
require_once 'config/database.php';
$stmt = $pdo->query("SHOW TABLES");
print_r($stmt->fetchAll(PDO::FETCH_ASSOC));
$stmt = $pdo->query("DESCRIBE notifications");
print_r($stmt->fetchAll(PDO::FETCH_ASSOC));
