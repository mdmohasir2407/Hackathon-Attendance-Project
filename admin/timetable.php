<?php
require_once 'includes/header.php';

$class_id = $_GET['class_id'] ?? '';
$timetable = [];

// Fetch all classes for the filter
$class_stmt = $pdo->query("SELECT c.*, d.name as dept_name FROM classes c JOIN departments d ON c.department_id = d.id ORDER BY d.name, c.name");
$classes = $class_stmt->fetchAll();

// If class is selected, fetch its timetable
if (!empty($class_id)) {
    $stmt = $pdo->prepare("
        SELECT t.*, s.name as subject_name, s.code as subject_code, 
               te.first_name, te.last_name 
        FROM timetable t
        LEFT JOIN subjects s ON t.subject_id = s.id
        LEFT JOIN teachers te ON t.teacher_id = te.id
        WHERE t.class_id = ?
        ORDER BY FIELD(t.day, 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'), t.period_number
    ");
    $stmt->execute([$class_id]);
    $results = $stmt->fetchAll();
    
    // Organize by day and period
    foreach ($results as $row) {
        $timetable[$row['day']][$row['period_number']] = $row;
    }
}

// Fetch subjects and teachers for the add/edit form
$subjects_stmt = $pdo->query("SELECT id, name, code FROM subjects WHERE status = 1 ORDER BY name ASC");
$subjects = $subjects_stmt->fetchAll();

$teachers_stmt = $pdo->query("SELECT id, first_name, last_name FROM teachers ORDER BY first_name ASC");
$teachers = $teachers_stmt->fetchAll();

$days = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'];
$periods = [1, 2, 3, 4, 5, 6, 7];

// Default timings based on periods
$default_timings = [
    1 => ['09:00', '09:50'],
    2 => ['09:50', '10:40'],
    3 => ['10:50', '11:40'],
    4 => ['11:40', '12:30'],
    5 => ['13:30', '14:20'],
    6 => ['14:20', '15:10'],
    7 => ['15:20', '16:10']
];
?>

<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
    <h1 class="h2">Timetable Management</h1>
</div>

<div class="card mb-4 shadow-sm">
    <div class="card-body">
        <form method="GET" action="timetable.php" class="row g-3 align-items-center">
            <div class="col-auto">
                <label for="class_id" class="col-form-label">Select Class:</label>
            </div>
            <div class="col-auto">
                <select name="class_id" id="class_id" class="form-select" onchange="this.form.submit()">
                    <option value="">-- Select Class --</option>
                    <?php foreach($classes as $c): ?>
                        <option value="<?php echo $c['id']; ?>" <?php echo ($class_id == $c['id']) ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($c['dept_name'] . ' - ' . $c['name']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <?php if (!empty($class_id)): ?>
            <div class="col-auto ms-auto">
                <button type="button" class="btn btn-success" id="autoGenerateBtn" data-class="<?php echo $class_id; ?>">
                    <i class="bi bi-magic"></i> Auto Generate Timetable
                </button>
            </div>
            <?php endif; ?>
        </form>
    </div>
</div>

<?php if (!empty($class_id)): ?>
    <div id="alert-container"></div>
    <div class="card shadow-sm mb-4">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-bordered text-center align-middle mb-0" id="timetableTable">
                    <thead class="table-light">
                        <tr>
                            <th>Day / Period</th>
                            <th>Period 1<br><small class="text-muted">09:00 - 09:50</small></th>
                            <th>Period 2<br><small class="text-muted">09:50 - 10:40</small></th>
                            <th class="table-warning text-dark align-middle" style="width: 50px;">Break 1</th>
                            <th>Period 3<br><small class="text-muted">10:50 - 11:40</small></th>
                            <th>Period 4<br><small class="text-muted">11:40 - 12:30</small></th>
                            <th class="table-warning text-dark align-middle" style="width: 50px;">Lunch</th>
                            <th>Period 5<br><small class="text-muted">13:30 - 14:20</small></th>
                            <th>Period 6<br><small class="text-muted">14:20 - 15:10</small></th>
                            <th class="table-warning text-dark align-middle" style="width: 50px;">Break 2</th>
                            <th>Period 7<br><small class="text-muted">15:20 - 16:10</small></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($days as $day): ?>
                            <tr>
                                <th class="table-light align-middle"><?php echo $day; ?></th>
                                <?php foreach ($periods as $p): ?>
                                    <?php 
                                    if ($p == 3) echo '<td class="table-warning text-dark align-middle border-start border-end" rowspan="1"><div style="writing-mode: vertical-rl; text-orientation: mixed; margin:auto;">BREAK</div></td>';
                                    if ($p == 5) echo '<td class="table-warning text-dark align-middle border-start border-end" rowspan="1"><div style="writing-mode: vertical-rl; text-orientation: mixed; margin:auto;">LUNCH</div></td>';
                                    if ($p == 7) echo '<td class="table-warning text-dark align-middle border-start border-end" rowspan="1"><div style="writing-mode: vertical-rl; text-orientation: mixed; margin:auto;">BREAK</div></td>';
                                    
                                    $cell = $timetable[$day][$p] ?? null; 
                                    $cell_class = $cell ? 'bg-light' : '';
                                    ?>
                                    <td class="<?php echo $cell_class; ?> position-relative timetable-cell" 
                                        style="min-width: 150px; height: 100px; cursor: pointer; transition: all 0.2s;"
                                        data-bs-toggle="modal" data-bs-target="#editPeriodModal"
                                        data-day="<?php echo $day; ?>" data-period="<?php echo $p; ?>" 
                                        <?php if ($cell): ?>
                                            data-id="<?php echo $cell['id']; ?>"
                                            data-subject="<?php echo $cell['subject_id']; ?>"
                                            data-teacher="<?php echo $cell['teacher_id']; ?>"
                                            data-classroom="<?php echo htmlspecialchars($cell['classroom']); ?>"
                                            data-start="<?php echo date('H:i', strtotime($cell['start_time'])); ?>"
                                            data-end="<?php echo date('H:i', strtotime($cell['end_time'])); ?>"
                                            data-is-edit="true"
                                        <?php else: ?>
                                            data-start="<?php echo $default_timings[$p][0]; ?>"
                                            data-end="<?php echo $default_timings[$p][1]; ?>"
                                            data-is-edit="false"
                                        <?php endif; ?>
                                        >
                                        <?php if ($cell && !empty($cell['subject_id'])): ?>
                                            <div class="fw-bold text-primary"><?php echo htmlspecialchars($cell['subject_code']); ?></div>
                                            <div class="small"><?php echo htmlspecialchars($cell['subject_name']); ?></div>
                                            <div class="small fst-italic mt-1 text-muted">
                                                <?php echo $cell['teacher_id'] ? htmlspecialchars($cell['first_name'] . ' ' . $cell['last_name']) : 'No Teacher Assigned'; ?>
                                            </div>
                                            <div class="small mt-1"><i class="bi bi-geo-alt"></i> <?php echo htmlspecialchars($cell['classroom']); ?></div>
                                            <div class="position-absolute top-0 end-0 m-1 text-muted" style="opacity: 0.5;">
                                                <i class="bi bi-pencil small"></i>
                                            </div>
                                        <?php elseif ($cell && empty($cell['subject_id'])): ?>
                                            <div class="text-danger fw-bold small fst-italic mb-2">TEST PERIOD</div>
                                            <div class="small mt-1"><i class="bi bi-geo-alt"></i> <?php echo htmlspecialchars($cell['classroom']); ?></div>
                                            <div class="position-absolute top-0 end-0 m-1 text-muted" style="opacity: 0.5;">
                                                <i class="bi bi-pencil small"></i>
                                            </div>
                                        <?php else: ?>
                                            <div class="text-muted small fst-italic mb-2">FREE PERIOD</div>
                                            <div class="text-primary small fw-bold mt-2 opacity-75">
                                                <i class="bi bi-plus"></i> Assign
                                            </div>
                                        <?php endif; ?>
                                    </td>
                                <?php endforeach; ?>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Edit/Add Period Modal -->
    <div class="modal fade" id="editPeriodModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="modalTitle">Assign Period</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form id="timetableForm">
                    <div class="modal-body">
                        <input type="hidden" name="class_id" value="<?php echo $class_id; ?>">
                        <input type="hidden" name="day" id="modal_day">
                        <input type="hidden" name="period_number" id="modal_period">
                        
                        <div class="alert alert-info py-2" id="modalSubtitle"></div>
                        
                        <div class="mb-3">
                            <label for="subject_id" class="form-label">Subject</label>
                            <select class="form-select" id="subject_id" name="subject_id">
                                <option value="">-- FREE PERIOD --</option>
                                <?php foreach($subjects as $sub): ?>
                                    <option value="<?php echo $sub['id']; ?>"><?php echo htmlspecialchars($sub['code'] . ' - ' . $sub['name']); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="mb-3">
                            <label for="teacher_id" class="form-label">Teacher</label>
                            <select class="form-select" id="teacher_id" name="teacher_id">
                                <option value="">-- No Teacher --</option>
                                <?php foreach($teachers as $t): ?>
                                    <option value="<?php echo $t['id']; ?>"><?php echo htmlspecialchars($t['first_name'] . ' ' . $t['last_name']); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="mb-3">
                            <label for="classroom" class="form-label">Classroom</label>
                            <input type="text" class="form-control" id="classroom" name="classroom" placeholder="e.g., Room 101">
                        </div>

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="start_time" class="form-label">Start Time</label>
                                <input type="time" class="form-control" id="start_time" name="start_time" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="end_time" class="form-label">End Time</label>
                                <input type="time" class="form-control" id="end_time" name="end_time" required>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">Save Changes</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
    $(document).ready(function() {
        $('.timetable-cell').on('click', function() {
            var btn = $(this);
            var day = btn.data('day');
            var period = btn.data('period');
            var isEdit = btn.data('is-edit');
            
            $('#modal_day').val(day);
            $('#modal_period').val(period);
            $('#modalSubtitle').text(day + ' - Period ' + period);
            
            if (isEdit) {
                $('#modalTitle').text('Edit Period');
                $('#subject_id').val(btn.data('subject'));
                $('#teacher_id').val(btn.data('teacher'));
                $('#classroom').val(btn.data('classroom'));
            } else {
                $('#modalTitle').text('Assign Period');
                $('#subject_id').val('');
                $('#teacher_id').val('');
                $('#classroom').val('');
            }
            
            $('#start_time').val(btn.data('start'));
            $('#end_time').val(btn.data('end'));
        });

        $('#timetableForm').on('submit', function(e) {
            e.preventDefault();
            $.ajax({
                url: '../api/timetable.php',
                type: 'POST',
                data: $(this).serialize() + '&action=save',
                dataType: 'json',
                success: function(response) {
                    if(response.success) {
                        location.reload(); // Simple reload to reflect changes
                    } else {
                        var alertHtml = '<div class="alert alert-danger alert-dismissible fade show" role="alert">' +
                                        response.message +
                                        '<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>' +
                                        '</div>';
                        $('#alert-container').html(alertHtml);
                        $('#editPeriodModal').modal('hide');
                    }
                },
                error: function(xhr, status, error) {
                    var alertHtml = '<div class="alert alert-danger alert-dismissible fade show" role="alert">' +
                                    'An unexpected error occurred. Please try again. ' + error +
                                    '<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>' +
                                    '</div>';
                    $('#alert-container').html(alertHtml);
                    $('#editPeriodModal').modal('hide');
                }
            });
        });

        $('#autoGenerateBtn').on('click', function() {
            if(confirm("Are you sure? This will OVERWRITE the current timetable for this class and automatically generate a new one with 3 tests, 3 free periods, and evenly distributed subjects.")) {
                var classId = $(this).data('class');
                $(this).prop('disabled', true).html('<i class="bi bi-hourglass-split"></i> Generating...');
                
                $.ajax({
                    url: '../api/timetable.php',
                    type: 'POST',
                    data: { action: 'generate', class_id: classId },
                    dataType: 'json',
                    success: function(response) {
                        if(response.success) {
                            location.reload();
                        } else {
                            var alertHtml = '<div class="alert alert-danger alert-dismissible fade show" role="alert">' +
                                            response.message +
                                            '<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>' +
                                            '</div>';
                            $('#alert-container').html(alertHtml);
                            $('#autoGenerateBtn').prop('disabled', false).html('<i class="bi bi-magic"></i> Auto Generate Timetable');
                        }
                    },
                    error: function() {
                        var alertHtml = '<div class="alert alert-danger alert-dismissible fade show" role="alert">' +
                                        'An error occurred during generation.' +
                                        '<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>' +
                                        '</div>';
                        $('#alert-container').html(alertHtml);
                        $('#autoGenerateBtn').prop('disabled', false).html('<i class="bi bi-magic"></i> Auto Generate Timetable');
                    }
                });
            }
        });
    });
    </script>
<?php endif; ?>

<?php require_once 'includes/footer.php'; ?>
