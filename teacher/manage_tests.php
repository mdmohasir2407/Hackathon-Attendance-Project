<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'teacher') {
    header("Location: ../auth/login.php");
    exit;
}
require_once '../config/database.php';

$teacher_id = $_SESSION['user_id'];
$success = '';
$error = '';
$action = $_GET['action'] ?? 'list';
$test_id = $_GET['id'] ?? null;

// Handle test creation
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['create_test'])) {
    $title = trim($_POST['title']);
    $description = trim($_POST['description']);
    $subject_id = $_POST['subject_id'];
    $class_id = $_POST['class_id'];
    $test_period = $_POST['test_period'] ?? 'Period 1';
    $scheduled_date = $_POST['scheduled_date'] ?? date('Y-m-d');
    $start_time = $_POST['start_time'] ?? '09:00';
    $end_time = $_POST['end_time'] ?? '10:00';
    $duration = $_POST['duration'];
    $status = $_POST['status'] ?? 'Scheduled';

    try {
        $stmt = $pdo->prepare("INSERT INTO tests (teacher_id, subject_id, class_id, test_period, scheduled_date, start_time, end_time, title, description, duration_minutes, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([$teacher_id, $subject_id, $class_id, $test_period, $scheduled_date, $start_time, $end_time, $title, $description, $duration, $status]);
        $success = "Weekly Test period successfully created and scheduled!";
    } catch (PDOException $e) {
        $error = "Failed to create test: " . $e->getMessage();
    }
}

// Handle question addition
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['add_question'])) {
    $q_text = trim($_POST['question_text']);
    $opt_a = trim($_POST['option_a']);
    $opt_b = trim($_POST['option_b']);
    $opt_c = trim($_POST['option_c']);
    $opt_d = trim($_POST['option_d']);
    $correct = $_POST['correct_option'];
    $marks = $_POST['marks'];

    try {
        $stmt = $pdo->prepare("INSERT INTO test_questions (test_id, question_text, option_a, option_b, option_c, option_d, correct_option, marks) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([$test_id, $q_text, $opt_a, $opt_b, $opt_c, $opt_d, $correct, $marks]);
        $success = "Question added to test successfully.";
    } catch (PDOException $e) {
        $error = "Failed to add question.";
    }
}

// Handle question deletion
if (isset($_GET['delete_q']) && $test_id) {
    $q_id = $_GET['delete_q'];
    try {
        $stmt = $pdo->prepare("DELETE FROM test_questions WHERE id = ? AND test_id = ?");
        $stmt->execute([$q_id, $test_id]);
        $success = "Question deleted.";
    } catch (PDOException $e) {
        $error = "Failed to delete question.";
    }
}

// Handle test deletion
if (isset($_GET['delete_test'])) {
    $del_id = $_GET['delete_test'];
    try {
        $stmt = $pdo->prepare("DELETE FROM tests WHERE id = ? AND teacher_id = ?");
        $stmt->execute([$del_id, $teacher_id]);
        $success = "Test deleted successfully.";
    } catch (PDOException $e) {
        $error = "Failed to delete test.";
    }
}

require_once 'includes/header.php';
?>

<?php if($success): ?>
    <div class="alert alert-success alert-dismissible fade show" role="alert">
        <i class="bi bi-check-circle me-2"></i> <?php echo htmlspecialchars($success); ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
<?php endif; ?>
<?php if($error): ?>
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
        <i class="bi bi-exclamation-circle me-2"></i> <?php echo htmlspecialchars($error); ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
<?php endif; ?>

<?php if ($action == 'list'): ?>
    
    <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
        <div>
            <h2 class="fw-bold mb-0">Manage Weekly Tests</h2>
            <p class="text-muted small mb-0">Set up 3 weekly test periods and prepare test questions in advance</p>
        </div>
        <button class="btn btn-gradient" data-bs-toggle="modal" data-bs-target="#createTestModal">
            <i class="bi bi-plus-lg me-2"></i> Schedule Weekly Test
        </button>
    </div>

    <!-- Filter Pills for Weekly Periods -->
    <?php 
    $filter_period = $_GET['period'] ?? 'all';
    ?>
    <div class="d-flex gap-2 mb-4">
        <a href="manage_tests.php?period=all" class="btn btn-sm <?php echo $filter_period === 'all' ? 'btn-primary' : 'btn-outline-secondary'; ?> rounded-pill px-3">All Periods</a>
        <a href="manage_tests.php?period=Period 1" class="btn btn-sm <?php echo $filter_period === 'Period 1' ? 'btn-primary' : 'btn-outline-secondary'; ?> rounded-pill px-3"><i class="bi bi-1-circle me-1"></i> Period 1</a>
        <a href="manage_tests.php?period=Period 2" class="btn btn-sm <?php echo $filter_period === 'Period 2' ? 'btn-primary' : 'btn-outline-secondary'; ?> rounded-pill px-3"><i class="bi bi-2-circle me-1"></i> Period 2</a>
        <a href="manage_tests.php?period=Period 3" class="btn btn-sm <?php echo $filter_period === 'Period 3' ? 'btn-primary' : 'btn-outline-secondary'; ?> rounded-pill px-3"><i class="bi bi-3-circle me-1"></i> Period 3</a>
    </div>

    <div class="card-modern">
        <div class="table-responsive">
            <table class="table table-modern align-middle">
                <thead>
                    <tr>
                        <th>Title</th>
                        <th>Weekly Period</th>
                        <th>Subject & Class</th>
                        <th>Schedule (Date & Time)</th>
                        <th>Duration</th>
                        <th>Questions</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $sql = "
                        SELECT t.*, s.name as subject_name, s.code as subject_code, c.name as class_name,
                               (SELECT COUNT(*) FROM test_questions tq WHERE tq.test_id = t.id) as question_count
                        FROM tests t
                        JOIN subjects s ON t.subject_id = s.id
                        JOIN classes c ON t.class_id = c.id
                        WHERE t.teacher_id = ?
                    ";
                    $params = [$teacher_id];
                    if ($filter_period !== 'all') {
                        $sql .= " AND t.test_period = ?";
                        $params[] = $filter_period;
                    }
                    $sql .= " ORDER BY t.scheduled_date DESC, t.created_at DESC";

                    $stmt = $pdo->prepare($sql);
                    $stmt->execute($params);
                    $tests = $stmt->fetchAll();

                    if(count($tests) > 0): 
                        foreach($tests as $t): 
                            $badge_color = 'bg-info';
                            if ($t['test_period'] == 'Period 1') $badge_color = 'bg-primary';
                            if ($t['test_period'] == 'Period 2') $badge_color = 'bg-purple';
                            if ($t['test_period'] == 'Period 3') $badge_color = 'bg-dark';

                            $status_badge = 'bg-secondary';
                            if ($t['status'] == 'Scheduled') $status_badge = 'bg-warning text-dark';
                            if ($t['status'] == 'Active') $status_badge = 'bg-success';
                            if ($t['status'] == 'Completed') $status_badge = 'bg-secondary';
                    ?>
                        <tr>
                            <td>
                                <div class="fw-bold"><?php echo htmlspecialchars($t['title']); ?></div>
                                <?php if($t['description']): ?>
                                    <div class="text-muted small"><?php echo htmlspecialchars(substr($t['description'], 0, 45)); ?>...</div>
                                <?php endif; ?>
                            </td>
                            <td>
                                <span class="badge <?php echo $badge_color; ?> rounded-pill px-3 py-2">
                                    <i class="bi bi-clock-history me-1"></i> <?php echo htmlspecialchars($t['test_period']); ?>
                                </span>
                            </td>
                            <td>
                                <div class="fw-medium"><?php echo htmlspecialchars($t['subject_name']); ?> (<?php echo htmlspecialchars($t['subject_code']); ?>)</div>
                                <div class="text-muted small"><?php echo htmlspecialchars($t['class_name']); ?></div>
                            </td>
                            <td>
                                <?php if($t['scheduled_date']): ?>
                                    <div class="small fw-semibold"><i class="bi bi-calendar-event me-1"></i><?php echo date('M d, Y', strtotime($t['scheduled_date'])); ?></div>
                                    <div class="text-muted small"><i class="bi bi-clock me-1"></i><?php echo date('h:i A', strtotime($t['start_time'])); ?> - <?php echo date('h:i A', strtotime($t['end_time'])); ?></div>
                                <?php else: ?>
                                    <span class="text-muted small">Not set</span>
                                <?php endif; ?>
                            </td>
                            <td><i class="bi bi-stopwatch me-1"></i><?php echo $t['duration_minutes']; ?> mins</td>
                            <td>
                                <span class="badge bg-light text-dark border rounded-pill px-3">
                                    <i class="bi bi-patch-question me-1 text-primary"></i><?php echo $t['question_count']; ?> Qs
                                </span>
                            </td>
                            <td>
                                <span class="badge <?php echo $status_badge; ?> rounded-pill px-3 py-2">
                                    <?php echo htmlspecialchars($t['status']); ?>
                                </span>
                            </td>
                            <td>
                                <div class="d-flex gap-2 flex-wrap">
                                    <a href="manage_tests.php?action=edit&id=<?php echo $t['id']; ?>" class="btn btn-sm btn-outline-primary rounded-pill">
                                        <i class="bi bi-list-task me-1"></i> Questions
                                    </a>
                                    <a href="manage_tests.php?action=view_paper&id=<?php echo $t['id']; ?>" class="btn btn-sm btn-outline-info rounded-pill">
                                        <i class="bi bi-eye me-1"></i> Question Paper
                                    </a>
                                    <a href="manage_tests.php?delete_test=<?php echo $t['id']; ?>" class="btn btn-sm btn-outline-danger rounded-pill" onclick="return confirm('Are you sure you want to delete this test?');">
                                        <i class="bi bi-trash"></i>
                                    </a>
                                </div>
                            </td>
                        </tr>
                    <?php 
                        endforeach; 
                    else: 
                    ?>
                        <tr>
                            <td colspan="8" class="text-center py-5 text-muted">
                                <i class="bi bi-journal-x display-4 d-block mb-2 text-secondary"></i>
                                No weekly test periods created yet for this filter.
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Schedule Weekly Test Modal -->
    <div class="modal fade" id="createTestModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content glass-panel">
                <div class="modal-header border-bottom-0">
                    <div>
                        <h5 class="modal-title fw-bold">Schedule Weekly Test Period</h5>
                        <p class="text-muted small mb-0">Set up test window & parameters beforehand for students</p>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form action="manage_tests.php" method="post">
                    <div class="modal-body">
                        <div class="row g-3">
                            <div class="col-md-8">
                                <label class="form-label text-muted fw-medium">Test Title</label>
                                <input type="text" class="form-control" name="title" placeholder="e.g. Weekly Test 1 - Chapter 3" required>
                            </div>
                            
                            <div class="col-md-4">
                                <label class="form-label text-muted fw-medium">Weekly Test Period</label>
                                <select class="form-select" name="test_period" required>
                                    <option value="Period 1">Period 1 (Weekly Test 1)</option>
                                    <option value="Period 2">Period 2 (Weekly Test 2)</option>
                                    <option value="Period 3">Period 3 (Weekly Test 3)</option>
                                </select>
                            </div>
                            
                            <?php
                            // Fetch subjects/classes taught by this teacher
                            $stmt = $pdo->prepare("
                                SELECT ts.*, s.name as subject_name, c.name as class_name
                                FROM teacher_subjects ts
                                JOIN subjects s ON ts.subject_id = s.id
                                JOIN classes c ON ts.class_id = c.id
                                WHERE ts.teacher_id = ?
                            ");
                            $stmt->execute([$teacher_id]);
                            $assignments = $stmt->fetchAll();
                            ?>
                            
                            <div class="col-md-6">
                                <label class="form-label text-muted fw-medium">Subject & Class</label>
                                <select class="form-select" name="subject_id" id="subject_select" required onchange="document.getElementById('class_input').value = this.options[this.selectedIndex].getAttribute('data-class');">
                                    <option value="">Select Subject & Class...</option>
                                    <?php foreach($assignments as $a): ?>
                                        <option value="<?php echo $a['subject_id']; ?>" data-class="<?php echo $a['class_id']; ?>">
                                            <?php echo htmlspecialchars($a['subject_name'] . ' - ' . $a['class_name']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                                <input type="hidden" name="class_id" id="class_input" value="">
                            </div>

                            <div class="col-md-6">
                                <label class="form-label text-muted fw-medium">Scheduled Test Date</label>
                                <input type="date" class="form-control" name="scheduled_date" value="<?php echo date('Y-m-d'); ?>" required>
                            </div>

                            <div class="col-md-4">
                                <label class="form-label text-muted fw-medium">Start Time</label>
                                <input type="time" class="form-control" name="start_time" value="09:00" required>
                            </div>

                            <div class="col-md-4">
                                <label class="form-label text-muted fw-medium">End Time</label>
                                <input type="time" class="form-control" name="end_time" value="10:00" required>
                            </div>
                            
                            <div class="col-md-4">
                                <label class="form-label text-muted fw-medium">Duration (minutes)</label>
                                <input type="number" class="form-control" name="duration" value="30" min="5" required>
                            </div>

                            <div class="col-md-12">
                                <label class="form-label text-muted fw-medium">Initial Status</label>
                                <select class="form-select" name="status">
                                    <option value="Scheduled">Scheduled (Question prepared beforehand)</option>
                                    <option value="Active">Active (Immediately open for students)</option>
                                </select>
                            </div>
                            
                            <div class="col-md-12">
                                <label class="form-label text-muted fw-medium">Instructions / Description (Optional)</label>
                                <textarea class="form-control" name="description" rows="2" placeholder="Enter instructions for students..."></textarea>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer border-top-0 pt-0 mt-3">
                        <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" name="create_test" class="btn btn-gradient px-4">Create & Schedule Test</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

<?php elseif ($action == 'edit' && $test_id): ?>

    <?php
    // Fetch test details
    $stmt = $pdo->prepare("
        SELECT t.*, s.name as subject_name, c.name as class_name 
        FROM tests t
        JOIN subjects s ON t.subject_id = s.id
        JOIN classes c ON t.class_id = c.id
        WHERE t.id = ? AND t.teacher_id = ?
    ");
    $stmt->execute([$test_id, $teacher_id]);
    $test = $stmt->fetch();
    if (!$test) {
        echo "<div class='alert alert-danger'>Invalid test specified.</div>";
        require_once 'includes/footer.php';
        exit;
    }
    
    // Fetch questions
    $stmt = $pdo->prepare("SELECT * FROM test_questions WHERE test_id = ? ORDER BY id ASC");
    $stmt->execute([$test_id]);
    $questions = $stmt->fetchAll();
    ?>
    
    <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
        <div>
            <a href="manage_tests.php" class="text-muted text-decoration-none small"><i class="bi bi-arrow-left me-1"></i>Back to Weekly Tests</a>
            <h2 class="fw-bold mb-0 mt-1"><?php echo htmlspecialchars($test['title']); ?></h2>
            <div class="text-muted small mt-1">
                <span class="badge bg-primary rounded-pill me-2"><?php echo htmlspecialchars($test['test_period']); ?></span>
                <span><?php echo htmlspecialchars($test['subject_name']); ?> &bull; <?php echo htmlspecialchars($test['class_name']); ?></span>
                &bull; <i class="bi bi-calendar-event me-1"></i><?php echo date('M d, Y', strtotime($test['scheduled_date'])); ?>
                (<?php echo date('h:i A', strtotime($test['start_time'])); ?> - <?php echo date('h:i A', strtotime($test['end_time'])); ?>)
            </div>
        </div>
        <button class="btn btn-gradient" data-bs-toggle="modal" data-bs-target="#addQuestionModal">
            <i class="bi bi-plus-lg me-2"></i> Add Question
        </button>
    </div>
    
    <?php if(count($questions) > 0): ?>
        <?php foreach($questions as $index => $q): ?>
            <div class="card-modern p-4 mb-3 position-relative">
                <div class="d-flex justify-content-between align-items-start mb-2">
                    <h6 class="fw-bold mb-0">Q<?php echo $index + 1; ?>. <?php echo htmlspecialchars($q['question_text']); ?></h6>
                    <div class="d-flex align-items-center gap-2">
                        <span class="badge bg-secondary rounded-pill"><?php echo $q['marks']; ?> Mark(s)</span>
                        <a href="manage_tests.php?action=edit&id=<?php echo $test_id; ?>&delete_q=<?php echo $q['id']; ?>" class="btn btn-sm btn-outline-danger border-0 rounded-circle" title="Delete Question" onclick="return confirm('Delete this question?');">
                            <i class="bi bi-trash"></i>
                        </a>
                    </div>
                </div>
                
                <div class="row g-2 mt-2">
                    <div class="col-md-6">
                        <div class="p-2 border rounded <?php echo $q['correct_option'] == 'A' ? 'bg-success bg-opacity-10 border-success text-success fw-bold' : ''; ?>">
                            <strong>A.</strong> <?php echo htmlspecialchars($q['option_a']); ?>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="p-2 border rounded <?php echo $q['correct_option'] == 'B' ? 'bg-success bg-opacity-10 border-success text-success fw-bold' : ''; ?>">
                            <strong>B.</strong> <?php echo htmlspecialchars($q['option_b']); ?>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="p-2 border rounded <?php echo $q['correct_option'] == 'C' ? 'bg-success bg-opacity-10 border-success text-success fw-bold' : ''; ?>">
                            <strong>C.</strong> <?php echo htmlspecialchars($q['option_c']); ?>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="p-2 border rounded <?php echo $q['correct_option'] == 'D' ? 'bg-success bg-opacity-10 border-success text-success fw-bold' : ''; ?>">
                            <strong>D.</strong> <?php echo htmlspecialchars($q['option_d']); ?>
                        </div>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    <?php else: ?>
        <div class="card-modern p-5 text-center">
            <i class="bi bi-question-circle display-4 text-muted mb-3 d-block"></i>
            <h5 class="fw-bold text-muted">No Questions Added Yet</h5>
            <p class="text-secondary mb-3">Prepare questions beforehand for this weekly test period.</p>
            <button class="btn btn-gradient rounded-pill px-4" data-bs-toggle="modal" data-bs-target="#addQuestionModal">
                <i class="bi bi-plus-lg me-2"></i> Add First Question
            </button>
        </div>
    <?php endif; ?>
    
    <!-- Add Question Modal -->
    <div class="modal fade" id="addQuestionModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content glass-panel">
                <div class="modal-header border-bottom-0">
                    <h5 class="modal-title fw-bold">Add Question to <?php echo htmlspecialchars($test['title']); ?></h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form action="manage_tests.php?action=edit&id=<?php echo $test_id; ?>" method="post">
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label text-muted fw-medium">Question Text</label>
                            <textarea class="form-control" name="question_text" rows="3" placeholder="Enter the question..." required></textarea>
                        </div>
                        
                        <div class="row g-3 mb-3">
                            <div class="col-md-6">
                                <label class="form-label text-muted fw-medium">Option A</label>
                                <input type="text" class="form-control" name="option_a" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label text-muted fw-medium">Option B</label>
                                <input type="text" class="form-control" name="option_b" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label text-muted fw-medium">Option C</label>
                                <input type="text" class="form-control" name="option_c" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label text-muted fw-medium">Option D</label>
                                <input type="text" class="form-control" name="option_d" required>
                            </div>
                        </div>
                        
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label text-muted fw-medium">Correct Option</label>
                                <select class="form-select" name="correct_option" required>
                                    <option value="A">Option A</option>
                                    <option value="B">Option B</option>
                                    <option value="C">Option C</option>
                                    <option value="D">Option D</option>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label text-muted fw-medium">Marks</label>
                                <input type="number" class="form-control" name="marks" value="1" min="1" required>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer border-top-0 pt-0 mt-3">
                        <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" name="add_question" class="btn btn-gradient px-4">Save Question</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

<?php elseif ($action == 'view_paper' && $test_id): ?>
    <?php
    $stmt = $pdo->prepare("
        SELECT t.*, s.name as subject_name, s.code as subject_code, c.name as class_name, te.first_name as teacher_first, te.last_name as teacher_last
        FROM tests t
        JOIN subjects s ON t.subject_id = s.id
        JOIN classes c ON t.class_id = c.id
        LEFT JOIN teachers te ON t.teacher_id = te.id
        WHERE t.id = ? AND t.teacher_id = ?
    ");
    $stmt->execute([$test_id, $teacher_id]);
    $test = $stmt->fetch();
    
    if (!$test) {
        echo "<div class='alert alert-danger'>Invalid test question paper.</div>";
        require_once 'includes/footer.php';
        exit;
    }
    
    $stmt = $pdo->prepare("SELECT * FROM test_questions WHERE test_id = ? ORDER BY id ASC");
    $stmt->execute([$test_id]);
    $questions = $stmt->fetchAll();

    $total_marks = 0;
    foreach ($questions as $q) {
        $total_marks += $q['marks'];
    }
    ?>
    
    <div class="d-flex justify-content-between align-items-center mb-4 print-hide flex-wrap gap-2">
        <a href="manage_tests.php" class="btn btn-outline-secondary rounded-pill">
            <i class="bi bi-arrow-left me-1"></i> Back to Manage Tests
        </a>
        <button onclick="window.print();" class="btn btn-gradient rounded-pill px-4">
            <i class="bi bi-printer me-2"></i> Print Question Paper
        </button>
    </div>

    <!-- Formatted Question Paper Sheet -->
    <div class="card-modern p-5 bg-white border shadow-sm print-area">
        <div class="text-center border-bottom pb-4 mb-4">
            <div class="d-flex justify-content-center align-items-center gap-2 mb-2">
                <i class="bi bi-hexagon-fill text-primary fs-3"></i>
                <h3 class="fw-bold mb-0 text-uppercase tracking-wide">SMART EDUCATION PORTAL</h3>
            </div>
            <h5 class="fw-bold text-secondary mb-1">OFFLINE WEEKLY TEST QUESTION PAPER</h5>
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
                No questions added to this test period yet.
            </div>
        <?php endif; ?>

        <div class="text-center text-muted small mt-5 pt-3 border-top">
            *** End of Question Paper ***
        </div>
    </div>

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
