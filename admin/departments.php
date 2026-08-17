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

// Handle Edit Department
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['action']) && $_POST['action'] == 'edit') {
    $id = $_POST['id'];
    $name = trim($_POST['name']);
    $code = trim($_POST['code']);
    
    if (!empty($id) && !empty($name) && !empty($code)) {
        try {
            $stmt = $pdo->prepare("UPDATE departments SET name = ?, code = ? WHERE id = ?");
            $stmt->execute([$name, $code, $id]);
            $success_msg = "Department updated successfully.";
        } catch(PDOException $e) {
            $error_msg = "Error updating department. Code or Name might already exist.";
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

<div class="row g-4">
    <?php foreach($departments as $dept): ?>
    <div class="col-xl-3 col-lg-4 col-md-6 col-sm-12 animate-on-scroll fade-in-up">
        <div class="card premium-glass-card tilt-card h-100 shadow-sm border-0 position-relative overflow-hidden" style="border-radius: 20px;">
            <div class="glare-effect"></div>
            <div class="card-body p-4 text-center d-flex flex-column">
                <div class="hover-bounce-icon mb-3">
                    <div class="d-inline-flex align-items-center justify-content-center rounded-circle shadow-sm" style="width: 80px; height: 80px; background: linear-gradient(135deg, rgba(14, 165, 233, 0.1), rgba(139, 92, 246, 0.1)); border: 2px solid rgba(14, 165, 233, 0.3);">
                        <i class="bi bi-building" style="font-size: 2.5rem; color: #0ea5e9; filter: drop-shadow(0 0 8px rgba(14, 165, 233, 0.4));"></i>
                    </div>
                </div>
                
                <h2 class="fw-bold premium-text mb-1 text-truncate" style="letter-spacing: 1px; font-size: 1.8rem;"><?php echo htmlspecialchars($dept['code']); ?></h2>
                <h6 class="text-secondary fw-semibold mb-3 px-2 text-wrap" style="min-height: 2.5rem;"><?php echo htmlspecialchars($dept['name']); ?></h6>
                
                <div class="small text-muted mb-4 mt-auto">
                    <i class="bi bi-clock-history me-1"></i> Since <?php echo date('M Y', strtotime($dept['created_at'])); ?>
                </div>
                
                <div class="d-flex justify-content-center gap-2" style="position: relative; z-index: 5;">
                    <button class="btn btn-outline-primary rounded-pill flex-grow-1 edit-btn fw-bold shadow-sm" data-id="<?php echo $dept['id']; ?>" data-code="<?php echo htmlspecialchars($dept['code']); ?>" data-name="<?php echo htmlspecialchars($dept['name']); ?>">
                        <i class="bi bi-pencil-square me-1"></i> Edit
                    </button>
                    <a href="?delete=<?php echo $dept['id']; ?>" class="btn btn-outline-danger rounded-circle shadow-sm d-flex align-items-center justify-content-center" style="width: 38px; height: 38px;" onclick="return confirm('Are you sure you want to delete this department?');">
                        <i class="bi bi-trash3"></i>
                    </a>
                </div>
            </div>
        </div>
    </div>
    <?php endforeach; ?>
    
    <?php if(empty($departments)): ?>
    <div class="col-12 fade-in-up">
        <div class="alert premium-glass-card shadow-sm text-center py-5 border-0">
            <i class="bi bi-inbox fs-1 text-muted mb-3 d-block"></i>
            <h4 class="text-secondary fw-bold">No Departments Found</h4>
            <p class="text-muted mb-0">Click the "Add Department" button to create one.</p>
        </div>
    </div>
    <?php endif; ?>
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

<!-- Edit Department Modal -->
<div class="modal fade" id="editDepartmentModal" tabindex="-1" aria-labelledby="editDepartmentModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="editDepartmentModalLabel">Edit Department</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form action="departments.php" method="POST">
                <div class="modal-body">
                    <input type="hidden" name="action" value="edit">
                    <input type="hidden" id="edit_id" name="id">
                    <div class="mb-3">
                        <label for="edit_code" class="form-label">Department Code</label>
                        <input type="text" class="form-control" id="edit_code" name="code" required>
                    </div>
                    <div class="mb-3">
                        <label for="edit_name" class="form-label">Department Name</label>
                        <input type="text" class="form-control" id="edit_name" name="name" required>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Update Department</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
$(document).ready(function() {
    $('.edit-btn').on('click', function() {
        var id = $(this).data('id');
        var code = $(this).data('code');
        var name = $(this).data('name');
        
        $('#edit_id').val(id);
        $('#edit_code').val(code);
        $('#edit_name').val(name);
        
        var editModal = new bootstrap.Modal(document.getElementById('editDepartmentModal'));
        editModal.show();
    });
});
</script>

<?php require_once 'includes/footer.php'; ?>
