<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);
session_start();
$_SESSION['user_id'] = 2; // Teacher Jane Doe
$_SESSION['role'] = 'teacher';
require '../config/database.php';
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
try {
    require 'analytics.php';
} catch (Throwable $e) {
    echo "<h1>ERROR: " . $e->getMessage() . "</h1>";
}
?>
