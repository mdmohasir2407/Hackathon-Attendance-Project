<?php
require_once 'includes/header.php';

// Fetch stats for dashboard
$stats = [
    'students' => 0,
    'teachers' => 0,
    'departments' => 0,
    'classes' => 0
];

$stmt = $pdo->query("SELECT COUNT(*) FROM students");
$stats['students'] = $stmt->fetchColumn();

$stmt = $pdo->query("SELECT COUNT(*) FROM teachers");
$stats['teachers'] = $stmt->fetchColumn();

$stmt = $pdo->query("SELECT COUNT(*) FROM departments");
$stats['departments'] = $stmt->fetchColumn();

$stmt = $pdo->query("SELECT COUNT(*) FROM classes");
$stats['classes'] = $stmt->fetchColumn();

// Recent activity logs
$stmt = $pdo->query("SELECT a.*, u.email, u.role FROM activity_logs a LEFT JOIN users u ON a.user_id = u.id ORDER BY a.timestamp DESC LIMIT 5");
$recent_logs = $stmt->fetchAll();
?>

<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
    <h1 class="h2">Dashboard</h1>
</div>

<div class="row">
    <div class="col-md-3 mb-4">
        <div class="card text-white bg-primary h-100">
            <div class="card-body">
                <h5 class="card-title">Total Students</h5>
                <h2 class="display-4"><?php echo $stats['students']; ?></h2>
            </div>
            <div class="card-footer d-flex align-items-center justify-content-between">
                <a class="small text-white stretched-link" href="students.php">View Details</a>
                <div class="small text-white"><i class="bi bi-chevron-right"></i></div>
            </div>
        </div>
    </div>
    <div class="col-md-3 mb-4">
        <div class="card text-white bg-success h-100">
            <div class="card-body">
                <h5 class="card-title">Total Teachers</h5>
                <h2 class="display-4"><?php echo $stats['teachers']; ?></h2>
            </div>
            <div class="card-footer d-flex align-items-center justify-content-between">
                <a class="small text-white stretched-link" href="teachers.php">View Details</a>
                <div class="small text-white"><i class="bi bi-chevron-right"></i></div>
            </div>
        </div>
    </div>
    <div class="col-md-3 mb-4">
        <div class="card text-white bg-warning h-100">
            <div class="card-body">
                <h5 class="card-title">Total Departments</h5>
                <h2 class="display-4"><?php echo $stats['departments']; ?></h2>
            </div>
            <div class="card-footer d-flex align-items-center justify-content-between">
                <a class="small text-white stretched-link" href="departments.php">View Details</a>
                <div class="small text-white"><i class="bi bi-chevron-right"></i></div>
            </div>
        </div>
    </div>
    <div class="col-md-3 mb-4">
        <div class="card text-white bg-danger h-100">
            <div class="card-body">
                <h5 class="card-title">Total Classes</h5>
                <h2 class="display-4"><?php echo $stats['classes']; ?></h2>
            </div>
            <div class="card-footer d-flex align-items-center justify-content-between">
                <a class="small text-white stretched-link" href="classes.php">View Details</a>
                <div class="small text-white"><i class="bi bi-chevron-right"></i></div>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-md-12">
        <div class="card mb-4">
            <div class="card-header">
                <i class="bi bi-list-check me-1"></i>
                Recent Activity
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead>
                            <tr>
                                <th>Timestamp</th>
                                <th>User</th>
                                <th>Role</th>
                                <th>Action</th>
                                <th>IP Address</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach($recent_logs as $log): ?>
                            <tr>
                                <td><?php echo $log['timestamp']; ?></td>
                                <td><?php echo htmlspecialchars($log['email'] ?? 'System'); ?></td>
                                <td><span class="badge bg-secondary"><?php echo htmlspecialchars($log['role'] ?? '-'); ?></span></td>
                                <td><?php echo htmlspecialchars($log['action']); ?></td>
                                <td><?php echo htmlspecialchars($log['ip_address']); ?></td>
                            </tr>
                            <?php endforeach; ?>
                            <?php if(empty($recent_logs)): ?>
                            <tr><td colspan="5" class="text-center">No recent activity.</td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>
