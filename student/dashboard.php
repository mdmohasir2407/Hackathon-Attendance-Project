<?php
require_once 'includes/header.php';

$student_id = $_SESSION['user_id'];

// Get student's class
$stmt = $pdo->prepare("SELECT class_id FROM enrollments WHERE student_id = ?");
$stmt->execute([$student_id]);
$enrollment = $stmt->fetch();
$class_id = $enrollment ? $enrollment['class_id'] : null;

// Get today's classes
$today = date('l');
$todays_classes = [];
$next_class = null;

if ($class_id) {
    $current_time = date('H:i:s');
    $stmt = $pdo->prepare("
        SELECT t.*, s.name as subject_name, s.code as subject_code, te.first_name, te.last_name
        FROM timetable t
        JOIN subjects s ON t.subject_id = s.id
        LEFT JOIN teachers te ON t.teacher_id = te.id
        WHERE t.class_id = ? AND t.day = ?
        ORDER BY t.period_number
    ");
    $stmt->execute([$class_id, $today]);
    $todays_classes = $stmt->fetchAll();

    foreach ($todays_classes as $class) {
        if ($class['start_time'] > $current_time) {
            $next_class = $class;
            break;
        }
    }
    if (!$next_class) {
        foreach ($todays_classes as $class) {
            if ($class['start_time'] <= $current_time && $class['end_time'] >= $current_time) {
                $next_class = $class;
                $next_class['is_current'] = true;
                break;
            }
        }
    }
}

$stmt = $pdo->prepare("SELECT COALESCE(SUM(xp_points), 0) FROM student_achievements WHERE student_id = ?");
$stmt->execute([$student_id]);
$learning_xp = $stmt->fetchColumn();

// Real attendance stats
$attendance_percent = 0;
$attendance_risk = "SAFE";
if ($class_id) {
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM attendance_sessions WHERE class_id = ?");
    $stmt->execute([$class_id]);
    $total_sessions = $stmt->fetchColumn();
    if ($total_sessions > 0) {
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM attendance WHERE student_id = ?");
        $stmt->execute([$student_id]);
        $attended = $stmt->fetchColumn();
        $attendance_percent = round(($attended / $total_sessions) * 100);
        if ($attendance_percent < 60) $attendance_risk = "CRITICAL";
        elseif ($attendance_percent < 80) $attendance_risk = "WARNING";
    }
}

// Real pending assignments
$pending_assignments = 0;
if ($class_id) {
    $stmt = $pdo->prepare("
        SELECT COUNT(*) FROM assignments a
        WHERE a.class_id = ? AND a.deadline >= NOW()
        AND a.id NOT IN (SELECT assignment_id FROM assignment_submissions WHERE student_id = ?)
    ");
    $stmt->execute([$class_id, $student_id]);
    $pending_assignments = $stmt->fetchColumn();
}

// Fetch actual recent feedback
$stmt = $pdo->prepare("
    SELECT f.*, s.name as subject_name, t.first_name, t.last_name 
    FROM feedback f
    JOIN subjects s ON f.subject_id = s.id
    JOIN teachers t ON f.teacher_id = t.id
    WHERE f.student_id = ?
    ORDER BY f.created_at DESC LIMIT 3
");
$stmt->execute([$student_id]);
$recent_feedback = $stmt->fetchAll();

?>

<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
    <h1 class="h2">Student Dashboard</h1>
    <div>
        <span class="badge bg-primary fs-6"><i class="bi bi-star-fill text-warning"></i> <?php echo $learning_xp; ?> XP</span>
    </div>
</div>

<div class="row mb-4 animate-on-scroll fade-in-up">
    <div class="col-md-3 delay-100">
        <div class="card premium-glass-card tilt-card h-100">
            <div class="glare-effect"></div>
            <div class="card-body">
                <h5 class="card-title text-uppercase fw-bold" style="letter-spacing: 1px; font-size: 0.9rem; color: #10b981;">Attendance</h5>
                <h2 class="display-5 fw-bold neon-text premium-text"><span class="count-up" data-count="<?php echo $attendance_percent; ?>">0</span>%</h2>
                <span class="badge bg-transparent border border-success text-success pulse-badge"><?php echo $attendance_risk; ?></span>
            </div>
        </div>
    </div>
    <div class="col-md-3 delay-200">
        <div class="card premium-glass-card tilt-card h-100">
            <div class="glare-effect"></div>
            <div class="card-body">
                <h5 class="card-title text-uppercase fw-bold" style="letter-spacing: 1px; font-size: 0.9rem; color: #0ea5e9;">Today's Classes</h5>
                <h2 class="display-5 count-up fw-bold neon-text premium-text" data-count="<?php echo count($todays_classes); ?>">0</h2>
            </div>
        </div>
    </div>
    <div class="col-md-3 delay-300">
        <div class="card premium-glass-card tilt-card h-100">
            <div class="glare-effect"></div>
            <div class="card-body">
                <h5 class="card-title text-uppercase fw-bold" style="letter-spacing: 1px; font-size: 0.9rem; color: #f59e0b;">Pending Assignments</h5>
                <h2 class="display-5 count-up fw-bold neon-text premium-text" data-count="<?php echo $pending_assignments; ?>">0</h2>
            </div>
        </div>
    </div>
    <div class="col-md-3 delay-400">
        <div class="card premium-glass-card tilt-card h-100">
            <div class="glare-effect"></div>
            <div class="card-body">
                <h5 class="card-title text-uppercase fw-bold" style="letter-spacing: 1px; font-size: 0.9rem; color: var(--premium-purple);">Next Class</h5>
                <?php if ($next_class): ?>
                    <h4 class="mb-0 text-truncate hover-bounce-icon fw-bold premium-text"><?php echo htmlspecialchars($next_class['subject_code']); ?></h4>
                    <small class="opacity-75 premium-text-muted"><?php echo date('h:i A', strtotime($next_class['start_time'])); ?></small>
                <?php else: ?>
                    <h4 class="hover-bounce-icon fw-bold premium-text">None</h4>
                    <small class="opacity-75 premium-text-muted">For today</small>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-md-8">
        <div class="card shadow-sm mb-4">
            <div class="card-header bg-white d-flex justify-content-between align-items-center">
                <span class="fw-bold"><i class="bi bi-calendar-event me-2"></i> Today's Schedule</span>
                <a href="timetable.php" class="btn btn-sm btn-outline-primary">Full Timetable</a>
            </div>
            <div class="list-group list-group-flush">
                <?php foreach ($todays_classes as $class): ?>
                    <?php 
                    $current_time = date('H:i:s');
                    $is_active = ($class['start_time'] <= $current_time && $class['end_time'] >= $current_time);
                    ?>
                    <div class="list-group-item <?php echo $is_active ? 'list-group-item-primary' : ''; ?>">
                        <div class="d-flex w-100 justify-content-between">
                            <h6 class="mb-1 fw-bold"><?php echo htmlspecialchars($class['subject_name']); ?></h6>
                            <small><?php echo date('h:i A', strtotime($class['start_time'])) . ' - ' . date('h:i A', strtotime($class['end_time'])); ?></small>
                        </div>
                        <p class="mb-1 text-muted small">
                            <i class="bi bi-person"></i> <?php echo $class['teacher_id'] ? htmlspecialchars($class['first_name'] . ' ' . $class['last_name']) : 'TBA'; ?> | 
                            <i class="bi bi-geo-alt"></i> <?php echo htmlspecialchars($class['classroom']); ?>
                        </p>
                    </div>
                <?php endforeach; ?>
                <?php if(empty($todays_classes)): ?>
                    <div class="list-group-item text-center text-muted py-4">No classes scheduled for today! Enjoy your free time.</div>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <div class="col-md-4">
        <!-- Quick Actions -->
        <div class="card shadow-sm mb-4">
            <div class="card-body">
                <a href="scan_qr.php" class="btn btn-primary w-100 py-2 mb-2">
                    <i class="bi bi-qr-code-scan fs-5 d-block mb-1"></i> Scan QR Attendance
                </a>
                <a href="planner.php" class="btn btn-outline-info w-100 py-2 mb-2">
                    <i class="bi bi-journal-check fs-5 d-block mb-1"></i> View Daily Planner
                </a>
                <a href="tests.php" class="btn btn-gradient w-100 py-2">
                    <i class="bi bi-ui-checks fs-5 d-block mb-1"></i> Weekly Tests Portal
                </a>
            </div>
        </div>

        <!-- Weekly Tests Card -->
        <?php
        $stmt = $pdo->prepare("
            SELECT t.*, s.code as subject_code
            FROM tests t
            JOIN subjects s ON t.subject_id = s.id
            JOIN enrollments e ON t.class_id = e.class_id
            LEFT JOIN test_results tr ON t.id = tr.test_id AND tr.student_id = ?
            WHERE e.student_id = ? AND tr.id IS NULL
            ORDER BY t.scheduled_date ASC LIMIT 2
        ");
        $stmt->execute([$student_id, $student_id]);
        $upcoming_tests = $stmt->fetchAll();
        ?>
        <div class="card shadow-sm mb-4">
            <div class="card-header bg-white d-flex justify-content-between align-items-center">
                <span class="fw-bold"><i class="bi bi-ui-checks me-1 text-primary"></i> Pending Tests</span>
                <a href="tests.php" class="btn btn-sm btn-link p-0 text-decoration-none">View All</a>
            </div>
            <div class="list-group list-group-flush">
                <?php foreach($upcoming_tests as $ut): ?>
                    <div class="list-group-item">
                        <div class="d-flex w-100 justify-content-between align-items-center">
                            <span class="badge bg-primary rounded-pill"><?php echo htmlspecialchars($ut['test_period']); ?></span>
                            <span class="small text-muted"><?php echo $ut['scheduled_date'] ? date('M d', strtotime($ut['scheduled_date'])) : 'Soon'; ?></span>
                        </div>
                        <h6 class="mb-1 mt-1 fw-bold"><?php echo htmlspecialchars($ut['title']); ?></h6>
                        <div class="small text-muted mb-2"><?php echo htmlspecialchars($ut['subject_code']); ?> &bull; <?php echo $ut['duration_minutes']; ?> mins</div>
                        <a href="tests.php?action=take&id=<?php echo $ut['id']; ?>" class="btn btn-sm btn-outline-primary w-100 rounded-pill">Start Test</a>
                    </div>
                <?php endforeach; ?>
                <?php if(empty($upcoming_tests)): ?>
                    <div class="list-group-item text-center text-muted small py-3"><i class="bi bi-check-circle text-success me-1"></i> No pending tests!</div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Teacher Feedback -->
        <div class="card shadow-sm mb-4">
            <div class="card-header bg-white">
                <i class="bi bi-chat-dots me-1"></i> Recent Feedback
            </div>
            <div class="list-group list-group-flush">
                <?php foreach($recent_feedback as $fb): ?>
                    <div class="list-group-item">
                        <div class="d-flex w-100 justify-content-between">
                            <small class="text-primary fw-bold"><?php echo htmlspecialchars($fb['subject_name']); ?></small>
                            <small class="text-muted"><?php echo date('M d', strtotime($fb['created_at'])); ?></small>
                        </div>
                        <p class="mb-1">
                            <?php 
                                $badge = 'bg-secondary';
                                if($fb['feedback_type'] == 'Excellent') $badge = 'bg-success';
                                if($fb['feedback_type'] == 'Improving') $badge = 'bg-info';
                                if($fb['feedback_type'] == 'Needs Practice') $badge = 'bg-warning text-dark';
                            ?>
                            <span class="badge <?php echo $badge; ?>"><?php echo $fb['feedback_type']; ?></span>
                            <span class="small ms-1"><?php echo htmlspecialchars($fb['note']); ?></span>
                        </p>
                        <small class="text-muted">- Mr/Ms. <?php echo htmlspecialchars($fb['last_name']); ?></small>
                    </div>
                <?php endforeach; ?>
                <?php if(empty($recent_feedback)): ?>
                    <div class="list-group-item text-center text-muted small py-3">No recent feedback.</div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>
