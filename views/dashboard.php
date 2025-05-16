<?php include '../backend/dashboard.php'; ?>
<?php include '../backend/monthly_stats.php'; ?>
<?php include '../includes/header.php'; ?>


<link rel="stylesheet" href="../css/report.css">
<style>
    #remarksModal .modal-body label,
  #remarksModal .modal-body input,
  #remarksModal .modal-body textarea,
  #remarksModal .modal-footer button {
    font-size: 0.75rem; /* adjust ayon sa gusto niyo */
  } 
</style>

<div class="container">
        <div class="row">
            <?php include '../includes/sidebar.php'; ?>
            <?php include '../includes/loader.php'; ?>
            <div class="col-md">
                <div class="container">
                    <div class="dashboard-header d-flex justify-content-between align-items-center">
                        <h4 class="mb-0">Network Monitoring Dashboard</h4>
                        <button id="refreshBtn" class="btn btn-refresh px-4 py-2">
                            <i class="bi bi-arrow-clockwise me-2"></i> Refresh Data
                        </button>
                    </div>

                    <!-- Summary Cards -->
                    <div class="dashboard-summary">
                    <div class="summary-row">

                        <div class="summary-card total-card">
                        <div class="card-content">
                            <div class="card-info">
                            <h5 class="card-label">TOTAL IP ADDRESSES</h5>
                            <!-- added class="total" -->
                            <h2 class="card-value total"><?= $summaryData['total'] ?></h2>
                            </div>
                            <div class="card-icon total-icon">
                            <i class="fas fa-network-wired"></i>
                            </div>
                        </div>
                        </div>

                        <div class="summary-card online-card">
                        <div class="card-content">
                            <div class="card-info">
                            <h5 class="card-label">ONLINE DEVICES</h5>
                            <h2 class="card-value online"><?= $summaryData['online'] ?></h2>
                            </div>
                            <div class="card-icon online-icon">
                            <i class="fas fa-signal"></i>
                            </div>
                        </div>
                        </div>

                        <div class="summary-card offline-card">
                        <div class="card-content">
                            <div class="card-info">
                            <h5 class="card-label">OFFLINE DEVICES</h5>
                            <h2 class="card-value offline"><?= $summaryData['offline'] ?></h2>
                            </div>
                            <div class="card-icon offline-icon">
                            <i class="fas fa-power-off"></i>
                            </div>
                        </div>
                        </div>

                    </div>
                    </div>


                    <div class="row">
    <!-- Page Navigation Tabs -->
                    <div class="col-12 mb-3">
                        <ul class="nav nav-tabs" id="deviceTabs" role="tablist">
                            <li class="nav-item" role="presentation">
                                <button class="nav-link active" id="offline-tab" data-bs-toggle="tab" data-bs-target="#offline" type="button" role="tab" aria-controls="offline" aria-selected="true">
                                    <i class="bi bi-exclamation-triangle-fill me-2"></i>Offline Devices
                                </button>
                            </li>
                            <li class="nav-item" role="presentation">
                                <button class="nav-link" id="history-tab" data-bs-toggle="tab" data-bs-target="#history" type="button" role="tab" aria-controls="history" aria-selected="false">
                                    <i class="bi bi-clock-history me-2"></i>Offline History
                                </button>
                            </li>
                        </ul>
                    </div>

                    <!-- Tab content -->
                    <div class="tab-content" id="deviceTabsContent">
                        <!-- Offline Devices Tab -->
                        <div class="tab-pane fade show active" id="offline" role="tabpanel" aria-labelledby="offline-tab">
                            <div class="row">
                                <!-- Offline Devices Table -->
                                <div class="col-lg-6">
                                    <div class="card">
                                        <div class="card-header bg-dark text-white">
                                            <i class="bi bi-exclamation-triangle-fill me-2"></i> Offline Devices
                                        </div>
                                        <div class="card-body table-container">
                                        <table class="table table-hover" id="offlineTable">
                                            <thead>
                                                <tr>
                                                <th>IP Address</th>
                                                <th>Location</th>
                                                <th>Category</th>
                                                <th>Description</th>
                                                <th>Status</th>
                                                <th>Action</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php if(count($offlineDevices) > 0): ?>
                                                <?php foreach($offlineDevices as $device): ?>
                                                <tr>
                                                    <td><?= htmlspecialchars($device['ip_address']) ?></td>
                                                    <td><?= htmlspecialchars($device['location']) ?></td>
                                                    <td><?= htmlspecialchars($device['category']) ?></td>
                                                    <td><?= htmlspecialchars($device['description']) ?></td>
                                                    <td><span class="badge bg-danger badge-sm">Offline</span></td>
                                                    <td>
                                                        <a href="report.php?report=<?= $device['id'] ?>" class="btn btn-primary" style="font-size:0.55rem; padding:4rem 0.6rem; line-height:1;">View</a>
                                                    </td>
                                                </tr>
                                                <?php endforeach; ?>
                                                <?php endif; ?>
                                            </tbody>
                                            </table>
                                        </div>
                                    </div>
                                </div>

                                <!-- High Latency Devices Table -->
                                <div class="col-lg-6">
                                    <div class="card">
                                        <div class="card-header bg-dark text-white">
                                            <i class="bi bi-speedometer me-2"></i> Latency Overview
                                        </div>
                                        <div class="card-body table-container">
                                            <table class="table table-hover" id="latencyTable">
                                                <thead>
                                                    <tr>
                                                        <th>IP Address</th>
                                                        <th>Location</th>
                                                        <th>Category</th>
                                                        <th>Description</th>
                                                        <th>Latency</th>
                                                        <th>Action</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    <?php 
                                                    $devices = getHighLatencyDevices($conn);
                                                    if (count($devices) > 0):
                                                        foreach ($devices as $device):
                                                            $isHigh     = $device['latency'] > 100;
                                                            $rowClass   = $isHigh ? 'high-latency' : 'low-latency';
                                                            $badgeClass = $isHigh ? 'bg-danger'    : 'bg-success';
                                                            $status     = $isHigh ? 'High'         : 'Low';
                                                    ?>
                                                    <tr class="<?= $rowClass ?>">
                                                        <td><?= htmlspecialchars($device['ip_address'], ENT_QUOTES) ?></td>
                                                        <td><?= htmlspecialchars($device['location'],   ENT_QUOTES) ?></td>
                                                        <td><?= htmlspecialchars($device['category'],   ENT_QUOTES) ?></td>
                                                        <td><?= htmlspecialchars($device['description'],ENT_QUOTES) ?></td>
                                                        <td class="d-flex justify-content-between align-items-center">
                                                            <span><?= htmlspecialchars($device['latency'], ENT_QUOTES) ?> ms</span>
                                                            <span class="badge <?= $badgeClass ?> badge-sm">
                                                                <?= $status ?>
                                                            </span>
                                                        </td>
                                                        <td>
                                                            <a href="report.php?report=<?= $device['id'] ?>" class="btn btn-primary" style="font-size:0.55rem; padding:.4rem 0.5rem; line-height:1;">View</a>
                                                        </td>
                                                    </tr>
                                                    <?php 
                                                        endforeach;
                                                    ?>

                                                    <?php endif; ?>
                                                </tbody>
                                            </table>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="tab-pane fade" id="history" role="tabpanel" aria-labelledby="history-tab">
                            <div class="col-lg-12">
                                <div class="card shadow-sm border-0">
                                    <div class="card-header bg-gradient-dark text-white py-2">
                                        <i class="bi bi-clock-history me-2"></i> Business Hours Offline History (8:00am - 6:00pm)
                                    </div>
                                    <div class="card-body table-container">
                                        <table class="table table-hover table-sm" id="historyTable">
                                            <thead class="table-light">
                                                <tr class="text-secondary">
                                                    <th class="fw-medium fs-7">IP Address</th>
                                                    <th class="fw-medium fs-7">Description</th>
                                                    <th class="fw-medium fs-7">Location</th>
                                                    <th class="fw-medium fs-7">Category</th>
                                                    <th class="fw-medium fs-7">Duration Offline</th>
                                                    <th class="fw-medium fs-7">Status</th>
                                                    <th class="fw-medium fs-7">Action</th>
                                                </tr>
                                            </thead>
                                            <tbody class="fs-7">
                                                <?php
                                                // First, get all IPs that have offline status
                                                $query = "
                                                    SELECT DISTINCT ip.id, ip.ip_address, ip.description, ip.location, ip.category
                                                    FROM add_ip ip
                                                    JOIN ping_logs pl ON ip.id = pl.ip_id
                                                    WHERE pl.status = 'offline'
                                                    ORDER BY ip.id
                                                ";
                                                
                                                $stmt = $conn->prepare($query);
                                                $stmt->execute();
                                                $offlineIPs = $stmt->fetchAll(PDO::FETCH_ASSOC);
                                                
                                                // Process each IP to find valid offline periods
                                                foreach ($offlineIPs as $ip) {
                                                    // Get all ping logs for this IP
                                                    $logsQuery = "
                                                        SELECT id, status, created_at
                                                        FROM ping_logs
                                                        WHERE ip_id = :ip_id
                                                        ORDER BY created_at
                                                    ";
                                                    
                                                    $logsStmt = $conn->prepare($logsQuery);
                                                    $logsStmt->bindParam(':ip_id', $ip['id'], PDO::PARAM_INT);
                                                    $logsStmt->execute();
                                                    $logs = $logsStmt->fetchAll(PDO::FETCH_ASSOC);
                                                    
                                                    // Process logs to find consecutive offline periods
                                                    $consecutiveOffline = 0;
                                                    $offlinePeriods = [];
                                                    $startTime = null;
                                                    $lastOfflineTime = null;
                                                    $hasValidOfflinePeriod = false;
                                                    
                                                    foreach ($logs as $index => $log) {
                                                        if ($log['status'] == 'offline') {
                                                            $consecutiveOffline++;
                                                            
                                                            if ($consecutiveOffline == 1) {
                                                                $startTime = $log['created_at'];
                                                            }
                                                            
                                                            $lastOfflineTime = $log['created_at'];
                                                            
                                                            // Check if this is the third consecutive offline
                                                            if ($consecutiveOffline == 3) {
                                                                // We found a valid offline period start
                                                                $hasValidOfflinePeriod = true;
                                                                if (!isset($offlinePeriods[$startTime])) {
                                                                    $offlinePeriods[$startTime] = ['start' => $startTime, 'end' => $lastOfflineTime];
                                                                }
                                                            } elseif ($consecutiveOffline > 3) {
                                                                // Update the end time of the current period
                                                                $offlinePeriods[$startTime]['end'] = $lastOfflineTime;
                                                            }
                                                        } else {
                                                            // Reset consecutive count when online
                                                            $consecutiveOffline = 0;
                                                            $startTime = null;
                                                        }
                                                    }
                                                    
                                                    // Skip IPs with no valid offline periods (at least 3 consecutive)
                                                    if (!$hasValidOfflinePeriod) {
                                                        continue;
                                                    }
                                                    
                                                    // Calculate business hours offline duration
                                                    $totalOfflineMinutes = 0;
                                                    foreach ($offlinePeriods as $period) {
                                                        $start = new DateTime($period['start']);
                                                        $end = new DateTime($period['end']);
                                                        
                                                        // Loop through each day in the period
                                                        $currentDay = clone $start;
                                                        while ($currentDay <= $end) {
                                                            // Calculate business hours for this day
                                                            $dayStart = clone $currentDay;
                                                            $dayStart->setTime(8, 0); // 8:00 AM
                                                            
                                                            $dayEnd = clone $currentDay;
                                                            $dayEnd->setTime(18, 0); // 6:00 PM
                                                            
                                                            // Adjust period start/end to be within business hours
                                                            $periodStart = ($currentDay->format('Y-m-d') == $start->format('Y-m-d')) 
                                                                ? clone $start 
                                                                : clone $dayStart;
                                                            
                                                            $periodEnd = ($currentDay->format('Y-m-d') == $end->format('Y-m-d')) 
                                                                ? clone $end 
                                                                : clone $dayEnd;
                                                            
                                                            // If period start is before business hours, set to business hours start
                                                            if ($periodStart < $dayStart) {
                                                                $periodStart = clone $dayStart;
                                                            }
                                                            
                                                            // If period end is after business hours, set to business hours end
                                                            if ($periodEnd > $dayEnd) {
                                                                $periodEnd = clone $dayEnd;
                                                            }
                                                            
                                                            // Only count if period is within business hours
                                                            if ($periodStart < $periodEnd) {
                                                                $diff = $periodStart->diff($periodEnd);
                                                                
                                                                // Convert to minutes
                                                                $minutes = $diff->h * 60 + $diff->i;
                                                                $totalOfflineMinutes += $minutes;
                                                            }
                                                            
                                                            // Move to next day
                                                            $currentDay->modify('+1 day');
                                                            $currentDay->setTime(0, 0);
                                                        }
                                                    }
                                                    
                                                    // Calculate days, hours, minutes for display
                                                    $days = floor($totalOfflineMinutes / 1440);
                                                    $hours = floor(($totalOfflineMinutes % 1440) / 60);
                                                    $minutes = $totalOfflineMinutes % 60;
                                                    
                                                    $parts = [];
                                                    if ($days > 0) $parts[] = $days . ' day' . ($days > 1 ? 's' : '');
                                                    if ($hours > 0) $parts[] = $hours . ' hour' . ($hours > 1 ? 's' : '');
                                                    if ($minutes > 0 || count($parts) == 0) $parts[] = $minutes . ' minute' . ($minutes !== 1 ? 's' : '');
                                                    
                                                    $offlineDuration = implode(', ', $parts);
                                                ?>
                                                <tr>
                                                    <td><?= htmlspecialchars($ip['ip_address']) ?></td>
                                                    <td><?= htmlspecialchars($ip['description']) ?></td>
                                                    <td><?= htmlspecialchars($ip['location']) ?></td>
                                                    <td><?= htmlspecialchars($ip['category']) ?></td>
                                                    <td><?= $offlineDuration ?></td>
                                                    <td><span class="badge bg-danger rounded-pill badge-sm">Offline</span></td>
                                                    <td>
                                                        <div class="d-flex gap-1">
                                                            <a href="offline_logs.php?ip_id=<?= $ip['id'] ?>" class="btn btn-sm btn-outline-primary px-2 py-0">View</a>
                                                            <button class="btn btn-sm btn-outline-info px-2 py-0 remarks-btn" 
                                                                    data-bs-toggle="modal" 
                                                                    data-bs-target="#remarksModal" 
                                                                    data-ip-id="<?= $ip['id'] ?>"
                                                                    data-ip="<?= htmlspecialchars($ip['ip_address']) ?>"
                                                                    data-description="<?= htmlspecialchars($ip['description']) ?>"
                                                                    data-category="<?= htmlspecialchars($ip['category']) ?>"
                                                                    data-location="<?= htmlspecialchars($ip['location']) ?>">
                                                                Remarks
                                                            </button>
                                                        </div>
                                                    </td>
                                                </tr>
                                                <?php
                                                } // End of IP processing loop
                                                ?>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        </div>

            <div class="modal fade" id="remarksModal" tabindex="-1" aria-labelledby="remarksModalLabel" aria-hidden="true">
              <div class="modal-dialog modal-lg">
                  <div class="modal-content border-0 shadow">
                      <div class="modal-header bg-gradient-dark text-white py-2">
                          <h5 class="modal-title fs-6" id="remarksModalLabel"><i class="bi bi-pencil-square me-2"></i>Add Remarks</h5>
                          <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                      </div>

                   
            <div class="modal-body">
                <form id="remarksForm" method="post" action="../backend/remarks.php">
                    <input type="hidden" id="ip_id" name="ip_id">
                    
                    <div class="row g-3 mb-3">
                        <div class="col-md-6">
                            <label class="form-label fs-7">IP Address</label>
                            <input type="text" class="form-control form-control-sm" id="ip_address" readonly>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fs-7">Category</label>
                            <input type="text" class="form-control form-control-sm" id="category" readonly>
                        </div>
                    </div>

                    <div class="row g-3 mb-3">
                        <div class="col-md-6">
                            <label class="form-label fs-7">Description</label>
                            <input type="text" class="form-control form-control-sm" id="description" readonly>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fs-7">Location</label>
                            <input type="text" class="form-control form-control-sm" id="location" readonly>
                        </div>
                    </div>

                    <div class="row g-3 mb-3">
                        <div class="col-md-6">
                            <label class="form-label fs-7">Date</label>
                            <input type="date" class="form-control form-control-sm" id="date" name="date" required>
                        </div>
                        <div class="col-md-6">
                            <div class="row g-2">
                                <div class="col-6">
                                    <label class="form-label fs-7">Time From</label>
                                    <input type="time" class="form-control form-control-sm" id="time_from" name="time_from" required>
                                </div>
                                <div class="col-6">
                                    <label class="form-label fs-7">Time To</label>
                                    <input type="time" class="form-control form-control-sm" id="time_to" name="time_to" required>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fs-7">Remarks</label>
                        <textarea class="form-control form-control-sm" id="remarks" name="remarks" rows="3" required></textarea>
                    </div>

                    <div class="mt-4" style="font-size: 0.75rem;">
                          <h6 class="fs-7 fw-medium text-secondary">
                            <i class="bi bi-list-ul me-1"></i>Offline Logs for Selected Date
                          </h6>
                          <div id="offlineLogsList" class="list-group list-group-flush mt-2 border-top pt-2">
                            <!-- Offline logs will be loaded here -->
                          </div>
                        </div>

                </form>

                  </div>
                        <div class="modal-footer py-2">
                            <div class="d-flex gap-2">
                                <button type="button" class="btn btn-sm btn-outline-secondary" data-bs-dismiss="modal">Close</button>
                                <button type="button" class="btn btn-sm btn-primary" id="saveRemarksBtn">Save Remarks</button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

                        <!-- Chart Section - Add this after the Category Cards section -->
                    <div class="row mt-4">
                      <div class="col-12 col-xl-6 mb-4">
                        <div class="card shadow">
                          <div class="card-header bg-gradient-primary text-white d-flex justify-content-between align-items-center py-3">
                            <div class="fs-5 fw-bold"><i class="bi bi-ethernet me-2"></i> LAN Latency Trends</div>
                            <button class="btn btn-sm btn-outline-light filter-toggle" type="button" data-bs-toggle="collapse" data-bs-target="#lanFilters">
                              <i class="bi bi-funnel me-1"></i>Filters
                            </button>
                          </div>
                          
                          <div id="lanFilters" class="collapse bg-light p-3 border-bottom">
                            <div class="row g-2">
                              <div class="col-md-3">
                                <label class="form-label small mb-1">IP Address</label>
                                <select class="form-select form-select-sm lan-ip-filter">
                                  <option value="">All IPs</option>
                                </select>
                              </div>
                              <div class="col-md-3">
                                <label class="form-label small mb-1">Location</label>
                                <select class="form-select form-select-sm lan-location-filter">
                                  <option value="">All Locations</option>
                                </select>
                              </div>
                              <div class="col-md-3">
                                <label class="form-label small mb-1">Month</label>
                                <select class="form-select form-select-sm lan-month-filter">
                                  <option value="0">Full Year</option>
                                  <option value="1">January</option>
                                  <option value="2">February</option>
                                  <option value="3">March</option>
                                  <option value="4" selected>April</option>
                                  <option value="5">May</option>
                                  <option value="6">June</option>
                                  <option value="7">July</option>
                                  <option value="8">August</option>
                                  <option value="9">September</option>
                                  <option value="10">October</option>
                                  <option value="11">November</option>
                                  <option value="12">December</option>
                                </select>
                              </div>
                              <div class="col-md-3">
                                <label class="form-label small mb-1">Year</label>
                                <select class="form-select form-select-sm lan-year-filter">
                                  <option value="2023">2023</option>
                                  <option value="2024">2024</option>
                                  <option value="2025" selected>2025</option>
                                </select>
                              </div>
                            </div>
                          </div>
                          
                          <div class="card-body p-0">
                            <div class="chart-container p-3">
                              <canvas id="lanLatencyChart" style="width:100%; height:250px;"></canvas>
                            </div>
                          </div>
                        </div>
                      </div>

                      <!-- Internet Latency Chart -->
                      <div class="col-12 col-xl-6 mb-4">
                        <div class="card shadow">
                          <div class="card-header bg-gradient-success text-white d-flex justify-content-between align-items-center py-3">
                            <div class="fs-5 fw-bold"><i class="bi bi-globe me-2"></i> Internet Latency Trends</div>
                            <button class="btn btn-sm btn-outline-light filter-toggle" type="button" data-bs-toggle="collapse" data-bs-target="#internetFilters">
                              <i class="bi bi-funnel me-1"></i>Filters
                            </button>
                          </div>
                          
                          <div id="internetFilters" class="collapse bg-light p-3 border-bottom">
                            <div class="row g-2">
                              <div class="col-md-3">
                                <label class="form-label small mb-1">IP Address</label>
                                <select class="form-select form-select-sm internet-ip-filter">
                                  <option value="">All IPs</option>
                                </select>
                              </div>
                              <div class="col-md-3">
                                <label class="form-label small mb-1">Location</label>
                                <select class="form-select form-select-sm internet-location-filter">
                                  <option value="">All Locations</option>
                                </select>
                              </div>
                              <div class="col-md-3">
                                <label class="form-label small mb-1">Month</label>
                                <select class="form-select form-select-sm internet-month-filter">
                                  <option value="0">Full Year</option>
                                  <option value="1">January</option>
                                  <option value="2">February</option>
                                  <option value="3">March</option>
                                  <option value="4" selected>April</option>
                                  <option value="5">May</option>
                                  <option value="6">June</option>
                                  <option value="7">July</option>
                                  <option value="8">August</option>
                                  <option value="9">September</option>
                                  <option value="10">October</option>
                                  <option value="11">November</option>
                                  <option value="12">December</option>
                                </select>
                              </div>
                              <div class="col-md-3">
                                <label class="form-label small mb-1">Year</label>
                                <select class="form-select form-select-sm internet-year-filter">
                                  <option value="2023">2023</option>
                                  <option value="2024">2024</option>
                                  <option value="2025" selected>2025</option>
                                </select>
                              </div>
                            </div>
                          </div>
                          
                          <div class="card-body p-0">
                            <div class="chart-container p-3">
                              <canvas id="internetLatencyChart" style="width:100%; height:250px;"></canvas>
                            </div>
                          </div>
                        </div>
                      </div>
                    </div>

                                        <!-- Category Cards -->
                    <div class="row">
                        <?php 
                        $categoryIcons = [
                            'LAN' => 'bi-ethernet',
                            'CCTV' => 'bi-camera-video',
                            'Server' => 'bi-server',
                            'Internet' => 'bi-globe'
                        ];
                        
                        // Define default classes for known categories
                        $categoryClasses = [
                            'LAN' => 'category-lan',
                            'CCTV' => 'category-cctv',
                            'Server' => 'category-server',
                            'Internet' => 'category-internet'
                        ];
                        
                        foreach($categoryStats as $category => $stats): 
                            // Skip empty categories
                            if($stats['total'] == 0) continue;
                        ?>
                        <div class="col-xl-3 col-lg-4 col-md-6">
                            <div class="category-card <?= $categoryClasses[$category] ?? 'category-default' ?>">
                                <div class="card-header d-flex align-items-center">
                                    <i class="bi <?= $categoryIcons[$category] ?? 'bi-gear' ?> me-2"></i>
                                    <?= $category ?>
                                </div>
                                <div class="card-body">
                                    <div class="d-flex justify-content-between mb-2">
                                        <span>Total IPs:</span>
                                        <span class="fw-bold"><?= $stats['total'] ?></span>
                                    </div>
                                    <div class="d-flex justify-content-between mb-2">
                                        <span>Online:</span>
                                        <span class="online"><?= $stats['online'] ?></span>
                                    </div>
                                    <div class="d-flex justify-content-between mb-2">
                                        <span>Offline:</span>
                                        <span class="offline"><?= $stats['offline'] ?></span>
                                    </div>
                                    <div class="d-flex justify-content-between">
                                        <span>Avg Latency:</span>
                                        <span class="fw-bold"><?= $stats['avg_latency'] ?> ms</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>

                    <!-- Tables Section -->
                 
                        
                        <?php
                            $filterCategoriesStmt = $conn->query("
                                SELECT DISTINCT category 
                                FROM add_ip 
                                WHERE category IS NOT NULL 
                                AND category <> ''
                                ORDER BY category
                            ");
                            $filterCategories = $filterCategoriesStmt->fetchAll(PDO::FETCH_COLUMN);

                            $filterLocationsStmt = $conn->query("
                                SELECT DISTINCT location 
                                FROM add_ip 
                                WHERE location IS NOT NULL 
                                AND location <> ''
                                ORDER BY location
                            ");
                            $filterLocations = $filterLocationsStmt->fetchAll(PDO::FETCH_COLUMN);
                        ?>

                        <div class="row mt-4">
                        <div class="col-12">
                            <div class="card">
                            <!-- 1. CARD HEADER with title on left, buttons+filters on right -->
                            <div class="card-header d-flex justify-content-between align-items-center bg-dark text-white">
                                <div>
                                <i class="bi bi-calendar-check me-2"></i>
                                Monthly Network Uptime Statistics
                                </div>
                                <div class="header-actions d-flex align-items-center">
                                <!-- DataTables buttons placeholder -->
                                <div class="dt-buttons btn-group me-3"></div>

                                <!-- Category filter -->
                                <select id="categoryFilter" class="form-select form-select-sm me-2">
                                    <option value="">All Categories</option>
                                    <?php foreach($filterCategories as $cat): ?>
                                    <option value="<?= htmlspecialchars($cat) ?>"><?= htmlspecialchars($cat) ?></option>
                                    <?php endforeach; ?>
                                </select>

                                <!-- Location filter -->
                                <select id="locationFilter" class="form-select form-select-sm">
                                    <option value="">All Locations</option>
                                    <?php foreach($filterLocations as $loc): ?>
                                    <option value="<?= htmlspecialchars($loc) ?>"><?= htmlspecialchars($loc) ?></option>
                                    <?php endforeach; ?>
                                </select>
                                </div>
                            </div>

                            <!-- 2. TABLE BODY -->
                            <div class="card-body table-container">
                                <table class="table table-hover" id="monthlyStatsTable">
                                <thead>
                                    <tr>
                                    <th>IP Address</th>
                                    <th>Location</th>
                                    <th>Category</th>
                                    <th>Description</th>
                                    <th>Offline Events</th>
                                    <th>Monthly Uptime %</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php 
                                    $monthlyStats = getMonthlyUptimeStats($conn);
                                    foreach($monthlyStats as $device):
                                        if($device['uptime_percentage'] >= 99)       $rowClass = "uptime-excellent";
                                        elseif($device['uptime_percentage'] >= 95)   $rowClass = "uptime-good";
                                        elseif($device['uptime_percentage'] >= 90)   $rowClass = "uptime-warning";
                                        else                                         $rowClass = "uptime-critical";
                                    ?>
                                    <tr class="<?= $rowClass ?>">
                                    <td><?= htmlspecialchars($device['ip_address']) ?></td>
                                    <td><?= htmlspecialchars($device['location']) ?></td>
                                    <td><?= htmlspecialchars($device['category']) ?></td>
                                    <td><?= htmlspecialchars($device['description']) ?></td>
                                    <td><?= $device['offline_events'] ?></td>
                                    <td><?= number_format($device['uptime_percentage'], 2) ?>%</td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                                </table>
                            </div>
                            </div>
                        </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
<script>
  // Configuration
const REFRESH_INTERVAL = 15 * 60 * 1000; // 15 minutes
const SYNC_CHECK_INTERVAL = 5000; // Check for updates every 5 seconds

let refreshTimer;
let progressInterval;
let loaderTimeout;
let syncCheckTimer;
let lastUpdateTimestamp = 0;
let offlineTable, latencyTable; // Store DataTable instances

$(document).ready(function() {
    // Initialize DataTables
    offlineTable = $('#offlineTable').DataTable({
        paging: true,
        searching: true,
        ordering: true,
        info: true,
        language: {
            emptyTable: "No offline devices"
        }
    });
    
    latencyTable = $('#latencyTable').DataTable({
        paging:    true,
        searching: true,
        ordering:  true,
        info:      true,
        
        // Default sort by column 4 (Latency) descending:
        order: [[4, 'desc']],        

        // Let DataTables strip HTML and parse numbers in column 4:
        columnDefs: [
          { type: 'num-html', targets: 4 },
          { orderable: false, targets: 5 }  // disable sort on the Action column
        ],

        language: {
          emptyTable: "No latency data"
        }
    });

    
    // Start the auto-refresh timer and immediately fetch data
    startRefreshTimer();
    // Start sync check timer for cross-client updates
    startSyncCheckTimer();
    refreshData(false); // Initial data load without forcing ping
    
    // Listen for events from monitoring page
    window.addEventListener('storage', function(e) {
        if (e.key === 'networkDataUpdated') {
            refreshData(false); // Refresh without forcing ping
        }
    });
    
    // Manual refresh button - FIXED HERE
    $('#refreshBtn').on('click', function() {
        console.log("Manual refresh button clicked");
        showLoader(); // Show loader right away
        refreshData(true); // Force update with ping
    });
    
    // Initialize monthly statistics tables
    var monthlyTable = $('#monthlyTable').DataTable({
        dom: 'Bfrtip',
        buttons: [
            {
                extend: 'excel',
                text: 'Excel',
                className: 'btn btn-success btn-sm d-none',
                title: 'Monthly Network Statistics'
            },
            {
                extend: 'pdf',
                text: 'PDF',
                className: 'btn btn-danger btn-sm d-none',
                title: 'Monthly Network Statistics'
            }
        ],
        paging: false,
        searching: false,
        info: false
    });
    
    var detailedIPTable = $('#detailedIPTable').DataTable({
        dom: 'Bfrtip',
        buttons: [
            {
                extend: 'excel',
                text: 'Excel',
                className: 'btn btn-success btn-sm d-none',
                title: 'Detailed IP Monthly Statistics'
            },
            {
                extend: 'pdf',
                text: 'PDF',
                className: 'btn btn-danger btn-sm d-none',
                title: 'Detailed IP Monthly Statistics'
            }
        ],
        pageLength: 25
    });
    
    // Export buttons functionality
    $('#exportMonthlyExcel').click(function() {
        monthlyTable.button('.buttons-excel').trigger();
    });
    
    $('#exportMonthlyPDF').click(function() {
        monthlyTable.button('.buttons-pdf').trigger();
    });
    
    // Month form submission
    $('#monthForm').on('submit', function(e) {
        e.preventDefault();
        let month = $('#monthSelect').val();
        let year = $('#yearSelect').val();
        window.location.href = 'dashboard.php?month=' + month + '&year=' + year;
    });
});

function startRefreshTimer() {
    console.log("Starting refresh timer for", REFRESH_INTERVAL/1000, "seconds");
    clearTimeout(refreshTimer);
    
    refreshTimer = setTimeout(() => {
        console.log("Timer expired, refreshing data");
        showLoader(); // Show loader when auto-refreshing
        refreshData(true); // Force ping on auto-refresh
    }, REFRESH_INTERVAL);

    // Update progress bar
    const progressBar = document.querySelector('.progress-bar');
    if (progressBar) {
        progressBar.style.width = '0%';

        clearInterval(progressInterval);
        progressInterval = setInterval(() => {
            const currentWidth = parseFloat(progressBar.style.width) || 0;
            if (currentWidth < 100) {
                const increment = 100 / (REFRESH_INTERVAL / 1000);
                progressBar.style.width = (currentWidth + increment) + '%';
            }
        }, 1000);
    }
}

function refreshData(forceUpdate = false) {
    console.log("Refreshing data, force =", forceUpdate);
    
    const url = forceUpdate ? '?refresh=true&force=true' : '?refresh=true';
    
    $.ajax({
        url: url,
        type: 'GET',
        dataType: 'json',
        success: function(data) {
            console.log("Data refreshed successfully");
            
            // First update timestamp to show that something's happening
            $('#lastUpdated').text('Last updated: ' + data.timestamp);
            
            // Update the last update timestamp for sync checks
            lastUpdateTimestamp = Date.parse(data.timestamp);
            
            // Then update dashboard with visual indicator
            updateDashboardWithAnimation(data);
            
            // Reset the timer after successful refresh
            startRefreshTimer();
            
            // Hide loader after a delay to make updates more visible
            setTimeout(() => {
                hideLoader();
                
                if (data.newlyOfflineDevices?.length) {
                    showOfflineAlert(data.newlyOfflineDevices);
                }
                
                // Flash a status message
                showUpdateNotification("Dashboard updated successfully");
            }, 500);
            
            // broadcast to other tabs
            localStorage.setItem('networkDataUpdated', Date.now());
            
            // Store the data in sessionStorage for quick access
            sessionStorage.setItem('networkData', JSON.stringify(data));
        },
        error: function(xhr, status, error) {
            console.error('Error refreshing data:', error);
            
            // Clear and update tables on error
            offlineTable.clear().draw();
            latencyTable.clear().draw();
            
            startRefreshTimer();
            hideLoader();
            
            // Show error notification
            showUpdateNotification("Error updating dashboard", "error");
        }
    });
}

// New function to show update notification
function showUpdateNotification(message, type = "success") {
    // Create notification element if it doesn't exist
    let notification = document.getElementById('update-notification');
    if (!notification) {
        notification = document.createElement('div');
        notification.id = 'update-notification';
        notification.style.position = 'fixed';
        notification.style.bottom = '20px';
        notification.style.right = '20px';
        notification.style.padding = '10px 20px';
        notification.style.borderRadius = '5px';
        notification.style.zIndex = '9999';
        notification.style.boxShadow = '0 2px 10px rgba(0,0,0,0.2)';
        notification.style.transition = 'opacity 0.5s ease-in-out';
        document.body.appendChild(notification);
    }
    
    // Style based on type
    if (type === "success") {
        notification.style.backgroundColor = '#28a745';
        notification.style.color = 'white';
    } else {
        notification.style.backgroundColor = '#dc3545';
        notification.style.color = 'white';
    }
    
    // Set message and show
    notification.textContent = message;
    notification.style.opacity = '1';
    
    // Hide after 3 seconds
    setTimeout(() => {
        notification.style.opacity = '0';
    }, 3000);
}

function showOfflineAlert(offlineDevices) {
    // Create alert content with device list
    let alertContent = '<div class="offline-alert-content">';
    alertContent += '<ul style="text-align: left; padding-left: 20px;">';
    
    offlineDevices.forEach(device => {
        alertContent += `<li><strong>${device.ip_address}</strong> - ${device.location} (${device.category})</li>`;
    });
    
    alertContent += '</ul></div>';
    
    // Show SweetAlert2
    Swal.fire({
        title: 'Network Alert!',
        html: `<div>The following ${offlineDevices.length > 1 ? 'devices are' : 'device is'} now offline:</div>` + alertContent,
        icon: 'error',
        confirmButtonText: 'Acknowledge',
        confirmButtonColor: '#dc3545',
        showCloseButton: true,
        customClass: {
            popup: 'offline-alert-popup',
            title: 'offline-alert-title'
        }
    });
    
    // Add some CSS to ensure the alert is prominent
    const style = document.createElement('style');
    style.textContent = `
        .offline-alert-popup {
            border-left: 5px solid #dc3545;
        }
        .offline-alert-title {
            color: #dc3545;
            font-weight: bold;
        }
        .offline-alert-content {
            margin-top: 15px;
        }
    `;
    document.head.appendChild(style);
}

// New function with animations
function updateDashboardWithAnimation(data) {
    // Add transition styles if not already added
    if (!document.getElementById('dashboard-transitions')) {
        const style = document.createElement('style');
        style.id = 'dashboard-transitions';
        style.textContent = `
            .card-value, .category-card .fw-bold, .category-card .online, .category-card .offline {
                transition: background-color 0.5s ease;
            }
            .highlight {
                background-color: rgba(255, 255, 0, 0.3);
            }
            .table-highlight {
                animation: flashRow 1.5s;
            }
            @keyframes flashRow {
                0%, 100% { background-color: transparent; }
                50% { background-color: rgba(255, 255, 0, 0.3); }
            }
        `;
        document.head.appendChild(style);
    }

    // Update summary counters with highlight
    animateCounterWithHighlight($('.card-value.total'), data.summary.total);
    animateCounterWithHighlight($('.card-value.online'), data.summary.online);
    animateCounterWithHighlight($('.card-value.offline'), data.summary.offline);
  
    // Update category cards with highlight
    for (const [category, stats] of Object.entries(data.categories)) {
        const card = $(`.category-card .card-header:contains("${category}")`).closest('.category-card');
        if (!card.length) continue;
        
        updateWithHighlight(card.find('.d-flex:contains("Total IPs") .fw-bold'), stats.total);
        updateWithHighlight(card.find('.d-flex:contains("Online") .online'), stats.online);
        updateWithHighlight(card.find('.d-flex:contains("Offline") .offline'), stats.offline);
        updateWithHighlight(card.find('.d-flex:contains("Avg Latency") .fw-bold'), stats.avg_latency + ' ms');
    }

    // Update offline devices table with row highlighting
    const oldOfflineIPs = getTableIPs(offlineTable);
    offlineTable.clear();
    
    if (data.offlineDevices && data.offlineDevices.length) {
      data.offlineDevices.forEach(d => {
        const rowNode = offlineTable.row.add([
          d.ip_address,
          d.location || 'Unknown',
          d.category || 'Unknown',
          d.description || 'No description',
          '<span class="badge bg-danger badge-sm">Offline</span>',
          `<a href="report.php?report=${encodeURIComponent(d.id)}"
              class="btn btn-primary btn-sm">View</a>`
        ]).draw(false).node();
        
        // Highlight new additions
        if (!oldOfflineIPs.includes(d.ip_address)) {
          $(rowNode).addClass('table-highlight');
        }
      });
    }
    offlineTable.draw();

    // Update latency table with row highlighting
    const oldLatencyIPs = getTableIPs(latencyTable);
    latencyTable.clear();
    
    if (data.highLatencyDevices && data.highLatencyDevices.length) {
      data.highLatencyDevices.forEach(d => {
        const isHigh = parseFloat(d.latency) > 100;
        const badgeClass = isHigh ? 'bg-danger' : 'bg-success';
        const status     = isHigh ? 'High'      : 'Low';

        const rowNode = latencyTable.row.add([
          d.ip_address,
          d.location || 'Unknown',
          d.category || 'Unknown',
          d.description || 'No description',
          `<div class="d-flex justify-content-between align-items-center">
            <span>${d.latency} ms</span>
            <span class="badge ${badgeClass} badge-sm">${status}</span>
          </div>`,
          `<a href="report.php?report=${encodeURIComponent(d.id)}"
              class="btn btn-primary btn-sm">View</a>`
        ]).draw(false).node();

        // Apply row classes
        $(rowNode).addClass(isHigh ? 'high-latency' : 'low-latency');
        
        // Highlight new additions
        if (!oldLatencyIPs.includes(d.ip_address)) {
          $(rowNode).addClass('table-highlight');
        }
      });
    }
    latencyTable.draw();
}

// Helper to get current IPs in table
function getTableIPs(table) {
    const ips = [];
    table.rows().every(function() {
        const data = this.data();
        if (data && data[0]) {
            ips.push(data[0]);
        }
    });
    return ips;
}

// Helper to update with highlight effect
function updateWithHighlight(element, newValue) {
    const $element = $(element);
    const currentValue = $element.text();
    
    if (currentValue != newValue) {
        $element.text(newValue);
        $element.addClass('highlight');
        setTimeout(() => $element.removeClass('highlight'), 1500);
    }
}

// Animate counter with highlight effect
function animateCounterWithHighlight(element, targetValue) {
    const $element = $(element);
    const startValue = parseInt($element.text()) || 0;
    
    if (startValue !== targetValue) {
        $element.addClass('highlight');
        animateCounter($element, targetValue);
        setTimeout(() => $element.removeClass('highlight'), 1500);
    }
}

// Animate counter for smoother transitions
function animateCounter(element, targetValue) {
    const $element = $(element);
    const startValue = parseInt($element.text()) || 0;
    const duration = 1000; // 1 second
    const frameRate = 60;
    const increment = (targetValue - startValue) / (duration / (1000 / frameRate));
    
    let currentValue = startValue;
    const counter = setInterval(() => {
        currentValue += increment;
        if ((increment > 0 && currentValue >= targetValue) || 
            (increment < 0 && currentValue <= targetValue)) {
            clearInterval(counter);
            $element.text(targetValue);
        } else {
            $element.text(Math.round(currentValue));
        }
    }, 1000 / frameRate);
}

let loaderTimer, loaderStart;
function showLoader() {
    console.log("Showing loader");
    clearTimeout(loaderTimeout);
    loaderStart = Date.now();
    document.getElementById('loader').classList.remove('d-none');
    clearInterval(loaderTimer);
    loaderTimer = setInterval(() => {
        const s = Math.floor((Date.now() - loaderStart) / 1000);
        document.getElementById('loader-text').textContent = `Loading… ${s}s`;
    }, 500);
    loaderTimeout = setTimeout(() => {
        hideLoader();
        console.error('Loading timeout occurred');
        Swal.fire({
            title: 'Loading Error',
            text: 'The operation timed out. Please try again later.',
            icon: 'error',
            confirmButtonText: 'OK'
        });
    }, 60000);
}

function hideLoader(reloadPage = false) {
    console.log("Hiding loader");
    clearInterval(loaderTimer);
    clearTimeout(loaderTimeout);
    document.getElementById('loader-text').textContent = 'Loading… 0s';
    document.getElementById('loader').classList.add('d-none');
    if (reloadPage) {
        window.location.reload();
    }
}

// Add new functions for cross-client synchronization
function startSyncCheckTimer() {
    console.log("Starting sync check timer");
    // Check for server data updates every few seconds
    syncCheckTimer = setInterval(() => {
        fetchServerData();
    }, SYNC_CHECK_INTERVAL);
}

// Server is the single source of truth - no need to update server timestamp from client
function updateServerTimestamp(timestamp) {
    // Only log this since server controls the source of truth
    console.log("Server timestamp observed:", timestamp);
    // We don't update the server timestamp from client side anymore
}

function fetchServerData() {
    // Fetch the latest data directly from server
    $.ajax({
        url: '?refresh=true&nocache=' + Date.now(),
        type: 'GET',
        dataType: 'json',
        success: function(data) {
            if (data) {
                // Compare with last update timestamp to see if data is newer
                const dataTimestamp = Date.parse(data.timestamp);
                
                if (dataTimestamp > lastUpdateTimestamp) {
                    console.log("Server has newer data:", data.timestamp);
                    
                    // Update dashboard with new data
                    updateDashboardWithAnimation(data);
                    $('#lastUpdated').text('Last updated: ' + data.timestamp);
                    lastUpdateTimestamp = dataTimestamp;
                    
                    // Store for future use
                    sessionStorage.setItem('networkData', JSON.stringify(data));
                    
                    // Let user know dashboard was updated
                    showUpdateNotification("Dashboard synchronized");
                }
            }
        },
        error: function(xhr, status, error) {
            console.error("Error fetching server data:", error);
        }
    });
}
// DataTables + Buttons initialization script
$(document).ready(function() {
    var table = $('#monthlyStatsTable').DataTable({
      dom: 'Bfti',  // remove default button placeholder
      buttons: [
        {
          extend: 'copy',
          text: '<i class="bi bi-clipboard me-1"></i>Copy',
          className: 'btn btn-sm btn-outline-secondary'
        },
        {
          extend: 'excel',
          text: '<i class="bi bi-file-earmark-excel me-1"></i>Excel',
          className: 'btn btn-sm btn-outline-success'
        },
        {
          extend: 'pdf',
          text: '<i class="bi bi-file-earmark-pdf me-1"></i>PDF',
          className: 'btn btn-sm btn-outline-danger'
        },
        {
          extend: 'print',
          text: '<i class="bi bi-printer me-1"></i>Print',
          className: 'btn btn-sm btn-outline-primary'
        }
      ],
      pageLength: 10,
      order: [[5, 'desc']],
      processing: true,
      language: {
        processing: '<div class="spinner-border" role="status"><span class="visually-hidden">Loading…</span></div>'
      }
    });
  
    // move buttons into our thead actions row
    table.buttons().container().appendTo('#monthlyStatsTable thead .dt-buttons');
  
    // filters
    $('#categoryFilter, #locationFilter').on('change', function() {
      $('.table-container').append(`
        <div class="loader-overlay">
          <div class="spinner-border" role="status">
            <span class="visually-hidden">Loading…</span>
          </div>
        </div>`);
      table
        .column(2).search($('#categoryFilter').val())
        .column(1).search($('#locationFilter').val())
        .draw();
    });
  
    table.on('draw.dt', function() {
      $('.loader-overlay').remove();
    });
  });
  
  // Chart initialization script
  $(document).ready(function() {
    // Add CSS for chart containers
    $('<style>')
      .prop('type', 'text/css')
      .html(`
        .chart-container {
          position: relative;
          height: 400px;
          max-height: 400px;
          width: 100%;
        }
        .chart-container canvas {
          max-height: 100%;
        }
      `)
      .appendTo('head');
      
    // Initialize charts and filters
    const lanCtx = document.getElementById('lanLatencyChart').getContext('2d');
    const internetCtx = document.getElementById('internetLatencyChart').getContext('2d');
    let lanChart, internetChart;
    
    // Current date for default settings
    const now = new Date();
    const currentMonth = now.getMonth() + 1;
    const currentYear = now.getFullYear();
    
    // Set default months and years in filters
    $('.lan-month-filter, .internet-month-filter').val(currentMonth);
    $('.lan-year-filter, .internet-year-filter').val(currentYear);
    
    // Function to format dates for display
    function formatDay(day) {
      return day < 10 ? '0' + day : day;
    }
    
    // Generate days in a month
    function getDaysInMonth(month, year) {
      return new Date(year, month, 0).getDate();
    }
    
    function generateLabels(month, year) {
      const daysInMonth = getDaysInMonth(month, year);
      return Array.from({length: daysInMonth}, (_, i) => 
        `${year}-${month < 10 ? '0' + month : month}-${formatDay(i + 1)}`
      );
    }
    
    // Load IP addresses and locations for each category
    function loadFilterOptions() {
      $.ajax({
        url: 'get_filter_options.php',
        type: 'GET',
        dataType: 'json',
        success: function(response) {
          // Populate LAN IP filter
          if (response.lan && response.lan.ips) {
            $('.lan-ip-filter').empty().append('<option value="">All IPs</option>');
            response.lan.ips.forEach(ip => {
              $('.lan-ip-filter').append(`<option value="${ip.id}">${ip.ip_address} - ${ip.description}</option>`);
            });
          }
          
          // Populate LAN location filter
          if (response.lan && response.lan.locations) {
            $('.lan-location-filter').empty().append('<option value="">All Locations</option>');
            response.lan.locations.forEach(location => {
              $('.lan-location-filter').append(`<option value="${location}">${location}</option>`);
            });
          }
          
          // Populate Internet IP filter
          if (response.internet && response.internet.ips) {
            $('.internet-ip-filter').empty().append('<option value="">All IPs</option>');
            response.internet.ips.forEach(ip => {
              $('.internet-ip-filter').append(`<option value="${ip.id}">${ip.ip_address} - ${ip.description}</option>`);
            });
          }
          
          // Populate Internet location filter
          if (response.internet && response.internet.locations) {
            $('.internet-location-filter').empty().append('<option value="">All Locations</option>');
            response.internet.locations.forEach(location => {
              $('.internet-location-filter').append(`<option value="${location}">${location}</option>`);
            });
          }
        },
        error: function(xhr, status, error) {
          console.error('Error loading filter options:', error);
        }
      });
    }
    
    // Fetch latency data for a chart
    function fetchLatencyData(chartType) {
      const isLAN = chartType === 'lan';
      const chartContainer = isLAN ? $(lanCtx.canvas).parent() : $(internetCtx.canvas).parent();
      const month = isLAN ? $('.lan-month-filter').val() : $('.internet-month-filter').val();
      const year = isLAN ? $('.lan-year-filter').val() : $('.internet-year-filter').val();
      const ipId = isLAN ? $('.lan-ip-filter').val() : $('.internet-ip-filter').val();
      const location = isLAN ? $('.lan-location-filter').val() : $('.internet-location-filter').val();
      
      chartContainer.append(`
        <div class="loader-overlay">
          <div class="spinner-border" role="status">
            <span class="visually-hidden">Loading…</span>
          </div>
        </div>`);
        
      $.ajax({
        url: 'get_latency_data.php',
        type: 'GET',
        data: {
          category: isLAN ? 'LAN' : 'Internet',
          month: month,
          year: year,
          ip_id: ipId,
          location: location,
          view_type: month == 0 ? 'year' : 'month'
        },
        dataType: 'json',
        success: function(response) {
          updateChart(chartType, response, month, year);
          chartContainer.find('.loader-overlay').remove();
          
          // Trigger resize event to ensure chart dimensions are properly set
          window.dispatchEvent(new Event('resize'));
        },
        error: function(xhr, status, error) {
          console.error(`Error fetching ${chartType} data:`, error);
          console.log('Raw response:', xhr.responseText);
          chartContainer.find('.loader-overlay').remove();
          chartContainer.append('<div class="alert alert-danger">Failed to load chart data</div>');
        }
      });
    }
    
    // Update chart with new data
    function updateChart(chartType, data, month, year) {
      const isLAN = chartType === 'lan';
      const ctx = isLAN ? lanCtx : internetCtx;
      let chart = isLAN ? lanChart : internetChart;
      const isYearView = month == 0;
      
      let labels, chartData;
      
      if (isYearView) {
        // Year view: show all months
        labels = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];
        chartData = Array(12).fill(0);
        
        // Fill in data for months that have values
        data.forEach(item => {
          const monthIndex = parseInt(item.month) - 1;
          chartData[monthIndex] = parseFloat(item.avg_latency);
        });
      } else {
        // Month view: show days in month
        labels = generateLabels(month, year);
        
        // Create dataset with zeros for days without data
        chartData = labels.map(label => {
          const matchingDay = data.find(item => item.log_date === label);
          return matchingDay ? parseFloat(matchingDay.avg_latency) : 0;
        });
        
        // Convert labels to just day numbers for display
        labels = labels.map(date => date.split('-')[2]);
      }
      
      // Destroy previous chart if it exists
      if (chart) {
        chart.destroy();
      }
      
      // Get the actual height of the container for proper gradient
      const containerHeight = ctx.canvas.parentNode.clientHeight || 400;
      
      // Create new chart with enhanced elegant design
      const gradient = ctx.createLinearGradient(0, 0, 0, containerHeight);
      
      if (isLAN) {
        // Blue theme for LAN
        gradient.addColorStop(0, 'rgba(56, 128, 255, 0.7)');
        gradient.addColorStop(0.5, 'rgba(56, 128, 255, 0.2)');
        gradient.addColorStop(1, 'rgba(56, 128, 255, 0.05)');
      } else {
        // Green theme for Internet
        gradient.addColorStop(0, 'rgba(11, 186, 133, 0.7)');
        gradient.addColorStop(0.5, 'rgba(11, 186, 133, 0.2)');
        gradient.addColorStop(1, 'rgba(11, 186, 133, 0.05)');
      }
      
      // Shadow configuration for more depth
      Chart.defaults.font.family = "'Poppins', 'Helvetica Neue', 'Helvetica', 'Arial', sans-serif";
      
      const newChart = new Chart(ctx, {
        type: 'line',
        data: {
          labels: labels,
          datasets: [{
            label: isLAN ? 'LAN Average Latency (ms)' : 'Internet Average Latency (ms)',
            data: chartData,
            borderColor: isLAN ? '#3880ff' : '#0bba85',
            backgroundColor: gradient,
            borderWidth: 2.5,
            fill: true,
            tension: 0.4,
            pointRadius: 4,
            pointBackgroundColor: isLAN ? '#3880ff' : '#0bba85',
            pointBorderColor: '#fff',
            pointBorderWidth: 2,
            pointHoverRadius: 6,
            pointHoverBackgroundColor: '#fff',
            pointHoverBorderColor: isLAN ? '#3880ff' : '#0bba85',
            pointHoverBorderWidth: 2,
            pointHitRadius: 10,
            shadowOffsetX: 3,
            shadowOffsetY: 3,
            shadowBlur: 10,
            shadowColor: 'rgba(0, 0, 0, 0.2)',
          }]
        },
        options: {
          responsive: true,
          maintainAspectRatio: false,
          layout: {
            padding: {
              top: 15,
              right: 25,
              bottom: 15,
              left: 15
            }
          },
          scales: {
            y: {
              beginAtZero: true,
              grid: {
                color: 'rgba(200, 200, 200, 0.15)',
                drawBorder: false,
                lineWidth: 0.5
              },
              border: {
                display: false
              },
              title: {
                display: true,
                text: 'Latency (ms)',
                font: {
                  size: 14,
                  weight: '500',
                  family: "'Poppins', 'Helvetica Neue', 'Helvetica', 'Arial', sans-serif"
                },
                color: '#555'
              },
              ticks: {
                callback: function(value) {
                  return value + ' ms';
                },
                font: {
                  size: 11,
                  family: "'Poppins', 'Helvetica Neue', 'Helvetica', 'Arial', sans-serif"
                },
                color: '#888',
                padding: 10
              }
            },
            x: {
              grid: {
                display: false
              },
              border: {
                display: false
              },
              title: {
                display: true,
                text: isYearView 
                  ? `Months in ${year}` 
                  : `Days in ${new Date(year, month-1).toLocaleString('default', { month: 'long' })} ${year}`,
                font: {
                  size: 14,
                  weight: '500',
                  family: "'Poppins', 'Helvetica Neue', 'Helvetica', 'Arial', sans-serif"
                },
                color: '#555',
                padding: 10
              },
              ticks: {
                font: {
                  size: 11,
                  family: "'Poppins', 'Helvetica Neue', 'Helvetica', 'Arial', sans-serif"
                },
                color: '#888',
                maxRotation: 0,
                autoSkip: true,
                maxTicksLimit: isYearView ? 12 : 15
              }
            }
          },
          plugins: {
            tooltip: {
              backgroundColor: 'rgba(33, 33, 44, 0.85)',
              titleFont: {
                size: 13,
                weight: '600',
                family: "'Poppins', 'Helvetica Neue', 'Helvetica', 'Arial', sans-serif"
              },
              bodyFont: {
                size: 12,
                family: "'Poppins', 'Helvetica Neue', 'Helvetica', 'Arial', sans-serif"
              },
              padding: 14,
              cornerRadius: 8,
              displayColors: false,
              boxShadow: '0 4px 8px rgba(0,0,0,0.15)',
              borderColor: isLAN ? 'rgba(56, 128, 255, 0.3)' : 'rgba(11, 186, 133, 0.3)',
              borderWidth: 1,
              caretSize: 6,
              callbacks: {
                title: function(tooltipItems) {
                  if (isYearView) {
                    return `${tooltipItems[0].label} ${year}`;
                  } else {
                    const monthName = new Date(year, month-1).toLocaleString('default', { month: 'long' });
                    return `${monthName} ${tooltipItems[0].label}, ${year}`;
                  }
                },
                label: function(context) {
                  return `Latency: ${context.raw.toFixed(2)} ms`;
                }
              }
            },
            legend: {
              display: true,
              position: 'top',
              align: 'end',
              labels: {
                boxWidth: 15,
                usePointStyle: true,
                pointStyle: 'circle',
                padding: 20,
                font: {
                  size: 12,
                  weight: '500',
                  family: "'Poppins', 'Helvetica Neue', 'Helvetica', 'Arial', sans-serif"
                },
                color: '#555'
              }
            },
            filler: {
              propagate: true
            }
          },
          interaction: {
            mode: 'index',
            intersect: false
          },
          elements: {
            line: {
              borderJoinStyle: 'round',
              cubicInterpolationMode: 'monotone' 
            },
            point: {
              hitRadius: 8
            }
          },
          animation: {
            duration: 1200,
            easing: 'easeOutQuart'
          }
        }
      });
      
      // Add drop shadow to chart canvas
      ctx.canvas.style.boxShadow = '0 4px 16px rgba(0,0,0,0.08)';
      
      // Update the chart reference
      if (isLAN) {
        lanChart = newChart;
      } else {
        internetChart = newChart;
      }
    }
    
    // Filter change event handlers
    $('.lan-ip-filter, .lan-location-filter, .lan-month-filter, .lan-year-filter').on('change', function() {
      fetchLatencyData('lan');
    });
    
    $('.internet-ip-filter, .internet-location-filter, .internet-month-filter, .internet-year-filter').on('change', function() {
      fetchLatencyData('internet');
    });
    
    // Add yearly view option to month filters
    $('.lan-month-filter, .internet-month-filter').each(function() {
      $(this).prepend('<option value="0">Full Year</option>');
    });
    
    // Enhance the UI elements
    $('.card').addClass('shadow-sm border-0');
    $('.card-header').addClass('border-bottom-0');
    $('.form-select').addClass('border-0 shadow-sm');
    $('.chart-container').addClass('p-2');
    
    // Initial data load
    loadFilterOptions();
    fetchLatencyData('lan');
    fetchLatencyData('internet');
    
    // Refresh data when "Refresh Data" button is clicked
    $('#refreshBtn').on('click', function() {
      fetchLatencyData('lan');
      fetchLatencyData('internet');
    });
  });
  
  // Bootstrap tooltips initialization
  document.addEventListener('DOMContentLoaded', function() {
    // Initialize tooltips
    var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'))
    var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
      return new bootstrap.Tooltip(tooltipTriggerEl)
    });
    
    // Make the charts responsive to filter collapse/expand
    document.querySelectorAll('.filter-toggle').forEach(function(button) {
      button.addEventListener('click', function() {
        setTimeout(function() {
          window.dispatchEvent(new Event('resize'));
        }, 350);
      });
    });
  });

  document.addEventListener('DOMContentLoaded', function() {
    var tabEls = document.querySelectorAll('button[data-bs-toggle="tab"]');
    
    tabEls.forEach(function(tabEl) {
        tabEl.addEventListener('click', function(event) {
            event.preventDefault();
            
            // Remove active class from all tabs and tab panes
            document.querySelectorAll('#deviceTabs .nav-link').forEach(function(tab) {
                tab.classList.remove('active');
                tab.setAttribute('aria-selected', 'false');
            });
            
            document.querySelectorAll('.tab-pane').forEach(function(pane) {
                pane.classList.remove('show', 'active');
            });
            
            // Add active class to current tab and tab pane
            this.classList.add('active');
            this.setAttribute('aria-selected', 'true');
            
            var target = document.querySelector(this.getAttribute('data-bs-target'));
            if (target) {
                target.classList.add('show', 'active');
            }
        });
    });
});
</script>

<script>
        $(document).ready(function() {
            $('#historyTable').DataTable({
                responsive: true,
                pageLength: 10,
                order: [[4, 'desc']], // sort by Last Seen
            });
        });
</script>

<!-- Add this JavaScript to the bottom of your page or in your JS file -->
<script>
document.addEventListener('DOMContentLoaded', function() {
  // Handle remarks button click
  const remarksButtons = document.querySelectorAll('.remarks-btn');
  remarksButtons.forEach(button => {
    button.addEventListener('click', function() {
      const ipId = this.getAttribute('data-ip-id');
      const ip = this.getAttribute('data-ip');
      const description = this.getAttribute('data-description');
      const category = this.getAttribute('data-category');
      const location = this.getAttribute('data-location');

      // Set modal field values
      document.getElementById('ip_id').value = ipId;
      document.getElementById('ip_address').value = ip;
      document.getElementById('description').value = description;
      document.getElementById('category').value = category;
      document.getElementById('location').value = location;

      // Set today's date as default
      const today = new Date().toISOString().split('T')[0];
      document.getElementById('date').value = today;

      // Clear previous logs
      document.getElementById('offlineLogsList').innerHTML = '';

      // Now fetch offline logs for today's date
      fetchOfflineLogs(ipId, today);
    });
  });

  // Handle date change to fetch offline logs for that date
  document.getElementById('date').addEventListener('change', function() {
    const date = this.value;
    const ipId = document.getElementById('ip_id').value;
    if (date && ipId) {
      fetchOfflineLogs(ipId, date);
    }
  });

  // Function to fetch offline logs
  function fetchOfflineLogs(ipId, date) {
    const logsList = document.getElementById('offlineLogsList');
    logsList.innerHTML = '<div class="alert alert-info">Loading offline logs...</div>';

    fetch(`get_offline_logs.php?ip_id=${ipId}&date=${date}`)
      .then(response => {
        if (!response.ok) throw new Error('Network response was not ok');
        return response.json();
      })
      .then(data => {
        logsList.innerHTML = '';
        if (data.length > 0) {
          data.forEach(log => {
            const time = new Date(log.created_at).toLocaleTimeString();
            const item = document.createElement('div');
            item.className = 'list-group-item d-flex justify-content-between align-items-center';
            item.innerHTML = `
              <div>
                <span class="fw-bold">${time}</span>
                <span class="ms-3">Latency: ${log.latency} ms</span>
              </div>
              <span class="badge bg-danger rounded-pill">Offline</span>
            `;
            logsList.appendChild(item);
          });
        } else {
          logsList.innerHTML = '<div class="alert alert-info">No offline logs found for this date.</div>';
        }
      })
      .catch(error => {
        console.error('Error fetching offline logs:', error);
        logsList.innerHTML = '<div class="alert alert-danger">Error loading offline logs. Please check console for details.</div>';
      });
  }

  // Handle save button click
  document.getElementById('saveRemarksBtn').addEventListener('click', function() {
    const form = document.getElementById('remarksForm');

    // Check if form is valid
    if (form.checkValidity()) {
      // Show loading state with SweetAlert2
      Swal.fire({
        title: 'Saving...',
        text: 'Please wait while we save your remarks',
        allowOutsideClick: false,
        didOpen: () => { Swal.showLoading(); }
      });

      // Build FormData
      const formData = new FormData();
      formData.append('action', 'save_remark');
      formData.append('ip_id', document.getElementById('ip_id').value);
      formData.append('ip_address', document.getElementById('ip_address').value);
      formData.append('description', document.getElementById('description').value);
      formData.append('category', document.getElementById('category').value);
      formData.append('location', document.getElementById('location').value);
      formData.append('date', document.getElementById('date').value);
      formData.append('time_from', document.getElementById('time_from').value);
      formData.append('time_to', document.getElementById('time_to').value);
      // ✅ Dito na tumutugma sa PHP: 'remarks'
      formData.append('remarks', document.getElementById('remarks').value);

      // Send AJAX request
      fetch('../backend/remarks.php', {
        method: 'POST',
        body: formData
      })
      .then(response => {
        if (!response.ok) throw new Error('Network response was not ok');
        return response.json();
      })
      .then(data => {
        if (data.status === 'success') {
          bootstrap.Modal.getInstance(document.getElementById('remarksModal')).hide();
          Swal.fire({
            icon: 'success',
            title: 'Success!',
            text: 'Remark saved successfully!',
            confirmButtonColor: '#28a745'
          }).then(() => { window.location.href = 'dashboard.php'; });
        } else {
          Swal.fire({
            icon: 'error',
            title: 'Error',
            text: data.message || 'Failed to save remark',
            confirmButtonColor: '#dc3545'
          });
        }
      })
      .catch(error => {
        console.error('Error saving remark:', error);
        Swal.fire({
          icon: 'error',
          title: 'Oops...',
          text: 'An error occurred while saving the remark. Please try again.',
          confirmButtonColor: '#dc3545'
        });
      });

    } else {
      form.reportValidity();
      Swal.fire({
        icon: 'warning',
        title: 'Validation Error',
        text: 'Please fill in all required fields',
        confirmButtonColor: '#ffc107'
      });
    }
  });
});
</script>
