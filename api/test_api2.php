<?php
session_start();
$_SERVER['REQUEST_METHOD'] = 'POST';
$_POST['action'] = 'generate';
$_POST['class_id'] = 2; // Testing Class 2
$_SESSION['role'] = 'admin';
$_SESSION['user_id'] = 1;

require_once 'timetable.php';
?>
