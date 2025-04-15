<?php
require_once '../config/db.php';
requireLogin();

// Get data from database
$dataRows = $conn->query("SELECT * FROM add_ip ORDER BY date DESC")->fetchAll(PDO::FETCH_ASSOC);

// Calculate summary statistics
$totalIPs = count($dataRows);
$onlineIPs = array_filter($dataRows, function($row) { return $row['status'] === 'online'; });
$onlineCount = count($onlineIPs);
$offlineCount = $totalIPs - $onlineCount;

// Calculate average latency of online IPs
$totalLatency = 0;
foreach($onlineIPs as $ip) {
    $totalLatency += floatval($ip['latency']);
}
$avgLatency = $onlineCount > 0 ? number_format($totalLatency / $onlineCount, 2) : 0;

// Group by location for chart
$locationData = [];
foreach($dataRows as $row) {
    $location = $row['location'];
    if(!isset($locationData[$location])) {
        $locationData[$location] = 0;
    }
    $locationData[$location]++;
}

// Get latency data for line chart
$latencyData = [];
foreach($dataRows as $row) {
    if($row['status'] === 'online') {
        $latencyData[] = [
            'ip' => $row['ip_address'],
            'latency' => floatval($row['latency'])
        ];
    }
}
// Sort by latency for the chart
usort($latencyData, function($a, $b) {
    return $a['latency'] <=> $b['latency'];
});
?>

<?php include '../includes/header.php'; ?>
<!-- Include the dashboard CSS file -->
<link rel="stylesheet" href="../assets/css/dashboard.css">

<div class="container-fluid">
  <div class="row">
    <?php include '../includes/sidebar.php'; ?>

    <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4 content">
      <div class="d-flex justify-content-between flex-wrap flex-md-nowrap 
                  align-items-center pt-3 pb-2 mb-3 border-bottom">
        <h1 class="h2">IP Monitoring Dashboard</h1>
        <div class="btn-toolbar mb-2 mb-md-0">
          <div class="btn-group me-2">
            <form method="POST" action="../backend/process.php" class="d-inline">
              <input type="hidden" name="action" value="refresh_all">
              <button type="submit" class="btn btn-dashboard btn-refresh">
                <i class="fas fa-sync"></i> Refresh All
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

      <!-- Summary Cards -->
      <div class="row mb-4">
        <div class="col-xl-3 col-md-6">
          <div class="card summary-card primary">
            <div class="card-body">
              <div class="row align-items-center">
                <div class="col mr-2">
                  <div class="card-title text-uppercase">Total IPs</div>
                  <div class="card-value"><?= $totalIPs ?></div>
                </div>
                <div class="col-auto">
                  <i class="fas fa-ethernet fa-2x text-gray-300"></i>
                </div>
              </div>
            </div>
          </div>
        </div>
        <div class="col-xl-3 col-md-6">
          <div class="card summary-card success">
            <div class="card-body">
              <div class="row align-items-center">
                <div class="col mr-2">
                  <div class="card-title text-uppercase">Online</div>
                  <div class="card-value"><?= $onlineCount ?></div>
                </div>
                <div class="col-auto">
                  <i class="fas fa-check-circle fa-2x text-gray-300"></i>
                </div>
              </div>
            </div>
          </div>
        </div>
        <div class="col-xl-3 col-md-6">
          <div class="card summary-card danger">
            <div class="card-body">
              <div class="row align-items-center">
                <div class="col mr-2">
                  <div class="card-title text-uppercase">Offline</div>
                  <div class="card-value"><?= $offlineCount ?></div>
                </div>
                <div class="col-auto">
                  <i class="fas fa-times-circle fa-2x text-gray-300"></i>
                </div>
              </div>
            </div>
          </div>
        </div>
        <div class="col-xl-3 col-md-6">
          <div class="card summary-card info">
            <div class="card-body">
              <div class="row align-items-center">
                <div class="col mr-2">
                  <div class="card-title text-uppercase">Avg Latency</div>
                  <div class="card-value"><?= $avgLatency ?> ms</div>
                </div>
                <div class="col-auto">
                  <i class="fas fa-tachometer-alt fa-2x text-gray-300"></i>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Charts Row -->
      <div class="row mb-4">
        <!-- Latency Chart -->
        <div class="col-md-8">
          <div class="card dashboard-card">
            <div class="card-header">
              <h5 class="card-title"><i class="fas fa-chart-bar me-2"></i>Latency by IP</h5>
            </div>
            <div class="card-body">
              <div class="chart-container">
                <canvas id="latencyChart"></canvas>
              </div>
            </div>
          </div>
        </div>
        
        <!-- Status Distribution Chart -->
        <div class="col-md-4">
          <div class="card dashboard-card">
            <div class="card-header">
              <h5 class="card-title"><i class="fas fa-chart-pie me-2"></i>Status Distribution</h5>
            </div>
            <div class="card-body">
              <div class="chart-container">
                <canvas id="statusChart"></canvas>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Location Distribution and Latest Activity -->
      <div class="row mb-4">
        <div class="col-md-6">
          <div class="card dashboard-card">
            <div class="card-header">
              <h5 class="card-title"><i class="fas fa-map-marker-alt me-2"></i>IPs by Location</h5>
            </div>
            <div class="card-body">
              <div class="chart-container">
                <canvas id="locationChart"></canvas>
              </div>
            </div>
          </div>
        </div>
        <div class="col-md-6">
          <div class="card dashboard-card">
            <div class="card-header">
              <h5 class="card-title"><i class="fas fa-history me-2"></i>Latest Activity</h5>
            </div>
            <div class="card-body">
              <div class="latest-activity">
                <table class="table table-dashboard table-hover table-sm">
                  <thead>
                    <tr>
                      <th>IP Address</th>
                      <th>Status</th>
                      <th>Latency</th>
                      <th>Location</th>
                    </tr>
                  </thead>
                  <tbody id="latest-activity-table">
                    <?php foreach(array_slice($dataRows, 0, 5) as $row): ?>
                    <tr>
                      <td><?= htmlspecialchars($row['ip_address']) ?></td>
                      <td>
                        <span class="status-indicator <?= $row['status'] === 'online' ? 'status-online' : 'status-offline' ?>"></span>
                        <?= ucfirst(htmlspecialchars($row['status'])) ?>
                      </td>
                      <td><?= htmlspecialchars($row['latency']) ?> ms</td>
                      <td><?= htmlspecialchars($row['location']) ?></td>
                    </tr>
                    <?php endforeach; ?>
                  </tbody>
                </table>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- IP Monitoring Table -->
      <div class="card dashboard-card mb-4">
        <div class="card-header d-flex justify-content-between align-items-center">
          <h5 class="card-title"><i class="fas fa-table me-2"></i>IP Monitoring Table</h5>
          <button type="button" class="btn btn-sm btn-dashboard btn-primary" data-bs-toggle="modal" data-bs-target="#addModal">
            <i class="fas fa-plus"></i> Add IP
          </button>
        </div>
        <div class="card-body">
          <div class="table-responsive">
            <table class="table table-dashboard table-hover mb-0">
              <thead>
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
              <tbody id="monitoring-table">
                <?php foreach ($dataRows as $row): ?>
                  <tr>
                    <td><?= htmlspecialchars($row['date']) ?></td>
                    <td><?= htmlspecialchars($row['ip_address']) ?></td>
                    <td><?= htmlspecialchars($row['description']) ?></td>
                    <td><?= htmlspecialchars($row['location']) ?></td>
                    <td id="latency-<?= $row['id'] ?>"><?= htmlspecialchars($row['latency']) ?> ms</td>
                    <td id="status-<?= $row['id'] ?>">
                      <span class="status-indicator <?= $row['status'] === 'online' ? 'status-online' : 'status-offline' ?>"></span>
                      <?= ucfirst(htmlspecialchars($row['status'])) ?>
                    </td>
                    <td>
                      <button type="button"
                              class="btn btn-sm btn-primary view-details"
                              data-bs-toggle="modal"
                              data-bs-target="#dataModal"
                              data-date="<?= htmlspecialchars($row['date']) ?>"
                              data-ip="<?= htmlspecialchars($row['ip_address']) ?>"
                              data-description="<?= htmlspecialchars($row['description']) ?>"
                              data-location="<?= htmlspecialchars($row['location']) ?>">
                        <i class="fas fa-eye"></i>
                      </button>
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
              <h5 class="modal-title">IP Details</h5>
              <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
              <div class="mb-3">
                <label class="fw-bold">Date:</label>
                <p id="modal-date" class="ms-2"></p>
              </div>
              <div class="mb-3">
                <label class="fw-bold">IP Address:</label>
                <p id="modal-ip" class="ms-2"></p>
              </div>
              <div class="mb-3">
                <label class="fw-bold">Description:</label>
                <p id="modal-description" class="ms-2"></p>
              </div>
              <div class="mb-3">
                <label class="fw-bold">Location:</label>
                <p id="modal-location" class="ms-2"></p>
              </div>
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
              <h5 class="modal-title">Add New IP</h5>
              <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
              <div class="mb-3">
                <label class="form-label">Date</label>
                <input type="date" name="date" class="form-control" value="<?= date('Y-m-d') ?>" required>
              </div>
              <div class="mb-3">
                <label class="form-label">IP Address</label>
                <input type="text" name="ip_address" class="form-control" placeholder="e.g. 192.168.1.1" required>
              </div>
              <div class="mb-3">
                <label class="form-label">Description</label>
                <textarea name="description" class="form-control" rows="2" placeholder="Enter device description" required></textarea>
              </div>
              <div class="mb-3">
                <label class="form-label">Location</label>
                <input type="text" name="location" class="form-control" placeholder="e.g. Server Room" required>
              </div>
            </div>
            <div class="modal-footer">
              <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
              <button type="submit" class="btn btn-primary">Save</button>
            </div>
          </form>
        </div>
      </div>

    </main>
  </div>
</div>

<?php include '../includes/footer.php'; ?>

<!-- Include Chart.js -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<script>
// Chart data preparation
const statusData = {
  labels: ['Online', 'Offline'],
  datasets: [{
    data: [<?= $onlineCount ?>, <?= $offlineCount ?>],
    backgroundColor: ['#1cc88a', '#e74a3b'],
    hoverBackgroundColor: ['#17a673', '#be2617'],
    hoverOffset: 4
  }]
};

const locationLabels = <?= json_encode(array_keys($locationData)) ?>;
const locationCounts = <?= json_encode(array_values($locationData)) ?>;

const latencyIPs = <?= json_encode(array_column($latencyData, 'ip')) ?>;
const latencyValues = <?= json_encode(array_column($latencyData, 'latency')) ?>;

// Initialize charts when DOM content is loaded
document.addEventListener('DOMContentLoaded', () => {
  // Status Pie Chart
  const statusChart = new Chart(document.getElementById('statusChart'), {
    type: 'doughnut',
    data: statusData,
    options: {
      responsive: true,
      maintainAspectRatio: false,
      cutout: '70%',
      plugins: {
        legend: {
          position: 'bottom',
          labels: {
            padding: 20,
            boxWidth: 12
          }
        },
        tooltip: {
          callbacks: {
            label: function(context) {
              return context.label + ': ' + context.parsed + ' (' + 
                Math.round((context.parsed / (<?= $onlineCount ?> + <?= $offlineCount ?>)) * 100) + '%)';
            }
          }
        }
      }
    }
  });

  // Location Bar Chart
  const locationChart = new Chart(document.getElementById('locationChart'), {
    type: 'bar',
    data: {
      labels: locationLabels,
      datasets: [{
        label: 'Number of IPs',
        data: locationCounts,
        backgroundColor: '#4e73df',
        hoverBackgroundColor: '#2e59d9',
        borderColor: '#4e73df',
        borderWidth: 1
      }]
    },
    options: {
      responsive: true,
      maintainAspectRatio: false,
      scales: {
        y: {
          beginAtZero: true,
          ticks: {
            precision: 0
          }
        }
      },
      plugins: {
        legend: {
          display: false
        }
      }
    }
  });

  // Latency Bar Chart
  const latencyChart = new Chart(document.getElementById('latencyChart'), {
    type: 'bar',
    data: {
      labels: latencyIPs,
      datasets: [{
        label: 'Latency (ms)',
        data: latencyValues,
        backgroundColor: '#36b9cc',
        hoverBackgroundColor: '#2c9faf',
        borderColor: '#36b9cc',
        borderWidth: 1
      }]
    },
    options: {
      responsive: true,
      maintainAspectRatio: false,
      scales: {
        y: {
          beginAtZero: true,
          title: {
            display: true,
            text: 'Milliseconds (ms)'
          }
        }
      },
      plugins: {
        legend: {
          display: false
        },
        tooltip: {
          callbacks: {
            title: function(tooltipItems) {
              return 'IP: ' + tooltipItems[0].label;
            },
            label: function(context) {
              return 'Latency: ' + context.parsed.y + ' ms';
            }
          }
        }
      }
    }
  });

  // Set up modal details population
  const dataModal = document.getElementById('dataModal');
  dataModal.addEventListener('show.bs.modal', e => {
    const btn = e.relatedTarget;
    document.getElementById('modal-date').textContent = btn.dataset.date;
    document.getElementById('modal-ip').textContent = btn.dataset.ip;
    document.getElementById('modal-description').textContent = btn.dataset.description;
    document.getElementById('modal-location').textContent = btn.dataset.location;
  });
});

// Auto-update latency and status
function refreshData() {
  fetch('../backend/get_latency.php')
    .then(response => response.json())
    .then(data => {
      data.forEach(row => {
        // Update table cells
        const latencyCell = document.getElementById('latency-' + row.id);
        const statusCell = document.getElementById('status-' + row.id);
        
        if (latencyCell) {
          latencyCell.textContent = row.latency + ' ms';
        }
        if (statusCell) {
          let statusHTML = `<span class="status-indicator ${row.status === 'online' ? 'status-online' : 'status-offline'}"></span>`;
          statusHTML += row.status === 'online' ? 'Online' : 'Offline';
          statusCell.innerHTML = statusHTML;
        }
      });
      
      // Update latest activity table
      let latestHTML = '';
      data.slice(0, 5).forEach(row => {
        latestHTML += `
          <tr>
            <td>${row.ip_address}</td>
            <td>
              <span class="status-indicator ${row.status === 'online' ? 'status-online' : 'status-offline'}"></span>
              ${row.status === 'online' ? 'Online' : 'Offline'}
            </td>
            <td>${row.latency} ms</td>
            <td>${row.location}</td>
          </tr>
        `;
      });
      document.getElementById('latest-activity-table').innerHTML = latestHTML;
    })
    .catch(error => console.error('Error fetching data:', error));
}

// Refresh data every 5 seconds
setInterval(refreshData, 1000);
</script>