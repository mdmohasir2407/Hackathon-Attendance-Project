<?php
require_once 'includes/header.php';

// Handle Add Class
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['action']) && $_POST['action'] == 'add') {
    $department_id = $_POST['department_id'];
    $semester_id = $_POST['semester_id'];
    $name = trim($_POST['name']);
    
    if (!empty($department_id) && !empty($semester_id) && !empty($name)) {
        try {
            $stmt = $pdo->prepare("INSERT INTO classes (department_id, semester_id, name) VALUES (?, ?, ?)");
            $stmt->execute([$department_id, $semester_id, $name]);
            $success_msg = "Class added successfully.";
        } catch(PDOException $e) {
            $error_msg = "Error adding class.";
        }
    } else {
        $error_msg = "All fields are required.";
    }
}

// Handle Delete Class
if (isset($_GET['delete'])) {
    $id = $_GET['delete'];
    try {
        $stmt = $pdo->prepare("DELETE FROM classes WHERE id = ?");
        $stmt->execute([$id]);
        $success_msg = "Class deleted successfully.";
    } catch(PDOException $e) {
        $error_msg = "Cannot delete class because it is linked to existing records.";
    }
}

// Fetch all classes
$stmt = $pdo->query("SELECT c.*, d.name as department_name, s.name as semester_name, ay.name as academic_year 
                     FROM classes c 
                     JOIN departments d ON c.department_id = d.id 
                     JOIN semesters s ON c.semester_id = s.id
                     JOIN academic_years ay ON s.academic_year_id = ay.id
                     ORDER BY d.name ASC, s.name ASC, c.name ASC");
$classes = $stmt->fetchAll();

// Fetch departments for form
$dept_stmt = $pdo->query("SELECT * FROM departments ORDER BY name ASC");
$departments = $dept_stmt->fetchAll();

// Fetch semesters for form
$sem_stmt = $pdo->query("SELECT s.*, ay.name as ay_name FROM semesters s JOIN academic_years ay ON s.academic_year_id = ay.id ORDER BY ay.start_date DESC, s.name ASC");
$semesters = $sem_stmt->fetchAll();
?>

<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
    <h1 class="h2">Classes</h1>
    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addClassModal">
        <i class="bi bi-plus-lg"></i> Add Class
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
                        <th>Class Name</th>
                        <th>Department</th>
                        <th>Semester (Academic Year)</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach($classes as $c): ?>
                    <tr>
                        <td><?php echo $c['id']; ?></td>
                        <td><?php echo htmlspecialchars($c['name']); ?></td>
                        <td><?php echo htmlspecialchars($c['department_name']); ?></td>
                        <td><?php echo htmlspecialchars($c['semester_name']) . ' (' . htmlspecialchars($c['academic_year']) . ')'; ?></td>
                        <td>
                            <a href="?delete=<?php echo $c['id']; ?>" class="btn btn-sm btn-outline-danger" onclick="return confirm('Are you sure you want to delete this class?');">
                                <i class="bi bi-trash"></i>
                            </a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                    <?php if(empty($classes)): ?>
                    <tr><td colspan="5" class="text-center">No classes found.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Add Class Modal -->
<div class="modal fade" id="addClassModal" tabindex="-1" aria-labelledby="addClassModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="addClassModalLabel">Add New Class</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form action="classes.php" method="POST">
                <div class="modal-body">
                    <input type="hidden" name="action" value="add">
                    
                    <div class="mb-3">
                        <label for="department_id" class="form-label">Department</label>
                        <select class="form-select" id="department_id" name="department_id" required>
                            <option value="">Select Department...</option>
                            <?php foreach($departments as $dept): ?>
                                <option value="<?php echo $dept['id']; ?>"><?php echo htmlspecialchars($dept['name'] . ' (' . $dept['code'] . ')'); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label for="semester_id" class="form-label">Semester</label>
                        <select class="form-select" id="semester_id" name="semester_id" required>
                            <option value="">Select Semester...</option>
                            <?php foreach($semesters as $sem): ?>
                                <option value="<?php echo $sem['id']; ?>"><?php echo htmlspecialchars($sem['name'] . ' - ' . $sem['ay_name']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label for="name" class="form-label">Class Name</label>
                        <input type="text" class="form-control" id="name" name="name" required placeholder="e.g., MCA-A, BTech CS Sec A">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Save Class</button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>
