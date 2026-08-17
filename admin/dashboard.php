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

<div class="row animate-on-scroll fade-in-up">
    <div class="col-md-4 mb-4 delay-100">
        <div class="card premium-glass-card tilt-card h-100">
            <div class="glare-effect"></div>
            <div class="card-body">
                <h5 class="card-title text-uppercase fw-bold" style="letter-spacing: 1px; font-size: 0.9rem; color: #0ea5e9;">Total Students</h5>
                <h2 class="display-4 fw-bold neon-text"><?php echo $stats['students']; ?></h2>
            </div>
            <div class="card-footer d-flex align-items-center justify-content-between border-0 bg-transparent">
                <a class="small premium-text stretched-link text-decoration-none magnetic-btn" href="students.php">View Details</a>
                <div class="small premium-text hover-bounce-icon"><i class="bi bi-chevron-right"></i></div>
            </div>
        </div>
    </div>
    <div class="col-md-4 mb-4 delay-200">
        <div class="card premium-glass-card tilt-card h-100">
            <div class="glare-effect"></div>
            <div class="card-body">
                <h5 class="card-title text-uppercase fw-bold" style="letter-spacing: 1px; font-size: 0.9rem; color: #10b981;">Total Teachers</h5>
                <h2 class="display-4 fw-bold neon-text"><?php echo $stats['teachers']; ?></h2>
            </div>
            <div class="card-footer d-flex align-items-center justify-content-between border-0 bg-transparent">
                <a class="small premium-text stretched-link text-decoration-none magnetic-btn" href="teachers.php">View Details</a>
                <div class="small premium-text hover-bounce-icon"><i class="bi bi-chevron-right"></i></div>
            </div>
        </div>
    </div>
    <div class="col-md-4 mb-4 delay-300">
        <div class="card premium-glass-card tilt-card h-100">
            <div class="glare-effect"></div>
            <div class="card-body">
                <h5 class="card-title text-uppercase fw-bold" style="letter-spacing: 1px; font-size: 0.9rem; color: #f59e0b;">Total Departments</h5>
                <h2 class="display-4 fw-bold neon-text"><?php echo $stats['departments']; ?></h2>
            </div>
            <div class="card-footer d-flex align-items-center justify-content-between border-0 bg-transparent">
                <a class="small premium-text stretched-link text-decoration-none magnetic-btn" href="departments.php">View Details</a>
                <div class="small premium-text hover-bounce-icon"><i class="bi bi-chevron-right"></i></div>
            </div>
        </div>
    </div>
</div>

<div class="row animate-on-scroll fade-in-up delay-500">
    <div class="col-md-12">
        <div class="card premium-glass-card tilt-card mb-4">
            <div class="glare-effect"></div>
            <div class="card-header bg-transparent border-bottom-0 pt-4 pb-2">
                <i class="bi bi-list-check me-2 pulse-badge" style="color: var(--premium-accent); font-size: 1.2rem;"></i>
                <span class="fw-bold premium-text fs-5" style="letter-spacing: 0.5px;">Recent Activity</span>
            </div>
            <div class="card-body p-0">
                <div class="p-3">
                    <!-- Header Row -->
                    <div class="d-flex text-uppercase fw-bold small premium-text-muted mb-2 px-3" style="letter-spacing: 1px;">
                        <div class="data-cell">Timestamp</div>
                        <div class="data-cell flex-grow-1">User</div>
                        <div class="data-cell">Role</div>
                        <div class="data-cell flex-grow-1">Action</div>
                        <div class="data-cell">IP Address</div>
                    </div>
                    
                    <!-- Data Rows -->
                    <div class="data-cards-container">
                        <?php foreach($recent_logs as $index => $log): ?>
                        <div class="data-card-row animate-on-scroll fade-in-up" style="animation-delay: <?php echo ($index * 100); ?>ms;">
                            <div class="data-cell text-muted small"><i class="bi bi-clock me-2"></i><?php echo date('M d, H:i', strtotime($log['timestamp'])); ?></div>
                            <div class="data-cell flex-grow-1 fw-bold"><?php echo htmlspecialchars($log['email'] ?? 'System'); ?></div>
                            <div class="data-cell">
                                <span class="badge bg-transparent border border-secondary text-secondary rounded-pill px-3 py-2">
                                    <?php echo htmlspecialchars($log['role'] ?? '-'); ?>
                                </span>
                            </div>
                            <div class="data-cell flex-grow-1">
                                <span class="text-truncate d-inline-block" style="max-width: 250px;">
                                    <?php echo htmlspecialchars($log['action']); ?>
                                </span>
                            </div>
                            <div class="data-cell font-monospace small opacity-75">
                                <?php echo htmlspecialchars($log['ip_address']); ?>
                            </div>
                        </div>
                        <?php endforeach; ?>
                        
                        <?php if(empty($recent_logs)): ?>
                        <div class="text-center p-5 premium-text-muted">
                            <i class="bi bi-inbox fs-1 d-block mb-3"></i>
                            No recent activity found.
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>
