<?php
require_once 'includes/header.php';

// Handle Add Department
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['action']) && $_POST['action'] == 'add') {
    $name = trim($_POST['name']);
    $code = trim($_POST['code']);
    
    if (!empty($name) && !empty($code)) {
        try {
            $stmt = $pdo->prepare("INSERT INTO departments (name, code) VALUES (?, ?)");
            $stmt->execute([$name, $code]);
            $success_msg = "Department added successfully.";
        } catch(PDOException $e) {
            $error_msg = "Error adding department. Code or Name might already exist.";
        }
    } else {
        $error_msg = "All fields are required.";
    }
}

// Handle Delete Department
if (isset($_GET['delete'])) {
    $id = $_GET['delete'];
    try {
        $stmt = $pdo->prepare("DELETE FROM departments WHERE id = ?");
        $stmt->execute([$id]);
        $success_msg = "Department deleted successfully.";
    } catch(PDOException $e) {
        $error_msg = "Cannot delete department because it is linked to existing records.";
    }
}

// Fetch all departments
$stmt = $pdo->query("SELECT * FROM departments ORDER BY name ASC");
$departments = $stmt->fetchAll();
?>

<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
    <h1 class="h2">Departments</h1>
    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addDepartmentModal">
        <i class="bi bi-plus-lg"></i> Add Department
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
                        <th>Code</th>
                        <th>Name</th>
                        <th>Created At</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach($departments as $dept): ?>
                    <tr>
                        <td><?php echo $dept['id']; ?></td>
                        <td><?php echo htmlspecialchars($dept['code']); ?></td>
                        <td><?php echo htmlspecialchars($dept['name']); ?></td>
                        <td><?php echo date('M d, Y', strtotime($dept['created_at'])); ?></td>
                        <td>
                            <a href="?delete=<?php echo $dept['id']; ?>" class="btn btn-sm btn-outline-danger" onclick="return confirm('Are you sure you want to delete this department?');">
                                <i class="bi bi-trash"></i>
                            </a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                    <?php if(empty($departments)): ?>
                    <tr><td colspan="5" class="text-center">No departments found.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Add Department Modal -->
<div class="modal fade" id="addDepartmentModal" tabindex="-1" aria-labelledby="addDepartmentModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="addDepartmentModalLabel">Add New Department</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form action="departments.php" method="POST">
                <div class="modal-body">
                    <input type="hidden" name="action" value="add">
                    <div class="mb-3">
                        <label for="code" class="form-label">Department Code</label>
                        <input type="text" class="form-control" id="code" name="code" required placeholder="e.g., MCA">
                    </div>
                    <div class="mb-3">
                        <label for="name" class="form-label">Department Name</label>
                        <input type="text" class="form-control" id="name" name="name" required placeholder="e.g., Computer Applications">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Save Department</button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>
