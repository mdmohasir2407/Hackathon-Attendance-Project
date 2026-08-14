<?php
require_once 'includes/header.php';
?>

<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
    <h1 class="h2">Scan QR Attendance</h1>
</div>

<div class="row justify-content-center">
    <div class="col-md-6">
        <div id="alert-container"></div>
        
        <div class="card shadow-sm mb-4">
            <div class="card-header bg-primary text-white">
                <i class="bi bi-camera-video me-1"></i> Camera Scanner
            </div>
            <div class="card-body p-0">
                <div id="reader" width="100%"></div>
            </div>
            <div class="card-footer text-muted text-center small">
                Point your camera at the QR code displayed by your teacher.
            </div>
        </div>

        <div class="card shadow-sm">
            <div class="card-body">
                <h5 class="card-title fs-6">Manual Entry</h5>
                <form id="manualEntryForm" class="d-flex">
                    <input type="text" id="manualToken" class="form-control me-2" placeholder="Enter Token Manually" required>
                    <button type="submit" class="btn btn-outline-primary">Submit</button>
                </form>
            </div>
        </div>
    </div>
</div>

<script src="https://unpkg.com/html5-qrcode" type="text/javascript"></script>
<script>
$(document).ready(function() {
    let isProcessing = false;

    function showAlert(type, message, icon) {
        var alertHtml = '<div class="alert alert-' + type + ' alert-dismissible fade show shadow-sm" role="alert">' +
                        '<i class="bi ' + icon + ' fs-4 me-2 align-middle"></i> ' +
                        '<strong>' + message + '</strong>' +
                        '<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>' +
                        '</div>';
        $('#alert-container').html(alertHtml);
    }

    function processQRToken(token) {
        if (isProcessing) return;
        isProcessing = true;

        $.ajax({
            url: '../api/attendance.php',
            type: 'POST',
            data: {
                action: 'mark_attendance',
                token: token
            },
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                    showAlert('success', 'Attendance Marked Successfully! (' + response.subject_name + ' - ' + response.time + ')', 'bi-check-circle-fill');
                    // Stop scanner if successful
                    if (html5QrcodeScanner) {
                        html5QrcodeScanner.clear();
                        $('#reader').html('<div class="text-center p-5 text-success"><i class="bi bi-check-circle-fill" style="font-size: 4rem;"></i><h3 class="mt-3">Attendance Recorded</h3><p>You can close this page.</p></div>');
                    }
                } else {
                    showAlert('danger', response.message, 'bi-exclamation-triangle-fill');
                    // Allow rescanning after 3 seconds on failure
                    setTimeout(() => { isProcessing = false; }, 3000);
                }
            },
            error: function() {
                showAlert('danger', 'Connection error. Please try again.', 'bi-wifi-off');
                setTimeout(() => { isProcessing = false; }, 3000);
            }
        });
    }

    function onScanSuccess(decodedText, decodedResult) {
        // Handle the scanned code as you like, for example:
        processQRToken(decodedText);
    }

    function onScanFailure(error) {
        // handle scan failure, usually better to ignore and keep scanning.
    }

    let html5QrcodeScanner = new Html5QrcodeScanner(
        "reader",
        { fps: 10, qrbox: {width: 250, height: 250} },
        /* verbose= */ false);
    
    html5QrcodeScanner.render(onScanSuccess, onScanFailure);

    $('#manualEntryForm').on('submit', function(e) {
        e.preventDefault();
        let token = $('#manualToken').val().trim();
        if(token) {
            processQRToken(token);
        }
    });
});
</script>

<?php require_once 'includes/footer.php'; ?>
