<?php
session_start();
require_once '../config/path.php';
session_destroy();
header("Location: $base_url/auth/login.php");
exit;
?>
