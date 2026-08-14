<?php
require_once 'includes/header.php';

$teacher_id = $_SESSION['user_id'];
$filter_class_id = $_GET['class_id'] ?? '';

// Fetch classes taught by this teacher for the filter dropdown
$stmt = $pdo->prepare("
    SELECT DISTINCT c.id, c.name, d.name as dept_name 
    FROM teacher_subjects ts
    JOIN classes c ON ts.class_id = c.id
    JOIN departments d ON c.department_id = d.id
    WHERE ts.teacher_id = ?
    ORDER BY d.name, c.name
");
$stmt->execute([$teacher_id]);
$my_classes = $stmt->fetchAll();

// Fetch students
$query = "
    SELECT DISTINCT s.*, u.email, c.name as class_name, d.name as dept_name
    FROM students s
    JOIN users u ON s.id = u.id
    JOIN enrollments e ON s.id = e.student_id
    JOIN classes c ON e.class_id = c.id
    JOIN departments d ON c.department_id = d.id
    JOIN teacher_subjects ts ON c.id = ts.class_id
    WHERE ts.teacher_id = ?
";
$params = [$teacher_id];

if (!empty($filter_class_id)) {
    $query .= " AND c.id = ?";
    $params[] = $filter_class_id;
}

$query .= " ORDER BY c.name, s.roll_number";
$stmt = $pdo->prepare($query);
$stmt->execute($params);
$students = $stmt->fetchAll();
?>

<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
    <h1 class="h2">Student List</h1>
</div>

<div class="card mb-4 shadow-sm">
    <div class="card-body">
        <form method="GET" action="students.php" class="row g-3 align-items-center">
            <div class="col-auto">
                <label for="class_id" class="col-form-label"><i class="bi bi-filter"></i> Filter by Class:</label>
            </div>
            <div class="col-auto">
                <select name="class_id" id="class_id" class="form-select" onchange="this.form.submit()">
                    <option value="">All My Classes</option>
                    <?php foreach($my_classes as $c): ?>
                        <option value="<?php echo $c['id']; ?>" <?php echo ($filter_class_id == $c['id']) ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($c['dept_name'] . ' - ' . $c['name']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
        </form>
    </div>
</div>

<div class="card shadow-sm">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead class="table-light">
                    <tr>
                        <th>Roll Number</th>
                        <th>Name</th>
                        <th>Class</th>
                        <th>Email</th>
                        <th>Phone</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach($students as $s): ?>
                    <tr>
                        <td><strong><?php echo htmlspecialchars($s['roll_number']); ?></strong></td>
                        <td>
                            <?php echo htmlspecialchars($s['first_name'] . ' ' . $s['last_name']); ?>
                        </td>
                        <td>
                            <span class="badge bg-secondary"><?php echo htmlspecialchars($s['dept_name'] . ' - ' . $s['class_name']); ?></span>
                        </td>
                        <td><a href="mailto:<?php echo htmlspecialchars($s['email']); ?>"><?php echo htmlspecialchars($s['email']); ?></a></td>
                        <td><?php echo htmlspecialchars($s['phone'] ?? '-'); ?></td>
                        <td>
                            <a href="feedback.php?student_id=<?php echo $s['id']; ?>" class="btn btn-sm btn-outline-warning" title="Send Feedback">
                                <i class="bi bi-chat-dots"></i> Feedback
                            </a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                    <?php if(empty($students)): ?>
                    <tr><td colspan="6" class="text-center py-4">No students found in your assigned classes.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>
