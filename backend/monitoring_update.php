<?php
// process.php (or monitoring.php controller)
session_start();
require_once __DIR__ . '/../config/db.php';

$action = $_POST['action'] ?? '';
switch($action) {
  // … your other cases …

  case 'update_data':
    $id   = intval($_POST['id']);
    $ip   = $_POST['ip_address'];
    $desc = $_POST['description'];
    $loc  = $_POST['location'];
    $cat  = $_POST['category'];

    try {
      $sql = "UPDATE add_ip
                SET ip_address = :ip,
                    description = :desc,
                    location    = :loc,
                    category    = :cat
              WHERE id = :id";
      $stmt = $conn->prepare($sql);
      $stmt->execute([
        ':ip'   => $ip,
        ':desc' => $desc,
        ':loc'  => $loc,
        ':cat'  => $cat,
        ':id'   => $id
      ]);
      $_SESSION['success'] = "Entry #$id updated successfully.";
    } catch (PDOException $e) {
      $_SESSION['error'] = "Update failed: " . $e->getMessage();
    }

    header("Location: /views/monitoring.php");
    exit;
    break;
}
