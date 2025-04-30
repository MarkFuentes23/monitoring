<?php include '../backend/dashboard.php'; ?>
<?php include '../backend/monthly_stats.php'; ?>
<?php include '../includes/header.php'; ?>


<link rel="stylesheet" href="../css/report.css">

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
    
<script>// Configuration
const REFRESH_INTERVAL = 15 * 60 * 1000;

let refreshTimer;
let progressInterval;
let loaderTimeout;
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
    refreshData(false); // Initial data load without forcing ping
    
    // Listen for events from monitoring page
    window.addEventListener('storage', function(e) {
        if (e.key === 'networkDataUpdated') {
            refreshData(false); // Refresh without forcing ping
        }
    });
    
    // Manual refresh button
    $('#refreshBtn').on('click', function() {
        showLoader(); // Show loader right away
        refreshData(true); // Force update
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
        success(data) {
            console.log("Data refreshed successfully");
            updateDashboard(data);
            $('#lastUpdated').text('Last updated: ' + data.timestamp);
            startRefreshTimer(); // Reset the timer after successful refresh
            
            // short delay so the UI updates are visible under the loader
            setTimeout(() => {
                hideLoader();
                
                if (data.newlyOfflineDevices?.length) {
                    showOfflineAlert(data.newlyOfflineDevices);
                }
            }, 500);
            
            // broadcast to other tabs
            localStorage.setItem('networkDataUpdated', Date.now());
        },
        error(xhr, status, error) {
            console.error('Error refreshing data:', error);
            
            // Clear and update tables on error
            offlineTable.clear().draw();
            latencyTable.clear().draw();
            
            startRefreshTimer();
            hideLoader();
        }
    });
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

function updateDashboard(data) {
    // Update summary counters
    animateCounter($('.card-value.total'), data.summary.total);
    animateCounter($('.card-value.online'), data.summary.online);
    animateCounter($('.card-value.offline'), data.summary.offline);
  
    // Update category cards
    for (const [category, stats] of Object.entries(data.categories)) {
        const card = $(`.category-card .card-header:contains("${category}")`).closest('.category-card');
        if (!card.length) continue;
        
        card.find('.d-flex:contains("Total IPs") .fw-bold').text(stats.total);
        card.find('.d-flex:contains("Online") .online').text(stats.online);
        card.find('.d-flex:contains("Offline") .offline').text(stats.offline);
        card.find('.d-flex:contains("Avg Latency") .fw-bold').text(stats.avg_latency + ' ms');
    }
  }
  
    // Update offline devices table using DataTables API
    offlineTable.clear();
      if (data.offlineDevices && data.offlineDevices.length) {
        data.offlineDevices.forEach(d => {
          offlineTable.row.add([
            d.ip_address,
            d.location || 'Unknown',
            d.category || 'Unknown',
            d.description || 'No description',
            '<span class="badge bg-danger badge-sm">Offline</span>',
            // ← Action column
            `<a href="report.php?report=${encodeURIComponent(d.id)}"
                class="btn btn-primary btn-sm">View</a>`
          ]);
        });
      }
      offlineTable.draw();

  
    // Update latency table using DataTables API
    latencyTable.clear();
      if (data.highLatencyDevices && data.highLatencyDevices.length) {
        data.highLatencyDevices.forEach(d => {
          const isHigh = parseFloat(d.latency) > 100;
          const badgeClass = isHigh ? 'bg-danger' : 'bg-success';
          const status     = isHigh ? 'High'      : 'Low';

          // add row, now with 6th "Action" column
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

          // reapply your row classes if needed
          $(rowNode).addClass(isHigh ? 'high-latency' : 'low-latency');
        });
      }
      latencyTable.draw();


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
}</script>

<!-- DataTables + Buttons init -->
<script>
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
</script>


<script>
$(document).ready(function() {
  var $table = $('#monthlyStatsTable');

  // If not yet initialized, init; otherwise reuse existing instance
  var table = $.fn.dataTable.isDataTable($table)
    ? $table.DataTable()
    : $table.DataTable({
        dom: 'Bfrtip',
        processing: true,
        language: {
          processing: '<div class="spinner-border" role="status"><span class="visually-hidden">Loading…</span></div>'
        },
        buttons: ['copy', 'excel', 'pdf', 'print'],
        pageLength: 10,
        order: [[5, 'desc']]
      });

  $('#categoryFilter, #locationFilter').on('change', function() {
    // show overlay
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
</script>


<!-- Add this JavaScript code before the closing </body> tag -->

<script>
$(document).ready(function() {
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
    
    // Create new chart with enhanced elegant design
    const gradient = ctx.createLinearGradient(0, 0, 0, 400);
    
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
</script>

<script>
// Initialize Bootstrap tooltips
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
</script>