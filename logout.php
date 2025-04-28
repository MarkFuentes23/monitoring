<?php
session_start();
session_unset();    // clears all session variables
session_destroy();  // destroys the session itself

// Optional: para siguradong wala sa cache
header('Cache-Control: no-cache, no-store, must-revalidate');
header('Pragma: no-cache');
header('Expires: 0');

// Redirect to login page
header('Location: login.php');
exit();
?>
