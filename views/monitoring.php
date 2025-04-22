<?php
// views/monitoring.php
require_once '../config/db.php';
requireLogin();

// initial load of table data WITHOUT pinging
$dataRows = $conn
  ->query("SELECT * FROM add_ip ORDER BY date DESC")
  ->fetchAll(PDO::FETCH_ASSOC);

// Get locations and categories for dropdowns
$locations = $conn->query("SELECT * FROM locations")->fetchAll(PDO::FETCH_ASSOC);
$categories = $conn->query("SELECT * FROM categories")->fetchAll(PDO::FETCH_ASSOC);

$categoryData = [];
foreach ($dataRows as $row) {
  $category = !empty($row['category']) ? $row['category'] : 'Uncategorized';
  if (!isset($categoryData[$category])) {
    $categoryData[$category] = [];
  }
  $categoryData[$category][] = $row;
}

$standardCategories = ['Internet', 'CCTV', 'LAN', 'Server'];
foreach ($standardCategories as $cat) {
  if (!isset($categoryData[$cat])) {
    $categoryData[$cat] = [];
  }
}
?>
<?php include '../includes/header.php'; ?>


<div class="container-fluid">
  <div class="row">
    <?php include '../includes/sidebar.php'; ?>

    <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4 content">
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

      <div class="d-flex justify-content-between align-items-center 
                  pt-3 pb-2 mb-3 border-bottom">
        <h1 class="h2">Monitoring</h1>
        <div class="btn-toolbar mb-2 mb-md-0 align-items-center">

          <!-- Add / Refresh -->
          <div class="btn-group me-2">
            <button class="btn btn-sm btn-outline-secondary"
                    data-bs-toggle="modal"
                    data-bs-target="#addModal">
              <i class="fas fa-plus"></i> Add
            </button>
            <button class="btn btn-sm btn-outline-secondary"
                    data-bs-toggle="modal"
                    data-bs-target="#addLocationCategoryModal">
              <i class="fas fa-tags"></i> Add L/C
            </button>
            <button id="refreshAll"
                    class="btn btn-sm btn-outline-primary">
              <i class="fas fa-sync"></i> Refresh
            </button>
          </div>

          <!-- Schedule Resume at Specific Time (with seconds) -->
          <div class="input-group input-group-sm me-2" style="width:180px">
            <input type="time"
                   id="resumeTime"
                   class="form-control"
                   step="1"
                   title="Pick HH:MM:SS to resume">
            <button id="scheduleResume"
                    class="btn btn-sm btn-outline-secondary">
              Set Resume
            </button>
          </div>
        </div>
      </div>

      <?php if (!empty($_SESSION['error'])): ?>
        <div class="alert alert-danger py-1">
          <?= $_SESSION['error']; unset($_SESSION['error']); ?>
        </div>
      <?php endif; ?>
      <?php if (!empty($_SESSION['success'])): ?>
        <div class="alert alert-success py-1">
          <?= $_SESSION['success']; unset($_SESSION['success']); ?>
        </div>
      <?php endif; ?>

      <!-- Two tables per row using row/col system -->
      <div class="row">
        <!-- First row: Internet and CCTV -->
        <div class="col-md-6 mb-3">
          <div class="card h-100">
            <div class="card-header bg-primary text-white py-2">
              <h5 class="mb-0 fs-6">Internet</h5>
            </div>
            <div class="card-body">
              <div class="table-responsive">
                <table id="internet-table" class="table table-bordered table-hover table-sm datatable mb-0">
                  <thead class="table-light">
                    <tr>
                      <th>Date</th>
                      <th>IP</th>
                      <th>Description</th>
                      <th>Location</th>
                      <th>Status</th>
                      <th>Actions</th>
                    </tr>
                  </thead>
                  <tbody>
                    <?php if (!empty($categoryData['Internet'])): ?>
                      <?php foreach($categoryData['Internet'] as $row): ?>
                      <tr class="clickable-row" data-href="report.php?report=<?= $row['id'] ?>">
                        <td><?= date("M d, Y", strtotime($row['date'])) ?></td>
                        <td><?= htmlspecialchars($row['ip_address']) ?></td>
                        <td><?= htmlspecialchars($row['description']) ?></td>
                        <td><?= htmlspecialchars($row['location']) ?></td>
                        <td id="status-internet-<?= $row['id'] ?>">
                        <span class="badge badge-smaller <?= $row['status'] === 'online' ? 'bg-success' : 'bg-danger' ?>">
                          <?= ucfirst($row['status']) ?>
                        </span>
                        <span class="badge badge-smaller <?= $row['latency'] >= 100 ? 'bg-warning' : 'bg-info' ?>">
                          <?= $row['latency'] ?> ms
                        </span>

                        </td>
                        <td>
                          <div class="btn-group">
                            <button type="button"
                                    class="btn btn-sm btn-primary view-details btn-xs"
                                    data-bs-toggle="modal"
                                    data-bs-target="#dataModal"
                                    data-date="<?= htmlspecialchars($row['date']) ?>"
                                    data-ip="<?= htmlspecialchars($row['ip_address']) ?>"
                                    data-description="<?= htmlspecialchars($row['description']) ?>"
                                    data-location="<?= htmlspecialchars($row['location']) ?>"
                                    data-category="<?= htmlspecialchars($row['category']) ?>">
                              View
                            </button>
                            <a href="report.php?report=<?= $row['id'] ?>"
                               class="btn btn-sm btn-success btn-xs">Report</a>
                          </div>
                        </td>
                      </tr>
                      <?php endforeach; ?>
                    <?php endif; ?>
                  </tbody>
                </table>
              </div>
            </div>
          </div>
        </div>
        
        <div class="col-md-6 mb-3">
          <div class="card h-100">
            <div class="card-header bg-success text-white py-2">
              <h5 class="mb-0 fs-6">CCTV</h5>
            </div>
            <div class="card-body">
              <div class="table-responsive">
                <table id="cctv-table" class="table table-bordered table-hover table-sm datatable mb-0">
                  <thead class="table-light">
                    <tr>
                      <th>Date</th>
                      <th>IP</th>
                      <th>Description</th>
                      <th>Location</th>
                      <th>Status</th>
                      <th>Actions</th>
                    </tr>
                  </thead>
                  <tbody>
                    <?php if (!empty($categoryData['CCTV'])): ?>
                      <?php foreach($categoryData['CCTV'] as $row): ?>
                      <tr class="clickable-row" data-href="report.php?report=<?= $row['id'] ?>">
                        <td><?= date("M d, Y", strtotime($row['date'])) ?></td>
                        <td><?= htmlspecialchars($row['ip_address']) ?></td>
                        <td><?= htmlspecialchars($row['description']) ?></td>
                        <td><?= htmlspecialchars($row['location']) ?></td>
                        <td id="status-cctv-<?= $row['id'] ?>">
                          <span class="badge badge-smaller <?= $row['status'] === 'online' ? 'bg-success' : 'bg-danger' ?>">
                            <?= ucfirst($row['status']) ?>
                          </span>
                          <span class="badge badge-smaller <?= $row['latency'] >= 100 ? 'bg-warning' : 'bg-info' ?>">
                            <?= $row['latency'] ?> ms
                          </span>
                        </td>
                        <td>
                          <div class="btn-group">
                            <button type="button"
                                    class="btn btn-sm btn-primary view-details btn-xs"
                                    data-bs-toggle="modal"
                                    data-bs-target="#dataModal"
                                    data-date="<?= htmlspecialchars($row['date']) ?>"
                                    data-ip="<?= htmlspecialchars($row['ip_address']) ?>"
                                    data-description="<?= htmlspecialchars($row['description']) ?>"
                                    data-location="<?= htmlspecialchars($row['location']) ?>"
                                    data-category="<?= htmlspecialchars($row['category']) ?>">
                              View
                            </button>
                            <a href="report.php?report=<?= $row['id'] ?>"
                               class="btn btn-sm btn-success btn-xs">Report</a>
                          </div>
                        </td>
                      </tr>
                      <?php endforeach; ?>
                    <?php endif; ?>
                  </tbody>
                </table>
              </div>
            </div>
          </div>
        </div>
        
        <!-- Second row: LAN and Server -->
        <div class="col-md-6 mb-3">
          <div class="card h-100">
            <div class="card-header bg-info text-white py-2">
              <h5 class="mb-0 fs-6">LAN</h5>
            </div>
            <div class="card-body">
              <div class="table-responsive">
                <table id="lan-table" class="table table-bordered table-hover table-sm datatable mb-0">
                  <thead class="table-light">
                    <tr>
                      <th>Date</th>
                      <th>IP</th>
                      <th>Description</th>
                      <th>Location</th>
                      <th>Status</th>
                      <th>Actions</th>
                    </tr>
                  </thead>
                  <tbody>
                    <?php if (!empty($categoryData['LAN'])): ?>
                      <?php foreach($categoryData['LAN'] as $row): ?>
                      <tr class="clickable-row" data-href="report.php?report=<?= $row['id'] ?>">
                        <td><?= date("M d, Y", strtotime($row['date'])) ?></td>
                        <td><?= htmlspecialchars($row['ip_address']) ?></td>
                        <td><?= htmlspecialchars($row['description']) ?></td>
                        <td><?= htmlspecialchars($row['location']) ?></td>
                        <td id="status-lan-<?= $row['id'] ?>">
                          <span class="badge badge-smaller <?= $row['status'] === 'online' ? 'bg-success' : 'bg-danger' ?>">
                            <?= ucfirst($row['status']) ?>
                          </span>
                          <span class="badge badge-smaller <?= $row['latency'] >= 100 ? 'bg-warning' : 'bg-info' ?>">
                            <?= $row['latency'] ?> ms
                          </span>
                        </td>
                        <td>
                          <div class="btn-group">
                            <button type="button"
                                    class="btn btn-sm btn-primary view-details btn-xs"
                                    data-bs-toggle="modal"
                                    data-bs-target="#dataModal"
                                    data-date="<?= htmlspecialchars($row['date']) ?>"
                                    data-ip="<?= htmlspecialchars($row['ip_address']) ?>"
                                    data-description="<?= htmlspecialchars($row['description']) ?>"
                                    data-location="<?= htmlspecialchars($row['location']) ?>"
                                    data-category="<?= htmlspecialchars($row['category']) ?>">
                              View
                            </button>
                            <a href="report.php?report=<?= $row['id'] ?>"
                               class="btn btn-sm btn-success btn-xs">Report</a>
                          </div>
                        </td>
                      </tr>
                      <?php endforeach; ?>
                    <?php endif; ?>
                  </tbody>
                </table>
              </div>
            </div>
          </div>
        </div>
        
        <div class="col-md-6 mb-3">
          <div class="card h-100">
            <div class="card-header bg-warning text-dark py-2">
              <h5 class="mb-0 fs-6">Server</h5>
            </div>
            <div class="card-body">
              <div class="table-responsive">
                <table id="server-table" class="table table-bordered table-hover table-sm datatable mb-0">
                  <thead class="table-light">
                    <tr>
                      <th>Date</th>
                      <th>IP</th>
                      <th>Description</th>
                      <th>Location</th>
                      <th>Status</th>
                      <th>Actions</th>
                    </tr>
                  </thead>
                  <tbody>
                    <?php if (!empty($categoryData['Server'])): ?>
                      <?php foreach($categoryData['Server'] as $row): ?>
                      <tr class="clickable-row" data-href="report.php?report=<?= $row['id'] ?>">
                        <td><?= date("M d, Y", strtotime($row['date'])) ?></td>
                        <td><?= htmlspecialchars($row['ip_address']) ?></td>
                        <td><?= htmlspecialchars($row['description']) ?></td>
                        <td><?= htmlspecialchars($row['location']) ?></td>
                        <td id="status-server-<?= $row['id'] ?>">
                          <span class="badge badge-smaller <?= $row['status'] === 'online' ? 'bg-success' : 'bg-danger' ?>">
                            <?= ucfirst($row['status']) ?>
                          </span>
                          <span class="badge badge-smaller <?= $row['latency'] >= 100 ? 'bg-warning' : 'bg-info' ?>">
                            <?= $row['latency'] ?> ms
                          </span>
                        </td>
                        <td>
                          <div class="btn-group">
                            <button type="button"
                                    class="btn btn-sm btn-primary view-details btn-xs"
                                    data-bs-toggle="modal"
                                    data-bs-target="#dataModal"
                                    data-date="<?= htmlspecialchars($row['date']) ?>"
                                    data-ip="<?= htmlspecialchars($row['ip_address']) ?>"
                                    data-description="<?= htmlspecialchars($row['description']) ?>"
                                    data-location="<?= htmlspecialchars($row['location']) ?>"
                                    data-category="<?= htmlspecialchars($row['category']) ?>">
                              View
                            </button>
                            <a href="report.php?report=<?= $row['id'] ?>"
                               class="btn btn-sm btn-success btn-xs">Report</a>
                          </div>
                        </td>
                      </tr>
                      <?php endforeach; ?>
                    <?php endif; ?>
                  </tbody>
                </table>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Data Details Modal -->
      <div class="modal fade" id="dataModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
          <div class="modal-content">
            <div class="modal-header py-2">
              <h5 class="modal-title">Data Details</h5>
              <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
              <p class="mb-1"><strong>Date:</strong> <span id="modal-date"></span></p>
              <p class="mb-1"><strong>IP:</strong> <span id="modal-ip"></span></p>
              <p class="mb-1"><strong>Description:</strong> <span id="modal-description"></span></p>
              <p class="mb-1"><strong>Location:</strong> <span id="modal-location"></span></p>
              <p class="mb-1"><strong>Category:</strong> <span id="modal-category"></span></p>
            </div>
            <div class="modal-footer py-1">
              <button type="button" class="btn btn-secondary btn-sm"
                      data-bs-dismiss="modal">Close</button>
            </div>
          </div>
        </div>
      </div>

      <!-- Add Data Modal -->
      <div class="modal fade" id="addModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
          <form action="../backend/process.php" method="POST" class="modal-content">
            <input type="hidden" name="action" value="add_data">
            <div class="modal-header py-2">
              <h5 class="modal-title">Add New Data</h5>
              <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
              <div class="mb-2">
                <label class="form-label small">Date</label>
                <input type="date" name="date" class="form-control form-control-sm" required>
              </div>
              <div class="mb-2">
                <label class="form-label small">IP Address</label>
                <input type="text" name="ip_address" class="form-control form-control-sm" required>
              </div>
              <div class="mb-2">
                <label class="form-label small">Description</label>
                <textarea name="description" class="form-control form-control-sm" rows="2" required></textarea>
              </div>
              <div class="mb-2">
                <label class="form-label small">Location</label>
                <select name="location" class="form-control form-control-sm" required>
                  <option value="">-- Select Location --</option>
                  <?php foreach($locations as $loc): ?>
                    <option value="<?= htmlspecialchars($loc['location']) ?>"><?= htmlspecialchars($loc['location']) ?></option>
                  <?php endforeach; ?>
                </select>
              </div>
              <div class="mb-2">
                <label class="form-label small">Category</label>
                <select name="category" class="form-control form-control-sm" required>
                  <option value="">-- Select Category --</option>
                  <?php foreach($categories as $cat): ?>
                    <option value="<?= htmlspecialchars($cat['category']) ?>"><?= htmlspecialchars($cat['category']) ?></option>
                  <?php endforeach; ?>
                </select>
              </div>
            </div>
            <div class="modal-footer py-1">
              <button type="button" class="btn btn-secondary btn-sm"
                      data-bs-dismiss="modal">Cancel</button>
              <button type="submit" class="btn btn-primary btn-sm">Save</button>
            </div>
          </form>
        </div>
      </div>

      <!-- Add Location & Category Modal -->
      <div class="modal fade" id="addLocationCategoryModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
          <form action="../backend/process.php" method="POST" class="modal-content">
            <input type="hidden" name="action" value="add_location_category">
            <div class="modal-header py-2">
              <h5 class="modal-title">Add Location/Category</h5>
              <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
              <div class="mb-2">
                <label class="form-label small">Location</label>
                <input type="text" name="location" class="form-control form-control-sm" placeholder="Enter location name">
                <small class="text-muted">Optional: You can add just a category if you prefer</small>
              </div>
              <div class="mb-2">
                <label class="form-label small">Category</label>
                <input type="text" name="category" class="form-control form-control-sm" placeholder="Enter category name">
                <small class="text-muted">Optional: You can add just a location if you prefer</small>
              </div>
            </div>
            <div class="modal-footer py-1">
              <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cancel</button>
              <button type="submit" class="btn btn-primary btn-sm">Save</button>
            </div>
          </form>
        </div>
      </div>

    </main>
  </div>
</div>


<!-- DataTables JavaScript -->
<script src="https://cdn.datatables.net/1.11.5/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.11.5/js/dataTables.bootstrap5.min.js"></script>

<script>
document.addEventListener('DOMContentLoaded', () => {
  // Initialize DataTables with smaller pagination
  $('.datatable').each(function() {
    $(this).DataTable({
      responsive: true,
      pageLength: 5,
      lengthMenu: [[5, 10, 25], [5, 10, 25]],
      order: [[0, 'desc']], // Sort by date desc
      language: {
        emptyTable: "No data",
        zeroRecords: "No matches",
        lengthMenu: "Show _MENU_",
        info: "_START_-_END_ of _TOTAL_",
        infoEmpty: "0 records",
        infoFiltered: "(from _MAX_)",
        search: "",
        searchPlaceholder: "Search..."
      },
      dom: "<'row'<'col-sm-6'l><'col-sm-6'f>>" +
           "<'row'<'col-sm-12'tr>>" +
           "<'row'<'col-sm-5'i><'col-sm-7'p>>"
    });
  });

  // clickable rows
  document.querySelectorAll('.clickable-row').forEach(row => {
    row.style.cursor = 'pointer';
    row.addEventListener('click', e => {
      if (e.target.closest('button') || e.target.closest('a')) return;
      window.location = row.dataset.href;
    });
  });

  // details modal
  document.getElementById('dataModal')
    .addEventListener('show.bs.modal', e => {
      const btn = e.relatedTarget;
      ['date','ip','description','location','category']
        .forEach(f => document.getElementById(`modal-${f}`)
                         .textContent = btn.dataset[f]);
    });

  // Set auto-refresh interval (15 minutes)
  const intervalMs = 900000;
  let intervalId = null;
  let scheduleId = null;

  function startAutoRefresh() {
    // Clear any existing interval
    if (intervalId) clearInterval(intervalId);
    // Start new interval
    intervalId = setInterval(refreshLatency, intervalMs);
  }

  // Start auto-refresh on load
  startAutoRefresh();

  // Manual refresh button - triggers refresh and resets auto timer
  document.getElementById('refreshAll').addEventListener('click', () => {
    refreshLatency();
    startAutoRefresh();
  });

  // Schedule Daily Resume (skip Sundays) with SweetAlert2
  document.getElementById('scheduleResume')
    .addEventListener('click', () => {
      const t = document.getElementById('resumeTime').value;
      if (!t) {
        return Swal.fire({
          icon: 'error',
          title: 'Error',
          text: 'Please select a time first.'
        });
      }
      const [h,m,s] = t.split(':').map((v,i)=>Number(v)||(i===2?0:0));
      if (scheduleId) clearTimeout(scheduleId);

      (function scheduleNext(){
        const now = new Date();
        let resumeAt = new Date(
          now.getFullYear(), now.getMonth(), now.getDate(),
          h, m, s
        );
        // bump if past or Sunday (day 0)
        while (resumeAt <= now || resumeAt.getDay() === 0) {
          resumeAt.setDate(resumeAt.getDate() + 1);
        }
        scheduleId = setTimeout(() => {
          showLoader();
          refreshLatency();
          startAutoRefresh();
          scheduleNext();
        }, resumeAt - now);
      })();

      Swal.fire({
        icon: 'success',
        title: 'Scheduled',
        text: `Ping will resume daily at ${t}, skipping Sundays.`,
        timer: 3000,
        showConfirmButton: false
      });
    });
});

// loader UI
let loaderTimer, loaderStart;
function showLoader() {
  loaderStart = Date.now();
  document.getElementById('loader').classList.remove('d-none');
  loaderTimer = setInterval(() => {
    const s = Math.floor((Date.now() - loaderStart)/1000);
    document.getElementById('loader-text').textContent = `Loading... ${s}s`;
  }, 500);
}
function hideLoader() {
  clearInterval(loaderTimer);
  document.getElementById('loader-text').textContent = 'Loading... 0s';
  document.getElementById('loader').classList.add('d-none');
}

// ping + update all tables
function refreshLatency() {
  showLoader();
  fetch('/backend/get_latency.php')
    .then(res => res.ok ? res.json() : Promise.reject())
    .then(data => {
      data.forEach(r => {
        // Update in category-specific tables
        const categories = ['internet', 'cctv', 'lan', 'server'];
        const cat = r.category ? r.category.toLowerCase() : '';
        
        if (categories.includes(cat)) {
          const st = document.getElementById(`status-${cat}-${r.id}`);
          
          if (st) st.innerHTML = `
            <span class="badge badge-smaller ${r.status === 'online' ? 'bg-success' : 'bg-danger'}">
              ${r.status === 'online' ? 'Online' : 'Offline'}
            </span>
            <span class="badge badge-smaller ${r.latency >= 100 ? 'bg-warning' : 'bg-info'}">
              ${r.latency} ms
            </span>`;
        }
      });
    })
    .catch(console.error)
    .finally(hideLoader);
}
</script>