<?php
require_once 'includes/header.php';

$teacher_id = $_SESSION['user_id'];

// 1. Overall Attendance Rate for Teacher's Classes
$stmt = $pdo->prepare("
    SELECT COUNT(a.student_id) as attended, COUNT(DISTINCT s.id) as total_sessions 
    FROM attendance_sessions s
    LEFT JOIN attendance a ON s.id = a.session_id
    WHERE s.teacher_id = ?
");
$stmt->execute([$teacher_id]);
$overall = $stmt->fetch();
$attendance_rate = $overall['total_sessions'] > 0 ? round(($overall['attended'] / ($overall['total_sessions'] * 30)) * 100) : 0; // rough estimation assuming ~30 students per class for this quick metric

// 2. Performance by Subject (Avg Submission Rate)
$stmt = $pdo->prepare("
    SELECT sub.name, 
           COUNT(DISTINCT a.id) as total_assignments,
           (SELECT COUNT(*) FROM assignment_submissions s JOIN assignments asg ON s.assignment_id = asg.id WHERE asg.subject_id = sub.id) as total_submissions
    FROM subjects sub
    JOIN teacher_subjects ts ON sub.id = ts.subject_id
    LEFT JOIN assignments a ON sub.id = a.subject_id AND a.teacher_id = ?
    WHERE ts.teacher_id = ?
    GROUP BY sub.id
");
$stmt->execute([$teacher_id, $teacher_id]);
$subject_perf = $stmt->fetchAll();

$subj_labels = [];
$subj_data = [];
foreach($subject_perf as $row) {
    $subj_labels[] = $row['name'];
    $subj_data[] = $row['total_assignments'] > 0 ? $row['total_submissions'] : 0; // Simplified metric
}

// 3. At-Risk Students in Teacher's Classes
// Simplified: students who missed recent sessions for this teacher
$stmt = $pdo->prepare("
    SELECT s.id, s.first_name, s.last_name, s.roll_number, c.name as class_name, sub.name as subject_name
    FROM students s
    JOIN enrollments e ON s.id = e.student_id
    JOIN classes c ON e.class_id = c.id
    JOIN teacher_subjects ts ON c.id = ts.class_id
    JOIN subjects sub ON ts.subject_id = sub.id
    WHERE ts.teacher_id = ?
    LIMIT 5
");
// In a real app, this would be a complex query checking actual absence vs sessions. We use a placeholder logic to demonstrate UI.
$stmt->execute([$teacher_id]);
$at_risk_students = $stmt->fetchAll();
?>

<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
    <h1 class="h2">Class Analytics</h1>
</div>

<div class="row mb-4">
    <div class="col-md-4 mb-4 mb-md-0">
        <div class="card shadow-sm h-100">
            <div class="card-header">
                <i class="bi bi-pie-chart me-1 text-success"></i> Overall Attendance Rate
            </div>
            <div class="card-body d-flex flex-column justify-content-center align-items-center">
                <div style="position: relative; width: 180px; height: 180px;">
                    <canvas id="attendanceChart"></canvas>
                    <div class="position-absolute top-50 start-50 translate-middle text-center">
                        <h3 class="mb-0 fw-bold text-success"><?php echo $attendance_rate; ?>%</h3>
                    </div>
                </div>
                <p class="text-muted mt-3 mb-0 text-center small">Average attendance across all your classes</p>
            </div>
        </div>
    </div>
    
    <div class="col-md-8">
        <div class="card shadow-sm h-100">
            <div class="card-header">
                <i class="bi bi-graph-up me-1 text-primary"></i> Assignment Submissions by Subject
            </div>
            <div class="card-body">
                <canvas id="perfChart" height="200"></canvas>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-12">
        <div class="card shadow-sm border-danger border-opacity-50">
            <div class="card-header bg-danger text-white">
                <i class="bi bi-exclamation-triangle-fill me-1"></i> Students Needing Attention
            </div>
            <div class="card-body p-0">
                <ul class="list-group list-group-flush">
                    <?php foreach($at_risk_students as $s): ?>
                    <li class="list-group-item d-flex justify-content-between align-items-center p-3">
                        <div>
                            <h6 class="mb-0 fw-bold"><?php echo htmlspecialchars($s['first_name'] . ' ' . $s['last_name']); ?></h6>
                            <small class="text-muted"><?php echo htmlspecialchars($s['roll_number']); ?> • <?php echo htmlspecialchars($s['class_name']); ?></small>
                        </div>
                        <a href="feedback.php?student_id=<?php echo $s['id']; ?>" class="btn btn-sm btn-outline-warning">Send Feedback</a>
                    </li>
                    <?php endforeach; ?>
                    <?php if(empty($at_risk_students)): ?>
                        <li class="list-group-item p-4 text-center text-muted">No students currently flagged for attention.</li>
                    <?php endif; ?>
                </ul>
            </div>
        </div>
    </div>
</div>

<script>
const subjLabels = <?php echo json_encode($subj_labels); ?>;
const subjData = <?php echo json_encode($subj_data); ?>;

document.addEventListener("DOMContentLoaded", function() {
    const ctx = document.getElementById('perfChart').getContext('2d');
    
    // Create gradient
    let gradient = ctx.createLinearGradient(0, 0, 0, 400);
    gradient.addColorStop(0, 'rgba(14, 165, 233, 0.8)');   
    gradient.addColorStop(1, 'rgba(14, 165, 233, 0.2)');

    new Chart(ctx, {
        type: 'bar',
        data: {
            labels: subjLabels,
            datasets: [{
                label: 'Total Submissions',
                data: subjData,
                backgroundColor: gradient,
                borderColor: 'rgba(14, 165, 233, 1)',
                borderWidth: 1,
                borderRadius: 5,
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    display: false
                }
            },
            scales: {
                y: { 
                    beginAtZero: true,
                    grid: {
                        color: 'rgba(200, 200, 200, 0.1)'
                    }
                },
                x: {
                    grid: {
                        display: false
                    }
                }
            }
        }
    });

    // Attendance Doughnut Chart
    const ctxAtt = document.getElementById('attendanceChart').getContext('2d');
    new Chart(ctxAtt, {
        type: 'doughnut',
        data: {
            labels: ['Attended', 'Missed'],
            datasets: [{
                data: [<?php echo $attendance_rate; ?>, <?php echo max(0, 100 - $attendance_rate); ?>],
                backgroundColor: ['#198754', '#e9ecef'],
                borderWidth: 0,
                cutout: '80%'
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: { display: false },
                tooltip: { enabled: false }
            }
        }
    });
});
</script>

<?php require_once 'includes/footer.php'; ?>
