<?php include '../backend/dashboard.php'; ?>
<?php include '../backend/monthly_stats.php'; ?>
<?php include '../includes/header.php'; ?>
<?php include '../includes/loader.php'; ?>

<link rel="stylesheet" href="../css/report.css">

<div class="container-fluid">
        <div class="row">
            <?php include '../includes/sidebar.php'; ?>
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
                            <div class="card-header bg-dark text-white">
                                <i class="bi bi-calendar-check me-2"></i> Monthly Network Uptime Statistics
                            </div>
                            <div class="card-body table-container">

                                <!-- 2. FILTERS -->
                                <div class="filter-container mb-3">
                                <select id="categoryFilter" class="form-select d-inline-block w-auto me-2">
                                    <option value="">All Categories</option>
                                    <?php foreach($filterCategories as $cat): ?>
                                    <option value="<?= htmlspecialchars($cat) ?>"><?= htmlspecialchars($cat) ?></option>
                                    <?php endforeach; ?>
                                </select>

                                <select id="locationFilter" class="form-select d-inline-block w-auto">
                                    <option value="">All Locations</option>
                                    <?php foreach($filterLocations as $loc): ?>
                                    <option value="<?= htmlspecialchars($loc) ?>"><?= htmlspecialchars($loc) ?></option>
                                    <?php endforeach; ?>
                                </select>
                                </div>

                                <!-- 3. DATA TABLE -->
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
                                    if(count($monthlyStats) > 0): 
                                    foreach($monthlyStats as $device): 
                                        // Set row class based on uptime percentage
                                        if($device['uptime_percentage'] >= 99) {
                                        $rowClass = "uptime-excellent";
                                        } elseif($device['uptime_percentage'] >= 95) {
                                        $rowClass = "uptime-good";
                                        } elseif($device['uptime_percentage'] >= 90) {
                                        $rowClass = "uptime-warning";
                                        } else {
                                        $rowClass = "uptime-critical";
                                        }
                                    ?>
                                    <tr class="<?= $rowClass ?>">
                                    <td><?= htmlspecialchars($device['ip_address']) ?></td>
                                    <td><?= htmlspecialchars($device['location']) ?></td>
                                    <td><?= htmlspecialchars($device['category']) ?></td>
                                    <td><?= htmlspecialchars($device['description']) ?></td>
                                    <td><?= $device['offline_events'] ?></td>
                                    <td><?= number_format($device['uptime_percentage'], 2) ?>%</td>
                                    </tr>
                                    <?php 
                                    endforeach;
                                    endif; 
                                    ?>
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
    
   <script src="../js/dashboard.js"></script>

   
   <script>
$(document).ready(function() {
  // Initialize DataTables with export buttons
  $('#monthlyStatsTable').DataTable({
    dom: 'Bfrtip',
    buttons: [
      'copy', 'excel', 'pdf', 'print'
    ],
    pageLength: 10,
    order: [[5, 'desc']]
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