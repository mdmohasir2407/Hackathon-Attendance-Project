<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'student') {
    header("Location: ../auth/login.php");
    exit;
}
require_once '../config/database.php';

$student_id = $_SESSION['user_id'];
$action = $_GET['action'] ?? 'list';
$test_id = $_GET['id'] ?? null;

require_once 'includes/header.php';
?>

<?php if ($action == 'list'): ?>
    
    <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
        <div>
            <h2 class="fw-bold mb-0">Weekly Questions</h2>
            <p class="text-muted small mb-0">View scheduled weekly question papers for offline exams</p>
        </div>
    </div>

    <!-- Period Filter Buttons -->
    <?php 
    $filter_period = $_GET['period'] ?? 'all'; 
    ?>
    <div class="d-flex gap-2 mb-4 flex-wrap">
        <a href="tests.php?period=all" class="btn btn-sm <?php echo $filter_period === 'all' ? 'btn-primary' : 'btn-outline-secondary'; ?> rounded-pill px-4">All Periods</a>
        <a href="tests.php?period=Period 1" class="btn btn-sm <?php echo $filter_period === 'Period 1' ? 'btn-primary' : 'btn-outline-secondary'; ?> rounded-pill px-4">
            <i class="bi bi-1-circle me-1"></i> Period 1
        </a>
        <a href="tests.php?period=Period 2" class="btn btn-sm <?php echo $filter_period === 'Period 2' ? 'btn-primary' : 'btn-outline-secondary'; ?> rounded-pill px-4">
            <i class="bi bi-2-circle me-1"></i> Period 2
        </a>
        <a href="tests.php?period=Period 3" class="btn btn-sm <?php echo $filter_period === 'Period 3' ? 'btn-primary' : 'btn-outline-secondary'; ?> rounded-pill px-4">
            <i class="bi bi-3-circle me-1"></i> Period 3
        </a>
    </div>

    <div class="row g-4">
        <?php
        // Fetch tests for classes this student is enrolled in
        $sql = "
            SELECT t.*, s.name as subject_name, s.code as subject_code, c.name as class_name,
                   (SELECT COUNT(*) FROM test_questions tq WHERE tq.test_id = t.id) as question_count,
                   (SELECT COALESCE(SUM(marks), 0) FROM test_questions tq WHERE tq.test_id = t.id) as total_marks
            FROM tests t
            JOIN subjects s ON t.subject_id = s.id
            JOIN classes c ON t.class_id = c.id
            JOIN enrollments e ON c.id = e.class_id
            WHERE e.student_id = ?
        ";

        $params = [$student_id];
        if ($filter_period !== 'all') {
            $sql .= " AND t.test_period = ?";
            $params[] = $filter_period;
        }

        $sql .= " ORDER BY t.scheduled_date DESC, t.created_at DESC";

        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $tests = $stmt->fetchAll();
        
        if (count($tests) > 0):
            foreach ($tests as $t):
                $period_badge = 'bg-primary';
                if ($t['test_period'] == 'Period 1') $period_badge = 'bg-primary';
                if ($t['test_period'] == 'Period 2') $period_badge = 'bg-purple';
                if ($t['test_period'] == 'Period 3') $period_badge = 'bg-dark';
        ?>
            <div class="col-md-6 col-lg-4">
                <div class="card-modern h-100 p-4 d-flex flex-column justify-content-between">
                    <div>
                        <div class="d-flex justify-content-between align-items-start mb-3">
                            <span class="badge <?php echo $period_badge; ?> px-3 py-2 rounded-pill fw-bold">
                                <i class="bi bi-clock-history me-1"></i> <?php echo htmlspecialchars($t['test_period']); ?>
                            </span>
                            <span class="badge bg-secondary px-3 py-2 rounded-pill">
                                <i class="bi bi-file-earmark-text me-1"></i> Question Paper
                            </span>
                        </div>
                        
                        <h5 class="fw-bold mb-1"><?php echo htmlspecialchars($t['title']); ?></h5>
                        <p class="text-muted small mb-2">
                            <strong><?php echo htmlspecialchars($t['subject_code']); ?></strong> &bull; <?php echo htmlspecialchars($t['subject_name']); ?>
                        </p>

                        <?php if($t['scheduled_date']): ?>
                            <div class="p-2 rounded bg-light border text-secondary small mb-3">
                                <div><i class="bi bi-calendar-check me-1 text-primary"></i> <strong>Test Date:</strong> <?php echo date('M d, Y', strtotime($t['scheduled_date'])); ?></div>
                                <div><i class="bi bi-clock me-1 text-primary"></i> <strong>Time Window:</strong> <?php echo date('h:i A', strtotime($t['start_time'])); ?> - <?php echo date('h:i A', strtotime($t['end_time'])); ?></div>
                            </div>
                        <?php endif; ?>
                        
                        <?php if($t['description']): ?>
                            <p class="text-secondary mb-3 small"><?php echo htmlspecialchars($t['description']); ?></p>
                        <?php endif; ?>

                        <div class="d-flex align-items-center justify-content-between mb-4 text-muted small fw-medium">
                            <span><i class="bi bi-stopwatch me-1 text-primary"></i> <?php echo $t['duration_minutes']; ?> Mins</span>
                            <span><i class="bi bi-patch-question me-1 text-primary"></i> <?php echo $t['question_count']; ?> Questions (<?php echo $t['total_marks']; ?> Marks)</span>
                        </div>
                    </div>
                    
                    <div>
                        <a href="tests.php?action=view_paper&id=<?php echo $t['id']; ?>" class="btn btn-outline-primary w-100 rounded-pill fw-bold py-2">
                            <i class="bi bi-eye-fill me-1"></i> View Question Paper
                        </a>
                    </div>
                </div>
            </div>
        <?php 
            endforeach;
        else:
        ?>
            <div class="col-12">
                <div class="card-modern p-5 text-center">
                    <i class="bi bi-journal-x text-muted mb-3 d-block" style="font-size: 3rem;"></i>
                    <h5 class="fw-bold text-muted">No Question Papers Available</h5>
                    <p class="text-secondary">No weekly test period question papers match your filter choice.</p>
                </div>
            </div>
        <?php endif; ?>
    </div>

<?php elseif ($action == 'view_paper' && $test_id): ?>
    
    <?php
    // Verify test belongs to student's enrolled class
    $stmt = $pdo->prepare("
        SELECT t.*, s.name as subject_name, s.code as subject_code, c.name as class_name, te.first_name as teacher_first, te.last_name as teacher_last
        FROM tests t
        JOIN enrollments e ON t.class_id = e.class_id
        JOIN subjects s ON t.subject_id = s.id
        JOIN classes c ON t.class_id = c.id
        LEFT JOIN teachers te ON t.teacher_id = te.id
        WHERE t.id = ? AND e.student_id = ?
    ");
    $stmt->execute([$test_id, $student_id]);
    $test = $stmt->fetch();
    
    if (!$test) {
        echo "<div class='alert alert-danger'>Invalid test question paper specified.</div>";
        require_once 'includes/footer.php';
        exit;
    }
    
    // Fetch questions
    $stmt = $pdo->prepare("SELECT * FROM test_questions WHERE test_id = ? ORDER BY id ASC");
    $stmt->execute([$test_id]);
    $questions = $stmt->fetchAll();

    $total_marks = 0;
    foreach ($questions as $q) {
        $total_marks += $q['marks'];
    }
    ?>
    
    <div class="d-flex justify-content-between align-items-center mb-4 print-hide flex-wrap gap-2">
        <a href="tests.php" class="btn btn-outline-secondary rounded-pill">
            <i class="bi bi-arrow-left me-1"></i> Back to All Papers
        </a>
        <button onclick="window.print();" class="btn btn-gradient rounded-pill px-4">
            <i class="bi bi-printer me-2"></i> Print Question Paper
        </button>
    </div>

    <!-- Formatted Question Paper Sheet -->
    <div class="card-modern p-5 bg-white border shadow-sm print-area" id="question-paper-sheet">
        <!-- Question Paper Header -->
        <div class="text-center border-bottom pb-4 mb-4">
            <div class="d-flex justify-content-center align-items-center gap-2 mb-2">
                <i class="bi bi-hexagon-fill text-primary fs-3"></i>
                <h3 class="fw-bold mb-0 text-uppercase tracking-wide">SMART EDUCATION PORTAL</h3>
            </div>
            <h5 class="fw-bold text-secondary mb-1">OFFLINE WEEKLY QUESTION PAPER</h5>
            <div class="badge bg-primary fs-6 rounded-pill px-3 py-2 mt-1"><?php echo htmlspecialchars($test['test_period']); ?></div>
        </div>

        <div class="row g-3 p-3 bg-light rounded-3 border mb-4 text-dark">
            <div class="col-md-6">
                <div><strong>Subject:</strong> <?php echo htmlspecialchars($test['subject_name']); ?> (<?php echo htmlspecialchars($test['subject_code']); ?>)</div>
                <div><strong>Class:</strong> <?php echo htmlspecialchars($test['class_name']); ?></div>
                <div><strong>Teacher:</strong> <?php echo htmlspecialchars(($test['teacher_first'] ?? '') . ' ' . ($test['teacher_last'] ?? '')); ?></div>
            </div>
            <div class="col-md-6 text-md-end">
                <div><strong>Test Title:</strong> <?php echo htmlspecialchars($test['title']); ?></div>
                <div><strong>Date:</strong> <?php echo $test['scheduled_date'] ? date('M d, Y', strtotime($test['scheduled_date'])) : 'N/A'; ?></div>
                <div><strong>Duration:</strong> <?php echo $test['duration_minutes']; ?> Minutes &bull; <strong>Total Marks:</strong> <?php echo $total_marks; ?> Marks</div>
            </div>
        </div>

        <?php if($test['description']): ?>
            <div class="alert alert-info border-info border-opacity-25 py-2 px-3 small mb-4">
                <i class="bi bi-info-circle me-1"></i> <strong>Instructions:</strong> <?php echo htmlspecialchars($test['description']); ?>
            </div>
        <?php endif; ?>

        <!-- Questions Section -->
        <h5 class="fw-bold mb-3 border-bottom pb-2 text-primary">QUESTIONS</h5>

        <?php if (count($questions) > 0): ?>
            <?php foreach ($questions as $index => $q): ?>
                <div class="mb-4 pb-3 border-bottom border-light">
                    <div class="d-flex justify-content-between align-items-start mb-2">
                        <h6 class="fw-bold fs-5 mb-0">Q<?php echo $index + 1; ?>. <?php echo nl2br(htmlspecialchars($q['question_text'])); ?></h6>
                        <span class="badge bg-secondary rounded-pill ms-2"><?php echo $q['marks']; ?> Mark(s)</span>
                    </div>

                    <?php if (!empty($q['option_a']) || !empty($q['option_b'])): ?>
                        <div class="row g-2 mt-2 ps-3">
                            <div class="col-md-6">
                                <div><strong>(A)</strong> <?php echo htmlspecialchars($q['option_a']); ?></div>
                            </div>
                            <div class="col-md-6">
                                <div><strong>(B)</strong> <?php echo htmlspecialchars($q['option_b']); ?></div>
                            </div>
                            <div class="col-md-6">
                                <div><strong>(C)</strong> <?php echo htmlspecialchars($q['option_c']); ?></div>
                            </div>
                            <div class="col-md-6">
                                <div><strong>(D)</strong> <?php echo htmlspecialchars($q['option_d']); ?></div>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
        <?php else: ?>
            <div class="text-center py-5 text-muted">
                <i class="bi bi-exclamation-circle display-4 d-block mb-2"></i>
                Questions have not been published for this test period yet.
            </div>
        <?php endif; ?>

        <div class="text-center text-muted small mt-5 pt-3 border-top">
            *** End of Question Paper ***
        </div>
    </div>

    <!-- Print Styles -->
    <style>
        @media print {
            body * {
                visibility: hidden;
            }
            .print-area, .print-area * {
                visibility: visible;
            }
            .print-area {
                position: absolute;
                left: 0;
                top: 0;
                width: 100%;
                box-shadow: none !important;
                border: none !important;
            }
            .print-hide {
                display: none !important;
            }
            .sidebar, .top-navbar {
                display: none !important;
            }
        }
    </style>

<?php endif; ?>

<?php require_once 'includes/footer.php'; ?>
