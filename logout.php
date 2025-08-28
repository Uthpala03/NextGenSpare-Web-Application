<?php
session_start();

// Destroy all session variables
session_unset();
session_destroy();

// Redirect to signin page
header("Location: signin.html");
exit();
?>