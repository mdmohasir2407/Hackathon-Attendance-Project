<?php
// auth/logout.php
session_start();
require_once '../config/database.php';

if (isset($_SESSION['user_id'])) {
    try {
        // Log logout activity
        $ip = $_SERVER['REMOTE_ADDR'] ?? 'UNKNOWN';
        $stmt = $pdo->prepare("INSERT INTO activity_logs (user_id, action, ip_address) VALUES (?, 'Logged out', ?)");
        $stmt->execute([$_SESSION['user_id'], $ip]);
    } catch (PDOException $e) {
        // Ignore foreign key constraint errors (e.g. if user was deleted but session is still active)
        error_log("Logout activity log failed: " . $e->getMessage());
    }
}

// Unset all of the session variables
$_SESSION = array();

// Destroy the session.
session_destroy();

// Redirect to login page
header("Location: login.php");
exit;
?>
