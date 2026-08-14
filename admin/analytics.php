<?php
require_once 'includes/header.php';

// --- DATA FETCHING FOR ANALYTICS ---

// 1. Overview Stats
$stats = [
    'total_students' => $pdo->query("SELECT COUNT(*) FROM students")->fetchColumn(),
    'total_teachers' => $pdo->query("SELECT COUNT(*) FROM teachers")->fetchColumn(),
    'total_classes' => $pdo->query("SELECT COUNT(*) FROM classes")->fetchColumn(),
    'total_attendance_records' => $pdo->query("SELECT COUNT(*) FROM attendance")->fetchColumn()
];

// 2. Attendance by Day (Last 7 Days) for Chart.js
$stmt = $pdo->query("
    SELECT DATE(s.date) as act_date, COUNT(a.student_id) as count
    FROM attendance_sessions s
    LEFT JOIN attendance a ON s.id = a.session_id
    WHERE s.date >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)
    GROUP BY act_date
    ORDER BY act_date ASC
");
$attendance_trend = $stmt->fetchAll();
$chart_labels = [];
$chart_data = [];
foreach($attendance_trend as $row) {
    $chart_labels[] = date('M d', strtotime($row['act_date']));
    $chart_data[] = $row['count'];
}

// 3. At-Risk Students (Missing > 3 classes recently, simplified logic)
// In a real scenario, this would calculate percentage. We'll simulate by finding students with least attendance compared to total sessions for their class.
$stmt = $pdo->query("
    SELECT * FROM (
        SELECT s.id, s.first_name, s.last_name, s.roll_number, c.name as class_name,
               (SELECT COUNT(*) FROM attendance a WHERE a.student_id = s.id) as attended,
               (SELECT COUNT(*) FROM attendance_sessions ssn JOIN enrollments e ON ssn.class_id = e.class_id WHERE e.student_id = s.id) as total_sessions
        FROM students s
        JOIN enrollments e ON s.id = e.student_id
        JOIN classes c ON e.class_id = c.id
    ) AS student_stats
    WHERE total_sessions > 0 AND (attended / total_sessions) < 0.75
    ORDER BY (attended / total_sessions) ASC
    LIMIT 10
");
$at_risk_students = $stmt->fetchAll();

// 4. Department Distribution
$stmt = $pdo->query("
    SELECT d.name, COUNT(c.id) as class_count 
    FROM departments d 
    LEFT JOIN classes c ON d.id = c.department_id 
    GROUP BY d.id
");
$dept_data = $stmt->fetchAll();
$pie_labels = [];
$pie_data = [];
foreach($dept_data as $row) {
    $pie_labels[] = $row['name'];
    $pie_data[] = $row['class_count'];
}
?>

<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
    <h1 class="h2">System Analytics</h1>
    <button class="btn btn-sm btn-outline-secondary" onclick="window.print()"><i class="bi bi-printer"></i> Print Report</button>
</div>

<div class="row mb-4">
    <div class="col-md-3">
        <div class="card text-white bg-primary shadow-sm h-100">
            <div class="card-body">
                <h5 class="card-title">Total Students</h5>
                <h2 class="display-5"><?php echo $stats['total_students']; ?></h2>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card text-white bg-success shadow-sm h-100">
            <div class="card-body">
                <h5 class="card-title">Total Teachers</h5>
                <h2 class="display-5"><?php echo $stats['total_teachers']; ?></h2>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card text-white bg-info shadow-sm h-100">
            <div class="card-body">
                <h5 class="card-title">Active Classes</h5>
                <h2 class="display-5"><?php echo $stats['total_classes']; ?></h2>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card text-white bg-warning shadow-sm h-100">
            <div class="card-body">
                <h5 class="card-title">Attendance Logged</h5>
                <h2 class="display-5"><?php echo $stats['total_attendance_records']; ?></h2>
            </div>
        </div>
    </div>
</div>

<div class="row mb-4">
    <!-- Bar Chart -->
    <div class="col-md-8">
        <div class="card shadow-sm h-100">
            <div class="card-header bg-white">
                <i class="bi bi-bar-chart-fill me-1 text-primary"></i> Attendance Trend (Last 7 Days)
            </div>
            <div class="card-body">
                <canvas id="attendanceChart" height="100"></canvas>
            </div>
        </div>
    </div>
    
    <!-- Pie Chart -->
    <div class="col-md-4">
        <div class="card shadow-sm h-100">
            <div class="card-header bg-white">
                <i class="bi bi-pie-chart-fill me-1 text-success"></i> Classes per Department
            </div>
            <div class="card-body d-flex justify-content-center align-items-center">
                <div style="width: 80%;">
                    <canvas id="deptChart"></canvas>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- At Risk Students Table -->
<div class="row">
    <div class="col-12">
        <div class="card shadow-sm border-danger border-opacity-50">
            <div class="card-header bg-danger text-white d-flex justify-content-between align-items-center">
                <span><i class="bi bi-exclamation-triangle-fill me-2"></i> At-Risk Students (Attendance < 75%)</span>
                <span class="badge bg-light text-danger rounded-pill"><?php echo count($at_risk_students); ?> Found</span>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Roll No</th>
                                <th>Name</th>
                                <th>Class</th>
                                <th>Attendance %</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach($at_risk_students as $s): 
                                $pct = round(($s['attended'] / $s['total_sessions']) * 100);
                            ?>
                            <tr>
                                <td><strong><?php echo htmlspecialchars($s['roll_number']); ?></strong></td>
                                <td><?php echo htmlspecialchars($s['first_name'] . ' ' . $s['last_name']); ?></td>
                                <td><?php echo htmlspecialchars($s['class_name']); ?></td>
                                <td>
                                    <div class="d-flex align-items-center">
                                        <span class="me-2"><?php echo $pct; ?>%</span>
                                        <div class="progress w-100" style="height: 8px;">
                                            <div class="progress-bar bg-danger" role="progressbar" style="width: <?php echo $pct; ?>%"></div>
                                        </div>
                                    </div>
                                </td>
                                <td><span class="badge bg-danger">Critical</span></td>
                            </tr>
                            <?php endforeach; ?>
                            <?php if(empty($at_risk_students)): ?>
                            <tr><td colspan="5" class="text-center py-4 text-success"><i class="bi bi-check-circle-fill fs-4 d-block mb-2"></i> No at-risk students found!</td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
// Pass PHP arrays to JS
const attLabels = <?php echo json_encode($chart_labels); ?>;
const attData = <?php echo json_encode($chart_data); ?>;
const pieLabels = <?php echo json_encode($pie_labels); ?>;
const pieData = <?php echo json_encode($pie_data); ?>;

document.addEventListener("DOMContentLoaded", function() {
    // Bar Chart
    const ctxBar = document.getElementById('attendanceChart').getContext('2d');
    new Chart(ctxBar, {
        type: 'bar',
        data: {
            labels: attLabels,
            datasets: [{
                label: 'Total Scans',
                data: attData,
                backgroundColor: 'rgba(13, 110, 253, 0.7)',
                borderColor: 'rgba(13, 110, 253, 1)',
                borderWidth: 1,
                borderRadius: 4
            }]
        },
        options: {
            responsive: true,
            scales: {
                y: { beginAtZero: true }
            }
        }
    });

    // Pie Chart
    const ctxPie = document.getElementById('deptChart').getContext('2d');
    new Chart(ctxPie, {
        type: 'doughnut',
        data: {
            labels: pieLabels,
            datasets: [{
                data: pieData,
                backgroundColor: [
                    '#0d6efd', '#198754', '#ffc107', '#dc3545', '#0dcaf0', '#6610f2'
                ],
                borderWidth: 0
            }]
        },
        options: {
            responsive: true,
            plugins: {
                legend: { position: 'bottom' }
            }
        }
    });
});
</script>

<?php require_once 'includes/footer.php'; ?>
