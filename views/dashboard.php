
<?php include '../backend/dashboard.php'; ?>
<?php include '../includes/header.php'; ?>
<?php include '../includes/loader.php'; ?>

<!-- Sa <head>, pagkatapos ng DataTables CSS -->
<style>
  /* Table header */
  #offlineTable thead th {
    font-size: 0.75rem;  /* maliit na header */
  }

  /* Table body */
  #offlineTable tbody td {
    font-size: 0.65rem;  /* mas maliit pa ang body text */
  }

  /* Search box at length dropdown label at control */
  #offlineTable_wrapper .dataTables_filter label,
  #offlineTable_wrapper .dataTables_length label {
    font-size: 0.7rem;
  }
  #offlineTable_wrapper .dataTables_filter input,
  #offlineTable_wrapper .dataTables_length select {
    font-size: 0.7rem;
    padding: 0.25em 0.4em;  /* bawasan ang padding para mas compact */
    height: auto;
  }

  /* Info text (“Showing X to Y of Z entries”) */
  #offlineTable_wrapper .dataTables_info {
    font-size: 0.7rem;
  }

  /* Pagination buttons */
  #offlineTable_wrapper .dataTables_paginate .paginate_button {
    font-size: 0.7rem;
  }
  /* Active/current page button */
  #offlineTable_wrapper .dataTables_paginate .paginate_button.current {
    background-color: #0d6efd;  /* Bootstrap primary bg */
    color: white !important;
    border: none;
  }
</style>

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

                                        <!-- Category Cards -->
                    <div class="row">
                        <?php 
                        // Define default icons for known categories
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
                                            <td><span class="badge bg-danger">Offline</span></td>
                                        </tr>
                                        <?php endforeach; ?>
                                        <?php else: ?>
                                        <tr>
                                            <td colspan="5" class="text-center">No offline devices</td>
                                        </tr>
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
                                    <i class="bi bi-speedometer me-2"></i> High Latency Devices
                                </div>
                                <div class="card-body table-container">
                                    <table class="table table-hover" id="latencyTable">
                                        <thead>
                                            <tr>
                                                <th>IP Address</th>
                                                <th>Location</th>
                                                <th>Category</th>
                                                <th>Latency</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php if(count($highLatencyDevices) > 0): ?>
                                                <?php foreach($highLatencyDevices as $device): ?>
                                                <tr class="<?= $device['latency'] > 200 ? 'high-latency' : '' ?>">
                                                    <td><?= $device['ip_address'] ?></td>
                                                    <td><?= $device['location'] ?></td>
                                                    <td><?= $device['category'] ?></td>
                                                    <td><?= $device['latency'] ?> ms</td>
                                                </tr>
                                                <?php endforeach; ?>
                                            <?php else: ?>
                                                <tr>
                                                    <td colspan="4" class="text-center">No high latency devices</td>
                                                </tr>
                                            <?php endif; ?>
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

   <script src="../js/dashboard.js"></script>