<?php
require_once 'includes/header.php';

$student_id = $_SESSION['user_id'];

// Mock Demo Initialization: If student has 0 achievements, give them some starting ones.
$stmt = $pdo->prepare("SELECT COUNT(*) FROM student_achievements WHERE student_id = ?");
$stmt->execute([$student_id]);
if ($stmt->fetchColumn() == 0) {
    $pdo->prepare("INSERT INTO student_achievements (student_id, achievement_name, xp_points) VALUES (?, 'Early Bird Sign In', 50)")->execute([$student_id]);
    $pdo->prepare("INSERT INTO student_achievements (student_id, achievement_name, xp_points) VALUES (?, 'Profile Completed', 100)")->execute([$student_id]);
    $pdo->prepare("INSERT INTO student_achievements (student_id, achievement_name, xp_points) VALUES (?, 'First Assignment Submitted', 150)")->execute([$student_id]);
}

// Fetch Achievements
$stmt = $pdo->prepare("SELECT * FROM student_achievements WHERE student_id = ? ORDER BY awarded_at DESC");
$stmt->execute([$student_id]);
$achievements = $stmt->fetchAll();

$total_xp = 0;
foreach($achievements as $a) {
    $total_xp += $a['xp_points'];
}

// Level Calculation (Simple: 1 Level per 500 XP)
$level = floor($total_xp / 500) + 1;
$next_level_xp = $level * 500;
$progress_pct = (($total_xp % 500) / 500) * 100;

?>

<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
    <h1 class="h2">My Achievements</h1>
</div>

<div class="row mb-4">
    <div class="col-12">
        <div class="card shadow-sm bg-primary text-white border-0 overflow-hidden relative">
            <div class="card-body p-4 d-flex align-items-center justify-content-between">
                <div>
                    <h5 class="mb-1 text-white-50">Current Rank</h5>
                    <h1 class="display-4 fw-bold mb-0">Level <?php echo $level; ?> Scholar</h1>
                    <p class="mb-0 mt-2"><i class="bi bi-star-fill text-warning"></i> <?php echo $total_xp; ?> Total XP</p>
                </div>
                <div class="text-center me-md-5">
                    <i class="bi bi-trophy-fill text-warning" style="font-size: 5rem; text-shadow: 0 4px 15px rgba(0,0,0,0.3);"></i>
                </div>
            </div>
            <div class="card-footer bg-dark bg-opacity-25 border-0">
                <div class="d-flex justify-content-between small mb-1">
                    <span>Level <?php echo $level; ?></span>
                    <span>Level <?php echo $level + 1; ?> (<?php echo $next_level_xp; ?> XP)</span>
                </div>
                <div class="progress" style="height: 10px; background-color: rgba(255,255,255,0.2);">
                    <div class="progress-bar bg-warning" role="progressbar" style="width: <?php echo $progress_pct; ?>%"></div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-md-8">
        <h4 class="mb-3 text-secondary">Recent Badges & XP</h4>
        <div class="row">
            <?php foreach($achievements as $index => $a): 
                $icons = ['bi-award-fill text-primary', 'bi-star-fill text-warning', 'bi-bookmark-star-fill text-success', 'bi-lightning-fill text-info'];
                $icon = $icons[$index % count($icons)];
            ?>
            <div class="col-md-6 mb-3">
                <div class="card shadow-sm h-100 border-0 border-start border-4 border-primary">
                    <div class="card-body d-flex align-items-center">
                        <div class="me-3 display-5">
                            <i class="bi <?php echo $icon; ?>"></i>
                        </div>
                        <div>
                            <h6 class="mb-1 fw-bold"><?php echo htmlspecialchars($a['achievement_name']); ?></h6>
                            <span class="badge bg-light text-primary border border-primary">+<?php echo $a['xp_points']; ?> XP</span>
                            <small class="text-muted d-block mt-1"><?php echo date('M d, Y', strtotime($a['awarded_at'])); ?></small>
                        </div>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
    
    <div class="col-md-4">
        <div class="card shadow-sm mb-4">
            <div class="card-header bg-white">
                <i class="bi bi-info-circle me-1"></i> How to Earn XP?
            </div>
            <div class="card-body">
                <ul class="list-group list-group-flush">
                    <li class="list-group-item d-flex justify-content-between align-items-center">
                        <div><i class="bi bi-qr-code text-primary me-2"></i> Scan Attendance</div>
                        <span class="badge bg-success rounded-pill">+10 XP</span>
                    </li>
                    <li class="list-group-item d-flex justify-content-between align-items-center">
                        <div><i class="bi bi-cloud-arrow-up text-primary me-2"></i> Submit Assignment</div>
                        <span class="badge bg-success rounded-pill">+50 XP</span>
                    </li>
                    <li class="list-group-item d-flex justify-content-between align-items-center">
                        <div><i class="bi bi-clock-history text-primary me-2"></i> Early Submission</div>
                        <span class="badge bg-success rounded-pill">+20 XP</span>
                    </li>
                    <li class="list-group-item d-flex justify-content-between align-items-center">
                        <div><i class="bi bi-emoji-smile text-primary me-2"></i> Positive Feedback</div>
                        <span class="badge bg-success rounded-pill">+100 XP</span>
                    </li>
                </ul>
            </div>
        </div>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>
