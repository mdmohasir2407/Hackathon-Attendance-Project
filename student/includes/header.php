<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'student') {
    header("Location: ../auth/login.php");
    exit;
}
require_once '../config/database.php';

// Fetch unread notifications count for header badge
$stmt = $pdo->prepare("SELECT COUNT(*) FROM notifications WHERE user_id = ? AND is_read = FALSE");
$stmt->execute([$_SESSION['user_id']]);
$unread_notifications = $stmt->fetchColumn();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Dashboard - Campus Nova</title>
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
                        <a class="nav-link" href="timetable.php">
                            <i class="bi bi-calendar3"></i> My Timetable
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="planner.php">
                            <i class="bi bi-journal-check"></i> Smart Planner
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="scan_qr.php">
                            <i class="bi bi-qr-code-scan"></i> Scan QR Attendance
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="attendance.php">
                            <i class="bi bi-person-check"></i> My Attendance
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="assignments.php">
                            <i class="bi bi-pencil-square"></i> Assignments
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="tests.php">
                            <i class="bi bi-ui-checks"></i> Online Tests
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="leave_form.php">
                            <i class="bi bi-file-earmark-plus"></i> Leave Application
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="gate_permission.php">
                            <i class="bi bi-door-open"></i> Gate Permission
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="materials.php">
                            <i class="bi bi-book"></i> Study Materials
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="performance.php">
                            <i class="bi bi-graph-up"></i> My Performance
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="feedback.php">
                            <i class="bi bi-chat-dots"></i> Teacher Feedback
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="announcements.php">
                            <i class="bi bi-megaphone"></i> Announcements
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="achievements.php">
                            <i class="bi bi-trophy"></i> Achievements
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
                <a href="notifications.php" class="btn btn-light rounded-circle position-relative me-3 shadow-sm d-flex align-items-center justify-content-center" style="width: 40px; height: 40px;">
                    <i class="bi bi-bell"></i>
                    <?php if($unread_notifications > 0): ?>
                    <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger" style="font-size: 0.65rem;">
                        <?php echo $unread_notifications; ?>
                    </span>
                    <?php endif; ?>
                </a>
                <span class="me-3 fw-medium">Welcome, <?php echo htmlspecialchars($_SESSION['name'] ?? 'Student'); ?></span>
                <button class="btn btn-light rounded-circle me-3 shadow-sm d-flex align-items-center justify-content-center" style="width: 40px; height: 40px;" id="theme-toggle">
                    <i class="bi bi-moon"></i>
                </button>
                <a class="btn btn-gradient rounded-pill px-4" href="../auth/logout.php">Logout</a>
            </div>
        </header>

        <!-- Main Content -->
        <main class="main-content">
