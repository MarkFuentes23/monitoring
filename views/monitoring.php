<?php
//monitoring.php
require_once '../config/db.php';
requireLogin();

// initial load of table data WITHOUT pinging
$dataRows = $conn
  ->query("SELECT * FROM add_ip ORDER BY date DESC")
  ->fetchAll(PDO::FETCH_ASSOC);

// Get locations and categories for dropdowns
$locations = $conn->query("SELECT * FROM locations")->fetchAll(PDO::FETCH_ASSOC);
$categories = $conn->query("SELECT * FROM categories")->fetchAll(PDO::FETCH_ASSOC);

// Organize data by category
$categoryData = [];
foreach ($dataRows as $row) {
  $category = !empty($row['category']) ? $row['category'] : 'Uncategorized';
  if (!isset($categoryData[$category])) {
    $categoryData[$category] = [];
  }
  $categoryData[$category][] = $row;
}

// Make sure we have entries for all categories from the database
foreach ($categories as $cat) {
  $categoryName = $cat['category'];
  if (!isset($categoryData[$categoryName])) {
    $categoryData[$categoryName] = [];
  }
}

// Add uncategorized if needed
if (!isset($categoryData['Uncategorized'])) {
  $categoryData['Uncategorized'] = [];
}
?>
<?php include '../includes/header.php'; ?>
<?php include '../includes/loader.php'; ?>


<div class="container">
  <div class="row">
    <?php include '../includes/sidebar.php'; ?>
     

      <div class="d-flex justify-content-between align-items-center pt-3 pb-2 mb-3 border-bottom">
        <h1 class="h2">Monitoring</h1>
        <div class="btn-toolbar mb-2 mb-md-0 align-items-center">

          <!-- Add / Refresh -->
          <div class="btn-group me-2">
            <button class="btn btn-sm btn-outline-secondary"
                    data-bs-toggle="modal"
                    data-bs-target="#addModal">
              <i class="fas fa-plus"></i> Add New IP
            </button>
            <button class="btn btn-sm btn-outline-secondary"
                    data-bs-toggle="modal"
                    data-bs-target="#addLocationCategoryModal">
              <i class="fas fa-tags"></i> Add New Location/Categories
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

      <!-- Dynamic category tables using row/col system -->
      <div class="row">
        <?php 
        // Define colors for common categories (add more as needed)
        $categoryColors = [
          'Internet' => 'primary',
          'CCTV' => 'success',
          'LAN' => 'info',
          'Server' => 'warning',
          'Uncategorized' => 'secondary'
        ];
        
        // Counter to track position
        $counter = 0;
        
        // Loop through all categories
        foreach ($categoryData as $categoryName => $rows): 
          // Set a default color if not defined
          $colorClass = isset($categoryColors[$categoryName]) ? $categoryColors[$categoryName] : 'dark';
          
          // Text color based on background (dark background gets white text)
          $textClass = ($colorClass == 'warning' || $colorClass == 'info' || $colorClass == 'light') ? 'text-dark' : 'text-white';
        ?>
          <!-- Create a new row every 2 tables -->
          <?php if ($counter % 2 == 0): ?>
            <?php if ($counter > 0): ?></div><?php endif; ?>
            <div class="row">
          <?php endif; ?>
          
          <div class="col-md-6 mb-3">
            <div class="card h-100">
              <div class="card-header bg-<?= $colorClass ?> <?= $textClass ?> py-2">
                <h5 class="mb-0 fs-6"><?= htmlspecialchars($categoryName) ?></h5>
              </div>
              <div class="card-body">
                <div class="table-responsive">
                  <table id="<?= strtolower(str_replace(' ', '-', $categoryName)) ?>-table" class="table table-bordered table-hover table-sm datatable mb-0">
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
                      <?php if (!empty($rows)): ?>
                        <?php foreach($rows as $row): ?>
                        <tr class="clickable-row" data-href="report.php?report=<?= $row['id'] ?>">
                          <td><?= date("M d, Y", strtotime($row['date'])) ?></td>
                          <td><?= htmlspecialchars($row['ip_address']) ?></td>
                          <td><?= htmlspecialchars($row['description']) ?></td>
                          <td><?= htmlspecialchars($row['location']) ?></td>
                          <td id="status-<?= $row['id'] ?>">
                            <span class="badge badge-smaller <?= $row['status'] === 'online' ? 'bg-success' : 'bg-danger' ?>">
                              <?= ucfirst($row['status']) ?>
                            </span>
                            <span class="badge badge-smaller <?= $row['latency'] >= 100 ? 'bg-warning' : 'bg-info' ?>">
                              <?= $row['latency'] ?> ms
                            </span>
                            <span class="badge badge-smaller <?= $row['latency'] >= 100 ? 'bg-danger' : 'bg-success' ?>">
                              <?= $row['latency'] >= 100 ? 'High Latency' : 'Low Latency' ?>
                            </span>
                          </td>
                          <td>
                            <!-- Report button -->
                            <a href="report.php?report=<?= $row['id'] ?>" class="btn btn-sm btn-success btn-xs" onclick="event.stopPropagation()"><i class="fas fa-file-alt me-1"></i>                            </a>
                            <!-- Update button -->
                            <button  type="button" class="btn btn-sm btn-primary btn-xs btn-update" data-id="<?= $row['id'] ?>" data-ip="<?= htmlspecialchars($row['ip_address']) ?>"
                            data-desc="<?= htmlspecialchars($row['description']) ?>" data-loc="<?= htmlspecialchars($row['location']) ?>" data-cat="<?= htmlspecialchars($row['category']) ?>" onclick="event.stopPropagation()" data-bs-toggle="modal" data-bs-target="#updateModal"> <i class="fas fa-edit me-1"></i>
                            </button>

                            <!-- Delete button -->
                            <form method="post" action="../backend/process.php" class="delete-form" style="display: inline;">
                            <input type="hidden" name="action" value="delete_data"> <input type="hidden" name="id" value="<?= $row['id'] ?>">
                            <button type="button" class="btn btn-sm btn-danger btn-xs btn-delete" onclick="event.stopPropagation()"> <i class="fas fa-trash-alt me-1"></i> </button>
                            </form>
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
          
          <?php $counter++; ?>
        <?php endforeach; ?>
        
        <!-- Close the last row if there was an odd number of categories -->
        <?php if ($counter % 2 != 0): ?>
          </div>
        <?php endif; ?>
      </div>


      <!-- Add Data Modal -->
      <div class="modal fade" id="addModal" tabindex="-1" aria-hidden="true">
      <div class="modal-dialog modal-lg">
        <form action="../backend/process.php" method="POST" class="modal-content">
          <input type="hidden" name="action" value="add_data">
          <div class="modal-header py-2">
            <h5 class="modal-title">Add New Data (Single / Bulk)</h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
          </div>
          <div class="modal-body">
            <!-- Container for dynamic rows -->
            <div id="row-container">
              <div class="row mb-2 data-row">
                <div class="col-md-3">
                  <label class="form-label small">IP Address</label>
                  <input type="text" name="ip_address[]" class="form-control form-control-sm" required>
                </div>
                <div class="col-md-4">
                  <label class="form-label small">Description</label>
                  <textarea name="description[]" class="form-control form-control-sm" rows="1" required></textarea>
                </div>
                <div class="col-md-2">
                  <label class="form-label small">Location</label>
                  <select name="location[]" class="form-control form-control-sm" required>
                    <option value="">-- Select --</option>
                    <?php foreach($locations as $loc): ?>
                      <option value="<?= htmlspecialchars($loc['location']) ?>"><?= htmlspecialchars($loc['location']) ?></option>
                    <?php endforeach; ?>
                  </select>
                </div>
                <div class="col-md-2">
                  <label class="form-label small">Category</label>
                  <select name="category[]" class="form-control form-control-sm" required>
                    <option value="">-- Select --</option>
                    <?php foreach($categories as $cat): ?>
                      <option value="<?= htmlspecialchars($cat['category']) ?>"><?= htmlspecialchars($cat['category']) ?></option>
                    <?php endforeach; ?>
                  </select>
                </div>
                <div class="col-md-1 d-flex align-items-end">
                  <button type="button" class="btn btn-sm btn-danger remove-row">&times;</button>
                </div>
              </div>
            </div>

            <button type="button" id="add-row" class="btn btn-sm btn-outline-secondary mb-2">
              <i class="fas fa-plus me-1"></i> Add Row
            </button>
          </div>
          <div class="modal-footer py-1">
            <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cancel</button>
            <button type="submit" class="btn btn-primary btn-sm">Save All</button>
          </div>
        </form>
      </div>
    </div>

      <!-- Update Data Modal -->
        <div class="modal fade" id="updateModal" tabindex="-1" aria-hidden="true">
          <div class="modal-dialog">
            <form action="../backend/monitoring_update.php" method="POST" class="modal-content">
              <input type="hidden" name="action" value="update_data">
              <input type="hidden" name="id" id="update-id">
              <div class="modal-header py-2">
                <h5 class="modal-title">Update Entry</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
              </div>
              <div class="modal-body">
                <div class="mb-2">
                  <label class="form-label small">IP Address</label>
                  <input type="text" name="ip_address" id="update-ip" class="form-control form-control-sm" required>
                </div>
                <div class="mb-2">
                  <label class="form-label small">Description</label>
                  <textarea name="description" id="update-desc" class="form-control form-control-sm" rows="2" required></textarea>
                </div>
                <div class="mb-2">
                  <label class="form-label small">Location</label>
                  <select name="location" id="update-loc" class="form-control form-control-sm" required>
                    <option value="">-- Select Location --</option>
                    <?php foreach($locations as $loc): ?>
                      <option value="<?= htmlspecialchars($loc['location']) ?>"><?= htmlspecialchars($loc['location']) ?></option>
                    <?php endforeach; ?>
                  </select>
                </div>
                <div class="mb-2">
                  <label class="form-label small">Category</label>
                  <select name="category" id="update-cat" class="form-control form-control-sm" required>
                    <option value="">-- Select Category --</option>
                    <?php foreach($categories as $cat): ?>
                      <option value="<?= htmlspecialchars($cat['category']) ?>"><?= htmlspecialchars($cat['category']) ?></option>
                    <?php endforeach; ?>
                  </select>
                </div>
              </div>
              <div class="modal-footer py-1">
                <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cancel</button>
                <button type="submit" class="btn btn-primary btn-sm">Save changes</button>
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

    </main>
  </div>
</div>

<script src="../js/monitoring.js"> </script>
<SCript>
  document.addEventListener('DOMContentLoaded', function() {
  document.querySelectorAll('.btn-delete').forEach(function(btn) {
    btn.addEventListener('click', function(e) {
      e.stopPropagation();  // Huwag patakbuhin row click
      const form = this.closest('form');

      Swal.fire({
        title: 'Are you sure?',
        text: "This action cannot be undone.",
        icon: 'warning',
        showCancelButton: true,
        confirmButtonText: 'Yes, delete it!',
        cancelButtonText: 'Cancel',
        reverseButtons: true
      }).then((result) => {
        if (result.isConfirmed) {
          form.submit();
        }
      });
    });
  });
});
</SCript>

<script>
document.addEventListener('DOMContentLoaded', () => {
  // Populate update-modal when any .btn-update is clicked
  document.querySelectorAll('.btn-update').forEach(btn => {
    btn.addEventListener('click', () => {
      // grab from data-attributes
      const id   = btn.getAttribute('data-id');
      const ip   = btn.getAttribute('data-ip');
      const desc = btn.getAttribute('data-desc');
      const loc  = btn.getAttribute('data-loc');
      const cat  = btn.getAttribute('data-cat');

      // set into modal fields
      document.getElementById('update-id').value         = id;
      document.getElementById('update-ip').value         = ip;
      document.getElementById('update-desc').value       = desc;
      document.getElementById('update-loc').value        = loc;
      document.getElementById('update-cat').value        = cat;
    });
  });
});
</script>

<script>
document.addEventListener('DOMContentLoaded', () => {
  const container = document.getElementById('row-container');
  const addBtn = document.getElementById('add-row');

  // Add new row
  addBtn.addEventListener('click', () => {
    const firstRow = container.querySelector('.data-row');
    const newRow = firstRow.cloneNode(true);
    // Clear inputs
    newRow.querySelectorAll('input, textarea').forEach(el => el.value = '');
    newRow.querySelectorAll('select').forEach(sel => sel.selectedIndex = 0);
    container.appendChild(newRow);
    attachRemove(newRow);
  });

  // Attach remove handler to existing rows
  function attachRemove(row) {
    row.querySelector('.remove-row').addEventListener('click', () => {
      if (container.querySelectorAll('.data-row').length > 1) {
        row.remove();
      }
    });
  }

  // Initialize remove on the first row
  container.querySelectorAll('.data-row').forEach(r => attachRemove(r));
});
</script>
