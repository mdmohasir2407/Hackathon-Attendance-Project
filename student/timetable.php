<?php
require_once 'includes/header.php';

$student_id = $_SESSION['user_id'];

// Get student's class
$stmt = $pdo->prepare("SELECT class_id FROM enrollments WHERE student_id = ?");
$stmt->execute([$student_id]);
$enrollment = $stmt->fetch();
$class_id = $enrollment ? $enrollment['class_id'] : null;

$timetable = [];

if ($class_id) {
    $stmt = $pdo->prepare("
        SELECT t.*, s.name as subject_name, s.code as subject_code, 
               te.first_name, te.last_name 
        FROM timetable t
        LEFT JOIN subjects s ON t.subject_id = s.id
        LEFT JOIN teachers te ON t.teacher_id = te.id
        WHERE t.class_id = ?
        ORDER BY FIELD(t.day, 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'), t.period_number
    ");
    $stmt->execute([$class_id]);
    $results = $stmt->fetchAll();
    
    foreach ($results as $row) {
        $timetable[$row['day']][$row['period_number']] = $row;
    }
}

$days = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'];
$periods = [1, 2, 3, 4, 5, 6, 7];

$default_timings = [
    1 => ['09:00', '09:50'],
    2 => ['09:50', '10:40'],
    3 => ['10:50', '11:40'],
    4 => ['11:40', '12:30'],
    5 => ['13:30', '14:20'],
    6 => ['14:20', '15:10'],
    7 => ['15:20', '16:10']
];
?>

<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
    <h1 class="h2">My Timetable</h1>
</div>

<?php if (!$class_id): ?>
    <div class="alert alert-warning">
        You are not enrolled in any class yet. Please contact the administration.
    </div>
<?php else: ?>
    <div class="card shadow-sm mb-4">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-bordered text-center align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Day / Period</th>
                            <th>Period 1<br><small class="text-muted">09:00 - 09:50</small></th>
                            <th>Period 2<br><small class="text-muted">09:50 - 10:40</small></th>
                            <th class="table-warning text-dark align-middle" style="width: 50px;">Break 1</th>
                            <th>Period 3<br><small class="text-muted">10:50 - 11:40</small></th>
                            <th>Period 4<br><small class="text-muted">11:40 - 12:30</small></th>
                            <th class="table-warning text-dark align-middle" style="width: 50px;">Lunch</th>
                            <th>Period 5<br><small class="text-muted">13:30 - 14:20</small></th>
                            <th>Period 6<br><small class="text-muted">14:20 - 15:10</small></th>
                            <th class="table-warning text-dark align-middle" style="width: 50px;">Break 2</th>
                            <th>Period 7<br><small class="text-muted">15:20 - 16:10</small></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($days as $day): ?>
                            <tr>
                                <th class="table-light align-middle"><?php echo $day; ?></th>
                                <?php foreach ($periods as $p): ?>
                                    <?php 
                                    if ($p == 3) echo '<td class="table-warning text-dark align-middle border-start border-end" rowspan="1"><div style="writing-mode: vertical-rl; text-orientation: mixed; margin:auto;">BREAK</div></td>';
                                    if ($p == 5) echo '<td class="table-warning text-dark align-middle border-start border-end" rowspan="1"><div style="writing-mode: vertical-rl; text-orientation: mixed; margin:auto;">LUNCH</div></td>';
                                    if ($p == 7) echo '<td class="table-warning text-dark align-middle border-start border-end" rowspan="1"><div style="writing-mode: vertical-rl; text-orientation: mixed; margin:auto;">BREAK</div></td>';
                                    
                                    $cell = $timetable[$day][$p] ?? null; 
                                    $cell_class = $cell ? 'bg-light' : '';
                                    if ($day == date('l') && $cell) {
                                        $current_time = date('H:i:s');
                                        if ($cell['start_time'] <= $current_time && $cell['end_time'] >= $current_time) {
                                            $cell_class = 'bg-primary text-white';
                                        }
                                    }
                                    ?>
                                    <td class="<?php echo $cell_class; ?>" style="min-width: 130px; height: 90px;">
                                        <?php if ($cell && !empty($cell['subject_id'])): ?>
                                            <div class="fw-bold premium-text text-wrap" style="font-size: 0.85rem; line-height: 1.2;">
                                                <?php echo htmlspecialchars($cell['subject_name']); ?>
                                            </div>
                                            <div class="small mt-1"><i class="bi bi-geo-alt"></i> <?php echo htmlspecialchars($cell['classroom']); ?></div>
                                        <?php else: ?>
                                            <div class="<?php echo ($cell_class == 'bg-primary text-white') ? 'text-white' : 'text-muted'; ?> fw-bold mb-1">Free Period</div>
                                            <div class="small"><a href="planner.php" class="<?php echo ($cell_class == 'bg-primary text-white') ? 'text-white text-decoration-underline' : 'text-primary text-decoration-none'; ?>"><i class="bi bi-lightbulb-fill text-warning"></i> View Suggestion</a></div>
                                        <?php endif; ?>
                                    </td>
                                <?php endforeach; ?>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
<?php endif; ?>

<?php require_once 'includes/footer.php'; ?>
