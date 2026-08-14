<?php
require_once 'includes/header.php';

$student_id = $_SESSION['user_id'];

// Get student's class
$stmt = $pdo->prepare("SELECT class_id FROM enrollments WHERE student_id = ?");
$stmt->execute([$student_id]);
$enrollment = $stmt->fetch();
$class_id = $enrollment ? $enrollment['class_id'] : null;

// 1. Personal Attendance Rate
$attendance_pct = 0;
if ($class_id) {
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM attendance_sessions WHERE class_id = ?");
    $stmt->execute([$class_id]);
    $total_sessions = $stmt->fetchColumn();
    
    if ($total_sessions > 0) {
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM attendance WHERE student_id = ?");
        $stmt->execute([$student_id]);
        $attended = $stmt->fetchColumn();
        $attendance_pct = round(($attended / $total_sessions) * 100);
    }
}

// Risk detector
$risk_status = 'SAFE';
$risk_color = 'success';
if ($attendance_pct < 60) {
    $risk_status = 'CRITICAL';
    $risk_color = 'danger';
} elseif ($attendance_pct < 80) {
    $risk_status = 'WARNING';
    $risk_color = 'warning text-dark';
}

// 2. Assignment Completion Rate
$assignment_pct = 0;
if ($class_id) {
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM assignments WHERE class_id = ?");
    $stmt->execute([$class_id]);
    $total_assignments = $stmt->fetchColumn();
    
    if ($total_assignments > 0) {
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM assignment_submissions WHERE student_id = ?");
        $stmt->execute([$student_id]);
        $completed_assignments = $stmt->fetchColumn();
        $assignment_pct = round(($completed_assignments / $total_assignments) * 100);
    }
}

// 3. Subject-wise Feedback sentiment (mock logic based on feedback types)
$stmt = $pdo->prepare("
    SELECT sub.name, 
           SUM(CASE WHEN f.feedback_type = 'Excellent' THEN 1 ELSE 0 END) as pos,
           SUM(CASE WHEN f.feedback_type = 'Improving' THEN 1 ELSE 0 END) as neu,
           SUM(CASE WHEN f.feedback_type = 'Needs Practice' THEN 1 ELSE 0 END) as neg
    FROM feedback f
    JOIN subjects sub ON f.subject_id = sub.id
    WHERE f.student_id = ?
    GROUP BY sub.id
");
$stmt->execute([$student_id]);
$feedback_perf = $stmt->fetchAll();

$fb_labels = [];
$fb_data_pos = [];
$fb_data_neg = [];
foreach($feedback_perf as $row) {
    $fb_labels[] = $row['name'];
    $fb_data_pos[] = $row['pos'] + $row['neu'];
    $fb_data_neg[] = $row['neg'];
}
?>

<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
    <h1 class="h2">My Performance Analytics</h1>
</div>

<div class="row mb-4">
    <div class="col-md-6">
        <div class="card shadow-sm h-100">
            <div class="card-body text-center d-flex flex-column justify-content-center py-5">
                <h5 class="text-muted mb-4">Overall Attendance</h5>
                <div class="position-relative d-inline-block mx-auto" style="width: 150px; height: 150px;">
                    <canvas id="attChart"></canvas>
                    <div class="position-absolute top-50 start-50 translate-middle">
                        <h2 class="mb-0 fw-bold"><?php echo $attendance_pct; ?>%</h2>
                    </div>
                </div>
                <div class="mt-4">
                    Status: <span class="badge bg-<?php echo $risk_color; ?> fs-6"><?php echo $risk_status; ?></span>
                </div>
            </div>
        </div>
    </div>
    
    <div class="col-md-6">
        <div class="card shadow-sm h-100">
            <div class="card-body text-center d-flex flex-column justify-content-center py-5">
                <h5 class="text-muted mb-4">Task Completion Rate</h5>
                <div class="position-relative d-inline-block mx-auto" style="width: 150px; height: 150px;">
                    <canvas id="taskChart"></canvas>
                    <div class="position-absolute top-50 start-50 translate-middle">
                        <h2 class="mb-0 fw-bold"><?php echo $assignment_pct; ?>%</h2>
                    </div>
                </div>
                <div class="mt-4">
                    Great job keeping up with assignments!
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-12">
        <div class="card shadow-sm">
            <div class="card-header bg-white">
                <i class="bi bi-bar-chart-line text-primary me-1"></i> Feedback Sentiment by Subject
            </div>
            <div class="card-body">
                <?php if(empty($fb_labels)): ?>
                    <p class="text-center text-muted py-4">Not enough feedback data to display chart.</p>
                <?php else: ?>
                    <canvas id="fbChart" height="80"></canvas>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener("DOMContentLoaded", function() {
    // Attendance Donut
    const ctxAtt = document.getElementById('attChart').getContext('2d');
    new Chart(ctxAtt, {
        type: 'doughnut',
        data: {
            labels: ['Attended', 'Missed'],
            datasets: [{
                data: [<?php echo $attendance_pct; ?>, <?php echo 100 - $attendance_pct; ?>],
                backgroundColor: ['#0d6efd', '#e9ecef'],
                borderWidth: 0,
                cutout: '80%'
            }]
        },
        options: { responsive: true, plugins: { legend: { display: false }, tooltip: { enabled: false } } }
    });

    // Task Donut
    const ctxTask = document.getElementById('taskChart').getContext('2d');
    new Chart(ctxTask, {
        type: 'doughnut',
        data: {
            labels: ['Completed', 'Pending'],
            datasets: [{
                data: [<?php echo $assignment_pct; ?>, <?php echo 100 - $assignment_pct; ?>],
                backgroundColor: ['#198754', '#e9ecef'],
                borderWidth: 0,
                cutout: '80%'
            }]
        },
        options: { responsive: true, plugins: { legend: { display: false }, tooltip: { enabled: false } } }
    });

    <?php if(!empty($fb_labels)): ?>
    // Feedback Bar Chart
    const ctxFb = document.getElementById('fbChart').getContext('2d');
    new Chart(ctxFb, {
        type: 'bar',
        data: {
            labels: <?php echo json_encode($fb_labels); ?>,
            datasets: [
                {
                    label: 'Positive / Neutral',
                    data: <?php echo json_encode($fb_data_pos); ?>,
                    backgroundColor: '#0dcaf0'
                },
                {
                    label: 'Needs Practice',
                    data: <?php echo json_encode($fb_data_neg); ?>,
                    backgroundColor: '#ffc107'
                }
            ]
        },
        options: {
            responsive: true,
            scales: {
                x: { stacked: true },
                y: { stacked: true, beginAtZero: true, ticks: { stepSize: 1 } }
            }
        }
    });
    <?php endif; ?>
});
</script>

<?php require_once 'includes/footer.php'; ?>
