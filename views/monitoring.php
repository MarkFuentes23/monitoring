<?php
require_once '../config/db.php';
requireLogin();

// Get initial data from the database for initial load
$dataRows = $conn->query("SELECT * FROM add_ip ORDER BY date DESC")
               ->fetchAll(PDO::FETCH_ASSOC);

// Check if we're in report view
$report_mode = isset($_GET['report']) && is_numeric($_GET['report']);
$report_id = $report_mode ? (int)$_GET['report'] : 0;

// If in report mode, get the specific device data
$device_data = null;
$daily_stats = [];

if ($report_mode) {
    // Get the specific device data
    $stmt = $conn->prepare("SELECT * FROM add_ip WHERE id = ?");
    $stmt->execute([$report_id]);
    $device_data = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$device_data) {
        $_SESSION['error'] = "Device not found!";
        header("Location: monitoring.php");
        exit;
    }
    
    // Get historical data for the past 30 days
    $thirtyDaysAgo = date('Y-m-d', strtotime('-30 days'));
    $stmt = $conn->prepare("
        SELECT 
            DATE(created_at) as log_date,
            AVG(latency) as avg_latency,
            SUM(CASE WHEN status = 'offline' THEN 1 ELSE 0 END) as offline_count,
            COUNT(*) as total_checks
        FROM ping_logs
        WHERE ip_id = ? AND created_at >= ?
        GROUP BY DATE(created_at)
        ORDER BY log_date DESC
    ");
    $stmt->execute([$report_id, $thirtyDaysAgo]);
    $daily_stats = $stmt->fetchAll(PDO::FETCH_ASSOC);
}
?>
<?php include '../includes/header.php'; ?>

<div class="container-fluid">
  <div class="row">
    <?php include '../includes/sidebar.php'; ?>

    <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4 content">
      <?php if ($report_mode && $device_data): ?>
        <!-- REPORT VIEW -->
        <div class="d-flex justify-content-between flex-wrap flex-md-nowrap 
                    align-items-center pt-3 pb-2 mb-3 border-bottom">
          <h1 class="h2">Device Report: <?= htmlspecialchars($device_data['description']) ?></h1>
          <div class="btn-toolbar mb-2 mb-md-0">
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

        <div class="row mb-4">
          <div class="col-md-6">
            <div class="card h-100">
              <div class="card-header">
                <h5 class="card-title mb-0">Device Information</h5>
              </div>
              <div class="card-body">
                <table class="table">
                  <tr>
                    <th>IP Address:</th>
                    <td><?= htmlspecialchars($device_data['ip_address']) ?></td>
                  </tr>
                  <tr>
                    <th>Description:</th>
                    <td><?= htmlspecialchars($device_data['description']) ?></td>
                  </tr>
                  <tr>
                    <th>Location:</th>
                    <td><?= htmlspecialchars($device_data['location']) ?></td>
                  </tr>
                  <tr>
                    <th>Current Status:</th>
                    <td>
                      <?php if ($device_data['status'] === 'online'): ?>
                        <span class="badge bg-success">Online</span>
                      <?php else: ?>
                        <span class="badge bg-danger">Offline</span>
                      <?php endif; ?>
                    </td>
                  </tr>
                  <tr>
                    <th>Current Latency:</th>
                    <td>
                      <?= htmlspecialchars($device_data['latency']) ?> ms
                      <?php 
                        $latencyValue = (float)$device_data['latency'];
                        if($latencyValue >= 100) {
                          echo '<span class="badge bg-warning">High Latency</span>';
                        } else {
                          echo '<span class="badge bg-info">Low Latency</span>';
                        }
                      ?>
                    </td>
                  </tr>
                  <tr>
                    <th>Added Date:</th>
                    <td><?= date("F j, Y", strtotime($device_data['date'])) ?></td>
                  </tr>
                </table>
              </div>
            </div>
          </div>
          
          <div class="col-md-6">
            <div class="card h-100">
              <div class="card-header">
                <h5 class="card-title mb-0">Performance Summary</h5>
              </div>
              <div class="card-body">
                <?php
                  // Calculate summary statistics
                  $total_checks = 0;
                  $total_offline = 0;
                  $latency_values = [];
                  
                  foreach ($daily_stats as $day) {
                      $total_checks += $day['total_checks'];
                      $total_offline += $day['offline_count'];
                      $latency_values[] = $day['avg_latency'];
                  }
                  
                  $avg_latency = count($latency_values) > 0 ? array_sum($latency_values) / count($latency_values) : 0;
                  $uptime_percentage = $total_checks > 0 ? (($total_checks - $total_offline) / $total_checks) * 100 : 0;
                ?>
                
                <div class="row text-center">
                  <div class="col-md-4 mb-3">
                    <div class="border rounded p-3">
                      <h3><?= number_format($uptime_percentage, 2) ?>%</h3>
                      <p class="text-muted mb-0">Uptime</p>
                    </div>
                  </div>
                  <div class="col-md-4 mb-3">
                    <div class="border rounded p-3">
                      <h3><?= number_format($avg_latency, 2) ?> ms</h3>
                      <p class="text-muted mb-0">Avg Latency</p>
                    </div>
                  </div>
                  <div class="col-md-4 mb-3">
                    <div class="border rounded p-3">
                      <h3><?= $total_offline ?></h3>
                      <p class="text-muted mb-0">Offline Events</p>
                    </div>
                  </div>
                </div>
                
                <div class="mt-4">
                  <canvas id="latencyChart" width="400" height="200"></canvas>
                </div>
              </div>
            </div>
          </div>
        </div>
        
        <div class="card mb-4">
          <div class="card-header">
            <h5 class="card-title mb-0">Daily Status (Last 30 Days)</h5>
          </div>
          <div class="card-body">
            <div class="table-responsive">
              <table class="table table-bordered mb-0">
                <thead class="table-light">
                  <tr>
                    <th>Date</th>
                    <th>Average Latency</th>
                    <th>Offline Events</th>
                    <th>Total Checks</th>
                    <th>Uptime %</th>
                  </tr>
                </thead>
                <tbody>
                  <?php foreach ($daily_stats as $day): ?>
                    <tr>
                      <td><?= date("F j, Y", strtotime($day['log_date'])) ?></td>
                      <td>
                        <?= number_format($day['avg_latency'], 2) ?> ms
                        <?php if ($day['avg_latency'] >= 100): ?>
                          <span class="badge bg-warning">High Latency</span>
                        <?php else: ?>
                          <span class="badge bg-info">Low Latency</span>
                        <?php endif; ?>
                      </td>
                      <td><?= $day['offline_count'] ?></td>
                      <td><?= $day['total_checks'] ?></td>
                      <td>
                        <?php
                          $day_uptime = $day['total_checks'] > 0 ? 
                            (($day['total_checks'] - $day['offline_count']) / $day['total_checks']) * 100 : 0;
                          echo number_format($day_uptime, 2) . '%';
                        ?>
                      </td>
                    </tr>
                  <?php endforeach; ?>
                  <?php if (empty($daily_stats)): ?>
                    <tr>
                      <td colspan="5" class="text-center">No historical data available</td>
                    </tr>
                  <?php endif; ?>
                </tbody>
              </table>
            </div>
          </div>
        </div>

      <?php else: ?>
        <!-- MONITORING VIEW -->
        <div class="d-flex justify-content-between flex-wrap flex-md-nowrap 
                    align-items-center pt-3 pb-2 mb-3 border-bottom">
          <h1 class="h2">Monitoring</h1>
          <div class="btn-toolbar mb-2 mb-md-0">
            <div class="btn-group me-2">
              <!-- Add Data Button; no refresh/reload button -->
              <form method="POST" action="../backend/process.php" class="d-inline">
                <input type="hidden" name="action" value="add_data">
                <button type="button"
                        class="btn btn-sm btn-outline-secondary"
                        data-bs-toggle="modal"
                        data-bs-target="#addModal">
                  <i class="fas fa-plus"></i> Add
                </button>
              </form>
            </div>
          </div>
        </div>

        <?php if (!empty($_SESSION['error'])): ?>
          <div class="alert alert-danger"><?= $_SESSION['error']; unset($_SESSION['error']); ?></div>
        <?php endif; ?>
        <?php if (!empty($_SESSION['success'])): ?>
          <div class="alert alert-success"><?= $_SESSION['success']; unset($_SESSION['success']); ?></div>
        <?php endif; ?>

        <div class="card mb-4">
          <div class="card-body">
            <div class="table-responsive">
              <table class="table table-bordered mb-0">
                <thead class="table-light">
                  <tr>
                    <th>Date</th>
                    <th>IP Address</th>
                    <th>Description</th>
                    <th>Location</th>
                    <th>Latency</th>
                    <th>Status</th>
                    <th>Actions</th>
                  </tr>
                </thead>
                <tbody>
                  <?php foreach ($dataRows as $row): ?>
                    <tr class="clickable-row" data-href="monitoring.php?report=<?= $row['id'] ?>">
                      <td><?= date("F j, Y", strtotime($row['date'])) ?></td>
                      <td><?= htmlspecialchars($row['ip_address']) ?></td>
                      <td><?= htmlspecialchars($row['description']) ?></td>
                      <td><?= htmlspecialchars($row['location']) ?></td>
                      <!-- Latency cell now displays the numerical value plus a badge -->
                      <td id="latency-<?= $row['id'] ?>">
                        <?= htmlspecialchars($row['latency']) ?> ms 
                        <?php 
                          $latencyValue = (float)$row['latency'];
                          if($latencyValue >= 100) {
                            echo '<span class="badge bg-warning">High Latency</span>';
                          } else {
                            echo '<span class="badge bg-info">Low Latency</span>';
                          }
                        ?>
                      </td>
                      <td id="status-<?= $row['id'] ?>">
                        <?php if ($row['status'] === 'online'): ?>
                          <span class="badge bg-success">Online</span>
                        <?php else: ?>
                          <span class="badge bg-danger">Offline</span>
                        <?php endif; ?>
                      </td>
                      <td>
                        <div class="btn-group">
                          <button type="button"
                                  class="btn btn-sm btn-primary view-details"
                                  data-bs-toggle="modal"
                                  data-bs-target="#dataModal"
                                  data-date="<?= htmlspecialchars($row['date']) ?>"
                                  data-ip="<?= htmlspecialchars($row['ip_address']) ?>"
                                  data-description="<?= htmlspecialchars($row['description']) ?>"
                                  data-location="<?= htmlspecialchars($row['location']) ?>">
                            View
                          </button>
                          <a href="monitoring.php?report=<?= $row['id'] ?>" 
                             class="btn btn-sm btn-success">
                            Report
                          </a>
                        </div>
                      </td>
                    </tr>
                  <?php endforeach; ?>
                </tbody>
              </table>
            </div>
          </div>
        </div>

        <!-- View Details Modal -->
        <div class="modal fade" id="dataModal" tabindex="-1" aria-hidden="true">
          <div class="modal-dialog">
            <div class="modal-content">
              <div class="modal-header">
                <h5 class="modal-title">Data Details</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
              </div>
              <div class="modal-body">
                <p><strong>Date:</strong> <span id="modal-date"></span></p>
                <p><strong>IP Address:</strong> <span id="modal-ip"></span></p>
                <p><strong>Description:</strong> <span id="modal-description"></span></p>
                <p><strong>Location:</strong> <span id="modal-location"></span></p>
              </div>
              <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
              </div>
            </div>
          </div>
        </div>

        <!-- Add Data Modal -->
        <div class="modal fade" id="addModal" tabindex="-1" aria-hidden="true">
          <div class="modal-dialog">
            <form action="../backend/process.php" method="POST" class="modal-content">
              <input type="hidden" name="action" value="add_data">
              <div class="modal-header">
                <h5 class="modal-title">Add New Data</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
              </div>
              <div class="modal-body">
                <div class="mb-3">
                  <label class="form-label">Date</label>
                  <input type="date" name="date" class="form-control" required>
                </div>
                <div class="mb-3">
                  <label class="form-label">IP Address</label>
                  <input type="text" name="ip_address" class="form-control" required>
                </div>
                <div class="mb-3">
                  <label class="form-label">Description</label>
                  <textarea name="description" class="form-control" rows="2" required></textarea>
                </div>
                <div class="mb-3">
                  <label class="form-label">Location</label>
                  <input type="text" name="location" class="form-control" required>
                </div>
              </div>
              <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="submit" class="btn btn-primary">Save</button>
              </div>
            </form>
          </div>
        </div>
      <?php endif; ?>
    </main>
  </div>
</div>

<?php include '../includes/footer.php'; ?>

<script>
// Populate the view details modal from the button's data attributes
document.addEventListener('DOMContentLoaded', () => {
  const dataModal = document.getElementById('dataModal');
  if (dataModal) {
    dataModal.addEventListener('show.bs.modal', e => {
      const btn = e.relatedTarget;
      document.getElementById('modal-date').textContent        = btn.dataset.date;
      document.getElementById('modal-ip').textContent          = btn.dataset.ip;
      document.getElementById('modal-description').textContent = btn.dataset.description;
      document.getElementById('modal-location').textContent    = btn.dataset.location;
    });
  }
  
  // Make table rows clickable
  document.querySelectorAll('.clickable-row').forEach(row => {
    row.addEventListener('click', function(e) {
      if (e.target.tagName === 'BUTTON' || e.target.tagName === 'A' ||
          e.target.closest('button') || e.target.closest('a')) {
        return;
      }
      window.location.href = this.dataset.href;
    });
    row.style.cursor = 'pointer';
  });
  
  // Initialize chart if we're in report mode
  const chartCanvas = document.getElementById('latencyChart');
  if (chartCanvas) {
    const ctx = chartCanvas.getContext('2d');
    
    <?php if ($report_mode && !empty($daily_stats)): ?>
    const labels = [];
    const latencyData = [];
    const offlineData = [];
    
    <?php foreach (array_reverse(array_slice($daily_stats, 0, 14)) as $day): ?>
      labels.push('<?= date("M j", strtotime($day['log_date'])) ?>');
      latencyData.push(<?= $day['avg_latency'] ?>);
      offlineData.push(<?= $day['offline_count'] ?>);
    <?php endforeach; ?>
    
    new Chart(ctx, {
      type: 'line',
      data: {
        labels: labels,
        datasets: [
          {
            label: 'Latency (ms)',
            data: latencyData,
            borderColor: 'rgba(54, 162, 235, 1)',
            backgroundColor: 'rgba(54, 162, 235, 0.2)',
            yAxisID: 'y',
            tension: 0.1
          },
          {
            label: 'Offline Events',
            data: offlineData,
            borderColor: 'rgba(255, 99, 132, 1)',
            backgroundColor: 'rgba(255, 99, 132, 0.2)',
            yAxisID: 'y1',
            type: 'bar'
          }
        ]
      },
      options: {
        responsive: true,
        interaction: {
          mode: 'index',
          intersect: false,
        },
        scales: {
          y: {
            type: 'linear',
            display: true,
            position: 'left',
            title: {
              display: true,
              text: 'Latency (ms)'
            }
          },
          y1: {
            type: 'linear',
            display: true,
            position: 'right',
            grid: {
              drawOnChartArea: false,
            },
            title: {
              display: true,
              text: 'Offline Events'
            },
            min: 0,
            suggestedMax: 5
          }
        }
      }
    });
    <?php endif; ?>
  }
});

// Auto-update latency and status every 5 minutes
function refreshLatency() {
  fetch('../backend/get_latency.php')
    .then(response => response.json())
    .then(data => {
      data.forEach(row => {
        const latencyCell = document.getElementById('latency-' + row.id);
        const statusCell  = document.getElementById('status-' + row.id);
        
        if (latencyCell) {
          latencyCell.innerHTML = row.latency + ' ms ' +
            '<span class="badge ' + (row.latency >= 100 ? 'bg-warning' : 'bg-info') + '">' +
              (row.latency >= 100 ? 'High Latency' : 'Low Latency') +
            '</span>';
        }
        if (statusCell) {
          statusCell.innerHTML = row.status === 'online' ?
            '<span class="badge bg-success">Online</span>' :
            '<span class="badge bg-danger">Offline</span>';
        }
      });
    })
    .catch(error => console.error('Error fetching latency data:', error));
}

refreshLatency();

setInterval(refreshLatency, 300000);
</script>
