<?php
require_once 'includes/header.php';

$teacher_id = $_SESSION['user_id'];

// Fetch classes/subjects for this teacher for the form
$stmt = $pdo->prepare("
    SELECT ts.*, c.name as class_name, d.name as dept_name, s.name as subject_name, s.code as subject_code
    FROM teacher_subjects ts
    JOIN classes c ON ts.class_id = c.id
    JOIN departments d ON c.department_id = d.id
    JOIN subjects s ON ts.subject_id = s.id
    WHERE ts.teacher_id = ?
    ORDER BY d.name, c.name, s.name
");
$stmt->execute([$teacher_id]);
$subjects_options = $stmt->fetchAll();
// To prevent duplicate subject entries in the dropdown if teacher teaches same subject to multiple classes, we'll group them. 
// But study materials are usually per subject. Let's just group by subject for simplicity.
$unique_subjects = [];
foreach($subjects_options as $opt) {
    $unique_subjects[$opt['subject_id']] = $opt;
}

// Handle Upload Material
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['action']) && $_POST['action'] == 'upload') {
    $subject_id = $_POST['subject_id'];
    $title = trim($_POST['title']);
    $unit = trim($_POST['unit']);
    
    if ($subject_id && !empty($title)) {
        if (isset($_FILES['material_file']) && $_FILES['material_file']['error'] == 0) {
            $allowed = ['pdf', 'doc', 'docx', 'ppt', 'pptx', 'zip', 'mp4', 'txt'];
            $filename = $_FILES['material_file']['name'];
            $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
            
            if (in_array($ext, $allowed)) {
                if ($_FILES['material_file']['size'] <= 20000000) { // 20MB limit
                    $new_filename = uniqid('mat_') . '.' . $ext;
                    $upload_dir = '../assets/uploads/materials/';
                    if (!is_dir($upload_dir)) mkdir($upload_dir, 0777, true);
                    
                    if (move_uploaded_file($_FILES['material_file']['tmp_name'], $upload_dir . $new_filename)) {
                        try {
                            $stmt = $pdo->prepare("INSERT INTO study_materials (teacher_id, subject_id, title, unit, file_path, file_type) VALUES (?, ?, ?, ?, ?, ?)");
                            $stmt->execute([$teacher_id, $subject_id, $title, $unit, $new_filename, strtoupper($ext)]);
                            $success_msg = "Material uploaded successfully.";
                        } catch (PDOException $e) {
                            $error_msg = "Database error while saving material.";
                        }
                    } else {
                        $error_msg = "Failed to move uploaded file.";
                    }
                } else {
                    $error_msg = "File is too large. Limit is 20MB.";
                }
            } else {
                $error_msg = "Invalid file type.";
            }
        } else {
            $error_msg = "Please select a valid file.";
        }
    } else {
        $error_msg = "Subject and Title are required.";
    }
}

// Handle Delete Material
if (isset($_GET['delete'])) {
    $id = $_GET['delete'];
    try {
        // Fetch to get filename first
        $stmt = $pdo->prepare("SELECT file_path FROM study_materials WHERE id = ? AND teacher_id = ?");
        $stmt->execute([$id, $teacher_id]);
        $mat = $stmt->fetch();
        if ($mat) {
            @unlink('../assets/uploads/materials/' . $mat['file_path']);
            $stmt = $pdo->prepare("DELETE FROM study_materials WHERE id = ?");
            $stmt->execute([$id]);
            $success_msg = "Material deleted successfully.";
        }
    } catch(PDOException $e) {
        $error_msg = "Cannot delete material.";
    }
}

// Fetch materials
$stmt = $pdo->prepare("
    SELECT m.*, s.name as subject_name, s.code as subject_code
    FROM study_materials m
    JOIN subjects s ON m.subject_id = s.id
    WHERE m.teacher_id = ?
    ORDER BY s.name, m.unit, m.created_at DESC
");
$stmt->execute([$teacher_id]);
$materials = $stmt->fetchAll();
?>

<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
    <h1 class="h2">Study Materials</h1>
    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#uploadMaterialModal">
        <i class="bi bi-cloud-arrow-up"></i> Upload Material
    </button>
</div>

<?php if(isset($success_msg)): ?>
    <div class="alert alert-success alert-dismissible fade show shadow-sm" role="alert">
        <?php echo $success_msg; ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
<?php endif; ?>

<?php if(isset($error_msg)): ?>
    <div class="alert alert-danger alert-dismissible fade show shadow-sm" role="alert">
        <?php echo $error_msg; ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
<?php endif; ?>

<div class="card shadow-sm">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th>Subject</th>
                        <th>Unit</th>
                        <th>Title</th>
                        <th>Type</th>
                        <th>Uploaded On</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach($materials as $m): ?>
                    <tr>
                        <td>
                            <span class="fw-bold"><?php echo htmlspecialchars($m['subject_code']); ?></span><br>
                            <small class="text-muted"><?php echo htmlspecialchars($m['subject_name']); ?></small>
                        </td>
                        <td><?php echo htmlspecialchars($m['unit'] ?: '-'); ?></td>
                        <td><?php echo htmlspecialchars($m['title']); ?></td>
                        <td>
                            <?php 
                                $icon = 'file-earmark';
                                if($m['file_type'] == 'PDF') $icon = 'file-earmark-pdf text-danger';
                                elseif(in_array($m['file_type'], ['DOC','DOCX'])) $icon = 'file-earmark-word text-primary';
                                elseif(in_array($m['file_type'], ['PPT','PPTX'])) $icon = 'file-earmark-slides text-warning';
                                elseif($m['file_type'] == 'ZIP') $icon = 'file-earmark-zip text-secondary';
                            ?>
                            <i class="bi bi-<?php echo $icon; ?> fs-5"></i> <?php echo $m['file_type']; ?>
                        </td>
                        <td><?php echo date('M d, Y', strtotime($m['created_at'])); ?></td>
                        <td>
                            <a href="../assets/uploads/materials/<?php echo $m['file_path']; ?>" target="_blank" class="btn btn-sm btn-outline-primary" title="View/Download">
                                <i class="bi bi-eye"></i>
                            </a>
                            <a href="?delete=<?php echo $m['id']; ?>" class="btn btn-sm btn-outline-danger" onclick="return confirm('Delete this material?');" title="Delete">
                                <i class="bi bi-trash"></i>
                            </a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                    <?php if(empty($materials)): ?>
                    <tr><td colspan="6" class="text-center py-4">No materials uploaded yet.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Upload Material Modal -->
<div class="modal fade" id="uploadMaterialModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Upload Study Material</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form action="materials.php" method="POST" enctype="multipart/form-data">
                <div class="modal-body">
                    <input type="hidden" name="action" value="upload">
                    
                    <div class="mb-3">
                        <label for="subject_id" class="form-label">Subject</label>
                        <select class="form-select" id="subject_id" name="subject_id" required>
                            <option value="">-- Choose Subject --</option>
                            <?php foreach ($unique_subjects as $id => $opt): ?>
                                <option value="<?php echo $id; ?>">
                                    <?php echo htmlspecialchars($opt['subject_code'] . ' - ' . $opt['subject_name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label for="unit" class="form-label">Unit / Chapter (Optional)</label>
                        <input type="text" class="form-control" id="unit" name="unit" placeholder="e.g., Unit 1, Chapter 3">
                    </div>

                    <div class="mb-3">
                        <label for="title" class="form-label">Material Title</label>
                        <input type="text" class="form-control" id="title" name="title" required placeholder="e.g., Lecture Slides: Introduction">
                    </div>

                    <div class="mb-3">
                        <label for="material_file" class="form-label">File</label>
                        <input class="form-control" type="file" id="material_file" name="material_file" accept=".pdf,.doc,.docx,.ppt,.pptx,.txt,.zip,.mp4" required>
                        <div class="form-text">Max size: 20MB. Allowed: PDF, DOC, PPT, ZIP, TXT, MP4.</div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Upload Material</button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>
