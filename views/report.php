<?php include '../includes/header.php'; ?>
<?php include '../backend/report.php'; ?>
<?php include '../backend/monthly_average_report.php'; ?>
<?php
$selectedMonth = date('n');
$selectedYear  = date('Y');
$metrics = getMonthlyAverageData($device_data['id'], $selectedMonth, $selectedYear);
?>

<link rel="stylesheet" href="../css/report.css">
<div class="container-fluid">
  <div class="row">
    <?php include '../includes/sidebar.php'; ?>

    <main class="container-fluid">
      <div class="d-flex justify-content-between flex-wrap 
                  align-items-center pt-3 pb-2 mb-3 border-bottom">
        <div>
          <h1 class="h2">
            Device Report: <?= htmlspecialchars($device_data['description']) ?>
          </h1>
          <p><strong>Location:</strong> <?= htmlspecialchars($device_data['location']) ?></p>
        </div>
        <div class="btn-toolbar mb-2 mb-md-0">
          <a href="daily_report.php?report=<?= $report_id ?>"
             target="_blank" class="btn btn-sm btn-primary ms-2">
            <i class="fas fa-print"></i> Print Summary Monthly report
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

      <!-- Device Overview -->
      <div class="card mb-4">
        <div class="card-header d-flex justify-content-between align-items-center">
          <h5 class="card-title mb-0"><i class="fas fa-server me-2"></i>Device Overview</h5>
        </div>
        <div class="card-body">
          <div class="row g-4">
            <!-- Left table with device details -->
            <div class="col-md-6">
              <table class="table table-bordered table-hover">
                <tr>
                  <th class="table-secondary" width="35%">IP Address:</th>
                  <td class="fw-medium"><?= htmlspecialchars($device_data['ip_address']) ?></td>
                </tr>
                <tr>
                  <th class="table-secondary">Description:</th>
                  <td><?= htmlspecialchars($device_data['description']) ?></td>
                </tr>
                <tr>
                  <th class="table-secondary">Location:</th>
                  <td><?= htmlspecialchars($device_data['location']) ?></td>
                </tr>
                <tr>
                  <th class="table-secondary">Current Status:</th>
                  <td>
                    <?php if ($device_data['status'] === 'online'): ?>
                      <span class="badge bg-success rounded-pill"><i class="fas fa-check-circle me-1"></i>Online</span>
                    <?php else: ?>
                      <span class="badge bg-danger rounded-pill"><i class="fas fa-times-circle me-1"></i>Offline</span>
                    <?php endif; ?>
                  </td>
                </tr>
                <tr>
                  <th class="table-secondary">Current Latency:</th>
                  <td>
                    <div class="d-flex align-items-center">
                      <span class="me-2"><?= htmlspecialchars($device_data['latency']) ?> ms</span>
                      <?php 
                        $lat = (float)$device_data['latency'];
                        if ($lat >= 150) {
                            echo '<span class="badge bg-danger"><i class="fas fa-exclamation-triangle me-1"></i>Critical</span>';
                        } elseif ($lat >= 100) {
                            echo '<span class="badge bg-warning text-dark"><i class="fas fa-exclamation-circle me-1"></i>High</span>';
                        } else {
                            echo '<span class="badge bg-info"><i class="fas fa-info-circle me-1"></i>Good</span>';
                        }
                      ?>
                    </div>
                  </td>
                </tr>
              </table>
            </div>
            
            <!-- Right metrics dashboard -->
            <div class="col-md-6">
                <div class="row g-3">
                  <div class="col-6">
                    <div class="card text-center shadow-sm border-0" style="background: linear-gradient(45deg, #4e73df, #6983e8); border-radius: 10px; max-height: 120px;">
                      <div class="card-body p-2">
                        <i class="fas fa-calendar-alt mb-1" style="color: #ffffff; font-size: 1.2rem;"></i>
                        <small class="d-block text-white">Days Running</small>
                        <h5 class="text-white mb-0"><?= htmlspecialchars($metrics['days_running']) ?></h5>
                      </div>
                    </div>
                  </div>

                  <div class="col-6">
                    <div class="card text-center shadow-sm border-0" style="background: linear-gradient(45deg, #1cc88a, #36e3af); border-radius: 10px; max-height: 120px;">
                      <div class="card-body p-2">
                        <i class="fas fa-tachometer-alt mb-1" style="color: #ffffff; font-size: 1.2rem;"></i>
                        <small class="d-block text-white">Avg Latency</small>
                        <h5 class="text-white mb-0"><?= htmlspecialchars(round($metrics['avg_latency'], 2)) ?> ms</h5>
                      </div>
                    </div>
                  </div>

                  <div class="col-6">
                  <div class="card text-center shadow-sm border-0" style="background: linear-gradient(45deg, #e74a3b, #ef8579); border-radius: 10px; max-height: 120px;">
                    <div class="card-body p-2">
                      <i class="fas fa-clock mb-1" style="color: #ffffff; font-size: 1.2rem;"></i>
                      <small class="d-block text-white">Downtime</small>
                      <h5 class="text-white mb-0">
                        <?php 
                          $minutes = $metrics['downtime_minutes'];
                          $hours = floor($minutes / 60);
                          $remaining_minutes = $minutes % 60;
                          echo htmlspecialchars("$hours h $remaining_minutes m"); 
                        ?>
                      </h5>
                    </div>
                  </div>
                </div>

                  <div class="col-6">
                    <div class="card text-center shadow-sm border-0" style="background: linear-gradient(45deg, #f6c23e, #f9d675); border-radius: 10px; max-height: 120px;">
                      <div class="card-body p-2">
                        <i class="fas fa-bolt mb-1" style="color: #ffffff; font-size: 1.2rem;"></i>
                        <small class="d-block text-white">Max Latency</small>
                        <h5 class="text-white mb-0"><?= htmlspecialchars($metrics['monthly_total']['max_latency']) ?> ms</h5>
                      </div>
                    </div>
                  </div>

                  <div class="col-6">
                    <div class="card text-center shadow-sm border-0" style="background: linear-gradient(45deg, #5a5c69, #7e8089); border-radius: 10px; max-height: 120px;">
                      <div class="card-body p-2">
                        <i class="fas fa-power-off mb-1" style="color: #ffffff; font-size: 1.2rem;"></i>
                        <small class="d-block text-white">Offline Events</small>
                        <h5 class="text-white mb-0"><?= htmlspecialchars($metrics['total_offline_periods']) ?></h5>
                      </div>
                    </div>
                  </div>

                  <div class="col-6">
                    <div class="card text-center shadow-sm border-0" style="background: linear-gradient(45deg, #36b9cc, #5dcfdf); border-radius: 10px; max-height: 120px;">
                      <div class="card-body p-2">
                        <i class="fas fa-chart-line mb-1" style="color: #ffffff; font-size: 1.2rem;"></i>
                        <small class="d-block text-white">Uptime Percentage</small>
                        <h5 class="text-white mb-0"><?= htmlspecialchars(round($metrics['uptime_percent'], 2)) ?>%</h5>
                      </div>
                    </div>
                  </div>
                </div>
              </div>
              </div>
            </div>
          </div>
        </div>

<?php 
    $monthlyData   = getMonthlyAverageData($report_id, $selectedMonth, $selectedYear);
    $monthly_stats = $monthlyData['monthly_stats'];
    $monthly_total = $monthlyData['monthly_total'];
    ?>
    <div class="card mb-4 shadow-sm">
      <div class="card-header d-flex justify-content-between align-items-center">
        <h5 class="card-title mb-0">
          Monthly Average Latency - Working Hours (8am-6pm)
          (<?= date("F Y", mktime(0,0,0,$selectedMonth,1,$selectedYear)) ?>)
        </h5>
        <div class="btn-group">
          <button id="printReportBtn" class="btn btn-sm btn-outline-secondary me-2">
            <i class="bi bi-printer"></i> Print Report
          </button>
          <div id="monthlyExportButtons"></div>
        </div>
      </div>
      <div class="card-body">
        <div class="table-responsive">
          <table id="monthlyAverageTable" class="table table-bordered table-striped mb-0">
            <thead>
              <tr class="table-secondary">
                <th>Date</th>
                <th>Avg Latency</th>
                <th>Min Latency</th>
                <th>Max Latency</th>
                <th>Offline Events</th>
                <th>Total Checks</th>
                <th>Uptime %</th>
                <th>Status</th>
                <th>Remarks</th>
                <th>Action</th>
              </tr>
            </thead>
            <tbody>
              <?php if ($monthly_stats): ?>
                <?php foreach ($monthly_stats as $day): 
                  // status color & text
                  $u = $day['uptime_percent'];
                  if ($u == 100) {
                      $sc = 'bg-success text-white'; $st = 'Excellent';
                  } elseif ($u >= 99.5) {
                      $sc = 'bg-success text-white'; $st = 'Very Good';
                  } elseif ($u >= 95) {
                      $sc = 'bg-warning';        $st = 'Average';
                  } else {
                      $sc = 'bg-danger text-white'; $st = 'Poor';
                  }
                  
                  // Generate a unique ID for this day's row
                  $dayId = 'day-' . str_replace('-', '', $day['log_date']);
                ?>
                <tr id="<?= $dayId ?>">
                  <td><?= date("D, M j, Y", strtotime($day['log_date'])) ?></td>
                  <td>
                    <?= number_format($day['avg_latency'],2) ?> ms
                    <?php
                      if ($day['avg_latency'] >= 150) {
                          echo '<span class="badge bg-danger">Critical</span>';
                      } elseif ($day['avg_latency'] >= 100) {
                          echo '<span class="badge bg-warning">High</span>';
                      } else {
                          echo '<span class="badge bg-info">Good</span>';
                      }
                    ?>
                  </td>
                  <td><?= number_format($day['min_latency'],2) ?> ms</td>
                  <td><?= number_format($day['max_latency'],2) ?> ms</td>
                  <td class="offline-count"><?= $day['adjusted_offline_count'] ?? $day['offline_count'] ?></td>
                  <td><?= $day['total_checks'] ?></td>
                  <td class="uptime-percent"><?= number_format($day['uptime_percent'],2) ?>%</td>
                  <td class="status-cell <?= $sc ?>"><?= $st ?></td>
                  <td>
                    <?php if (!empty($day['remarks'])): ?>
                      <div class="remarks-container" data-date="<?= $day['log_date'] ?>">
                        <?php foreach ($day['remarks'] as $r): 
                          $fromTime = date("h:ia", strtotime($r['from']));
                          $toTime   = date("h:ia", strtotime($r['to']));
                          // generate a unique ID per remark
                          $boxId = 'remark-' . $day['log_date'] . '-' . str_replace(':','',$r['from']);
                          
                          // Check if this remark is already excluded
                          $isExcluded = isset($r['is_excluded']) && $r['is_excluded'] == 1;
                        ?>
                        <div class="remark-item bg-light p-1 mb-1 <?= $isExcluded ? 'text-muted' : '' ?>">
                              <div class="form-check">
                                <input 
                                  class="form-check-input remark-checkbox" 
                                  type="checkbox" 
                                  data-day-id="<?= $dayId ?>"
                                  data-remark-id="<?= $r['id'] ?? '' ?>"
                                  id="<?= $boxId ?>"
                                  <?= $isExcluded ? 'checked disabled' : '' ?>>
                                <label class="form-check-label" for="<?= $boxId ?>">
                                  <span class="remark-time text-muted"> <?= $fromTime ?> – <?= $toTime ?> </span>
                                  <span class="remark-text fw-bold"> <?= htmlspecialchars($r['remark']) ?> </span>
                                  <?php if ($isExcluded): ?>
                                    <span class="badge bg-secondary ms-2">Excluded</span>
                                  <?php endif; ?>
                              </label>
                            </div>
                          </div>
                        <?php endforeach; ?>
                      </div>
                    <?php else: ?>
                      <span class="text-muted">No remarks</span>
                    <?php endif; ?>
                  </td>
                  <td>
                    <?php 
                    $hasUnexcludedRemarks = false;
                    if (!empty($day['remarks'])) {
                      foreach ($day['remarks'] as $r) {
                        if (!(isset($r['is_excluded']) && $r['is_excluded'] == 1)) {
                          $hasUnexcludedRemarks = true;
                          break;
                        }
                      }
                    }
                    
                    // Define hasExcludedRemarks variable
                    $hasExcludedRemarks = false;
                    if (!empty($day['remarks'])) {
                      foreach ($day['remarks'] as $r) {
                        if (isset($r['is_excluded']) && $r['is_excluded'] == 1) {
                          $hasExcludedRemarks = true;
                          break;
                        }
                      }
                    }
                    
                    if ($hasUnexcludedRemarks): 
                    ?>
                      <button 
                        class="btn btn-sm btn-primary save-remarks" 
                        data-date="<?= $day['log_date'] ?>"
                        data-day-id="<?= $dayId ?>"
                        data-report-id="<?= $report_id ?>">
                        Approve
                      </button>
                    <?php endif; ?>
                    <?php if ($hasExcludedRemarks): ?>
                      <button 
                        class="btn btn-sm btn-warning uncheck-remarks" 
                        data-date="<?= $day['log_date'] ?>"
                        data-day-id="<?= $dayId ?>"
                        data-report-id="<?= $report_id ?>">
                        Revert
                      </button>
                    <?php endif; ?>
                  </td>
                </tr>
                <?php endforeach; ?>

                <!-- Monthly total/average row -->
                <tr class="table-primary fw-bold" id="monthly-total">
                  <td>MONTHLY AVERAGE</td>
                  <td>
                    <?= number_format($monthly_total['avg_latency'],2) ?> ms
                    <?php
                      if ($monthly_total['avg_latency'] >= 150) {
                          echo '<span class="badge bg-danger">Critical</span>';
                      } elseif ($monthly_total['avg_latency'] >= 100) {
                          echo '<span class="badge bg-warning">High</span>';
                      } else {
                          echo '<span class="badge bg-info">Good</span>';
                      }
                    ?>
                  </td>
                  <td><?= number_format($monthly_total['min_latency'],2) ?> ms</td>
                  <td><?= number_format($monthly_total['max_latency'],2) ?> ms</td>
                  <td id="total-offline"><?= $monthly_total['total_offline_count'] ?></td>
                  <td><?= $monthly_total['total_checks'] ?></td>
                  <td id="total-uptime"><?= number_format($monthly_total['avg_uptime_percent'],2) ?>%</td>
                  <td class="<?php 
                    $u2 = $monthly_total['avg_uptime_percent'];
                    if ($u2 == 100) {
                        echo 'bg-success text-white'; $st2 = 'Excellent';
                    } elseif ($u2 >= 99.5) {
                        echo 'bg-success text-white'; $st2 = 'Very Good';
                    } elseif ($u2 >= 95) {
                        echo 'bg-warning';          $st2 = 'Average';
                    } else {
                        echo 'bg-danger text-white'; $st2 = 'Poor';
                    }
                  ?>" id="total-status">
                    <?= $st2 ?>
                  </td>
                  <td>
                    <?php if (!empty($monthly_total['remarks'])): ?>
                      <div class="remarks-container">
                        <?php foreach ($monthly_total['remarks'] as $r): 
                          $fromTime = date("h:ia", strtotime($r['from']));
                          $toTime   = date("h:ia", strtotime($r['to']));
                          $boxId = 'remark-monthly-' . str_replace(':','',$r['from']);
                        ?>
                          <div class="remark-item bg-light p-1 mb-1">
                            <div class="form-check">
                              <input 
                                class="form-check-input" 
                                type="checkbox" 
                                id="<?= $boxId ?>">
                              <label class="form-check-label" for="<?= $boxId ?>">
                                <span class="remark-time text-muted">
                                  <?= $fromTime ?> – <?= $toTime ?>
                                </span>
                                <span class="remark-text fw-bold">
                                  <?= htmlspecialchars($r['remark']) ?>
                                </span>
                              </label>
                            </div>
                          </div>
                        <?php endforeach; ?>
                      </div>
                    <?php else: ?>
                      <span class="text-muted">No remarks</span>
                    <?php endif; ?>
                  </td>
                  <td></td>
                </tr>

              <?php else: ?>
                <tr>
                  <td colspan="10" class="text-center">No historical data available</td>
                </tr>
              <?php endif; ?>
            </tbody>
          </table>
        </div>
      </div>
    </div>

    <script src="../js/remarks.js"> </script>

<!-- Monthly Latency section with enhanced filtering options -->
    <div class="card mb-4 shadow-sm">
      <div class="card-header d-flex justify-content-between align-items-center">
        <h5 class="card-title mb-0">
          <?php
          $titleParts = [];
          $titleParts[] = "Monthly Latency";
          $titleParts[] = date('F', mktime(0,0,0,$selectedMonth,1));
          $titleParts[] = $selectedYear;
          
          switch ($viewMode) {
              case 'day': 
                  $titleParts[] = "(Day " . $singleDay . ")"; 
                  break;
              case 'date_range': 
                  $titleParts[] = "(Days " . $startDate . "-" . $endDate . ")"; 
                  break;
              case 'time_only': 
                  $titleParts[] = "(Time " . $startTime . "-" . $endTime . ")"; 
                  break;
              case 'custom': 
                  $titleParts[] = "(Days " . $startDate . "-" . $endDate . ", Time " . $startTime . "-" . $endTime . ")"; 
                  break;
              case 'all': 
                  $titleParts[] = "(All Data Mon-Sat)"; 
                  break;
          }
          echo implode(' ', $titleParts);
          ?>
        </h5>
        <div id="exportButtons"></div>
      </div>
      <div class="card-body">
        <!-- Quick view buttons -->
        <div class="mb-3">
          <div class="btn-group">
            <a href="?report=<?= $report_id ?>&month=<?= $selectedMonth ?>&view_mode=all" class="btn btn-sm <?= $viewMode == 'all' ? 'btn-primary' : 'btn-outline-primary' ?>">
              <i class="fas fa-calendar-alt"></i> All <?= date('F', mktime(0,0,0,$selectedMonth,1)) ?> Data
            </a>
            <button type="button" class="btn btn-sm <?= $viewMode == 'day' ? 'btn-primary' : 'btn-outline-primary' ?>" data-bs-toggle="modal" data-bs-target="#dayModal">
              <i class="fas fa-calendar-day"></i> Single Day View
            </button>
            <button type="button" class="btn btn-sm <?= $viewMode == 'time_only' ? 'btn-primary' : 'btn-outline-primary' ?>" data-bs-toggle="modal" data-bs-target="#timeModal">
              <i class="fas fa-clock"></i> Time Range View
            </button>
            <button type="button" class="btn btn-sm <?= $viewMode == 'custom' ? 'btn-primary' : 'btn-outline-primary' ?>" data-bs-toggle="modal" data-bs-target="#customFilterModal">
              <i class="fas fa-filter"></i> Custom Filters
            </button>
          </div>
        </div>

        <div class="table-responsive" style="max-height:400px; overflow:auto;">
          <table id="monthlyLogsTable"
                class="table table-bordered table-striped table-sm mb-0">
            <thead class="table-secondary text-center">
              <tr>
                <th>Date</th>
                <th>Time</th>
                <th>Latency (ms)</th>
                <th>Status</th>
                <th>IP Address</th>
                <th>Location</th>
                <th>Category</th>
                <th>Description</th>
              </tr>
            </thead>
            <tbody class="text-center">
              <?php if (!empty($monthly_logs)): ?>
                <?php foreach($monthly_logs as $log): ?>
                  <tr>
                    <td><?= date("F j, Y", strtotime($log['created_at'])) ?></td>
                    <td><?= date("h:i A", strtotime($log['created_at'])) ?></td>
                    <td><?= htmlspecialchars($log['latency']) ?></td>
                    <td>
                      <span class="badge <?= $log['status'] === 'offline' ? 'bg-danger' : 'bg-success' ?>">
                        <?= ucfirst($log['status']) ?>
                      </span>
                    </td>
                    <td><?= htmlspecialchars($log['ip_address']) ?></td>
                    <td><?= htmlspecialchars($log['location']) ?></td>
                    <td><?= htmlspecialchars($log['category']) ?></td>
                    <td><?= htmlspecialchars($log['description']) ?></td>
                  </tr>
                <?php endforeach; ?>
              <?php else: ?>
                <tr>
                  <td colspan="8" class="text-center">
                    No logs found for the selected filters.
                  </td>
                </tr>
              <?php endif; ?>
            </tbody>
          </table>
        </div>
      </div>
    </div>

    </main>
  </div>
</div>

<!-- Day View Modal -->
<div class="modal fade" id="dayModal" tabindex="-1" aria-labelledby="dayModalLabel" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="dayModalLabel">Select Day</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <form method="get">
        <input type="hidden" name="report" value="<?= $report_id ?>">
        <input type="hidden" name="month" value="<?= $selectedMonth ?>">
        <input type="hidden" name="view_mode" value="day">
        <div class="modal-body">
          <div class="mb-3">
            <label for="single_day" class="form-label">Day of Month</label>
            <input type="number" class="form-control" id="single_day" name="single_day" min="1" max="31" value="<?= $singleDay ?>" required>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
          <button type="submit" class="btn btn-primary">View Day</button>
        </div>
      </form>
    </div>
  </div>
</div>

<!-- Time Range Modal -->
<div class="modal fade" id="timeModal" tabindex="-1" aria-labelledby="timeModalLabel" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="timeModalLabel">Select Time Range</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <form method="get">
        <input type="hidden" name="report" value="<?= $report_id ?>">
        <input type="hidden" name="month" value="<?= $selectedMonth ?>">
        <input type="hidden" name="view_mode" value="time_only">
        <div class="modal-body">
          <div class="row">
            <div class="col-6">
              <label for="start_time" class="form-label">Start Time</label>
              <input type="time" class="form-control" id="start_time" name="start_time" value="<?= $startTime ?>" required>
            </div>
            <div class="col-6">
              <label for="end_time" class="form-label">End Time</label>
              <input type="time" class="form-control" id="end_time" name="end_time" value="<?= $endTime ?>" required>
            </div>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
          <button type="submit" class="btn btn-primary">Apply Time Filter</button>
        </div>
      </form>
    </div>
  </div>
</div>

<!-- Custom Filter Modal -->
<div class="modal fade" id="customFilterModal" tabindex="-1" aria-labelledby="customFilterModalLabel" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="customFilterModalLabel">Custom Filters</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <form method="get">
        <input type="hidden" name="report" value="<?= $report_id ?>">
        <input type="hidden" name="view_mode" value="custom">
        <div class="modal-body">
          <div class="mb-3">
            <label class="form-label">Month</label>
            <select name="month" class="form-select">
              <?php for($m=1; $m<=12; $m++): ?>
                <option value="<?= $m ?>" <?= $m === $selectedMonth ? 'selected' : '' ?>>
                  <?= date('F', mktime(0,0,0,$m,1)) ?>
                </option>
              <?php endfor; ?>
            </select>
          </div>
          
          <div class="mb-3">
            <label class="form-label">Date Range</label>
            <div class="row">
              <div class="col-6">
                <input type="number" name="start_date" min="1" max="31" class="form-control" placeholder="From" value="<?= $startDate ?>" required>
              </div>
              <div class="col-6">
                <input type="number" name="end_date" min="1" max="31" class="form-control" placeholder="To" value="<?= $endDate ?>" required>
              </div>
            </div>
          </div>
          
          <div class="mb-3">
            <label class="form-label">Time Range</label>
            <div class="row">
              <div class="col-6">
                <input type="time" name="start_time" class="form-control" value="<?= $startTime ?>" required>
              </div>
              <div class="col-6">
                <input type="time" name="end_time" class="form-control" value="<?= $endTime ?>" required>
              </div>
            </div>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
          <button type="submit" class="btn btn-primary">Apply Filters</button>
        </div>
      </form>
    </div>
  </div>
</div>

<script>
$(document).ready(function() {
    // Initialize DataTable for monthly logs
    var logsTable = $('#monthlyLogsTable');
    
    // Check if the table has data rows (excluding the "No logs found" message row)
    var hasLogsData = logsTable.find('tbody tr').length > 0 && 
                 !(logsTable.find('tbody tr').length === 1 && 
                   logsTable.find('tbody tr td[colspan="8"]').length === 1);
    
    if (hasLogsData) {
        logsTable.DataTable({
            dom: 'Bt',  // Remove pagination and search (only buttons and table)
            buttons: [
                {
                    extend: 'excelHtml5',
                    text: '<i class="fas fa-file-excel"></i> Excel',
                    className: 'btn btn-sm btn-success',
                    title: 'Monthly Logs <?= date('F', mktime(0,0,0,$selectedMonth,1)) ?> <?= $selectedYear ?>'
                },
                {
                    extend: 'pdfHtml5',
                    text: '<i class="fas fa-file-pdf"></i> PDF',
                    className: 'btn btn-sm btn-danger',
                    title: 'Monthly Logs <?= date('F', mktime(0,0,0,$selectedMonth,1)) ?> <?= $selectedYear ?>'
                },
                {
                    extend: 'print',
                    text: '<i class="fas fa-print"></i> Print',
                    className: 'btn btn-sm btn-primary',
                    title: 'Monthly Logs <?= date('F', mktime(0,0,0,$selectedMonth,1)) ?> <?= $selectedYear ?>'
                }
            ],
            paging: false,  // Disable pagination
            searching: false,  // Disable search bar
            info: false,  // Remove table info
            ordering: false  // Disable column sorting
        });
        
        $('#monthlyLogsTable_wrapper .dt-buttons').appendTo('#exportButtons');
        $('#exportButtons').addClass('btn-group');
    } else {
        $('#exportButtons').html('<span class="text-muted">No data to export</span>');
    }
    
    // Initialize DataTable for monthly average
    var averageTable = $('#monthlyAverageTable');
    var hasAverageData = averageTable.find('tbody tr').length > 0 && 
                 !(averageTable.find('tbody tr').length === 1 && 
                   averageTable.find('tbody tr td[colspan="8"]').length === 1);
    
    if (hasAverageData) {
        averageTable.DataTable({
            dom: 'Bt',  // Remove pagination and search (only buttons and table)
            buttons: [
                {
                    extend: 'excelHtml5',
                    text: '<i class="fas fa-file-excel"></i> Excel',
                    className: 'btn btn-sm btn-success',
                    title: 'Monthly Average Latency <?= date('F', mktime(0,0,0,$selectedMonth,1)) ?> <?= $selectedYear ?>'
                },
                {
                  extend: 'pdfHtml5',
                    text: '<i class="fas fa-file-pdf"></i> PDF',
                    className: 'btn btn-sm btn-danger',
                    title: 'Monthly Average Latency <?= date('F', mktime(0,0,0,$selectedMonth,1)) ?> <?= $selectedYear ?>',
                    orientation: 'landscape',
                    pageSize: 'A4',
                    customize: function(doc) {
                        // Add custom styling to the monthly totals row
                        doc.content[1].table.body.forEach(function(row, rowIndex) {
                            if (rowIndex === doc.content[1].table.body.length - 1) {
                                row.forEach(function(cell, cellIndex) {
                                    cell.fillColor = '#cfe2ff';
                                    cell.bold = true;
                                });
                            }
                        });
                    }
                },
                {
                    extend: 'print',
                    text: '<i class="fas fa-print"></i> Print',
                    className: 'btn btn-sm btn-primary',
                    title: 'Monthly Average Latency <?= date('F', mktime(0,0,0,$selectedMonth,1)) ?> <?= $selectedYear ?>',
                    customize: function(win) {
                        $(win.document.body).find('table tr:last-child').css({
                            'background-color': '#cfe2ff',
                            'font-weight': 'bold'
                        });
                    }
                }
            ],
            paging: false,  // Disable pagination
            searching: false,  // Disable search bar
            info: false,  // Remove table info
            ordering: false  // Disable column sorting
        });
        
        $('#monthlyAverageTable_wrapper .dt-buttons').appendTo('#monthlyExportButtons');
        $('#monthlyExportButtons').addClass('btn-group');
    } else {
        $('#monthlyExportButtons').html('<span class="text-muted">No data to export</span>');
    }
});
</script>