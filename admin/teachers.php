<?php
require_once 'includes/header.php';

// Fetch departments for form
$dept_stmt = $pdo->query("SELECT * FROM departments ORDER BY name ASC");
$departments = $dept_stmt->fetchAll();

// Handle Add Teacher
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['action']) && $_POST['action'] == 'add') {
    $first_name = trim($_POST['first_name']);
    $last_name = trim($_POST['last_name']);
    $email = trim($_POST['email']);
    $phone = trim($_POST['phone']);
    $department_id = $_POST['department_id'];
    // Default password 'password123'
    $password_hash = password_hash('password123', PASSWORD_DEFAULT);
    
    if (!empty($first_name) && !empty($last_name) && !empty($email) && !empty($department_id)) {
        try {
            $pdo->beginTransaction();
            // Insert into users table
            $stmt = $pdo->prepare("INSERT INTO users (email, password_hash, role) VALUES (?, ?, 'teacher')");
            $stmt->execute([$email, $password_hash]);
            $user_id = $pdo->lastInsertId();
            
            // Insert into teachers table
            $stmt = $pdo->prepare("INSERT INTO teachers (id, first_name, last_name, department_id, phone) VALUES (?, ?, ?, ?, ?)");
            $stmt->execute([$user_id, $first_name, $last_name, $department_id, $phone]);
            
            $pdo->commit();
            $success_msg = "Teacher added successfully. Default password is 'password123'.";
        } catch(PDOException $e) {
            $pdo->rollBack();
            $error_msg = "Error adding teacher. Email might already exist.";
        }
    } else {
        $error_msg = "All required fields must be filled.";
    }
}

// Handle Edit Teacher
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['action']) && $_POST['action'] == 'edit') {
    $id = $_POST['id'];
    $first_name = trim($_POST['first_name']);
    $last_name = trim($_POST['last_name']);
    $email = trim($_POST['email']);
    $phone = trim($_POST['phone']);
    $department_id = $_POST['department_id'];
    
    if (!empty($id) && !empty($first_name) && !empty($last_name) && !empty($email) && !empty($department_id)) {
        try {
            $pdo->beginTransaction();
            
            $stmt = $pdo->prepare("UPDATE users SET email = ? WHERE id = ?");
            $stmt->execute([$email, $id]);
            
            $stmt = $pdo->prepare("UPDATE teachers SET first_name = ?, last_name = ?, department_id = ?, phone = ? WHERE id = ?");
            $stmt->execute([$first_name, $last_name, $department_id, $phone, $id]);
            
            $pdo->commit();
            $success_msg = "Teacher updated successfully.";
        } catch(PDOException $e) {
            $pdo->rollBack();
            $error_msg = "Error updating teacher. Email might already exist.";
        }
    } else {
        $error_msg = "All required fields must be filled.";
    }
}

// Handle Delete Teacher
if (isset($_GET['delete'])) {
    $id = $_GET['delete'];
    try {
        $stmt = $pdo->prepare("DELETE FROM users WHERE id = ? AND role = 'teacher'");
        $stmt->execute([$id]);
        $success_msg = "Teacher deleted successfully.";
    } catch(PDOException $e) {
        $error_msg = "Cannot delete teacher because they are linked to existing records.";
    }
}

// Fetch all teachers
$stmt = $pdo->query("SELECT t.*, u.email, d.name as department_name 
                     FROM teachers t 
                     JOIN users u ON t.id = u.id 
                     LEFT JOIN departments d ON t.department_id = d.id
                     ORDER BY t.first_name ASC");
$teachers = $stmt->fetchAll();
?>

<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
    <h1 class="h2">Teachers</h1>
    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addTeacherModal">
        <i class="bi bi-plus-lg"></i> Add Teacher
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
                        <th>ID</th>
                        <th>Name</th>
                        <th>Email</th>
                        <th>Department</th>
                        <th>Phone</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach($teachers as $t): ?>
                    <tr>
                        <td><?php echo $t['id']; ?></td>
                        <td><?php echo htmlspecialchars($t['first_name'] . ' ' . $t['last_name']); ?></td>
                        <td><?php echo htmlspecialchars($t['email']); ?></td>
                        <td><?php echo htmlspecialchars($t['department_name'] ?? 'None'); ?></td>
                        <td><?php echo htmlspecialchars($t['phone'] ?? '-'); ?></td>
                        <td>
                            <button class="btn btn-sm btn-outline-primary me-1 edit-btn" data-id="<?php echo $t['id']; ?>" data-fname="<?php echo htmlspecialchars($t['first_name']); ?>" data-lname="<?php echo htmlspecialchars($t['last_name']); ?>" data-email="<?php echo htmlspecialchars($t['email']); ?>" data-phone="<?php echo htmlspecialchars($t['phone']); ?>" data-dept="<?php echo $t['department_id']; ?>">
                                <i class="bi bi-pencil"></i>
                            </button>
                            <a href="?delete=<?php echo $t['id']; ?>" class="btn btn-sm btn-outline-danger" onclick="return confirm('Are you sure you want to delete this teacher?');">
                                <i class="bi bi-trash"></i>
                            </a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                    <?php if(empty($teachers)): ?>
                    <tr><td colspan="5" class="text-center">No teachers found.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Add Teacher Modal -->
<div class="modal fade" id="addTeacherModal" tabindex="-1" aria-labelledby="addTeacherModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="addTeacherModalLabel">Add New Teacher</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form action="teachers.php" method="POST">
                <div class="modal-body">
                    <input type="hidden" name="action" value="add">
                    
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
                        <label for="department_id" class="form-label">Department</label>
                        <select class="form-select" id="department_id" name="department_id" required>
                            <option value="">Select Department...</option>
                            <?php foreach($departments as $dept): ?>
                                <option value="<?php echo $dept['id']; ?>"><?php echo htmlspecialchars($dept['name']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Save Teacher</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Edit Teacher Modal -->
<div class="modal fade" id="editTeacherModal" tabindex="-1" aria-labelledby="editTeacherModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="editTeacherModalLabel">Edit Teacher</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form action="teachers.php" method="POST">
                <div class="modal-body">
                    <input type="hidden" name="action" value="edit">
                    <input type="hidden" id="edit_id" name="id">
                    
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
                        <label for="edit_department_id" class="form-label">Department</label>
                        <select class="form-select" id="edit_department_id" name="department_id" required>
                            <option value="">Select Department...</option>
                            <?php foreach($departments as $dept): ?>
                                <option value="<?php echo $dept['id']; ?>"><?php echo htmlspecialchars($dept['name']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Update Teacher</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
$(document).ready(function() {
    $('.edit-btn').on('click', function() {
        var id = $(this).data('id');
        var fname = $(this).data('fname');
        var lname = $(this).data('lname');
        var email = $(this).data('email');
        var phone = $(this).data('phone');
        var dept = $(this).data('dept');
        
        $('#edit_id').val(id);
        $('#edit_first_name').val(fname);
        $('#edit_last_name').val(lname);
        $('#edit_email').val(email);
        $('#edit_phone').val(phone);
        $('#edit_department_id').val(dept);
        
        var editModal = new bootstrap.Modal(document.getElementById('editTeacherModal'));
        editModal.show();
    });
});
</script>

<?php require_once 'includes/footer.php'; ?>
