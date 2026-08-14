<?php
require_once 'includes/header.php';

$teacher_id = $_SESSION['user_id'];

// Pre-fill from dashboard if passed
$default_class_id = $_GET['class_id'] ?? '';
$default_subject_id = $_GET['subject_id'] ?? '';
$default_period = $_GET['period'] ?? '';

// Fetch classes/subjects for this teacher
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
$assignments = $stmt->fetchAll();

$periods = [1, 2, 3, 4, 5, 6, 7];
?>

<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
    <h1 class="h2">Generate QR Attendance</h1>
</div>

<div class="row">
    <div class="col-md-5">
        <div class="card shadow-sm mb-4">
            <div class="card-header bg-white">
                <i class="bi bi-gear me-1"></i> Session Settings
            </div>
            <div class="card-body">
                <form id="generateQRForm">
                    <div class="mb-3">
                        <label for="assignment" class="form-label">Select Class & Subject</label>
                        <select class="form-select" id="assignment" name="assignment" required>
                            <option value="">-- Choose --</option>
                            <?php foreach ($assignments as $a): ?>
                                <?php 
                                $value = $a['class_id'] . '|' . $a['subject_id']; 
                                $selected = ($a['class_id'] == $default_class_id && $a['subject_id'] == $default_subject_id) ? 'selected' : '';
                                ?>
                                <option value="<?php echo $value; ?>" <?php echo $selected; ?>>
                                    <?php echo htmlspecialchars($a['dept_name'] . ' - ' . $a['class_name'] . ' (' . $a['subject_code'] . ')'); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label for="period" class="form-label">Period</label>
                        <select class="form-select" id="period" name="period" required>
                            <option value="">-- Choose Period --</option>
                            <?php foreach ($periods as $p): ?>
                                <option value="<?php echo $p; ?>" <?php echo ($p == $default_period) ? 'selected' : ''; ?>>Period <?php echo $p; ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label for="duration" class="form-label">Valid For (Minutes)</label>
                        <select class="form-select" id="duration" name="duration">
                            <option value="5">5 Minutes</option>
                            <option value="10" selected>10 Minutes</option>
                            <option value="15">15 Minutes</option>
                            <option value="30">30 Minutes</option>
                        </select>
                    </div>
                    <button type="submit" class="btn btn-primary w-100" id="generateBtn"><i class="bi bi-qr-code"></i> Generate Session</button>
                </form>
            </div>
        </div>
    </div>
    
    <div class="col-md-7">
        <div class="card shadow-sm h-100 text-center" id="qrCard" style="display: none;">
            <div class="card-header bg-success text-white">
                <i class="bi bi-broadcast"></i> Live Attendance Session Active
            </div>
            <div class="card-body d-flex flex-column align-items-center justify-content-center">
                <h4 id="qrSessionTitle" class="mb-3"></h4>
                <div id="qrcode" class="mb-4 bg-white p-3 border rounded shadow-sm"></div>
                
                <div class="alert alert-warning d-inline-block">
                    <i class="bi bi-stopwatch"></i> Expires in: <strong id="countdown">--:--</strong>
                </div>
                
                <div class="mt-3">
                    <h5 class="text-muted">Live Scans: <span id="liveScanCount" class="badge bg-primary fs-5">0</span></h5>
                </div>
                
                <button type="button" class="btn btn-outline-danger mt-4" id="endSessionBtn">End Session Now</button>
            </div>
        </div>
        
        <div class="card shadow-sm h-100 d-flex align-items-center justify-content-center" id="waitingCard">
            <div class="text-muted text-center p-5">
                <i class="bi bi-qr-code fs-1 d-block mb-3"></i>
                <p>Select settings and click generate to start an attendance session.</p>
            </div>
        </div>
    </div>
</div>

<script src="https://cdnjs.cloudflare.com/ajax/libs/qrcodejs/1.0.0/qrcode.min.js"></script>
<script>
$(document).ready(function() {
    let currentSessionToken = null;
    let countdownInterval = null;
    let scanPollInterval = null;
    let qrcodeObj = null;

    $('#generateQRForm').on('submit', function(e) {
        e.preventDefault();
        
        const assignment = $('#assignment').val().split('|');
        const class_id = assignment[0];
        const subject_id = assignment[1];
        const period = $('#period').val();
        const duration = $('#duration').val();
        const titleText = $("#assignment option:selected").text().trim() + " - Period " + period;
        
        $('#generateBtn').prop('disabled', true).html('<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Generating...');

        $.ajax({
            url: '../api/attendance.php',
            type: 'POST',
            data: {
                action: 'create_session',
                class_id: class_id,
                subject_id: subject_id,
                period: period,
                duration: duration
            },
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                    currentSessionToken = response.token;
                    startQRSession(currentSessionToken, response.expires_timestamp, response.duration_seconds, titleText);
                } else {
                    alert(response.message);
                    $('#generateBtn').prop('disabled', false).html('<i class="bi bi-qr-code"></i> Generate Session');
                }
            },
            error: function() {
                alert('Error connecting to server.');
                $('#generateBtn').prop('disabled', false).html('<i class="bi bi-qr-code"></i> Generate Session');
            }
        });
    });

    function startQRSession(token, expiresTimestamp, durationSeconds, titleText) {
        $('#waitingCard').hide();
        $('#qrCard').show();
        $('#qrSessionTitle').text(titleText);
        $('#liveScanCount').text('0');
        
        // Generate QR
        $('#qrcode').empty();
        qrcodeObj = new QRCode(document.getElementById("qrcode"), {
            text: token, // The token is what the student scans
            width: 256,
            height: 256,
            colorDark : "#000000",
            colorLight : "#ffffff",
            correctLevel : QRCode.CorrectLevel.H
        });

        // Setup Countdown using exact millisecond target
        const expiresAtMs = expiresTimestamp ? (expiresTimestamp * 1000) : (Date.now() + (durationSeconds * 1000));
        
        clearInterval(countdownInterval);
        countdownInterval = setInterval(function() {
            const now = Date.now();
            const distance = expiresAtMs - now;

            if (distance <= 0) {
                clearInterval(countdownInterval);
                endSession("Session Expired");
            } else {
                const totalSecs = Math.floor(distance / 1000);
                const minutes = Math.floor(totalSecs / 60);
                const seconds = totalSecs % 60;
                $('#countdown').text(minutes + "m " + (seconds < 10 ? '0' : '') + seconds + "s");
            }
        }, 1000);
        
        // Poll for live scans
        clearInterval(scanPollInterval);
        scanPollInterval = setInterval(pollScanCount, 3000);
    }

    function pollScanCount() {
        if (!currentSessionToken) return;
        $.ajax({
            url: '../api/attendance.php',
            type: 'GET',
            data: { action: 'get_scan_count', token: currentSessionToken },
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                    $('#liveScanCount').text(response.count);
                }
            }
        });
    }

    function endSession(reason = "Session Ended") {
        clearInterval(countdownInterval);
        clearInterval(scanPollInterval);
        $('#qrcode').empty().html('<div class="text-danger p-4 border rounded"><i class="bi bi-x-circle fs-1 d-block mb-2"></i>' + reason + '</div>');
        $('#countdown').text('--:--');
        currentSessionToken = null;
        $('#generateBtn').prop('disabled', false).html('<i class="bi bi-qr-code"></i> Generate New Session');
    }

    $('#endSessionBtn').on('click', function() {
        if (confirm("Are you sure you want to end this session early?")) {
            // Optional: send API request to expire token on server immediately
            $.ajax({
                url: '../api/attendance.php',
                type: 'POST',
                data: { action: 'end_session', token: currentSessionToken },
                success: function() {
                    endSession("Session Ended Manually");
                }
            });
        }
    });
});
</script>

<?php require_once 'includes/footer.php'; ?>
