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
    $reason = trim($_POST['reason'] ?? '');
    $date = $_POST['date'] ?? '';
    $time_out = $_POST['time_out'] ?? '';
    $time_in = $_POST['time_in'] ?? '';

    if (empty($reason) || empty($date) || empty($time_out) || empty($time_in)) {
        $error = "All fields are required.";
    } else {
        try {
            $stmt = $pdo->prepare("INSERT INTO gate_permissions (student_id, reason, request_date, time_out, expected_time_in, status) VALUES (?, ?, ?, ?, ?, 'Pending')");
            $stmt->execute([$_SESSION['user_id'], $reason, $date, $time_out, $time_in]);
            $success = "Gate permission requested successfully.";
        } catch (PDOException $e) {
            $error = "Failed to submit request.";
        }
    }
}

// Fetch past requests
$stmt = $pdo->prepare("SELECT * FROM gate_permissions WHERE student_id = ? ORDER BY request_date DESC, created_at DESC");
$stmt->execute([$_SESSION['user_id']]);
$requests = $stmt->fetchAll();
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h2 class="fw-bold mb-0">Gate Permission</h2>
    <button class="btn btn-gradient" data-bs-toggle="modal" data-bs-target="#requestModal">
        <i class="bi bi-plus-lg me-2"></i> New Request
    </button>
</div>

<?php if($success): ?>
    <div class="alert alert-success"><i class="bi bi-check-circle me-2"></i> <?php echo htmlspecialchars($success); ?></div>
<?php endif; ?>
<?php if($error): ?>
    <div class="alert alert-danger"><i class="bi bi-exclamation-circle me-2"></i> <?php echo htmlspecialchars($error); ?></div>
<?php endif; ?>

<div class="card-modern">
    <h5 class="fw-bold mb-4">My Requests</h5>
    <div class="table-responsive">
        <table class="table table-modern">
            <thead>
                <tr>
                    <th>Date</th>
                    <th>Time Out</th>
                    <th>Expected Return</th>
                    <th>Reason</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>
                <?php if(count($requests) > 0): ?>
                    <?php foreach($requests as $req): ?>
                    <tr>
                        <td><?php echo date('M d, Y', strtotime($req['request_date'])); ?></td>
                        <td><?php echo date('h:i A', strtotime($req['time_out'])); ?></td>
                        <td><?php echo date('h:i A', strtotime($req['expected_time_in'])); ?></td>
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
                        <td colspan="5" class="text-center py-4 text-muted">No gate permission requests found.</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Request Modal -->
<div class="modal fade" id="requestModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content glass-panel" style="border: 1px solid var(--glass-border);">
            <div class="modal-header border-bottom-0">
                <h5 class="modal-title fw-bold">Request Gate Permission</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form action="" method="post">
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label fw-medium text-muted">Date</label>
                        <input type="date" class="form-control" name="date" required min="<?php echo date('Y-m-d'); ?>">
                    </div>
                    <div class="row">
                        <div class="col-6 mb-3">
                            <label class="form-label fw-medium text-muted">Time Out</label>
                            <input type="time" class="form-control" name="time_out" required>
                        </div>
                        <div class="col-6 mb-3">
                            <label class="form-label fw-medium text-muted">Expected Time In</label>
                            <input type="time" class="form-control" name="time_in" required>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-medium text-muted">Reason</label>
                        <textarea class="form-control" name="reason" rows="3" required></textarea>
                    </div>
                </div>
                <div class="modal-footer border-top-0 pt-0">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-gradient">Submit Request</button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>
