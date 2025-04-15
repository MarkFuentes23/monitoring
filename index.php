<?php
include 'config/db.php';

// Redirect to dashboard if logged in, otherwise to login page
if (isLoggedIn()) {
    header("Location: login.php");
} else {
    header("Location: login.php");
}
exit;
?>