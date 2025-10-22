<?php
// Start session if not already started
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}
require_once '../config/path.php';
require_once $base_path . '/config/db.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: $base_url/auth/login.php");
    exit;
}

$user_role = $_SESSION['role'];
$user_name = $_SESSION['name'] ?? 'User';
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?php echo $page_title ?? 'LMS'; ?></title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
  <style>
    .sidebar {
      position: fixed;
      top: 0;
      left: 0;
      height: 100vh;
      width: 250px;
      background-color: #343a40;
      color: white;
      padding-top: 20px;
      z-index: 1000;
    }
    .sidebar .nav-link {
      color: rgba(255,255,255,.75);
      padding: 10px 20px;
      margin: 5px 0;
    }
    .sidebar .nav-link:hover {
      color: white;
      background-color: rgba(255,255,255,.1);
    }
    .sidebar .nav-link.active {
      color: white;
      background-color: #0d6efd;
    }
    .main-content {
      margin-left: 250px;
      padding: 20px;
    }
    @media (max-width: 768px) {
      .sidebar {
        width: 100%;
        height: auto;
        position: relative;
      }
      .main-content {
        margin-left: 0;
      }
    }
  </style>
</head>
<body>
<div class="d-flex">
  <nav class="sidebar">
    <div class="px-3 mb-4">
      <h4 class="text-white">LMS</h4>
      <p class="text-muted small">Selamat datang, <?php echo htmlspecialchars($user_name); ?></p>
    </div>
    <ul class="nav flex-column">
      <li class="nav-item">
        <a class="nav-link <?php echo (basename($_SERVER['PHP_SELF']) == 'dashboard.php') ? 'active' : ''; ?>" href="<?php echo $base_url; ?>/<?php echo $user_role; ?>/dashboard.php">
          <i class="fas fa-tachometer-alt me-2"></i>Dashboard
        </a>
      </li>
      <li class="nav-item">
        <a class="nav-link <?php echo (strpos($_SERVER['PHP_SELF'], '/class/') !== false) ? 'active' : ''; ?>" href="<?php echo $base_url; ?>/<?php echo $user_role; ?>/dashboard.php">
          <i class="fas fa-chalkboard-teacher me-2"></i>Kelas
        </a>
      </li>
      <li class="nav-item">
        <a class="nav-link" href="<?php echo $base_url; ?>/auth/logout.php">
          <i class="fas fa-sign-out-alt me-2"></i>Logout
        </a>
      </li>
    </ul>
  </nav>
  <div class="main-content flex-grow-1">
