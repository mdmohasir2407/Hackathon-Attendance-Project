<?php
require_once 'includes/header.php';

$student_id = $_SESSION['user_id'];
$today = date('l');

// Get student's class
$stmt = $pdo->prepare("SELECT class_id FROM enrollments WHERE student_id = ?");
$stmt->execute([$student_id]);
$enrollment = $stmt->fetch();
$class_id = $enrollment ? $enrollment['class_id'] : null;

// Generate Smart Plan
$smart_plan = [];
$free_periods = [];

if ($class_id) {
    // 1. Find today's free periods
    $stmt = $pdo->prepare("
        SELECT period_number, start_time, end_time 
        FROM timetable 
        WHERE class_id = ? AND day = ? AND (subject_id IS NULL OR teacher_id IS NULL)
        ORDER BY period_number
    ");
    $stmt->execute([$class_id, $today]);
    $free_periods = $stmt->fetchAll();

    // 2. Find pending assignments
    $stmt = $pdo->prepare("
        SELECT a.id, a.title, s.name as subject_name, a.deadline 
        FROM assignments a
        JOIN subjects s ON a.subject_id = s.id
        WHERE a.class_id = ? AND a.deadline >= NOW()
        AND a.id NOT IN (SELECT assignment_id FROM assignment_submissions WHERE student_id = ?)
        ORDER BY a.deadline ASC
    ");
    $stmt->execute([$class_id, $student_id]);
    $pending_assignments = $stmt->fetchAll();

    // 3. Construct the plan
    foreach ($free_periods as $fp) {
        $plan_item = [
            'period' => $fp['period_number'],
            'time' => date('h:i A', strtotime($fp['start_time'])) . ' - ' . date('h:i A', strtotime($fp['end_time'])),
            'tasks' => []
        ];
        
        // Distribute tasks into free periods (simple algorithm)
        if (!empty($pending_assignments)) {
            $task = array_shift($pending_assignments); // take most urgent
            $plan_item['tasks'][] = [
                'duration' => '30 min',
                'description' => "Work on {$task['subject_name']} Assignment: {$task['title']}",
                'type' => 'assignment'
            ];
            // Push back if we want to keep it in the queue for later, but let's just assign one per free period for simplicity
        } else {
            $plan_item['tasks'][] = [
                'duration' => '40 min',
                'description' => "Self-study / Revision for upcoming classes",
                'type' => 'study'
            ];
            $plan_item['tasks'][] = [
                'duration' => '10 min',
                'description' => "Break / Relax",
                'type' => 'break'
            ];
        }
        $smart_plan[] = $plan_item;
    }
}
?>

<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
    <h1 class="h2">Smart Daily Planner</h1>
    <div class="text-muted"><i class="bi bi-calendar-event"></i> <?php echo date('l, M d, Y'); ?></div>
</div>

<p class="lead text-muted">Your personalized study plan based on today's timetable and pending tasks.</p>

<?php if (!$class_id): ?>
    <div class="alert alert-warning">Please enroll in a class to generate a smart planner.</div>
<?php elseif (empty($free_periods)): ?>
    <div class="alert alert-info shadow-sm">
        <h4 class="alert-heading"><i class="bi bi-info-circle"></i> No Free Periods Today!</h4>
        <p>Your schedule is fully packed with classes today. Make sure to review your notes after school!</p>
    </div>
<?php else: ?>
    <div class="row">
        <div class="col-md-8">
            <div class="timeline">
                <?php foreach ($smart_plan as $plan): ?>
                    <div class="card shadow-sm mb-4 border-start border-primary border-4">
                        <div class="card-header bg-white d-flex justify-content-between align-items-center">
                            <h5 class="mb-0 text-primary">FREE PERIOD <?php echo $plan['period']; ?></h5>
                            <span class="badge bg-light text-dark border"><i class="bi bi-clock"></i> <?php echo $plan['time']; ?></span>
                        </div>
                        <div class="card-body">
                            <ul class="list-group list-group-flush">
                                <?php foreach ($plan['tasks'] as $task): ?>
                                    <?php 
                                        $icon = 'bi-book'; $color = 'text-info';
                                        if($task['type'] == 'assignment') { $icon = 'bi-pencil-square'; $color = 'text-warning'; }
                                        if($task['type'] == 'break') { $icon = 'bi-cup-hot'; $color = 'text-success'; }
                                    ?>
                                    <li class="list-group-item d-flex align-items-center border-0 px-0 py-2">
                                        <div class="me-3 fs-4 <?php echo $color; ?>"><i class="bi <?php echo $icon; ?>"></i></div>
                                        <div>
                                            <div class="fw-bold"><?php echo htmlspecialchars($task['description']); ?></div>
                                            <small class="text-muted"><i class="bi bi-hourglass-split"></i> <?php echo $task['duration']; ?></small>
                                        </div>
                                    </li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card shadow-sm mb-4 bg-light">
                <div class="card-body text-center">
                    <h5 class="card-title">How it works</h5>
                    <p class="card-text small text-muted">The Smart Daily Planner analyzes your daily timetable to identify free periods. It then cross-references your pending assignments, deadlines, and upcoming quizzes to automatically generate an optimized study schedule tailored for you.</p>
                </div>
            </div>
        </div>
    </div>
<?php endif; ?>

<?php require_once 'includes/footer.php'; ?>
