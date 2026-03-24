<?php
// api/auth/logout.php
session_start();

// Tanggalin lahat ng laman ng session variables
session_unset();

// Sirain ang mismong session para tuluyan nang maka-log out
session_destroy();

// I-redirect ang user pabalik sa Login Page
header("Location: ../../views/auth/login.php");
exit();
?>