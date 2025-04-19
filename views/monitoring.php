<?php
// views/monitoring.php
require_once '../config/db.php';
requireLogin();

// initial load of table data
$dataRows = $conn
  ->query("SELECT * FROM add_ip ORDER BY date DESC")
  ->fetchAll(PDO::FETCH_ASSOC);
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

          <!-- Add / Refresh / Pause -->
          <div class="btn-group me-2">
            <button class="btn btn-sm btn-outline-secondary"
                    data-bs-toggle="modal"
                    data-bs-target="#addModal">
              <i class="fas fa-plus"></i> Add
            </button>
            <button id="refreshAll"
                    class="btn btn-sm btn-outline-primary">
              <i class="fas fa-sync"></i> Refresh All
            </button>
            <button id="pauseToggle"
                    class="btn btn-sm btn-outline-warning">
              <i class="fas fa-pause"></i> Pause
            </button>
          </div>

          <!-- Schedule Resume at Specific Time (with seconds) -->
          <div class="input-group input-group-sm me-2" style="width:200px">
            <input type="time"
                   id="resumeTime"
                   class="form-control"
                   step="1"
                   title="Pick HH:MM:SS to resume">
            <button id="scheduleResume"
                    class="btn btn-sm btn-outline-secondary">
              Set Daily Resume
            </button>
          </div>

        </div>
      </div>

      <?php if (!empty($_SESSION['error'])): ?>
        <div class="alert alert-danger">
          <?= $_SESSION['error']; unset($_SESSION['error']); ?>
        </div>
      <?php endif; ?>
      <?php if (!empty($_SESSION['success'])): ?>
        <div class="alert alert-success">
          <?= $_SESSION['success']; unset($_SESSION['success']); ?>
        </div>
      <?php endif; ?>

      <div class="card mb-4">
        <div class="card-body">
          <div class="table-responsive">
            <table class="table table-bordered mb-0">
              <thead class="table-light">
                <tr>
                  <th>Date</th>
                  <th>IP</th>
                  <th>Description</th>
                  <th>Location</th>
                  <th>Latency</th>
                  <th>Status</th>
                  <th>Actions</th>
                </tr>
              </thead>
              <tbody>
                <?php foreach($dataRows as $row): ?>
                <tr class="clickable-row"
                    data-href="report.php?report=<?= $row['id'] ?>">
                  <td><?= date("F j, Y", strtotime($row['date'])) ?></td>
                  <td><?= htmlspecialchars($row['ip_address']) ?></td>
                  <td><?= htmlspecialchars($row['description']) ?></td>
                  <td><?= htmlspecialchars($row['location']) ?></td>
                  <td id="latency-<?= $row['id'] ?>">
                    <?= htmlspecialchars($row['latency']) ?> ms
                    <span class="badge <?= $row['latency'] >= 100 ? 'bg-warning' : 'bg-info' ?>">
                      <?= $row['latency'] >= 100 ? 'High' : 'Low' ?> Latency
                    </span>
                  </td>
                  <td id="status-<?= $row['id'] ?>">
                    <span class="badge <?= $row['status'] === 'online' ? 'bg-success' : 'bg-danger' ?>">
                      <?= ucfirst($row['status']) ?>
                    </span>
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
                      <a href="report.php?report=<?= $row['id'] ?>"
                         class="btn btn-sm btn-success">Report</a>
                    </div>
                  </td>
                </tr>
                <?php endforeach; ?>
              </tbody>
            </table>
          </div>
        </div>
      </div>

      <!-- Data Details Modal -->
      <div class="modal fade" id="dataModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
          <div class="modal-content">
            <div class="modal-header">
              <h5 class="modal-title">Data Details</h5>
              <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
              <p><strong>Date:</strong> <span id="modal-date"></span></p>
              <p><strong>IP:</strong> <span id="modal-ip"></span></p>
              <p><strong>Description:</strong> <span id="modal-description"></span></p>
              <p><strong>Location:</strong> <span id="modal-location"></span></p>
            </div>
            <div class="modal-footer">
              <button type="button" class="btn btn-secondary"
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
              <button type="button" class="btn btn-secondary"
                      data-bs-dismiss="modal">Cancel</button>
              <button type="submit" class="btn btn-primary">Save</button>
            </div>
          </form>
        </div>
      </div>

    </main>
  </div>
</div>

<?php include '../includes/footer.php'; ?>

<script>
document.addEventListener('DOMContentLoaded', () => {
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
      ['date','ip','description','location']
        .forEach(f => document.getElementById(`modal-${f}`)
                         .textContent = btn.dataset[f]);
    });

  // manual refresh
  document.getElementById('refreshAll')
          .addEventListener('click', refreshLatency);

  const intervalMs = 300000;  // 5 minutes
  let intervalId = null,
      scheduleId = null,
      isPaused   = false;
  const pauseBtn = document.getElementById('pauseToggle');

  function startAuto() {
    if (!intervalId) intervalId = setInterval(refreshLatency, intervalMs);
  }
  function stopAuto() {
    clearInterval(intervalId);
    intervalId = null;
  }

  // initial load + auto‑start
  refreshLatency();
  startAuto();

  // Pause / Resume
  pauseBtn.addEventListener('click', () => {
    if (!isPaused) {
      stopAuto();
      isPaused = true;
      pauseBtn.innerHTML = '<i class="fas fa-play"></i> Resume';
    } else {
      refreshLatency();
      startAuto();
      isPaused = false;
      pauseBtn.innerHTML = '<i class="fas fa-pause"></i> Pause';
    }
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
          if (!isPaused) startAuto();
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

// ping + update
function refreshLatency(){
  showLoader();
  fetch('/backend/get_latency.php')
    .then(res => res.ok ? res.json() : Promise.reject())
    .then(data => {
      data.forEach(r => {
        const lat = document.getElementById('latency-'+r.id),
              st  = document.getElementById('status-'+r.id);
        if (lat) lat.innerHTML = `
          ${r.latency} ms
          <span class="badge ${r.latency>=100?'bg-warning':'bg-info'}">
            ${r.latency>=100?'High':'Low'} Latency
          </span>`;
        if (st) st.innerHTML = r.status === 'online'
          ? '<span class="badge bg-success">Online</span>'
          : '<span class="badge bg-danger">Offline</span>';
      });
    })
    .catch(console.error)
    .finally(hideLoader);
}
</script>
