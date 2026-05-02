<?php
// 1. Resume the current session
session_start();

// 2. Unset all of the session variables
$_SESSION = array();

// 3. Destroy the session entirely
session_destroy();

// 4. Redirect the user back to the landing page
header("Location: index.html");
exit();
?>