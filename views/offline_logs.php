<?php include '../backend/offlinehistpry.php'; ?>
<?php include '../includes/header.php'; ?>
<link rel="stylesheet" href="../css/offline.css">
   
<body>
    <div class="container mt-4">
    <?php include '../includes/sidebar.php'; ?>
        <div class="page-header d-flex justify-content-between align-items-center mb-4 no-print">
        <div>
                <h1 class="h4 mb-0">Offline Logs: <?= htmlspecialchars($ipDetails['ip_address']) ?></h1>
                <p class="text-muted mb-0 mt-1">
                    <span class="badge bg-secondary me-2"><?= htmlspecialchars($ipDetails['location']) ?></span>
                    <span class="badge bg-info"><?= htmlspecialchars($ipDetails['category']) ?></span>
                </p>
            </div>
            <div class="header-buttons" id="exportButtonsContainer">
            </div>
        </div>
        
        <div class="d-none d-print-block">
            <h1 class="text-center">Offline Logs for <?= htmlspecialchars($ipDetails['ip_address']) ?></h1>
            <p class="text-center">
                <strong>Location:</strong> <?= htmlspecialchars($ipDetails['location']) ?>  
                <strong>Category:</strong> <?= htmlspecialchars($ipDetails['category']) ?>
            </p>
        </div>
        
            <div class="card mb-4 no-print">
            <div class="card-header bg-white">
                <i class="bi bi-funnel me-2"></i> Filter Options
            </div>
            <div class="card-body filter-section">
                <form method="GET" action="" class="row g-2 align-items-end">
                    <input type="hidden" name="ip_id" value="<?= $ip_id ?>">
                    
                    <div class="col">
                        <label for="month" class="form-label small mb-1">Month</label>
                        <select class="form-select form-select-sm" style="width: 150px;" id="month" name="month">
                            <option value="">All</option>
                            <?php for($i = 1; $i <= 12; $i++): ?>
                                <option value="<?= $i ?>" <?= $filterMonth == $i ? 'selected' : '' ?>>
                                    <?= date('F', mktime(0, 0, 0, $i, 1)) ?>
                                </option>
                            <?php endfor; ?>
                        </select>
                    </div>
                    
                    <div class="col">
                        <label for="year" class="form-label small mb-1">Year</label>
                        <select class="form-select form-select-sm" style="width: 100px;" id="year" name="year">
                            <?php 
                            $currentYear = date('Y');
                            for($i = $currentYear; $i >= $currentYear - 2; $i--): 
                            ?>
                                <option value="<?= $i ?>" <?= $filterYear == $i ? 'selected' : '' ?>><?= $i ?></option>
                            <?php endfor; ?>
                        </select>
                    </div>
                    
                    <div class="col">
                        <label for="day" class="form-label small mb-1">Day</label>
                        <select class="form-select form-select-sm" style="width: 100px;" id="day" name="day">
                            <option value="">All</option>
                            <?php for($i = 1; $i <= 31; $i++): ?>
                                <option value="<?= $i ?>" <?= $filterDay == $i ? 'selected' : '' ?>><?= $i ?></option>
                            <?php endfor; ?>
                        </select>
                    </div>
                    
                    <div class="col">
                        <label for="start_time" class="form-label small mb-1">Start</label>
                        <select class="form-select form-select-sm" style="width: 100px;" id="start_time" name="start_time">
                            <option value="">Any</option>
                            <?php for($i = 0; $i < 24; $i++): ?>
                                <option value="<?= $i ?>" <?= $filterStartTime == (string)$i ? 'selected' : '' ?>>
                                    <?= sprintf('%02d:00', $i) ?>
                                </option>
                            <?php endfor; ?>
                        </select>
                    </div>
                    
                    <div class="col">
                        <label for="end_time" class="form-label small mb-1">End</label>
                        <select class="form-select form-select-sm" style="width: 100px;" id="end_time" name="end_time">
                            <option value="">Any</option>
                            <?php for($i = 0; $i < 24; $i++): ?>
                                <option value="<?= $i ?>" <?= $filterEndTime == (string)$i ? 'selected' : '' ?>>
                                    <?= sprintf('%02d:00', $i) ?>
                                </option>
                            <?php endfor; ?>
                        </select>
                    </div>
                    
                    <div class="col">
                        <button type="submit" class="btn btn-primary btn-sm" style="font-size: 10px; padding: 5px 8px;"><i class="bi bi-filter"></i> Apply</button>
                        <a href="offline_logs.php?ip_id=<?= $ip_id ?>" class="btn btn-outline-secondary btn-sm" style="font-size: 10px; padding: 5px 8px;"><i class="bi bi-x-circle"></i> Clear</a>
                    </div>
                </form>
            </div>
        </div>

        <div class="card">
            <div class="card-header bg-white">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <i class="bi bi-clock-history me-2 text-danger"></i>
                        <span class="fw-bold">Offline Logs</span>
                        <span class="ms-2 badge bg-secondary"><?= count($pingLogs) ?> records</span>
                    </div>
                </div>
            </div>
            <div class="card-body table-container p-0">
                <?php if(count($pingLogs) > 0): ?>
                    <table class="table" id="offlineLogsTable">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>Date & Time</th>
                                <th>Latency (ms)</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach($pingLogs as $index => $log): ?>
                            <tr>
                                <td><?= $index + 1 ?></td>
                                <td><?= date('F j, Y g:i:s A', strtotime($log['created_at'])) ?></td>
                                <td><?= $log['latency'] ?></td>
                                <td><span class="badge bg-danger"><?= htmlspecialchars($log['status']) ?></span></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php else: ?>
                    <div class="alert alert-info m-3">
                        No offline logs found for this IP with the selected filters.
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
  <script>
    $(document).ready(function() {
    // Initialize DataTable
    const dataTable = $('#offlineLogsTable').DataTable({
        dom: '<"dt-header"f>rt<"dt-footer"lip>',
        buttons: [
            {
                extend: 'print',
                text: '<i class="bi bi-printer"></i> Print',
                className: 'btn btn-primary',
                exportOptions: {
                    columns: ':visible'
                },
                customize: function(win) {
                    $(win.document.body).css('font-family', 'Poppins, sans-serif');
                    $(win.document.body).find('h1').css({
                        'font-family': 'Poppins, sans-serif',
                        'text-align': 'center',
                        'margin-bottom': '15px'
                    });
                    $(win.document.body).find('table').addClass('table table-bordered');
                    
                    // Add filter information to the print
                    let filterInfo = '';
                    
                    // Get filter values from form
                    const monthVal = $('#month').val();
                    const yearVal = $('#year').val();
                    const dayVal = $('#day').val();
                    const startTimeVal = $('#start_time').val();
                    const endTimeVal = $('#end_time').val();
                    
                    // Build filter string
                    const filters = [];
                    if (monthVal) filters.push('Month: ' + $('#month option:selected').text());
                    if (yearVal) filters.push('Year: ' + yearVal);
                    if (dayVal) filters.push('Day: ' + dayVal);
                    if (startTimeVal) filters.push('Start Time: ' + $('#start_time option:selected').text());
                    if (endTimeVal) filters.push('End Time: ' + $('#end_time option:selected').text());
                    
                    // Add filters to print if any exist
                    if (filters.length > 0) {
                        filterInfo = 'Applied Filters: ' + filters.join(' | ');
                        $(win.document.body).find('h1').after('<p style="text-align: center; margin-bottom: 20px;">' + filterInfo + '</p>');
                    }
                }
            },
            {
                extend: 'excel',
                text: '<i class="bi bi-file-earmark-excel"></i> Excel',
                className: 'btn btn-success',
                exportOptions: {
                    columns: ':visible'
                }
            },
            {
                extend: 'pdf',
                text: '<i class="bi bi-file-earmark-pdf"></i> PDF',
                className: 'btn btn-danger',
                exportOptions: {
                    columns: ':visible'
                },
                customize: function(doc) {
                    doc.defaultStyle.font = 'Poppins';
                    
                    // Add filter information to the PDF
                    let filterInfo = '';
                    
                    // Get filter values from form
                    const monthVal = $('#month').val();
                    const yearVal = $('#year').val();
                    const dayVal = $('#day').val();
                    const startTimeVal = $('#start_time').val();
                    const endTimeVal = $('#end_time').val();
                    
                    // Build filter string
                    const filters = [];
                    if (monthVal) filters.push('Month: ' + $('#month option:selected').text());
                    if (yearVal) filters.push('Year: ' + yearVal);
                    if (dayVal) filters.push('Day: ' + dayVal);
                    if (startTimeVal) filters.push('Start Time: ' + $('#start_time option:selected').text());
                    if (endTimeVal) filters.push('End Time: ' + $('#end_time option:selected').text());
                    
                    // Add filters to PDF if any exist
                    if (filters.length > 0) {
                        filterInfo = 'Applied Filters: ' + filters.join(' | ');
                        doc.content.splice(1, 0, {
                            text: filterInfo,
                            alignment: 'center',
                            margin: [0, 0, 0, 10]
                        });
                    }
                }
            }
        ],
        lengthMenu: [
            [10, 25, 50, 100, -1],
            [10, 25, 50, 100, "All"]
        ],
        pageLength: 25,
        language: {
            search: "<i class='bi bi-search'></i> Search:",
            lengthMenu: "Show _MENU_ entries",
            info: "Showing _START_ to _END_ of _TOTAL_ entries",
            paginate: {
                first: "<i class='bi bi-chevron-double-left'></i>",
                last: "<i class='bi bi-chevron-double-right'></i>",
                next: "<i class='bi bi-chevron-right'></i>",
                previous: "<i class='bi bi-chevron-left'></i>"
            }
        }
    });

    // Move export buttons to the header
    const exportButtons = new $.fn.dataTable.Buttons(dataTable, {
        buttons: dataTable.buttons().config().buttons
    });

    // Insert export buttons before the Back button
    $(exportButtons.dom.container).insertBefore('#exportButtonsContainer a:last-child');
    
    // Add spacing before Back button
    $('#exportButtonsContainer a:last-child').css('margin-left', '10px');
});
  </script>
