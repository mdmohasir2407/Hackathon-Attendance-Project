<?php
require_once 'includes/header.php';

$student_id = $_SESSION['user_id'];

// Get student's class
$stmt = $pdo->prepare("SELECT class_id FROM enrollments WHERE student_id = ?");
$stmt->execute([$student_id]);
$enrollment = $stmt->fetch();
$class_id = $enrollment ? $enrollment['class_id'] : null;

$subject_attendance = [];
$recent_scans = [];
$overall_percent = 0;

if ($class_id) {
    // Subject-wise Attendance Breakdown
    $stmt = $pdo->prepare("
        SELECT sub.name as subject_name, sub.code,
               (SELECT COUNT(*) FROM attendance_sessions s WHERE s.subject_id = sub.id AND s.class_id = ?) as total_sessions,
               (SELECT COUNT(*) FROM attendance a JOIN attendance_sessions s ON a.session_id = s.id 
                WHERE s.subject_id = sub.id AND s.class_id = ? AND a.student_id = ?) as attended
        FROM subjects sub
        JOIN teacher_subjects ts ON sub.id = ts.subject_id
        WHERE ts.class_id = ?
        GROUP BY sub.id
    ");
    $stmt->execute([$class_id, $class_id, $student_id, $class_id]);
    $subject_attendance = $stmt->fetchAll();

    // Recent Attendance Scans
    $stmt = $pdo->prepare("
        SELECT s.date, s.period, sub.name as subject_name, t.first_name, t.last_name, a.status, a.timestamp
        FROM attendance a
        JOIN attendance_sessions s ON a.session_id = s.id
        JOIN subjects sub ON s.subject_id = sub.id
        JOIN teachers t ON s.teacher_id = t.id
        WHERE a.student_id = ?
        ORDER BY a.timestamp DESC
        LIMIT 20
    ");
    $stmt->execute([$student_id]);
    $recent_scans = $stmt->fetchAll();

    // Calculate overall percent
    $total_s = 0;
    $total_a = 0;
    foreach ($subject_attendance as $sub) {
        $total_s += $sub['total_sessions'];
        $total_a += $sub['attended'];
    }
    if ($total_s > 0) {
        $overall_percent = round(($total_a / $total_s) * 100);
    }
}
?>

<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
    <h1 class="h2">My Attendance</h1>
    <a href="scan_qr.php" class="btn btn-primary"><i class="bi bi-qr-code-scan"></i> Scan QR</a>
</div>

<div class="row mb-4">
    <div class="col-12">
        <div class="card shadow-sm border-0 bg-primary text-white">
            <div class="card-body p-4 d-flex justify-content-between align-items-center">
                <div>
                    <h5 class="mb-1 text-white-50">Overall Attendance</h5>
                    <h2 class="display-4 fw-bold mb-0"><?php echo $overall_percent; ?>%</h2>
                </div>
                <div class="text-end">
                    <?php if($overall_percent >= 75): ?>
                        <i class="bi bi-shield-check display-4 text-success" title="Safe"></i>
                        <p class="mb-0 mt-2 text-success fw-bold">Safe</p>
                    <?php else: ?>
                        <i class="bi bi-shield-exclamation display-4 text-warning" title="At Risk"></i>
                        <p class="mb-0 mt-2 text-warning fw-bold">Action Needed</p>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row mb-4">
    <div class="col-md-7">
        <h4 class="mb-3 text-secondary">Subject Breakdown</h4>
        <div class="card shadow-sm border-0">
            <div class="card-body p-0">
                <ul class="list-group list-group-flush">
                    <?php foreach($subject_attendance as $sub): 
                        $pct = $sub['total_sessions'] > 0 ? round(($sub['attended'] / $sub['total_sessions']) * 100) : 0;
                        $color = $pct >= 75 ? 'success' : ($pct >= 60 ? 'warning' : 'danger');
                    ?>
                    <li class="list-group-item p-4">
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <div>
                                <h6 class="mb-0 fw-bold"><?php echo htmlspecialchars($sub['subject_name']); ?></h6>
                                <small class="text-muted"><?php echo htmlspecialchars($sub['code']); ?></small>
                            </div>
                            <div class="text-end">
                                <span class="badge bg-<?php echo $color; ?> fs-6"><?php echo $pct; ?>%</span>
                                <small class="d-block text-muted mt-1"><?php echo $sub['attended']; ?> / <?php echo $sub['total_sessions']; ?> Sessions</small>
                            </div>
                        </div>
                        <div class="progress" style="height: 6px;">
                            <div class="progress-bar bg-<?php echo $color; ?>" role="progressbar" style="width: <?php echo $pct; ?>%"></div>
                        </div>
                    </li>
                    <?php endforeach; ?>
                    <?php if(empty($subject_attendance)): ?>
                        <li class="list-group-item p-4 text-center text-muted">No attendance data available yet.</li>
                    <?php endif; ?>
                </ul>
            </div>
        </div>
    </div>
    
    <div class="col-md-5">
        <h4 class="mb-3 text-secondary">Recent Scans</h4>
        <div class="card shadow-sm border-0">
            <div class="card-body p-0">
                <div class="list-group list-group-flush" style="max-height: 500px; overflow-y: auto;">
                    <?php foreach($recent_scans as $scan): ?>
                    <div class="list-group-item p-3">
                        <div class="d-flex w-100 justify-content-between">
                            <h6 class="mb-1 text-primary fw-bold"><?php echo htmlspecialchars($scan['subject_name']); ?></h6>
                            <small class="text-success"><i class="bi bi-check-circle-fill"></i> Present</small>
                        </div>
                        <p class="mb-1 small">Prof. <?php echo htmlspecialchars($scan['last_name']); ?> (Period <?php echo $scan['period']; ?>)</p>
                        <small class="text-muted"><i class="bi bi-clock"></i> <?php echo date('M d, Y h:i A', strtotime($scan['timestamp'])); ?></small>
                    </div>
                    <?php endforeach; ?>
                    <?php if(empty($recent_scans)): ?>
                        <div class="p-4 text-center text-muted">No recent attendance scans found.</div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>
