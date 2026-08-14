<?php
require_once 'includes/header.php';

// Fetch classes for form
$class_stmt = $pdo->query("SELECT c.*, d.name as dept_name FROM classes c JOIN departments d ON c.department_id = d.id ORDER BY d.name, c.name");
$classes = $class_stmt->fetchAll();

// Handle Add Student
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['action']) && $_POST['action'] == 'add') {
    $roll_number = trim($_POST['roll_number']);
    $first_name = trim($_POST['first_name']);
    $last_name = trim($_POST['last_name']);
    $email = trim($_POST['email']);
    $phone = trim($_POST['phone']);
    $class_id = $_POST['class_id'];
    // Default password 'password123'
    $password_hash = password_hash('password123', PASSWORD_DEFAULT);
    
    if (!empty($roll_number) && !empty($first_name) && !empty($last_name) && !empty($email) && !empty($class_id)) {
        try {
            $pdo->beginTransaction();
            // Insert into users table
            $stmt = $pdo->prepare("INSERT INTO users (email, password_hash, role) VALUES (?, ?, 'student')");
            $stmt->execute([$email, $password_hash]);
            $user_id = $pdo->lastInsertId();
            
            // Insert into students table
            $stmt = $pdo->prepare("INSERT INTO students (id, roll_number, first_name, last_name, phone) VALUES (?, ?, ?, ?, ?)");
            $stmt->execute([$user_id, $roll_number, $first_name, $last_name, $phone]);
            
            // Insert enrollment
            $stmt = $pdo->prepare("INSERT INTO enrollments (student_id, class_id) VALUES (?, ?)");
            $stmt->execute([$user_id, $class_id]);

            $pdo->commit();
            $success_msg = "Student added successfully. Default password is 'password123'.";
        } catch(PDOException $e) {
            $pdo->rollBack();
            $error_msg = "Error adding student. Email or Roll Number might already exist.";
        }
    } else {
        $error_msg = "All required fields must be filled.";
    }
}

// Fetch all students
$stmt = $pdo->query("SELECT s.*, u.email, c.name as class_name 
                     FROM students s 
                     JOIN users u ON s.id = u.id 
                     LEFT JOIN enrollments e ON s.id = e.student_id
                     LEFT JOIN classes c ON e.class_id = c.id
                     ORDER BY s.roll_number ASC");
$students = $stmt->fetchAll();
?>

<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
    <h1 class="h2">Students</h1>
    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addStudentModal">
        <i class="bi bi-plus-lg"></i> Add Student
    </button>
</div>

<?php if(isset($success_msg)): ?>
    <div class="alert alert-success alert-dismissible fade show" role="alert">
        <?php echo $success_msg; ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
<?php endif; ?>

<?php if(isset($error_msg)): ?>
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
        <?php echo $error_msg; ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
<?php endif; ?>

<div class="card shadow-sm">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead class="table-light">
                    <tr>
                        <th>Roll Number</th>
                        <th>Name</th>
                        <th>Email</th>
                        <th>Class</th>
                        <th>Phone</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach($students as $s): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($s['roll_number']); ?></td>
                        <td><?php echo htmlspecialchars($s['first_name'] . ' ' . $s['last_name']); ?></td>
                        <td><?php echo htmlspecialchars($s['email']); ?></td>
                        <td><?php echo htmlspecialchars($s['class_name'] ?? 'Not Enrolled'); ?></td>
                        <td><?php echo htmlspecialchars($s['phone'] ?? '-'); ?></td>
                    </tr>
                    <?php endforeach; ?>
                    <?php if(empty($students)): ?>
                    <tr><td colspan="5" class="text-center">No students found.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Add Student Modal -->
<div class="modal fade" id="addStudentModal" tabindex="-1" aria-labelledby="addStudentModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="addStudentModalLabel">Add New Student</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form action="students.php" method="POST">
                <div class="modal-body">
                    <input type="hidden" name="action" value="add">
                    
                    <div class="mb-3">
                        <label for="roll_number" class="form-label">Roll Number</label>
                        <input type="text" class="form-control" id="roll_number" name="roll_number" required>
                    </div>

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="first_name" class="form-label">First Name</label>
                            <input type="text" class="form-control" id="first_name" name="first_name" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="last_name" class="form-label">Last Name</label>
                            <input type="text" class="form-control" id="last_name" name="last_name" required>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label for="email" class="form-label">Email Address</label>
                        <input type="email" class="form-control" id="email" name="email" required>
                    </div>

                    <div class="mb-3">
                        <label for="phone" class="form-label">Phone Number</label>
                        <input type="text" class="form-control" id="phone" name="phone">
                    </div>

                    <div class="mb-3">
                        <label for="class_id" class="form-label">Class</label>
                        <select class="form-select" id="class_id" name="class_id" required>
                            <option value="">Select Class...</option>
                            <?php foreach($classes as $c): ?>
                                <option value="<?php echo $c['id']; ?>"><?php echo htmlspecialchars($c['dept_name'] . ' - ' . $c['name']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Save Student</button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>
