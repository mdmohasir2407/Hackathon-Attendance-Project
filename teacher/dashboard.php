<?php
require_once 'includes/header.php';

$teacher_id = $_SESSION['user_id'];

// Get today's classes from timetable
$today = date('l'); // e.g., 'Monday'
$stmt = $pdo->prepare("
    SELECT t.*, c.name as class_name, s.name as subject_name, s.code as subject_code
    FROM timetable t
    JOIN classes c ON t.class_id = c.id
    JOIN subjects s ON t.subject_id = s.id
    WHERE t.teacher_id = ? AND t.day = ?
    ORDER BY t.period_number
");
$stmt->execute([$teacher_id, $today]);
$todays_classes = $stmt->fetchAll();

// Get next class
$current_time = date('H:i:s');
$next_class = null;
foreach ($todays_classes as $class) {
    if ($class['start_time'] > $current_time) {
        $next_class = $class;
        break;
    }
}
// If currently in class
if (!$next_class) {
    foreach ($todays_classes as $class) {
        if ($class['start_time'] <= $current_time && $class['end_time'] >= $current_time) {
            $next_class = $class;
            $next_class['is_current'] = true;
            break;
        }
    }
}

// Get stats
$stats = [
    'my_classes' => 0,
    'my_subjects' => 0,
    'total_students' => 0
];

$stmt = $pdo->prepare("SELECT COUNT(DISTINCT class_id) FROM teacher_subjects WHERE teacher_id = ?");
$stmt->execute([$teacher_id]);
$stats['my_classes'] = $stmt->fetchColumn();

$stmt = $pdo->prepare("SELECT COUNT(DISTINCT subject_id) FROM teacher_subjects WHERE teacher_id = ?");
$stmt->execute([$teacher_id]);
$stats['my_subjects'] = $stmt->fetchColumn();

$stmt = $pdo->prepare("
    SELECT COUNT(DISTINCT e.student_id) 
    FROM teacher_subjects ts
    JOIN enrollments e ON ts.class_id = e.class_id
    WHERE ts.teacher_id = ?
");
$stmt->execute([$teacher_id]);
$stats['total_students'] = $stmt->fetchColumn();

?>

<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
    <h1 class="h2">Teacher Dashboard</h1>
    <div>
        <span class="text-muted"><i class="bi bi-calendar3"></i> <?php echo date('l, F j, Y'); ?></span>
    </div>
</div>

<div class="row">
    <div class="col-md-8">
        <!-- Next/Current Class Alert -->
        <?php if ($next_class): ?>
            <div class="alert <?php echo isset($next_class['is_current']) ? 'alert-success' : 'alert-info'; ?> shadow-sm">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h4 class="alert-heading">
                            <?php echo isset($next_class['is_current']) ? 'Current Class' : 'Next Class'; ?>
                        </h4>
                        <p class="mb-0">
                            <strong><?php echo htmlspecialchars($next_class['subject_code'] . ' - ' . $next_class['subject_name']); ?></strong> 
                            | Class: <?php echo htmlspecialchars($next_class['class_name']); ?> 
                            | Room: <?php echo htmlspecialchars($next_class['classroom']); ?>
                        </p>
                    </div>
                    <div class="text-end">
                        <div class="fs-4 fw-bold">
                            <?php echo date('h:i A', strtotime($next_class['start_time'])) . ' - ' . date('h:i A', strtotime($next_class['end_time'])); ?>
                        </div>
                        <div>Period <?php echo $next_class['period_number']; ?></div>
                        <a href="qr_attendance.php?class_id=<?php echo $next_class['class_id']; ?>&subject_id=<?php echo $next_class['subject_id']; ?>&period=<?php echo $next_class['period_number']; ?>" class="btn btn-sm btn-primary mt-2">
                            <i class="bi bi-qr-code"></i> Take Attendance
                        </a>
                    </div>
                </div>
            </div>
        <?php else: ?>
            <div class="alert alert-secondary shadow-sm">
                <h4 class="alert-heading">No More Classes Today!</h4>
                <p class="mb-0">You don't have any upcoming classes in your timetable for today.</p>
            </div>
        <?php endif; ?>

        <!-- Today's Timetable -->
        <div class="card shadow-sm mb-4">
            <div class="card-header bg-white">
                <i class="bi bi-list-task me-1"></i> Today's Schedule
            </div>
            <ul class="list-group list-group-flush">
                <?php foreach ($todays_classes as $class): ?>
                    <?php 
                    $is_past = $class['end_time'] < $current_time;
                    $is_active = ($class['start_time'] <= $current_time && $class['end_time'] >= $current_time);
                    $list_class = $is_active ? 'list-group-item-primary' : ($is_past ? 'text-muted bg-light' : '');
                    ?>
                    <li class="list-group-item <?php echo $list_class; ?> d-flex justify-content-between align-items-center">
                        <div>
                            <span class="badge bg-secondary me-2">Period <?php echo $class['period_number']; ?></span>
                            <span class="fw-bold"><?php echo htmlspecialchars($class['subject_code']); ?></span>
                            - <?php echo htmlspecialchars($class['class_name']); ?>
                            <small class="ms-2 d-block d-md-inline"><i class="bi bi-geo-alt"></i> <?php echo htmlspecialchars($class['classroom']); ?></small>
                        </div>
                        <div>
                            <?php echo date('h:i A', strtotime($class['start_time'])) . ' - ' . date('h:i A', strtotime($class['end_time'])); ?>
                        </div>
                    </li>
                <?php endforeach; ?>
                <?php if(empty($todays_classes)): ?>
                    <li class="list-group-item text-center text-muted py-3">No classes scheduled for today.</li>
                <?php endif; ?>
            </ul>
        </div>
    </div>

    <div class="col-md-4">
        <!-- Stats -->
        <div class="row">
            <div class="col-6 mb-3">
                <div class="card text-white bg-primary h-100 shadow-sm">
                    <div class="card-body text-center p-3">
                        <h2 class="mb-0"><?php echo $stats['my_classes']; ?></h2>
                        <div class="small">My Classes</div>
                    </div>
                </div>
            </div>
            <div class="col-6 mb-3">
                <div class="card text-white bg-success h-100 shadow-sm">
                    <div class="card-body text-center p-3">
                        <h2 class="mb-0"><?php echo $stats['total_students']; ?></h2>
                        <div class="small">Total Students</div>
                    </div>
                </div>
            </div>
        </div>

        <div class="card shadow-sm mb-4">
            <div class="card-header bg-white">
                <i class="bi bi-bell me-1"></i> Quick Actions
            </div>
            <div class="card-body p-2">
                <div class="d-grid gap-2">
                    <a href="qr_attendance.php" class="btn btn-outline-primary text-start"><i class="bi bi-qr-code-scan me-2"></i> Generate QR Attendance</a>
                    <a href="assignments.php" class="btn btn-outline-success text-start"><i class="bi bi-journal-plus me-2"></i> Create Assignment</a>
                    <a href="materials.php" class="btn btn-outline-info text-start"><i class="bi bi-cloud-arrow-up me-2"></i> Upload Material</a>
                    <a href="feedback.php" class="btn btn-outline-warning text-start"><i class="bi bi-chat-right-text me-2"></i> Give Student Feedback</a>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>
