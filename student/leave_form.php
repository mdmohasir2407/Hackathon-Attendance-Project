<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'student') {
    header("Location: ../auth/login.php");
    exit;
}
require_once '../config/database.php';
require_once 'includes/header.php';

$success = '';
$error = '';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $leave_type = $_POST['leave_type'] ?? '';
    $start_date = $_POST['start_date'] ?? '';
    $end_date = $_POST['end_date'] ?? '';
    $reason = trim($_POST['reason'] ?? '');

    if (empty($leave_type) || empty($start_date) || empty($end_date) || empty($reason)) {
        $error = "All fields are required.";
    } elseif (strtotime($end_date) < strtotime($start_date)) {
        $error = "End date cannot be earlier than start date.";
    } else {
        try {
            $stmt = $pdo->prepare("INSERT INTO leave_forms (student_id, leave_type, start_date, end_date, reason, status) VALUES (?, ?, ?, ?, ?, 'Pending')");
            $stmt->execute([$_SESSION['user_id'], $leave_type, $start_date, $end_date, $reason]);
            $success = "Leave form submitted successfully.";
        } catch (PDOException $e) {
            $error = "Failed to submit leave form.";
        }
    }
}

// Fetch past requests
$stmt = $pdo->prepare("SELECT * FROM leave_forms WHERE student_id = ? ORDER BY created_at DESC");
$stmt->execute([$_SESSION['user_id']]);
$requests = $stmt->fetchAll();
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h2 class="fw-bold mb-0">Leave Application</h2>
    <button class="btn btn-gradient" data-bs-toggle="modal" data-bs-target="#leaveModal">
        <i class="bi bi-file-earmark-plus me-2"></i> Apply for Leave
    </button>
</div>

<?php if($success): ?>
    <div class="alert alert-success"><i class="bi bi-check-circle me-2"></i> <?php echo htmlspecialchars($success); ?></div>
<?php endif; ?>
<?php if($error): ?>
    <div class="alert alert-danger"><i class="bi bi-exclamation-circle me-2"></i> <?php echo htmlspecialchars($error); ?></div>
<?php endif; ?>

<div class="card-modern">
    <h5 class="fw-bold mb-4">My Leave History</h5>
    <div class="table-responsive">
        <table class="table table-modern">
            <thead>
                <tr>
                    <th>Type</th>
                    <th>Start Date</th>
                    <th>End Date</th>
                    <th>Reason</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>
                <?php if(count($requests) > 0): ?>
                    <?php foreach($requests as $req): ?>
                    <tr>
                        <td>
                            <span class="badge bg-light text-dark border">
                                <?php echo htmlspecialchars($req['leave_type']); ?>
                            </span>
                        </td>
                        <td><?php echo date('M d, Y', strtotime($req['start_date'])); ?></td>
                        <td><?php echo date('M d, Y', strtotime($req['end_date'])); ?></td>
                        <td><?php echo htmlspecialchars($req['reason']); ?></td>
                        <td>
                            <?php 
                                $badge = 'bg-warning';
                                if($req['status'] == 'Approved') $badge = 'bg-success';
                                if($req['status'] == 'Rejected') $badge = 'bg-danger';
                            ?>
                            <span class="badge <?php echo $badge; ?> px-3 py-2 rounded-pill"><?php echo htmlspecialchars($req['status']); ?></span>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="5" class="text-center py-4 text-muted">No leave applications found.</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Leave Modal -->
<div class="modal fade" id="leaveModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content glass-panel" style="border: 1px solid var(--glass-border);">
            <div class="modal-header border-bottom-0">
                <h5 class="modal-title fw-bold">Apply for Leave</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form action="" method="post">
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label fw-medium text-muted">Leave Type</label>
                        <select name="leave_type" class="form-select" required>
                            <option value="">Select type...</option>
                            <option value="Pre-Leave">Pre-Leave (Applying before absent)</option>
                            <option value="Post-Leave">Post-Leave (Applying after absent)</option>
                        </select>
                    </div>
                    <div class="row">
                        <div class="col-6 mb-3">
                            <label class="form-label fw-medium text-muted">Start Date</label>
                            <input type="date" class="form-control" name="start_date" required>
                        </div>
                        <div class="col-6 mb-3">
                            <label class="form-label fw-medium text-muted">End Date</label>
                            <input type="date" class="form-control" name="end_date" required>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-medium text-muted">Reason</label>
                        <textarea class="form-control" name="reason" rows="3" required placeholder="Provide a detailed reason..."></textarea>
                    </div>
                </div>
                <div class="modal-footer border-top-0 pt-0">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-gradient">Submit Application</button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>
