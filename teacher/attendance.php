<?php
require_once 'includes/header.php';

$teacher_id = $_SESSION['user_id'];

// Fetch all attendance sessions created by this teacher
$stmt = $pdo->prepare("
    SELECT s.id, s.date, s.period, c.name as class_name, sub.name as subject_name,
           (SELECT COUNT(*) FROM enrollments WHERE class_id = s.class_id) as total_students,
           (SELECT COUNT(*) FROM attendance WHERE session_id = s.id AND status = 'Present') as present_count
    FROM attendance_sessions s
    JOIN classes c ON s.class_id = c.id
    JOIN subjects sub ON s.subject_id = sub.id
    WHERE s.teacher_id = ?
    ORDER BY s.date DESC, s.period DESC
");
$stmt->execute([$teacher_id]);
$sessions = $stmt->fetchAll();

// Handle viewing a specific session
$selected_session = null;
$session_details = [];
if (isset($_GET['session_id'])) {
    $session_id = $_GET['session_id'];
    
    // Verify this session belongs to the teacher
    $stmt = $pdo->prepare("SELECT id FROM attendance_sessions WHERE id = ? AND teacher_id = ?");
    $stmt->execute([$session_id, $teacher_id]);
    if ($stmt->fetch()) {
        $selected_session = $session_id;
        
        $stmt = $pdo->prepare("
            SELECT st.roll_number, st.first_name, st.last_name, a.status, a.timestamp
            FROM students st
            JOIN enrollments e ON st.id = e.student_id
            JOIN attendance_sessions s ON e.class_id = s.class_id
            LEFT JOIN attendance a ON s.id = a.session_id AND st.id = a.student_id
            WHERE s.id = ?
            ORDER BY st.roll_number ASC
        ");
        $stmt->execute([$session_id]);
        $session_details = $stmt->fetchAll();
    }
}
?>

<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
    <h1 class="h2">Attendance History</h1>
    <a href="qr_attendance.php" class="btn btn-primary"><i class="bi bi-qr-code"></i> New QR Session</a>
</div>

<div class="row">
    <!-- Session List -->
    <div class="col-md-5 mb-4">
        <div class="card shadow-sm h-100">
            <div class="card-header bg-white">
                <i class="bi bi-calendar-check me-1"></i> Past Sessions
            </div>
            <div class="card-body p-0">
                <div class="list-group list-group-flush" style="max-height: 600px; overflow-y: auto;">
                    <?php foreach($sessions as $s): 
                        $pct = $s['total_students'] > 0 ? round(($s['present_count'] / $s['total_students']) * 100) : 0;
                    ?>
                    <a href="?session_id=<?php echo $s['id']; ?>" class="list-group-item list-group-item-action <?php echo ($selected_session == $s['id']) ? 'active' : ''; ?>">
                        <div class="d-flex w-100 justify-content-between">
                            <h6 class="mb-1 fw-bold"><?php echo htmlspecialchars($s['class_name'] . ' - ' . $s['subject_name']); ?></h6>
                            <small><?php echo date('M d, Y', strtotime($s['date'])); ?></small>
                        </div>
                        <p class="mb-1 small">Period: <?php echo $s['period']; ?></p>
                        <div class="d-flex justify-content-between align-items-center mt-2">
                            <small><?php echo $s['present_count']; ?> / <?php echo $s['total_students']; ?> Present</small>
                            <span class="badge bg-<?php echo ($pct >= 75) ? 'success' : (($pct >= 50) ? 'warning' : 'danger'); ?> rounded-pill"><?php echo $pct; ?>%</span>
                        </div>
                    </a>
                    <?php endforeach; ?>
                    <?php if(empty($sessions)): ?>
                        <div class="p-4 text-center text-muted">No attendance sessions found.</div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Session Details -->
    <div class="col-md-7 mb-4">
        <div class="card shadow-sm h-100">
            <div class="card-header bg-white">
                <i class="bi bi-people me-1"></i> Session Details
            </div>
            <div class="card-body p-0">
                <?php if($selected_session): ?>
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Roll No</th>
                                <th>Student Name</th>
                                <th>Status</th>
                                <th>Timestamp</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach($session_details as $st): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($st['roll_number']); ?></td>
                                <td><?php echo htmlspecialchars($st['first_name'] . ' ' . $st['last_name']); ?></td>
                                <td>
                                    <?php if($st['status'] == 'Present'): ?>
                                        <span class="badge bg-success">Present</span>
                                    <?php else: ?>
                                        <span class="badge bg-danger">Absent</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php echo $st['timestamp'] ? date('h:i:s A', strtotime($st['timestamp'])) : '-'; ?>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <?php else: ?>
                    <div class="d-flex flex-column justify-content-center align-items-center h-100 p-5 text-muted">
                        <i class="bi bi-hand-index-thumb display-1 mb-3 opacity-25"></i>
                        <h5>Select a session to view details</h5>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>
