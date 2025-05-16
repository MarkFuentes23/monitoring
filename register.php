<?php
// user_management.php
include 'db.php';
session_start();

// Fetch users with roles
$query = "SELECT id, username, email, role, created_at FROM users";
$stmt = $conn->prepare($query);
$stmt->execute();
$users = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Delete user
if (!empty($_GET['delete']) && is_numeric($_GET['delete'])) {
    $id = (int)$_GET['delete'];
    try {
        $del = $conn->prepare("DELETE FROM users WHERE id = :id");
        $del->bindParam(':id', $id, PDO::PARAM_INT);
        $del->execute();
        $_SESSION['success'] = "User deleted successfully.";
        header('Location: user_management.php');
        exit;
    } catch (PDOException $e) {
        $_SESSION['error'] = "Error deleting: " . $e->getMessage();
    }
}
?>

<?php include 'includes/header.php'; ?>
<link rel="stylesheet" href="/css/poppins.css">
<style>
  /* Apply Poppins font family to the entire page */
  body, h1, h2, h3, h4, h5, h6, p, .btn, .form-control, .alert, .card, .modal {
    font-family: 'Poppins', sans-serif !important;
  }
  
  .avatar-sm {
    width: 36px;
    height: 36px;
    font-size: 16px;
  }

  /* Improve sidebar styling */
  .sidebar {
    box-shadow: 0 0.125rem 0.25rem rgba(0, 0, 0, 0.075);
    border-radius: 8px;
  }

  /* Add hover effect to table rows */
  #usersTable tbody tr:hover {
    background-color: rgba(13, 110, 253, 0.05);
    transition: all 0.2s ease;
  }
  
  /* Enhance table appearance */
  #usersTable {
    border-collapse: separate;
    border-spacing: 0;
  }
  
  #usersTable th {
    font-weight: 600;
    text-transform: uppercase;
    font-size: 0.8rem;
    letter-spacing: 0.5px;
  }
  
  #usersTable td {
    padding: 12px 8px;
    font-family: 'poppins';
  }

  /* Style buttons */
  .btn-group .btn {
    margin: 0 3px;
    transition: all 0.2s;
  }
  
  .btn-outline-primary:hover {
    box-shadow: 0 2px 8px rgba(13, 110, 253, 0.25);
  }
  
  .btn-outline-danger:hover {
    box-shadow: 0 2px 8px rgba(220, 53, 69, 0.25);
  }
  
  /* Modal enhancements */
  .modal-content {
    overflow: hidden;
  }
  
  .modal-header {
    border-bottom: 1px solid rgba(0,0,0,0.05);
  }
  
  .form-control, .input-group-text {
    border-radius: 8px;
  }
  
  .input-group-text {
    background-color: #f8f9fa;
  }
  
  /* Card enhancements */
  .card {
    transition: all 0.2s ease;
    border-radius: 12px;
    overflow: hidden;
  }
  
  .card:hover {
    box-shadow: 0 5px 15px rgba(0,0,0,0.08);
  }
  .sidebar a, .sidebar a:hover {
    text-decoration: none;
  }
</style>

<div class="content-container pt-4">
  <div class="row g-0"> <!-- Remove gutters with g-0 -->
    <!-- Sidebar Column - Made more compact -->
    <aside class="col-6 me-3" style="text-decoration: none;">
      <?php include 'includes/sidebar.php'; ?>
    </aside>

    <!-- Main Column - Expanded to use more space -->
    <section class="col-12 col-md-11 px-4 ms-5">
      <div class="d-flex justify-content-between align-items-center mb-4">
        <h2><i class="fa-solid fa-users text-primary me-2"></i>User Management</h2>
        <button class="btn btn-primary shadow-sm rounded-pill" data-bs-toggle="modal" data-bs-target="#addUserModal">
          <i class="fa-solid fa-user-plus me-1"></i> Add User
        </button>
      </div>

      <!-- Alerts -->
      <?php if (!empty($_SESSION['error'])): ?>
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
          <i class="fa-solid fa-circle-exclamation me-2"></i><?php echo $_SESSION['error']; unset($_SESSION['error']); ?>
          <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
      <?php endif; ?>
      <?php if (!empty($_SESSION['success'])): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
          <i class="fa-solid fa-circle-check me-2"></i><?php echo $_SESSION['success']; unset($_SESSION['success']); ?>
          <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
      <?php endif; ?>

      <!-- User Table Card with improved styling -->
      <div class="card shadow-sm border-0 rounded-3">
        <div class="card-header bg-white py-3 border-bottom border-light">
          <h5 class="card-title mb-0 text-primary"><i class="fa-solid fa-list me-2"></i>User List</h5>
        </div>
        <div class="card-body p-0"> <!-- Remove padding for full-width table -->
          <div class="table-responsive">
            <table id="usersTable" class="table table-hover align-middle mb-0">
              <thead class="table-light">
                <tr>
                  <th class="ps-3">#</th>
                  <th>Username</th>
                  <th>Email</th>
                  <th>Role</th>
                  <th>Created At</th>
                  <th class="text-center">Actions</th>
                </tr>
              </thead>
              <tbody>
                <?php if ($users): foreach ($users as $i => $u): ?>
                  <tr>
                    <td class="ps-3"><?php echo $i + 1; ?></td>
                    <td>
                      <div class="d-flex align-items-center">
                        <div class="avatar-sm bg-light text-primary rounded-circle me-2 d-flex align-items-center justify-content-center">
                          <i class="fa-solid fa-user-circle"></i>
                        </div>
                        <?php echo htmlspecialchars($u['username']); ?>
                      </div>
                    </td>
                    <td><i class="fa-solid fa-envelope text-secondary me-1"></i><?php echo htmlspecialchars($u['email']); ?></td>
                    <td>
                      <?php if($u['role'] == 'admin'): ?>
                        <span class="badge bg-danger rounded-pill"><i class="fa-solid fa-user-shield me-1"></i>Admin</span>
                      <?php else: ?>
                        <span class="badge bg-info rounded-pill"><i class="fa-solid fa-user me-1"></i>Employee</span>
                      <?php endif; ?>
                    </td>
                    <td><i class="fa-solid fa-calendar-day text-secondary me-1"></i><?php echo date('M d, Y h:i A', strtotime($u['created_at'])); ?></td>
                    <td class="text-center">
                      <div class="btn-group">
                        <button class="btn btn-sm btn-outline-primary edit-user rounded-pill" 
                                data-id="<?php echo $u['id']; ?>" 
                                data-username="<?php echo htmlspecialchars($u['username']); ?>" 
                                data-email="<?php echo htmlspecialchars($u['email']); ?>"
                                data-role="<?php echo htmlspecialchars($u['role']); ?>"
                                data-bs-toggle="modal" data-bs-target="#editUserModal">
                          <i class="fa-solid fa-pen-to-square"></i>
                        </button>
                        <a href="?delete=<?php echo $u['id']; ?>" class="btn btn-sm btn-outline-danger rounded-pill" onclick="return confirm('Are you sure you want to delete this user?');">
                          <i class="fa-solid fa-trash-alt"></i>
                        </a>
                      </div>
                    </td>
                  </tr>
                <?php endforeach; else: ?>
                  <tr><td colspan="6" class="text-center p-4"><i class="fa-solid fa-info-circle me-2"></i>No users found.</td></tr>
                <?php endif; ?>
              </tbody>
            </table>
          </div>
        </div>
        <div class="card-footer bg-white">
          <small class="text-muted"><i class="fa-solid fa-chart-bar me-1"></i>Total users: <?php echo count($users); ?></small>
        </div>
      </div>
    </section>
  </div>
</div>

<!-- Modals with improved styling -->
<div class="modal fade" id="addUserModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content border-0 shadow rounded-4">
      <form action="/backend/register.php" method="post">
        <div class="modal-header bg-light">
          <h5 class="modal-title"><i class="fa-solid fa-user-plus me-2 text-primary"></i>Add User</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body">
          <input type="hidden" name="action" value="add_user">
          <div class="mb-3">
            <label class="form-label">Username</label>
            <div class="input-group">
              <span class="input-group-text"><i class="fa-solid fa-user"></i></span>
              <input name="username" class="form-control" required>
            </div>
          </div>
          <div class="mb-3">
            <label class="form-label">Email</label>
            <div class="input-group">
              <span class="input-group-text"><i class="fa-solid fa-envelope"></i></span>
              <input name="email" type="email" class="form-control" required>
            </div>
          </div>
          <div class="mb-3">
            <label class="form-label">Role</label>
            <div class="input-group">
              <span class="input-group-text"><i class="fa-solid fa-user-tag"></i></span>
              <select name="role" class="form-select" required>
                <option value="employee">Employee</option>
                <option value="admin">Admin</option>
              </select>
            </div>
          </div>
          <div class="mb-3">
            <label class="form-label">Password</label>
            <div class="input-group">
              <span class="input-group-text"><i class="fa-solid fa-lock"></i></span>
              <input name="password" type="password" class="form-control" required>
            </div>
          </div>
          <div class="mb-3">
            <label class="form-label">Confirm Password</label>
            <div class="input-group">
              <span class="input-group-text"><i class="fa-solid fa-shield-alt"></i></span>
              <input name="confirm_password" type="password" class="form-control" required>
            </div>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-light rounded-pill" data-bs-dismiss="modal">
            <i class="fa-solid fa-times me-1"></i> Cancel
          </button>
          <button type="submit" class="btn btn-primary rounded-pill">
            <i class="fa-solid fa-save me-1"></i> Save
          </button>
        </div>
      </form>
    </div>
  </div>
</div>

<div class="modal fade" id="editUserModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content border-0 shadow rounded-4">
      <form action="/backend/register.php" method="post">
        <div class="modal-header bg-light">
          <h5 class="modal-title"><i class="fa-solid fa-pen-to-square me-2 text-primary"></i>Edit User</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body">
          <input type="hidden" name="action" value="edit_user">
          <input type="hidden" id="edit_user_id" name="user_id">
          <div class="mb-3">
            <label class="form-label">Username</label>
            <div class="input-group">
              <span class="input-group-text"><i class="fa-solid fa-user"></i></span>
              <input id="edit_username" name="username" class="form-control" required>
            </div>
          </div>
          <div class="mb-3">
            <label class="form-label">Email</label>
            <div class="input-group">
              <span class="input-group-text"><i class="fa-solid fa-envelope"></i></span>
              <input id="edit_email" name="email" type="email" class="form-control" required>
            </div>
          </div>
          <div class="mb-3">
            <label class="form-label">Role</label>
            <div class="input-group">
              <span class="input-group-text"><i class="fa-solid fa-user-tag"></i></span>
              <select id="edit_role" name="role" class="form-select" required>
                <option value="employee">Employee</option>
                <option value="admin">Admin</option>
              </select>
            </div>
          </div>
          <div class="mb-3">
            <label class="form-label">New Password <small class="text-muted">(optional)</small></label>
            <div class="input-group">
              <span class="input-group-text"><i class="fa-solid fa-lock"></i></span>
              <input id="edit_password" name="password" type="password" class="form-control">
            </div>
          </div>
          <div class="mb-3">
            <label class="form-label">Confirm Password</label>
            <div class="input-group">
              <span class="input-group-text"><i class="fa-solid fa-shield-alt"></i></span>
              <input id="edit_confirm_password" name="confirm_password" type="password" class="form-control">
            </div>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-light rounded-pill" data-bs-dismiss="modal">
            <i class="fa-solid fa-times me-1"></i> Cancel
          </button>
          <button type="submit" class="btn btn-primary rounded-pill">
            <i class="fa-solid fa-save me-1"></i> Update
          </button>
        </div>
      </form>
    </div>
  </div>
</div>

<script>
  // Prefill edit modal
  document.querySelectorAll('.edit-user').forEach(btn => {
    btn.addEventListener('click', () => {
      document.getElementById('edit_user_id').value = btn.dataset.id;
      document.getElementById('edit_username').value = btn.dataset.username;
      document.getElementById('edit_email').value = btn.dataset.email;
      document.getElementById('edit_role').value = btn.dataset.role;
      document.getElementById('edit_password').value = '';
      document.getElementById('edit_confirm_password').value = '';
    });
  });

  // Initialize DataTables for better table functionality
  document.addEventListener('DOMContentLoaded', function() {
    if (typeof $.fn.DataTable !== 'undefined') {
      $('#usersTable').DataTable({
        responsive: true,
        pageLength: 10,
        language: {
          search: "<i class='fa-solid fa-search'></i>",
          searchPlaceholder: "Search users..."
        },
        dom: "<'row'<'col-sm-12 col-md-6'l><'col-sm-12 col-md-6'f>>" +
             "<'row'<'col-sm-12'tr>>" +
             "<'row'<'col-sm-12 col-md-5'i><'col-sm-12 col-md-7'p>>",
        drawCallback: function() {
          $('.dataTables_paginate > .pagination').addClass('pagination-rounded');
        }
      });
    }
  });
</script>