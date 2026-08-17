<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'teacher') {
    header("Location: ../auth/login.php");
    exit;
}
require_once '../config/database.php';

$stmt = $pdo->prepare("SELECT COUNT(*) FROM notifications WHERE user_id = ? AND is_read = FALSE");
$stmt->execute([$_SESSION['user_id']]);
$unread_notifications = $stmt->fetchColumn();
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
    <div class="dashboard-3d-bg">
        <div class="dashboard-3d-grid"></div>
        <div class="dashboard-orb d-orb-1"></div>
        <div class="dashboard-orb d-orb-2"></div>
        <div class="dashboard-orb d-orb-3"></div>
    </div>
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
                            <i class="bi bi-ui-checks"></i> Weekly Questions
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
                    <?php if(isset($unread_notifications) && $unread_notifications > 0): ?>
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
                        <img src="../<?php echo !empty($_SESSION['profile_pic']) ? htmlspecialchars($_SESSION['profile_pic']) : 'assets/images/default-avatar.png'; ?>" alt="" width="40" height="40" class="rounded-circle shadow-sm me-2" style="object-fit: cover; background: var(--bg-card);">
                        <span class="fw-medium text-dark d-none d-sm-inline">Welcome, <?php echo htmlspecialchars($_SESSION['name'] ?? 'Teacher'); ?></span>
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
