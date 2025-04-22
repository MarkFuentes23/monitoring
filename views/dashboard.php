<?php
 include '../backend/dashboard.php';
?>

<?php include '../includes/header.php'; ?>
<link rel="stylesheet" href="../css/dasshboard.css">

<div id="loader" class="d-none">
    <div class="loader-container">
        <div class="spinner-wrapper">
            <div class="spinner-ring"></div>
            <div class="spinner-ring"></div>
            <div class="spinner-ring"></div>
        </div>
        <div id="loader-text">Loading... 0s</div>
        <div class="progress-container">
            <div class="progress-bar"></div>
        </div>
        <div class="loader-dots">
            <div class="loader-dot"></div>
            <div class="loader-dot"></div>
            <div class="loader-dot"></div>
        </div>
    </div>
</div>

    <div class="row">
            <?php include '../includes/sidebar.php'; ?>
        <div class="col-md">
            <div class="container">
                <div class="row mb-4">
                    <div class="col-md-4 text-end">
                        <button id="refreshBtn" class="btn btn-primary btn-refresh text-end">
                            <i class="bi bi-arrow-clockwise"></i> Refresh Data
                        </button>
                    </div>
                </div>

                <!-- Summary Cards -->
                <div class="row">
                    <div class="col-md-4">
                        <div class="card summary-card">
                            <div class="card-body">
                                <div class="stats-icon text-primary">
                                    <i class="bi bi-hdd-network"></i>
                                </div>
                                <h5 class="card-title">Total IP Addresses</h5>
                        <h3><?= $summaryData['total'] ?></h3>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card summary-card">
                    <div class="card-body">
                        <div class="stats-icon text-success">
                            <i class="bi bi-check-circle"></i>
                        </div>
                        <h5 class="card-title">Online Devices</h5>
                        <h3 class="online"><?= $summaryData['online'] ?></h3>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card summary-card">
                    <div class="card-body">
                        <div class="stats-icon text-danger">
                            <i class="bi bi-x-circle"></i>
                        </div>
                        <h5 class="card-title">Offline Devices</h5>
                        <h3 class="offline"><?= $summaryData['offline'] ?></h3>
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
            
            $categoryClasses = [
                'LAN' => 'category-lan',
                'CCTV' => 'category-cctv',
                'Server' => 'category-server',
                'Internet' => 'category-internet'
            ];
            
            foreach($categoryStats as $category => $stats): 
            ?>
            <div class="col-md-3">
                <div class="card category-card <?= $categoryClasses[$category] ?? '' ?>">
                    <div class="card-header d-flex align-items-center">
                        <i class="bi <?= $categoryIcons[$category] ?? 'bi-gear' ?> me-2"></i>
                        <?= $category ?>
                    </div>
                    <div class="card-body">
                        <p><strong>Total IPs:</strong> <?= $stats['total'] ?></p>
                        <p><strong>Online:</strong> <span class="online"><?= $stats['online'] ?></span></p>
                        <p><strong>Offline:</strong> <span class="offline"><?= $stats['offline'] ?></span></p>
                        <p><strong>Avg Latency:</strong> <?= $stats['avg_latency'] ?> ms</p>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>

        <!-- Tables Section -->
        <div class="row mt-4">
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header bg-danger text-white">
                        <i class="bi bi-exclamation-triangle-fill me-2"></i> Offline Devices
                    </div>
                    <div class="card-body table-container">
                        <table class="table table-striped table-hover" id="offlineTable">
                            <thead>
                                <tr>
                                    <th>IP Address</th>
                                    <th>Location</th>
                                    <th>Category</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if(count($offlineDevices) > 0): ?>
                                    <?php foreach($offlineDevices as $device): ?>
                                    <tr>
                                        <td><?= $device['ip_address'] ?></td>
                                        <td><?= $device['location'] ?></td>
                                        <td><?= $device['category'] ?></td>
                                        <td><span class="badge bg-danger">Offline</span></td>
                                    </tr>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="4" class="text-center">No offline devices</td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- High Latency Devices Table -->
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header bg-warning text-dark">
                        <i class="bi bi-speedometer me-2"></i> High Latency Devices
                    </div>
                    <div class="card-body table-container">
                        <table class="table table-striped table-hover" id="latencyTable">
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
              <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="dashborad.js"></script>
</body>
</html>