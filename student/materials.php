<?php
require_once 'includes/header.php';

$student_id = $_SESSION['user_id'];

// Get student's class
$stmt = $pdo->prepare("SELECT class_id FROM enrollments WHERE student_id = ?");
$stmt->execute([$student_id]);
$enrollment = $stmt->fetch();
$class_id = $enrollment ? $enrollment['class_id'] : null;

$materials = [];
$grouped_materials = [];

if ($class_id) {
    // Fetch materials for subjects the student's class is enrolled in
    $stmt = $pdo->prepare("
        SELECT m.*, s.name as subject_name, s.code as subject_code, t.first_name, t.last_name
        FROM study_materials m
        JOIN subjects s ON m.subject_id = s.id
        JOIN teachers t ON m.teacher_id = t.id
        JOIN teacher_subjects ts ON ts.subject_id = s.id AND ts.class_id = ?
        ORDER BY s.name, m.unit, m.created_at DESC
    ");
    $stmt->execute([$class_id]);
    $materials = $stmt->fetchAll();
    
    foreach ($materials as $m) {
        $subject_key = $m['subject_code'] . ' - ' . $m['subject_name'];
        $unit_key = $m['unit'] ?: 'General / Uncategorized';
        $grouped_materials[$subject_key][$unit_key][] = $m;
    }
}
?>

<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
    <h1 class="h2">Study Materials</h1>
</div>

<?php if (!$class_id): ?>
    <div class="alert alert-warning">You are not enrolled in any class to view study materials.</div>
<?php elseif (empty($grouped_materials)): ?>
    <div class="alert alert-info text-center py-5">
        <i class="bi bi-folder2-open fs-1 d-block mb-3"></i>
        <h5>No Materials Found</h5>
        <p class="text-muted mb-0">Your teachers haven't uploaded any study materials yet.</p>
    </div>
<?php else: ?>
    
    <div class="row">
        <div class="col-md-3 mb-4">
            <!-- Subject Navigation -->
            <div class="list-group shadow-sm sticky-top" style="top: 70px;">
                <?php 
                $first = true;
                foreach($grouped_materials as $subj => $units): 
                    $target = "sub_" . preg_replace('/[^a-zA-Z0-9]/', '_', $subj);
                ?>
                    <a href="#<?php echo $target; ?>" class="list-group-item list-group-item-action <?php echo $first ? 'active' : ''; ?>">
                        <?php echo htmlspecialchars($subj); ?>
                    </a>
                <?php $first = false; endforeach; ?>
            </div>
        </div>
        
        <div class="col-md-9">
            <!-- Materials Content -->
            <div data-bs-spy="scroll" data-bs-target=".list-group" data-bs-smooth-scroll="true" tabindex="0">
                
                <?php foreach($grouped_materials as $subj => $units): 
                    $target = "sub_" . preg_replace('/[^a-zA-Z0-9]/', '_', $subj);
                ?>
                    <div id="<?php echo $target; ?>" class="mb-5 pt-3">
                        <h3 class="mb-3 border-bottom pb-2 text-primary"><?php echo htmlspecialchars($subj); ?></h3>
                        
                        <?php foreach($units as $unit => $mats): ?>
                            <div class="card shadow-sm mb-3 border-0">
                                <div class="card-header bg-light fw-bold">
                                    <i class="bi bi-journal-bookmark-fill text-warning me-2"></i> <?php echo htmlspecialchars($unit); ?>
                                </div>
                                <div class="list-group list-group-flush">
                                    <?php foreach($mats as $m): ?>
                                        <div class="list-group-item d-flex justify-content-between align-items-center py-3 hover-bg-light">
                                            <div>
                                                <h6 class="mb-1">
                                                    <?php 
                                                        $icon = 'file-earmark';
                                                        if($m['file_type'] == 'PDF') $icon = 'file-earmark-pdf-fill text-danger';
                                                        elseif(in_array($m['file_type'], ['DOC','DOCX'])) $icon = 'file-earmark-word-fill text-primary';
                                                        elseif(in_array($m['file_type'], ['PPT','PPTX'])) $icon = 'file-earmark-slides-fill text-warning';
                                                        elseif($m['file_type'] == 'ZIP') $icon = 'file-earmark-zip-fill text-secondary';
                                                    ?>
                                                    <i class="bi bi-<?php echo $icon; ?> me-2"></i>
                                                    <?php echo htmlspecialchars($m['title']); ?>
                                                </h6>
                                                <small class="text-muted d-block ms-4">
                                                    Uploaded by Mr/Ms. <?php echo htmlspecialchars($m['last_name']); ?> on <?php echo date('M d, Y', strtotime($m['created_at'])); ?>
                                                </small>
                                            </div>
                                            <a href="../assets/uploads/materials/<?php echo $m['file_path']; ?>" target="_blank" class="btn btn-sm btn-outline-primary rounded-pill px-3">
                                                <i class="bi bi-download"></i> View
                                            </a>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endforeach; ?>
                
            </div>
        </div>
    </div>
<?php endif; ?>

<style>
    .hover-bg-light:hover {
        background-color: #f8f9fa;
    }
    .dark-mode .hover-bg-light:hover {
        background-color: #2c2c2c;
    }
</style>

<script>
    // Simple script to handle active state of subject nav
    $(document).ready(function() {
        $('.list-group-item-action').on('click', function() {
            $('.list-group-item-action').removeClass('active');
            $(this).addClass('active');
        });
    });
</script>

<?php require_once 'includes/footer.php'; ?>
