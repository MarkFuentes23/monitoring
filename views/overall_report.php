<?php
// report.php (top of file)
include '../includes/header.php';
require_once '../backend/overall_report.php';
require_once '../backend/overall_reporting.php';

// First get month and year to avoid circular reference
$currentMonth = isset($_GET['month']) ? (int)$_GET['month'] : date('n');
$currentYear  = isset($_GET['year'])  ? (int)$_GET['year']  : date('Y');

// Get filter parameters from URL, including weekdays for custom view
$filters = [
    'month'      => $currentMonth,
    'year'       => $currentYear,
    'view_mode'  => $_GET['view_mode']  ?? 'custom',
    'start_date' => $_GET['start_date'] ?? '01',
    'end_date'   => $_GET['end_date']   ?? date('t', mktime(0,0,0,$currentMonth,1,$currentYear)),
    'start_time' => $_GET['start_time'] ?? '00:00',
    'end_time'   => $_GET['end_time']   ?? '23:59',
    'location'   => $_GET['location']   ?? '',
    'category'   => $_GET['category']   ?? '',
    'status'     => $_GET['status']     ?? '',
    'ip_id'      => $_GET['ip_id']      ?? '',
    'weekdays'   => $_GET['weekdays']   ?? ['1','2','3','4','5','6'], // Mon-Sat default
];

// Ensure weekdays is always an array
if (!is_array($filters['weekdays'])) {
    $filters['weekdays'] = [$filters['weekdays']]; 
}

try {
  // Locations
  $locStmt = $conn->prepare("SELECT DISTINCT location FROM add_ip WHERE location <> ''");
  $locStmt->execute();
  $filterOptions['locations'] = $locStmt->fetchAll(PDO::FETCH_COLUMN);

  // Categories
  $catStmt = $conn->prepare("SELECT DISTINCT category FROM add_ip WHERE category <> ''");
  $catStmt->execute();
  $filterOptions['categories'] = $catStmt->fetchAll(PDO::FETCH_COLUMN);

  // Devices
  $ipStmt = $conn->prepare("SELECT id, ip_address, description FROM add_ip ORDER BY ip_address");
  $ipStmt->execute();
  $filterOptions['ips'] = $ipStmt->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
  $filterOptions = [
    'locations'  => [],
    'categories' => [],
    'ips'        => [],
  ];
}

// Fetch data with all filters applied - these should respect all custom filter settings
$monitoringData = getGlobalMonitoringData($filters);
$dailyStats     = getGlobalDailyStats($filters);
$deviceStats    = calculateOverallMonthlyStats($filters);
$summary        = $dailyStats['monthly_total'];

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
            <?php if ($filters['view_mode'] == 'custom'): ?>
              | <strong>Custom Range:</strong> 
              <?= sprintf('%02d', $filters['start_date']) ?> - <?= sprintf('%02d', $filters['end_date']) ?> 
              (<?= $filters['start_time'] ?> - <?= $filters['end_time'] ?>)
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
                      <h3 class="text-white"><?= $totalDevices ?></h3>
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
                        
            <!-- Custom Filter Button -->
        <div class="col-md-auto">
          <button type="button" class="btn btn-sm <?= $filters['view_mode'] == 'custom' && 
                 ($filters['start_date'] != '01' || 
                  $filters['end_date'] != date('t', mktime(0,0,0,$filters['month'],1,$filters['year'])) || 
                  $filters['start_time'] != '00:00' || 
                  $filters['end_time'] != '23:59' || 
                  !in_array('0', $filters['weekdays']) || 
                  !in_array('1', $filters['weekdays']) || 
                  !in_array('2', $filters['weekdays']) ||
                  !in_array('3', $filters['weekdays']) ||
                  !in_array('4', $filters['weekdays']) ||
                  !in_array('5', $filters['weekdays']) ||
                  !in_array('6', $filters['weekdays'])) ? 'btn-success' : 'btn-outline-success' ?>" 
                  data-bs-toggle="modal" data-bs-target="#customFilterModal">
            <i class="fas fa-filter"></i> Custom Filter
          </button>
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
    <!-- always just month + year -->
    <h5 id="reportTitle" class="card-title mb-0">
      Monthly Report – <?= date("F Y", mktime(0,0,0,$filters['month'],1,$filters['year'])) ?>
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
            <th>Days Pinged</th>
            <th>Total Offline</th>
            <th>Avg Latency (ms)</th>
            <th>Monthly Avg Uptime %</th>
            <th>Status</th>
            <th>Offline Remarks</th>
          </tr>
        </thead>
        <tbody class="small">
          <?php foreach($dailyStats['ip_monthly_stats'] as $device): 
            // build the clickable URL using the numeric id
            $href = 'report.php?report=' . (int)$device['id'];

            // fetch offline remarks as before
            $remarks_query = "SELECT * FROM offline_remarks 
                              WHERE ip_address = :ip_address 
                              ORDER BY date_from DESC";
            $stmt = $conn->prepare($remarks_query);
            $stmt->bindParam(':ip_address', $device['ip_address'], PDO::PARAM_STR);
            $stmt->execute();
            $remarks = $stmt->fetchAll(PDO::FETCH_ASSOC);
          ?>
            <tr data-href="<?= $href ?>">
              <td><?= htmlspecialchars($device['ip_address']) ?></td>
              <td><?= htmlspecialchars($device['description']) ?></td>
              <td><?= htmlspecialchars($device['location']) ?></td>
              <td><?= htmlspecialchars($device['category']) ?></td>
              <td><?= $device['days_with_data'] ?></td>
              <td><?= $device['total_offline'] ?></td>
              <td><?= number_format($device['monthly_avg_latency'], 2) ?></td>
              <td class="<?= $device['monthly_status_class'] ?>">
                <?= number_format($device['monthly_uptime_percent'], 2) ?>%
              </td>
              <td>
                <span class="badge <?= $device['monthly_status_class'] ?>">
                  <?= $device['monthly_status_text'] ?>
                </span>
              </td>
              <td>
                <?php if (count($remarks) > 0): ?>
                  <div class="remarks-container">
                    <?php foreach($remarks as $remark): ?>
                      <div class="offline-remark mb-2">
                        <div class="d-flex align-items-start">
                          <div>
                            <div class="remarks" style="font-size: 10px;">
                              <?= date('M d g:ia', strtotime($remark['date_from'])) ?>
                               – <?= date('g:ia', strtotime($remark['date_to'])) ?>
                            </div>
                            <div class="fw-bold" style="font-size: 10px;">
                              <?= htmlspecialchars($remark['remarks']) ?>
                            </div>
                          </div>
                        </div>
                      </div>
                    <?php endforeach; ?>
                  </div>
                <?php else: ?>
                  <span class="text-muted">No remarks</span>
                <?php endif; ?>
              </td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>
</div>

<!-- Add to your CSS file or within a <style> block -->
<style>
  #dailyStatsTable tbody tr {
    cursor: pointer;
  }
  #dailyStatsTable tbody tr:hover {
    background-color: #f8f9fa;
  }
</style>

<!-- Add this script just before </body> -->
<script>
  document.addEventListener('DOMContentLoaded', function() {
    document.querySelectorAll('#dailyStatsTable tbody tr[data-href]')
      .forEach(function(row) {
        row.addEventListener('click', function() {
          window.location.href = this.dataset.href;
        });
      });
  });
</script>


<style>
  .small {
    font-size: 0.8rem;
  }
  
  .remarks-container {
    max-height: 120px;
    overflow-y: auto;
    min-width: 180px;
  }
  
  .offline-remark {
    padding: 4px 6px;
    border-left: 3px solid #6c757d;
    background-color: #f8f9fa;
    font-size: 0.75rem;
  }
  
  #dailyStatsTable {
    font-size: 0.75rem;
  }
  
  #dailyStatsTable th {
    white-space: nowrap;
    font-size: 0.75rem;
  }
  
  #dailyStatsTable td {
    font-size: 0.75rem;
    padding: 0.4rem;
  }
  
  .badge {
    font-size: 0.7rem;
  }
</style>

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
                <?php if($key != 'view_mode' && $key != 'start_date' && $key != 'end_date' && $key != 'start_time' && $key != 'end_time' && $key != 'weekdays'): ?>
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
            
            <div class="mb-3">
                <label class="form-label">Days of Week:</label>
                <div class="d-flex flex-wrap">
                    <?php 
                    $weekdays = is_array($filters['weekdays']) ? $filters['weekdays'] : [$filters['weekdays']];
                    $dayNames = [
                        '1' => 'Monday',
                        '2' => 'Tuesday',
                        '3' => 'Wednesday',
                        '4' => 'Thursday',
                        '5' => 'Friday',
                        '6' => 'Saturday',
                        '0' => 'Sunday'
                    ];
                    
                    foreach($dayNames as $dayValue => $dayName): 
                    ?>
                    <div class="form-check me-3">
                        <input class="form-check-input" type="checkbox" value="<?= $dayValue ?>" 
                               name="weekdays[]" id="weekday<?= $dayValue ?>"
                               <?= in_array($dayValue, $weekdays) ? 'checked' : '' ?>>
                        <label class="form-check-label" for="weekday<?= $dayValue ?>">
                            <?= $dayName ?>
                        </label>
                    </div>
                    <?php endforeach; ?>
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

    <script>
$(document).ready(function() {
  // build your month-year string in PHP and reuse it in JS
  var monthYearText = 'Monthly Report – <?= date("F Y", mktime(0,0,0,$filters['month'],1,$filters['year'])) ?>';
  
  // Set white font color for the month-year text
  $('#reportTitle').css('color', '#fff');

  // override <title> so DataTables print picks it up (just in case)
  document.title = monthYearText;

  // also ensure the on‐screen header matches
  $('#reportTitle').css('color', '#fff');

  // Style the table container
  $('#dailyStatsTable').addClass('table-striped table-hover');
  $('.dataTables_wrapper').addClass('p-3 bg-white rounded');

  // initialize DataTable with custom print button
  var dailyTable = $('#dailyStatsTable').DataTable({
    dom: 'Bfrtip',
    buttons: [
      'csv',
      'excel',
      'pdf',
      {
        extend: 'print',
        title: monthYearText,  // use only month-year
        messageTop: '',        // no extra message
        customize: function (win) {
          // Enhance print styling
          $(win.document.body).css({
            'font-size': '10pt',
            'font-family': 'Arial, Helvetica, sans-serif'
          });
          
          $(win.document.body).find('table')
            .addClass('compact')
            .css({
              'font-size': 'inherit',
              'border-collapse': 'collapse',
              'width': '100%'
            });
            
          $(win.document.body).find('table th')
            .css({
              'background-color': '#f5f5f5',
              'color': '#333',
              'border-bottom': '2px solid #ddd',
              'padding': '8px'
            });
            
          $(win.document.body).find('table td')
            .css({
              'border-bottom': '1px solid #ddd',
              'padding': '8px'
            });
            
          $(win.document.body).find('h1')
            .css({
              'text-align': 'center',
              'margin-bottom': '20px',
              'color': '#333'
            });
            
          // Add print-specific styles
          $(win.document.head).append('<style>@media print { @page { size: landscape; margin: 1cm; } }</style>');
        }
      }
    ],
    lengthMenu: [[10, 25, 50, -1], [10, 25, 50, "All"]],
    pageLength: 10,
    ordering: true,
    responsive: true
  });

  // Style the existing buttons without changing functionality
  setTimeout(function() {
    $('.dt-button').addClass('btn btn-sm mx-1');
    $('.buttons-csv').addClass('btn-outline-secondary');
    $('.buttons-excel').addClass('btn-outline-success');
    $('.buttons-pdf').addClass('btn-outline-danger');
    $('.buttons-print').addClass('btn-outline-primary');
  }, 0);

  // move the buttons into your header area
  dailyTable.buttons().container().appendTo('#dailyExportButtons');
});
</script>