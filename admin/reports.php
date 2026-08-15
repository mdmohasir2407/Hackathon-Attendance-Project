<?php
// admin/reports.php
session_start();
require_once '../config/database.php';

// Check if user is logged in and is an admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../auth/login.php");
    exit;
}

$page_title = "System Reports";
include 'includes/header.php';

// Quick stats for reports
$stats = [
    'users' => $pdo->query("SELECT COUNT(*) FROM users")->fetchColumn(),
    'students' => $pdo->query("SELECT COUNT(*) FROM students")->fetchColumn(),
    'teachers' => $pdo->query("SELECT COUNT(*) FROM teachers")->fetchColumn(),
    'activity' => $pdo->query("SELECT COUNT(*) FROM activity_logs")->fetchColumn(),
];
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h2 class="premium-text"><i class="bi bi-file-earmark-bar-graph me-2 text-primary"></i>System Reports</h2>
    <div>
        <button class="btn btn-outline-primary shadow-sm" onclick="window.print()">
            <i class="bi bi-printer me-2"></i>Print Report
        </button>
    </div>
</div>

<div class="row g-4 mb-4">
    <div class="col-md-3">
        <div class="card premium-glass-card h-100 p-3 text-center" style="border-bottom: 4px solid var(--primary);">
            <h5 class="text-secondary mb-3">Total Users</h5>
            <h2 class="display-4 fw-bold premium-text"><?php echo $stats['users']; ?></h2>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card premium-glass-card h-100 p-3 text-center" style="border-bottom: 4px solid var(--bs-success);">
            <h5 class="text-secondary mb-3">Students Enrolled</h5>
            <h2 class="display-4 fw-bold premium-text"><?php echo $stats['students']; ?></h2>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card premium-glass-card h-100 p-3 text-center" style="border-bottom: 4px solid var(--bs-info);">
            <h5 class="text-secondary mb-3">Staff / Teachers</h5>
            <h2 class="display-4 fw-bold premium-text"><?php echo $stats['teachers']; ?></h2>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card premium-glass-card h-100 p-3 text-center" style="border-bottom: 4px solid var(--bs-warning);">
            <h5 class="text-secondary mb-3">Activity Logs</h5>
            <h2 class="display-4 fw-bold premium-text"><?php echo $stats['activity']; ?></h2>
        </div>
    </div>
</div>

<div class="row g-4">
    <div class="col-md-6 mb-4">
        <div class="card premium-glass-card h-100">
            <div class="card-header border-0 bg-transparent pt-4 pb-0">
                <h5 class="mb-0 premium-text"><i class="bi bi-folder2-open me-2 text-primary"></i>Report Categories</h5>
            </div>
            <div class="card-body">
                <div class="list-group list-group-flush rounded-3 overflow-hidden shadow-sm">
                    <a href="students.php" class="list-group-item list-group-item-action d-flex align-items-center p-3" style="background: var(--glass-bg); color: var(--premium-text); border-color: var(--border-color);">
                        <i class="bi bi-person-badge fs-4 text-primary me-3"></i> 
                        <div>
                            <h6 class="mb-0 fw-bold">Student Directory Report</h6>
                            <small class="text-muted">Export complete student records</small>
                        </div>
                    </a>
                    <a href="teachers.php" class="list-group-item list-group-item-action d-flex align-items-center p-3" style="background: var(--glass-bg); color: var(--premium-text); border-color: var(--border-color);">
                        <i class="bi bi-person-workspace fs-4 text-info me-3"></i> 
                        <div>
                            <h6 class="mb-0 fw-bold">Staff Directory Report</h6>
                            <small class="text-muted">Export teacher and staff details</small>
                        </div>
                    </a>
                    <a href="activity_logs.php" class="list-group-item list-group-item-action d-flex align-items-center p-3" style="background: var(--glass-bg); color: var(--premium-text); border-color: var(--border-color);">
                        <i class="bi bi-clock-history fs-4 text-warning me-3"></i> 
                        <div>
                            <h6 class="mb-0 fw-bold">System Access Logs</h6>
                            <small class="text-muted">View recent logins and system activity</small>
                        </div>
                    </a>
                    <a href="leave_requests.php" class="list-group-item list-group-item-action d-flex align-items-center p-3" style="background: var(--glass-bg); color: var(--premium-text); border-color: var(--border-color);">
                        <i class="bi bi-calendar-event fs-4 text-danger me-3"></i> 
                        <div>
                            <h6 class="mb-0 fw-bold">Leave Requests Summary</h6>
                            <small class="text-muted">Generate reports of approved/rejected leaves</small>
                        </div>
                    </a>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-6 mb-4">
        <div class="card premium-glass-card h-100">
            <div class="card-header border-0 bg-transparent pt-4 pb-0">
                <h5 class="mb-0 premium-text"><i class="bi bi-sliders me-2 text-primary"></i>Custom Export Options</h5>
            </div>
            <div class="card-body">
                <form class="p-2">
                    <div class="mb-4">
                        <label class="form-label text-secondary fw-bold">Select Date Range</label>
                        <div class="input-group shadow-sm">
                            <span class="input-group-text bg-light border-0"><i class="bi bi-calendar"></i></span>
                            <input type="date" class="form-control border-0" style="background: var(--glass-bg); color: var(--premium-text);">
                            <span class="input-group-text border-0" style="background: var(--glass-bg); color: var(--premium-text);">to</span>
                            <input type="date" class="form-control border-0" style="background: var(--glass-bg); color: var(--premium-text);">
                        </div>
                    </div>
                    <div class="mb-4">
                        <label class="form-label text-secondary fw-bold">Report Format</label>
                        <select class="form-select border-0 shadow-sm p-3" style="background: var(--glass-bg); color: var(--premium-text);">
                            <option>📄 PDF Document (.pdf)</option>
                            <option>📊 Excel Spreadsheet (.xlsx)</option>
                            <option>📑 CSV Data (.csv)</option>
                        </select>
                    </div>
                    <button type="button" class="btn btn-primary btn-gradient w-100 py-3 shadow-sm rounded-3 mt-2 fw-bold" onclick="alert('Export module is gathering data. Your download will start shortly.')">
                        <i class="bi bi-cloud-download me-2"></i> Generate & Download Report
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>
