<?php
include 'config/db.php';

// Redirect to dashboard if logged in, otherwise to login page
if (isLoggedIn()) {
    header("Location: /views/dashboard.php");
} else {
    header("Location: login.php");
}
exit;
?>