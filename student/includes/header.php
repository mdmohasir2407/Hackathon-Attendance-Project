<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
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
    <link rel="stylesheet" href="../assets/css/animations.css">
    <link rel="stylesheet" id="theme-stylesheet" href="../assets/css/dark-mode.css">
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body class="page-<?php echo basename($_SERVER['PHP_SELF'], '.php'); ?>">
    <script>
        const storedTheme = localStorage.getItem('theme');
        if (storedTheme === 'dark' || (!storedTheme)) {
            document.body.classList.add('dark-mode');
        }
    </script>
    <div class="mesh-bg"></div>
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
            <div class="d-flex align-items-center">
                <button class="btn btn-outline-light rounded-circle me-3 d-md-none sidebar-toggler" id="sidebarToggle">
                    <i class="bi bi-list fs-5"></i>
                </button>
                <a class="navbar-brand d-none d-sm-block" href="dashboard.php">
                    <i class="bi bi-hexagon-fill me-2"></i> Campus Nova
                </a>
            </div>
            
            <div class="d-flex align-items-center">
                <a href="notifications.php" class="btn btn-light rounded-circle position-relative me-3 shadow-sm d-flex align-items-center justify-content-center" style="width: 40px; height: 40px;">
                    <i class="bi bi-bell"></i>
                    <?php if($unread_notifications > 0): ?>
                    <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger" style="font-size: 0.65rem;">
                        <?php echo $unread_notifications; ?>
                    </span>
                    <?php endif; ?>
                </a>
                
                <button class="btn btn-light rounded-circle me-3 shadow-sm d-flex align-items-center justify-content-center" style="width: 40px; height: 40px;" id="theme-toggle">
                    <i class="bi bi-moon"></i>
                </button>
                
                <!-- Profile Dropdown -->
                <div class="dropdown">
                    <a href="#" class="d-flex align-items-center text-decoration-none dropdown-toggle" id="dropdownUser" data-bs-toggle="dropdown" aria-expanded="false">
                        <img src="../<?php echo htmlspecialchars($_SESSION['profile_pic'] ?? 'assets/images/default-avatar.png'); ?>" alt="Profile" width="40" height="40" class="rounded-circle shadow-sm me-2" style="object-fit: cover;">
                        <span class="fw-medium text-dark d-none d-sm-inline">Welcome, <?php echo htmlspecialchars($_SESSION['name'] ?? 'Student'); ?></span>
                    </a>
                    <ul class="dropdown-menu dropdown-menu-end shadow-sm" aria-labelledby="dropdownUser">
                        <li><a class="dropdown-item" href="profile.php"><i class="bi bi-person me-2"></i>My Profile</a></li>
                        <li><hr class="dropdown-divider"></li>
                        <li><a class="dropdown-item text-danger" href="../auth/logout.php"><i class="bi bi-box-arrow-right me-2"></i>Logout</a></li>
                    </ul>
                </div>
            </div>
        </header>

        <!-- Main Content -->
        <main class="main-content">
