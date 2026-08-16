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

// Handle Edit Student
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['action']) && $_POST['action'] == 'edit') {
    $id = $_POST['id'];
    $roll_number = trim($_POST['roll_number']);
    $first_name = trim($_POST['first_name']);
    $last_name = trim($_POST['last_name']);
    $email = trim($_POST['email']);
    $phone = trim($_POST['phone']);
    $class_id = $_POST['class_id'];
    
    if (!empty($id) && !empty($roll_number) && !empty($first_name) && !empty($last_name) && !empty($email) && !empty($class_id)) {
        try {
            $pdo->beginTransaction();
            
            $stmt = $pdo->prepare("UPDATE users SET email = ? WHERE id = ?");
            $stmt->execute([$email, $id]);
            
            $stmt = $pdo->prepare("UPDATE students SET roll_number = ?, first_name = ?, last_name = ?, phone = ? WHERE id = ?");
            $stmt->execute([$roll_number, $first_name, $last_name, $phone, $id]);
            
            $stmt = $pdo->prepare("UPDATE enrollments SET class_id = ? WHERE student_id = ?");
            $stmt->execute([$class_id, $id]);
            
            $pdo->commit();
            $success_msg = "Student updated successfully.";
        } catch(PDOException $e) {
            $pdo->rollBack();
            $error_msg = "Error updating student. Email or Roll Number might already exist.";
        }
    } else {
        $error_msg = "All required fields must be filled.";
    }
}

// Handle Delete Student
if (isset($_GET['delete'])) {
    $id = $_GET['delete'];
    try {
        $stmt = $pdo->prepare("DELETE FROM users WHERE id = ? AND role = 'student'");
        $stmt->execute([$id]);
        $success_msg = "Student deleted successfully.";
    } catch(PDOException $e) {
        $error_msg = "Cannot delete student because they are linked to existing records.";
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
                        <th>Actions</th>
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
                        <td>
                            <button class="btn btn-sm btn-outline-primary me-1 edit-btn" data-id="<?php echo $s['id']; ?>" data-roll="<?php echo htmlspecialchars($s['roll_number']); ?>" data-fname="<?php echo htmlspecialchars($s['first_name']); ?>" data-lname="<?php echo htmlspecialchars($s['last_name']); ?>" data-email="<?php echo htmlspecialchars($s['email']); ?>" data-phone="<?php echo htmlspecialchars($s['phone']); ?>" data-class="<?php echo $s['class_id'] ?? ''; ?>">
                                <i class="bi bi-pencil"></i>
                            </button>
                            <a href="?delete=<?php echo $s['id']; ?>" class="btn btn-sm btn-outline-danger" onclick="return confirm('Are you sure you want to delete this student?');">
                                <i class="bi bi-trash"></i>
                            </a>
                        </td>
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

<!-- Edit Student Modal -->
<div class="modal fade" id="editStudentModal" tabindex="-1" aria-labelledby="editStudentModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="editStudentModalLabel">Edit Student</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form action="students.php" method="POST">
                <div class="modal-body">
                    <input type="hidden" name="action" value="edit">
                    <input type="hidden" id="edit_id" name="id">
                    
                    <div class="mb-3">
                        <label for="edit_roll_number" class="form-label">Roll Number</label>
                        <input type="text" class="form-control" id="edit_roll_number" name="roll_number" required>
                    </div>

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="edit_first_name" class="form-label">First Name</label>
                            <input type="text" class="form-control" id="edit_first_name" name="first_name" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="edit_last_name" class="form-label">Last Name</label>
                            <input type="text" class="form-control" id="edit_last_name" name="last_name" required>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label for="edit_email" class="form-label">Email Address</label>
                        <input type="email" class="form-control" id="edit_email" name="email" required>
                    </div>

                    <div class="mb-3">
                        <label for="edit_phone" class="form-label">Phone Number</label>
                        <input type="text" class="form-control" id="edit_phone" name="phone">
                    </div>

                    <div class="mb-3">
                        <label for="edit_class_id" class="form-label">Class</label>
                        <select class="form-select" id="edit_class_id" name="class_id" required>
                            <option value="">Select Class...</option>
                            <?php foreach($classes as $c): ?>
                                <option value="<?php echo $c['id']; ?>"><?php echo htmlspecialchars($c['dept_name'] . ' - ' . $c['name']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Update Student</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
$(document).ready(function() {
    $('.edit-btn').on('click', function() {
        var id = $(this).data('id');
        var roll = $(this).data('roll');
        var fname = $(this).data('fname');
        var lname = $(this).data('lname');
        var email = $(this).data('email');
        var phone = $(this).data('phone');
        var cls = $(this).data('class');
        
        $('#edit_id').val(id);
        $('#edit_roll_number').val(roll);
        $('#edit_first_name').val(fname);
        $('#edit_last_name').val(lname);
        $('#edit_email').val(email);
        $('#edit_phone').val(phone);
        $('#edit_class_id').val(cls);
        
        var editModal = new bootstrap.Modal(document.getElementById('editStudentModal'));
        editModal.show();
    });
});
</script>

<?php require_once 'includes/footer.php'; ?>
