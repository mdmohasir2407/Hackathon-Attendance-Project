<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'teacher') {
    header("Location: ../auth/login.php");
    exit;
}
require_once '../config/database.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Teacher Dashboard - Campus Nova</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" id="theme-stylesheet" href="../assets/css/dark-mode.css">
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body>
    <div class="wrapper">
        <!-- Sidebar -->
        <nav class="sidebar" id="sidebarMenu">
            <div class="sidebar-sticky">
                <ul class="nav flex-column">
                    <li class="nav-item">
                        <a class="nav-link" href="dashboard.php">
                            <i class="bi bi-speedometer2"></i> Dashboard
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="classes.php">
                            <i class="bi bi-easel"></i> My Classes & Subjects
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="students.php">
                            <i class="bi bi-people"></i> Student List
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="attendance.php">
                            <i class="bi bi-calendar-check"></i> Attendance History
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="qr_attendance.php">
                            <i class="bi bi-qr-code-scan"></i> QR Attendance
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="assignments.php">
                            <i class="bi bi-pencil-square"></i> Assignments
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="manage_tests.php">
                            <i class="bi bi-ui-checks"></i> Manage Tests
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="leave_requests.php">
                            <i class="bi bi-file-earmark-person"></i> Leave Requests
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="gate_permissions.php">
                            <i class="bi bi-door-open"></i> Gate Permissions
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="materials.php">
                            <i class="bi bi-book"></i> Study Materials
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="feedback.php">
                            <i class="bi bi-chat-dots"></i> Smart Feedback
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="announcements.php">
                            <i class="bi bi-megaphone"></i> Announcements
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="analytics.php">
                            <i class="bi bi-graph-up"></i> Analytics
                        </a>
                    </li>
                </ul>
            </div>
        </nav>

        <!-- Top Navbar -->
        <header class="top-navbar d-flex align-items-center justify-content-between">
            <a class="navbar-brand" href="dashboard.php">
                <i class="bi bi-hexagon-fill me-2"></i> Campus Nova
            </a>
            
            <div class="d-flex align-items-center">
                <span class="me-3 fw-medium">Welcome, <?php echo htmlspecialchars($_SESSION['name'] ?? 'Teacher'); ?></span>
                <button class="btn btn-light rounded-circle me-3 shadow-sm d-flex align-items-center justify-content-center" style="width: 40px; height: 40px;" id="theme-toggle">
                    <i class="bi bi-moon"></i>
                </button>
                <a class="btn btn-gradient rounded-pill px-4" href="../auth/logout.php">Logout</a>
            </div>
        </header>

        <!-- Main Content -->
        <main class="main-content">
