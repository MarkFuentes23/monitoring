<?php include '../includes/header.php'; ?>
<?php
require_once '../backend/overall_report.php';
require_once '../backend/overall_reporting.php';

// First get month and year separately to avoid circular reference
$currentMonth = isset($_GET['month']) ? (int)$_GET['month'] : date('n');
$currentYear = isset($_GET['year']) ? (int)$_GET['year'] : date('Y');

// Get filter parameters from URL
$filters = [
    'month' => $currentMonth,
    'year' => $currentYear,
    'view_mode' => isset($_GET['view_mode']) ? $_GET['view_mode'] : 'all',
    'start_date' => isset($_GET['start_date']) ? $_GET['start_date'] : '01',
    'end_date' => isset($_GET['end_date']) ? $_GET['end_date'] : date('t', mktime(0,0,0,$currentMonth,1,$currentYear)),
    'single_day' => isset($_GET['single_day']) ? $_GET['single_day'] : date('d'),
    'start_time' => isset($_GET['start_time']) ? $_GET['start_time'] : '00:00',
    'end_time' => isset($_GET['end_time']) ? $_GET['end_time'] : '23:59',
    'location' => isset($_GET['location']) ? $_GET['location'] : '',
    'category' => isset($_GET['category']) ? $_GET['category'] : '',
    'status' => isset($_GET['status']) ? $_GET['status'] : '',
    'ip_id' => isset($_GET['ip_id']) ? $_GET['ip_id'] : ''
];

// Get filter options (locations, categories, IPs)
$filterOptions = getFilterOptions();

// Get monitoring data based on filters
$monitoringData = getGlobalMonitoringData($filters);

// Get daily statistics
$dailyStats = getGlobalDailyStats($filters);

// Get device-specific statistics
$deviceStats = getDeviceStats($filters);
?>

<link rel="stylesheet" href="../css/report.css">
<div class="container-fluid">
  <div class="row">
    <?php include '../includes/sidebar.php'; ?>

    <main class="container-fluid">
      <div class="d-flex justify-content-between flex-wrap 
                align-items-center pt-3 pb-2 mb-3 border-bottom">
        <div>
          <h1 class="h2">Global Network Monitoring Dashboard</h1>
          <p>
            <strong>Month:</strong> <?= date('F Y', mktime(0,0,0,$filters['month'],1,$filters['year'])) ?> 
            <?php if (!empty($filters['location'])): ?>
              | <strong>Location:</strong> <?= htmlspecialchars($filters['location']) ?>
            <?php endif; ?>
            <?php if (!empty($filters['category'])): ?>
              | <strong>Category:</strong> <?= htmlspecialchars($filters['category']) ?>
            <?php endif; ?>
          </p>
        </div>
        <div class="btn-toolbar mb-2 mb-md-0">
          <button type="button" class="btn btn-sm btn-outline-primary me-2" data-bs-toggle="modal" data-bs-target="#filterModal">
            <i class="fas fa-filter"></i> Advanced Filters
          </button>
          <a href="global_report_print.php?<?= http_build_query($filters) ?>"
             target="_blank" class="btn btn-sm btn-primary">
            <i class="fas fa-print"></i> Print Global Report
          </a>
        </div>
      </div>

      <!-- Alerts -->
      <?php if (!empty($_SESSION['error'])): ?>
        <div class="alert alert-danger"><?= $_SESSION['error']; unset($_SESSION['error']); ?></div>
      <?php endif; ?>
      <?php if (!empty($_SESSION['success'])): ?>
        <div class="alert alert-success"><?= $_SESSION['success']; unset($_SESSION['success']); ?></div>
      <?php endif; ?>

      <!-- Global Summary Metrics -->
      <div class="row mb-4">
        <div class="col-md-12">
          <div class="card shadow-sm">
            <div class="card-header bg-primary text-white">
              <h5 class="card-title mb-0"><i class="fas fa-chart-pie me-2"></i>Global Network Summary</h5>
            </div>
            <div class="card-body">
              <div class="row g-4">
                <div class="col-md-3 col-sm-6">
                  <div class="card text-center shadow-sm border-0" style="background: linear-gradient(45deg, #4e73df, #6983e8); border-radius: 10px;">
                    <div class="card-body p-3">
                      <i class="fas fa-server mb-2" style="color: #ffffff; font-size: 1.5rem;"></i>
                      <h6 class="text-white mb-0">Total Devices</h6>
                      <h3 class="text-white"><?= count($deviceStats) ?></h3>
                    </div>
                  </div>
                </div>

                <div class="col-md-3 col-sm-6">
                  <div class="card text-center shadow-sm border-0" style="background: linear-gradient(45deg, #1cc88a, #36e3af); border-radius: 10px;">
                    <div class="card-body p-3">
                      <i class="fas fa-tachometer-alt mb-2" style="color: #ffffff; font-size: 1.5rem;"></i>
                      <h6 class="text-white mb-0">Avg Latency</h6>
                      <h3 class="text-white"><?= number_format($dailyStats['monthly_total']['avg_latency'], 2) ?> ms</h3>
                    </div>
                  </div>
                </div>

                <div class="col-md-3 col-sm-6">
                  <div class="card text-center shadow-sm border-0" style="background: linear-gradient(45deg, #e74a3b, #ef8579); border-radius: 10px;">
                    <div class="card-body p-3">
                      <i class="fas fa-exclamation-triangle mb-2" style="color: #ffffff; font-size: 1.5rem;"></i>
                      <h6 class="text-white mb-0">Offline Events</h6>
                      <h3 class="text-white"><?= $dailyStats['monthly_total']['total_offline_count'] ?></h3>
                    </div>
                  </div>
                </div>

                <div class="col-md-3 col-sm-6">
                  <div class="card text-center shadow-sm border-0" style="background: linear-gradient(45deg, #f6c23e, #f9d675); border-radius: 10px;">
                    <div class="card-body p-3">
                      <i class="fas fa-chart-line mb-2" style="color: #ffffff; font-size: 1.5rem;"></i>
                      <h6 class="text-white mb-0">Overall Uptime</h6>
                      <h3 class="text-white"><?= number_format($dailyStats['monthly_total']['avg_uptime_percent'], 2) ?>%</h3>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Quick Filter Buttons -->
      <div class="card mb-4 shadow-sm">
        <div class="card-header bg-light">
          <h5 class="card-title mb-0"><i class="fas fa-sliders-h me-2"></i>Quick Filters</h5>
        </div>
        <div class="card-body">
          <div class="row g-2">

    <div class="col-md-auto">
    <select class="form-select form-select-sm bg-white text-dark" id="monthDropdown" onchange="window.location.href=this.value" style="height: 31px; width: auto; display: inline-block;">
        <?php for($m=1; $m<=12; $m++): ?>
        <option value="?<?= http_build_query(array_merge($filters, ['month' => $m])) ?>" 
                <?= $filters['month'] == $m ? 'selected' : '' ?>>
            <?= date('F', mktime(0,0,0,$m,1)) ?>
        </option>
        <?php endfor; ?>
    </select>
    </div>
                        
            <!-- View Mode Buttons -->
    <div class="col-md-auto">
        <div class="btn-group">
        <a href="?<?= http_build_query(array_merge($filters, ['view_mode' => 'all'])) ?>" 
            class="btn btn-sm <?= $filters['view_mode'] == 'all' ? 'btn-success' : 'btn-outline-success' ?>">
            <i class="fas fa-calendar-alt"></i> All Month
        </a>
        <button type="button" class="btn btn-sm <?= $filters['view_mode'] == 'day' ? 'btn-success' : 'btn-outline-success' ?>" 
                data-bs-toggle="modal" data-bs-target="#dayModal">
            <i class="fas fa-calendar-day"></i> Single Day
        </button>
        <button type="button" class="btn btn-sm <?= $filters['view_mode'] == 'time_only' ? 'btn-success' : 'btn-outline-success' ?>" 
                data-bs-toggle="modal" data-bs-target="#timeModal">
            <i class="fas fa-clock"></i> Time Range
        </button>
        <button type="button" class="btn btn-sm <?= $filters['view_mode'] == 'custom' ? 'btn-success' : 'btn-outline-success' ?>" 
                data-bs-toggle="modal" data-bs-target="#customFilterModal">
            <i class="fas fa-filter"></i> Custom
        </button>
        </div>
    </div>


        <!-- Location & Category Filter Buttons -->
    <div class="col-md-auto">
    <div class="btn-group shadow-sm">
        <button type="button" class="btn btn-sm <?= !empty($filters['location']) ? 'btn-info' : 'btn-outline-info' ?>" 
                data-bs-toggle="modal" data-bs-target="#locationModal">
        <i class="fas fa-map-marker-alt me-1"></i><small>Location: 
        <span class="fw-semibold"><?= !empty($filters['location']) ? htmlspecialchars($filters['location']) : 'All' ?></span></small>
        </button>
        
        <button type="button" class="btn btn-sm <?= !empty($filters['category']) ? 'btn-info' : 'btn-outline-info' ?>" 
                data-bs-toggle="modal" data-bs-target="#categoryModal">
        <i class="fas fa-tag me-1"></i><small>Category: 
        <span class="fw-semibold"><?= !empty($filters['category']) ? htmlspecialchars($filters['category']) : 'All' ?></span></small>
        </button>
    </div>
    </div>

        <!-- Location Modal -->

    <div class="modal fade" id="locationModal" tabindex="-1" aria-labelledby="locationModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-sm modal-dialog-scrollable">
        <div class="modal-content border-0 shadow">
        <div class="modal-header bg-info text-white py-2">
            <h6 class="modal-title" id="locationModalLabel"><i class="fas fa-map-marker-alt me-1"></i>Select Location</h6>
            <button type="button" class="btn-close btn-close-white btn-sm" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body p-0">
            <div class="list-group list-group-flush">
            <a href="?<?= http_build_query(array_merge($filters, ['location' => ''])) ?>" 
                class="list-group-item list-group-item-action d-flex justify-content-between align-items-center py-2">
                <small><i class="fas fa-globe me-1"></i>All Locations</small>
                <?php if(empty($filters['location'])): ?><span class="badge bg-info rounded-pill"><i class="fas fa-check"></i></span><?php endif; ?>
            </a>
            <?php foreach($filterOptions['locations'] as $loc): ?>
                <a href="?<?= http_build_query(array_merge($filters, ['location' => $loc])) ?>" 
                class="list-group-item list-group-item-action d-flex justify-content-between align-items-center py-2">
                <small><?= htmlspecialchars($loc) ?></small>
                <?php if($filters['location'] == $loc): ?><span class="badge bg-info rounded-pill"><i class="fas fa-check"></i></span><?php endif; ?>
                </a>
            <?php endforeach; ?>
            </div>
        </div>
        </div>
    </div>
    </div>

            <!-- Category Modal -->
    <div class="modal fade" id="categoryModal" tabindex="-1" aria-labelledby="categoryModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-sm modal-dialog-scrollable">
        <div class="modal-content border-0 shadow">
        <div class="modal-header bg-info text-white py-2">
            <h6 class="modal-title" id="categoryModalLabel"><i class="fas fa-tag me-1"></i>Select Category</h6>
            <button type="button" class="btn-close btn-close-white btn-sm" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body p-0">
            <div class="list-group list-group-flush">
            <a href="?<?= http_build_query(array_merge($filters, ['category' => ''])) ?>" 
                class="list-group-item list-group-item-action d-flex justify-content-between align-items-center py-2">
                <small><i class="fas fa-layer-group me-1"></i>All Categories</small>
                <?php if(empty($filters['category'])): ?><span class="badge bg-info rounded-pill"><i class="fas fa-check"></i></span><?php endif; ?>
            </a>
            <?php foreach($filterOptions['categories'] as $cat): ?>
                <a href="?<?= http_build_query(array_merge($filters, ['category' => $cat])) ?>" 
                class="list-group-item list-group-item-action d-flex justify-content-between align-items-center py-2">
                <small><?= htmlspecialchars($cat) ?></small>
                <?php if($filters['category'] == $cat): ?><span class="badge bg-info rounded-pill"><i class="fas fa-check"></i></span><?php endif; ?>
                </a>
            <?php endforeach; ?>
            </div>
        </div>
        </div>
    </div>
    </div>

<!-- Daily Statistics Table -->
    <div class="card mb-4 shadow-sm">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h5 class="card-title mb-0">
        Daily Network Statistics (<?= date("F Y", mktime(0,0,0,$filters['month'],1,$filters['year'])) ?>)
        </h5>
        <div id="dailyExportButtons"></div>
    </div>
    <div class="card-body">
        <div class="table-responsive">
        <table id="dailyStatsTable" class="table table-bordered table-striped mb-0">
            <thead>
            <tr class="table-secondary">
                <th>IP Address</th>
                <th>Description</th>
                <th>Location</th>
                <th>Category</th>
                <th>Latency (ms)</th>
                <th>Status</th>
                <th>Date</th>
                <th>Time</th>
            </tr>
            </thead>
            <tbody>
            <?php foreach($deviceStats as $device): ?>
                <tr>
                <td><?= htmlspecialchars($device['ip_address']) ?></td>
                <td><?= htmlspecialchars($device['description']) ?></td>
                <td><?= htmlspecialchars($device['location']) ?></td>
                <td><?= htmlspecialchars($device['category']) ?></td>
                <td><?= number_format($device['latency'], 2) ?></td>
                <td>
                    <span class="badge <?= $device['status']=='online'?'bg-success':'bg-danger' ?>">
                    <?= ucfirst($device['status']) ?>
                    </span>
                </td>
                <td><?= date('M d, Y', strtotime($device['created_at'])) ?></td>
                <td><?= date('H:i:s', strtotime($device['created_at'])) ?></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
        </div>
    </div>
    </div>


<!-- Day Filter Modal -->
    <div class="modal fade" id="dayModal" tabindex="-1" aria-labelledby="dayModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
        <div class="modal-header bg-primary text-white">
            <h5 class="modal-title" id="dayModalLabel">Filter by Single Day</h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <form action="" method="GET">
            <div class="modal-body">
            <?php foreach($filters as $key => $value): ?>
                <?php if($key != 'view_mode' && $key != 'single_day'): ?>
                <input type="hidden" name="<?= $key ?>" value="<?= htmlspecialchars($value) ?>">
                <?php endif; ?>
            <?php endforeach; ?>
            <input type="hidden" name="view_mode" value="day">
            
            <div class="mb-3">
                <label for="single_day" class="form-label">Select Day:</label>
                <select class="form-select" id="single_day" name="single_day">
                <?php 
                $daysInMonth = date('t', mktime(0,0,0,$filters['month'],1,$filters['year']));
                for($d=1; $d<=$daysInMonth; $d++): 
                ?>
                    <option value="<?= sprintf('%02d', $d) ?>" <?= $filters['single_day'] == sprintf('%02d', $d) ? 'selected' : '' ?>>
                    <?= date('M d, Y (D)', mktime(0,0,0,$filters['month'],$d,$filters['year'])) ?>
                    </option>
                <?php endfor; ?>
                </select>
            </div>
            </div>
            <div class="modal-footer">
            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
            <button type="submit" class="btn btn-primary">Apply Filter</button>
            </div>
        </form>
        </div>
    </div>
    </div>

<!-- Time Range Modal -->
    <div class="modal fade" id="timeModal" tabindex="-1" aria-labelledby="timeModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
        <div class="modal-header bg-primary text-white">
            <h5 class="modal-title" id="timeModalLabel">Filter by Time Range</h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <form action="" method="GET">
            <div class="modal-body">
            <?php foreach($filters as $key => $value): ?>
                <?php if($key != 'view_mode' && $key != 'start_time' && $key != 'end_time'): ?>
                <input type="hidden" name="<?= $key ?>" value="<?= htmlspecialchars($value) ?>">
                <?php endif; ?>
            <?php endforeach; ?>
            <input type="hidden" name="view_mode" value="time_only">
            
            <div class="mb-3">
                <label for="start_time" class="form-label">Start Time:</label>
                <input type="time" class="form-control" id="start_time" name="start_time" value="<?= $filters['start_time'] ?>">
            </div>
            <div class="mb-3">
                <label for="end_time" class="form-label">End Time:</label>
                <input type="time" class="form-control" id="end_time" name="end_time" value="<?= $filters['end_time'] ?>">
            </div>
            </div>
            <div class="modal-footer">
            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
            <button type="submit" class="btn btn-primary">Apply Filter</button>
            </div>
        </form>
        </div>
    </div>
    </div>

<!-- Custom Filter Modal -->
    <div class="modal fade" id="customFilterModal" tabindex="-1" aria-labelledby="customFilterModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
        <div class="modal-header bg-primary text-white">
            <h5 class="modal-title" id="customFilterModalLabel">Custom Date & Time Filter</h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <form action="" method="GET">
            <div class="modal-body">
            <?php foreach($filters as $key => $value): ?>
                <?php if($key != 'view_mode' && $key != 'start_date' && $key != 'end_date' && $key != 'start_time' && $key != 'end_time'): ?>
                <input type="hidden" name="<?= $key ?>" value="<?= htmlspecialchars($value) ?>">
                <?php endif; ?>
            <?php endforeach; ?>
            <input type="hidden" name="view_mode" value="custom">
            
            <div class="row">
                <div class="col-md-6">
                <div class="mb-3">
                    <label for="start_date" class="form-label">Start Date:</label>
                    <select class="form-select" id="start_date" name="start_date">
                    <?php 
                    $daysInMonth = date('t', mktime(0,0,0,$filters['month'],1,$filters['year']));
                    for($d=1; $d<=$daysInMonth; $d++): 
                    ?>
                        <option value="<?= sprintf('%02d', $d) ?>" <?= $filters['start_date'] == sprintf('%02d', $d) ? 'selected' : '' ?>>
                        <?= date('M d, Y', mktime(0,0,0,$filters['month'],$d,$filters['year'])) ?>
                        </option>
                    <?php endfor; ?>
                    </select>
                </div>
                </div>
                <div class="col-md-6">
                <div class="mb-3">
                    <label for="end_date" class="form-label">End Date:</label>
                    <select class="form-select" id="end_date" name="end_date">
                    <?php for($d=1; $d<=$daysInMonth; $d++): ?>
                        <option value="<?= sprintf('%02d', $d) ?>" <?= $filters['end_date'] == sprintf('%02d', $d) ? 'selected' : '' ?>>
                        <?= date('M d, Y', mktime(0,0,0,$filters['month'],$d,$filters['year'])) ?>
                        </option>
                    <?php endfor; ?>
                    </select>
                </div>
                </div>
            </div>
            
            <div class="row">
                <div class="col-md-6">
                <div class="mb-3">
                    <label for="start_time" class="form-label">Start Time:</label>
                    <input type="time" class="form-control" id="start_time" name="start_time" value="<?= $filters['start_time'] ?>">
                </div>
                </div>
                <div class="col-md-6">
                <div class="mb-3">
                    <label for="end_time" class="form-label">End Time:</label>
                    <input type="time" class="form-control" id="end_time" name="end_time" value="<?= $filters['end_time'] ?>">
                </div>
                </div>
            </div>
            </div>
            <div class="modal-footer">
            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
            <button type="submit" class="btn btn-primary">Apply Filter</button>
            </div>
        </form>
        </div>
    </div>
    </div>

<!-- Advanced Filter Modal -->
    <div class="modal fade" id="filterModal" tabindex="-1" aria-labelledby="filterModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
        <div class="modal-header bg-primary text-white">
            <h5 class="modal-title" id="filterModalLabel">Advanced Filters</h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <form action="" method="GET">
            <div class="modal-body">
            <div class="row">
                <div class="col-md-6">
                <div class="mb-3">
                    <label for="month" class="form-label">Month:</label>
                    <select class="form-select" id="month" name="month">
                    <?php for($m=1; $m<=12; $m++): ?>
                        <option value="<?= $m ?>" <?= $filters['month'] == $m ? 'selected' : '' ?>>
                        <?= date('F', mktime(0,0,0,$m,1)) ?>
                        </option>
                    <?php endfor; ?>
                    </select>
                </div>
                </div>
                <div class="col-md-6">
                <div class="mb-3">
                    <label for="year" class="form-label">Year:</label>
                    <select class="form-select" id="year" name="year">
                    <?php 
                    $currentYear = date('Y');
                    for($y = $currentYear-2; $y <= $currentYear; $y++): 
                    ?>
                        <option value="<?= $y ?>" <?= $filters['year'] == $y ? 'selected' : '' ?>>
                        <?= $y ?>
                        </option>
                    <?php endfor; ?>
                    </select>
                </div>
                </div>
            </div>
            
            <div class="row">
                <div class="col-md-6">
                <div class="mb-3">
                    <label for="location" class="form-label">Location:</label>
                    <select class="form-select" id="location" name="location">
                    <option value="">All Locations</option>
                    <?php foreach($filterOptions['locations'] as $loc): ?>
                        <option value="<?= htmlspecialchars($loc) ?>" <?= $filters['location'] == $loc ? 'selected' : '' ?>>
                        <?= htmlspecialchars($loc) ?>
                        </option>
                    <?php endforeach; ?>
                    </select>
                </div>
                </div>
                
                <div class="col-md-6">
                <div class="mb-3">
                    <label for="category" class="form-label">Category:</label>
                    <select class="form-select" id="category" name="category">
                    <option value="">All Categories</option>
                    <?php foreach($filterOptions['categories'] as $cat): ?>
                        <option value="<?= htmlspecialchars($cat) ?>" <?= $filters['category'] == $cat ? 'selected' : '' ?>>
                        <?= htmlspecialchars($cat) ?>
                        </option>
                    <?php endforeach; ?>
                    </select>
                </div>
                </div>
            </div>
            
            <div class="mb-3">
                <label for="ip_id" class="form-label">Specific Device:</label>
                <select class="form-select" id="ip_id" name="ip_id">
                <option value="">All Devices</option>
                <?php foreach($filterOptions['ips'] as $ip): ?>
                    <option value="<?= $ip['id'] ?>" <?= $filters['ip_id'] == $ip['id'] ? 'selected' : '' ?>>
                    <?= htmlspecialchars($ip['ip_address']) ?> - <?= htmlspecialchars($ip['description']) ?>
                    </option>
                <?php endforeach; ?>
                </select>
            </div>
            
            <div class="modal-footer">
            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
            <button type="submit" class="btn btn-primary">Apply Filters</button>
            </div>
        </form>
        </div>
    </div>
    </div>

<!-- JavaScript for DataTables initialization -->
<script>
$(document).ready(function() {
  // Initialize daily stats table
  var dailyTable = $('#dailyStatsTable').DataTable({
    dom: 'Bfrtip',
    buttons: [
      'csv', 'excel', 'pdf', 'print'
    ],
    lengthMenu: [[10, 25, 50, -1], [10, 25, 50, "All"]],
    pageLength: 10,
    ordering: true,
    responsive: true
  });
  
  // Move export buttons to custom div
  dailyTable.buttons().container().appendTo('#dailyExportButtons');
  
  // Initialize device stats table
  var deviceTable = $('#deviceStatsTable').DataTable({
    dom: 'Bfrtip',
    buttons: [
      'csv', 'excel', 'pdf', 'print'
    ],
    lengthMenu: [[10, 25, 50, -1], [10, 25, 50, "All"]],
    pageLength: 10,
    ordering: true,
    responsive: true
  });
  
  // Move export buttons to custom div
  deviceTable.buttons().container().appendTo('#deviceExportButtons');
});
</script>

</main>
</div>
</div>
