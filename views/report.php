<?php
require_once '../config/db.php';
requireLogin();

// Validate report ID
if (!isset($_GET['report']) || !is_numeric($_GET['report'])) {
    $_SESSION['error'] = "Invalid report ID.";
    header("Location: monitoring.php");
    exit;
}
$report_id = (int)$_GET['report'];

// Fetch device info
$stmt = $conn->prepare("SELECT * FROM add_ip WHERE id = ?");
$stmt->execute([$report_id]);
$device_data = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$device_data) {
    $_SESSION['error'] = "Device not found!";
    header("Location: monitoring.php");
    exit;
}

// Fetch last 30 days of stats
$thirtyDaysAgo = date('Y-m-d', strtotime('-30 days'));
$stmt = $conn->prepare("
    SELECT 
        DATE(created_at) AS log_date,
        AVG(latency) AS avg_latency,
        MIN(latency) AS min_latency,
        MAX(latency) AS max_latency,
        SUM(CASE WHEN status = 'offline' THEN 1 ELSE 0 END) AS offline_count,
        COUNT(*) AS total_checks
    FROM ping_logs
    WHERE ip_id = ? AND created_at >= ?
    GROUP BY DATE(created_at)
    ORDER BY log_date DESC
");
$stmt->execute([$report_id, $thirtyDaysAgo]);
$daily_stats = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get hourly distribution of outages
$stmt = $conn->prepare("
    SELECT 
        HOUR(created_at) AS hour_of_day,
        COUNT(*) AS check_count,
        SUM(CASE WHEN status = 'offline' THEN 1 ELSE 0 END) AS offline_count
    FROM ping_logs
    WHERE ip_id = ? AND created_at >= ?
    GROUP BY HOUR(created_at)
    ORDER BY hour_of_day
");
$stmt->execute([$report_id, $thirtyDaysAgo]);
$hourly_stats = $stmt->fetchAll(PDO::FETCH_ASSOC);

include '../includes/header.php';
?>

<div class="container-fluid">
  <div class="row">
    <?php include '../includes/sidebar.php'; ?>

    <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4 content">
      <div class="d-flex justify-content-between flex-wrap flex-md-nowrap 
                  align-items-center pt-3 pb-2 mb-3 border-bottom">
        <div>
          <h1 class="h2">Device Report: <?= htmlspecialchars($device_data['description']) ?></h1>
          <p><strong>Location:</strong> <?= htmlspecialchars($device_data['location']) ?></p>
        </div>
        <div class="btn-toolbar mb-2 mb-md-0">
        <a href="report_pdf.php?report=<?= urlencode($report_id) ?>" target="_blank" class="btn btn-sm btn-primary ms-2">
          <i class="fas fa-print"></i> Print Report
        </a>
          <a href="monitoring.php" class="btn btn-sm btn-outline-secondary">
            <i class="fas fa-arrow-left"></i> Back to Monitoring
          </a>
        </div>
      </div>

      <?php if (!empty($_SESSION['error'])): ?>
        <div class="alert alert-danger"><?= $_SESSION['error']; unset($_SESSION['error']); ?></div>
      <?php endif; ?>
      <?php if (!empty($_SESSION['success'])): ?>
        <div class="alert alert-success"><?= $_SESSION['success']; unset($_SESSION['success']); ?></div>
      <?php endif; ?>

      <!-- Device Overview -->
      <div class="card mb-4 shadow-sm">
        <div class="card-header">
          <h5 class="card-title mb-0">Device Overview</h5>
        </div>
        <div class="card-body">
          <div class="row">
            <div class="col-md-6">
              <table class="table table-bordered">
                <tr>
                  <th width="35%" class="table-secondary">IP Address:</th>
                  <td><?= htmlspecialchars($device_data['ip_address']) ?></td>
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
                      <span class="badge bg-success">Online</span>
                    <?php else: ?>
                      <span class="badge bg-danger">Offline</span>
                    <?php endif; ?>
                  </td>
                </tr>
                <tr>
                  <th class="table-secondary">Current Latency:</th>
                  <td>
                    <?= htmlspecialchars($device_data['latency']) ?> ms
                    <?php 
                      $lat = (float)$device_data['latency'];
                      if ($lat >= 150) {
                          echo '<span class="badge bg-danger">Critical Latency</span>';
                      } elseif ($lat >= 100) {
                          echo '<span class="badge bg-warning">High Latency</span>';
                      } else {
                          echo '<span class="badge bg-info">Good Latency</span>';
                      }
                    ?>
                  </td>
                </tr>
                <tr>
                  <th class="table-secondary">Added Date:</th>
                  <td><?= date("F j, Y", strtotime($device_data['date'])) ?></td>
                </tr>
              </table>
            </div>
            <div class="col-md-6">
              <?php
                $total_checks = 0;
                $total_offline = 0;
                $latencies = [];
                $min_lat = PHP_INT_MAX;
                $max_lat = 0;
                
                foreach ($daily_stats as $d) {
                  $total_checks  += $d['total_checks'];
                  $total_offline += $d['offline_count'];
                  $latencies[]   = $d['avg_latency'];
                  
                  if ($d['min_latency'] < $min_lat) $min_lat = $d['min_latency'];
                  if ($d['max_latency'] > $max_lat) $max_lat = $d['max_latency'];
                }
                
                $avg_latency = count($latencies) ? array_sum($latencies)/count($latencies) : 0;
                $uptime_percent = $total_checks ? (($total_checks - $total_offline)/$total_checks)*100 : 0;
                $downtime_hours = $total_offline * 5 / 60; // Assuming checks every 5 minutes
              ?>
              <div class="row">
                <div class="col-lg-6 col-md-12">
                  <div class="metric-card <?= $uptime_percent >= 99.5 ? 'good' : ($uptime_percent >= 95 ? 'warning' : 'danger') ?>">
                    <h3><?= number_format($uptime_percent, 2) ?>%</h3>
                    <p class="mb-0">30-Day Uptime</p>
                  </div>
                </div>
                <div class="col-lg-6 col-md-12">
                  <div class="metric-card <?= $downtime_hours <= 1 ? 'good' : ($downtime_hours <= 24 ? 'warning' : 'danger') ?>">
                    <h3><?= number_format($downtime_hours, 1) ?> hrs</h3>
                    <p class="mb-0">Total Downtime</p>
                  </div>
                </div>
                <div class="col-lg-6 col-md-12">
                  <div class="metric-card <?= $avg_latency < 100 ? 'good' : ($avg_latency < 150 ? 'warning' : 'danger') ?>">
                    <h3><?= number_format($avg_latency, 2) ?> ms</h3>
                    <p class="mb-0">Avg Latency</p>
                  </div>
                </div>
                <div class="col-lg-6 col-md-12">
                  <div class="metric-card info">
                    <h3><?= $total_offline ?></h3>
                    <p class="mb-0">Offline Events</p>
                  </div>
                </div>
              </div>
              <div class="row mt-2">
                <div class="col-6">
                  <div class="border rounded p-2 text-center">
                    <p class="mb-0"><strong>Min Latency:</strong><br><?= number_format($min_lat, 2) ?> ms</p>
                  </div>
                </div>
                <div class="col-6">
                  <div class="border rounded p-2 text-center">
                    <p class="mb-0"><strong>Max Latency:</strong><br><?= number_format($max_lat, 2) ?> ms</p>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Charts Row -->
      <div class="row mb-4">
        <!-- Latency & Offline Events Chart -->
        <div class="col-md-8">
          <div class="card h-100 shadow-sm">
            <div class="card-header">
              <h5 class="card-title mb-0">Latency & Offline Events (Last 14 Days)</h5>
            </div>
            <div class="card-body">
              <div class="chart-container" style="position: relative; height:300px;">
                <canvas id="latencyChart"></canvas>
              </div>
            </div>
          </div>
        </div>

        <!-- Hourly Outage Distribution -->
        <div class="col-md-4">
          <div class="card h-100 shadow-sm">
            <div class="card-header">
              <h5 class="card-title mb-0">Outage Distribution by Hour</h5>
            </div>
            <div class="card-body">
              <div class="chart-container" style="position: relative; height:300px;">
                <canvas id="hourlyChart"></canvas>
              </div>
            </div>
          </div>
        </div>
      </div>
      
      <!-- Daily Status Table -->
      <div class="card mb-4 shadow-sm">
        <div class="card-header">
          <h5 class="card-title mb-0">Daily Performance Report (Last 30 Days)</h5>
        </div>
        <div class="card-body">
          <div class="table-responsive">
            <table class="table table-bordered table-striped mb-0">
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
                </tr>
              </thead>
              <tbody>
                <?php if (!empty($daily_stats)): ?>
                  <?php foreach ($daily_stats as $day): ?>
                    <?php
                      $u = $day['total_checks']
                          ? (($day['total_checks'] - $day['offline_count']) / $day['total_checks']) * 100
                          : 0;
                      
                      $status_class = '';
                      $status_text = '';
                      
                      if ($u == 100) {
                          $status_class = 'bg-success text-white';
                          $status_text = 'Excellent';
                      } elseif ($u >= 99.5) {
                          $status_class = 'bg-success text-white';
                          $status_text = 'Very Good';
                      } elseif ($u >= 95) {
                          $status_class = 'bg-warning';
                          $status_text = 'Average';
                      } else {
                          $status_class = 'bg-danger text-white';
                          $status_text = 'Poor';
                      }
                    ?>
                    <tr>
                      <td><?= date("D, M j, Y", strtotime($day['log_date'])) ?></td>
                      <td>
                        <?= number_format($day['avg_latency'], 2) ?> ms
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
                      <td><?= number_format($day['min_latency'], 2) ?> ms</td>
                      <td><?= number_format($day['max_latency'], 2) ?> ms</td>
                      <td><?= $day['offline_count'] ?></td>
                      <td><?= $day['total_checks'] ?></td>
                      <td><?= number_format($u, 2) ?>%</td>
                      <td class="<?= $status_class ?>"><?= $status_text ?></td>
                    </tr>
                  <?php endforeach; ?>
                <?php else: ?>
                  <tr>
                    <td colspan="8" class="text-center">No historical data available</td>
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

<?php include '../includes/footer.php'; ?>

<!-- Chart.js -->
<script>
document.addEventListener('DOMContentLoaded', function() {
  if (document.getElementById('latencyChart') && document.getElementById('hourlyChart')) {
    const labels = [];
    const latencyData = [];
    const minLatencyData = [];
    const maxLatencyData = [];
    const offlineData = [];
    
    <?php 
      $chart_data = array_slice($daily_stats, 0, 14);
      $chart_data = array_reverse($chart_data);
      foreach ($chart_data as $d): 
    ?>
      labels.push('<?= date("M j", strtotime($d['log_date'])) ?>');
      latencyData.push(<?= $d['avg_latency'] ?>);
      minLatencyData.push(<?= $d['min_latency'] ?>);
      maxLatencyData.push(<?= $d['max_latency'] ?>);
      offlineData.push(<?= $d['offline_count'] ?>);
    <?php endforeach; ?>

    const hourLabels = [];
    const hourlyOutages = [];
    
    <?php foreach ($hourly_stats as $hour): ?>
      hourLabels.push('<?= sprintf("%02d:00", $hour['hour_of_day']) ?>');
      hourlyOutages.push(<?= $hour['offline_count'] ?>);
    <?php endforeach; ?>

    function loadCharts() {
      if (typeof Chart !== 'undefined') {
        const latCtx = document.getElementById('latencyChart').getContext('2d');
        new Chart(latCtx, {
          type: 'line',
          data: {
            labels: labels,
            datasets: [
              { label: 'Avg Latency (ms)', data: latencyData, yAxisID: 'y', tension: 0.1, fill: false },
              { label: 'Min Latency (ms)', data: minLatencyData, yAxisID: 'y', borderDash: [5,5], tension: 0.1, fill: false },
              { label: 'Max Latency (ms)', data: maxLatencyData, yAxisID: 'y', borderDash: [5,5], tension: 0.1, fill: false },
              { label: 'Offline Events', data: offlineData, type: 'bar', yAxisID: 'y1' }
            ]
          },
          options: {
            responsive: true,
            maintainAspectRatio: false,
            interaction: { mode: 'index', intersect: false },
            scales: {
              y: { type: 'linear', position: 'left', title: { display: true, text: 'Latency (ms)' }, min: 0 },
              y1: { type: 'linear', position: 'right', grid: { drawOnChartArea: false }, title: { display: true, text: 'Offline Events' }, min: 0 }
            }
          }
        });

        const hourCtx = document.getElementById('hourlyChart').getContext('2d');
        new Chart(hourCtx, {
          type: 'bar',
          data: {
            labels: hourLabels,
            datasets: [{ label: 'Outages', data: hourlyOutages, borderWidth: 1 }]
          },
          options: {
            responsive: true,
            maintainAspectRatio: false,
            scales: {
              y: { beginAtZero: true, title: { display: true, text: 'Outage Count' } },
              x: { title: { display: true, text: 'Hour of Day' } }
            }
          }
        });
      } else {
        setTimeout(loadCharts, 100);
      }
    }

    if (typeof Chart === 'undefined') {
      const script = document.createElement('script');
      script.src = 'https://cdn.jsdelivr.net/npm/chart.js';
      script.onload = loadCharts;
      document.head.appendChild(script);
    } else {
      loadCharts();
    }
  }
});
</script>
