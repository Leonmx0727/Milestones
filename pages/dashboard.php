<?php
/**
 * UCID: lm64d | Date: 09/08/2025
 */
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';
require_login();
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Dashboard</title>
  <link rel="stylesheet" href="<?= BASE_URL ?>/css/styles.css">
  <style>
    .dashboard-grid {
      display: grid;
      grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
      gap: 24px;
      margin-top: 32px;
    }
    .dashboard-card {
      background: #f8f9fa;
      border-radius: 10px;
      box-shadow: 0 2px 8px rgba(0,0,0,0.07);
      padding: 28px 20px 20px 20px;
      text-align: center;
      transition: box-shadow 0.2s;
      border: 1px solid #e3e3e3;
    }
    .dashboard-card:hover {
      box-shadow: 0 4px 16px rgba(0,0,0,0.13);
    }
    .dashboard-card a {
      text-decoration: none;
      color: #0d6efd;
      font-weight: bold;
      font-size: 1.1rem;
      display: block;
      margin-top: 10px;
    }
    .dashboard-icon {
      font-size: 2.2rem;
      margin-bottom: 8px;
      color: #198754;
    }
    @media (max-width: 600px) {
      .dashboard-grid { grid-template-columns: 1fr; }
    }
  </style>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>
<?php render_navbar('dashboard'); ?>
<div class="container">
  <?php render_flash(); ?>
  <h1 style="margin-bottom:8px;">Dashboard</h1>
  <p style="color:#555;">Welcome! Choose where you want to go:</p>
  <div class="dashboard-grid">
    <div class="dashboard-card">
      <div class="dashboard-icon"><i class="fa fa-house"></i></div>
      <a href="<?= BASE_URL ?>/pages/home.php">Home</a>
    </div>
    <div class="dashboard-card">
      <div class="dashboard-icon"><i class="fa fa-trophy"></i></div>
      <a href="<?= BASE_URL ?>/pages/leagues_list.php">Leagues List</a>
    </div>
    <div class="dashboard-card">
      <div class="dashboard-icon"><i class="fa fa-users"></i></div>
      <a href="<?= BASE_URL ?>/pages/teams_list.php">Teams List</a>
    </div>
    <div class="dashboard-card">
      <div class="dashboard-icon"><i class="fa fa-user"></i></div>
      <a href="<?= BASE_URL ?>/pages/profile.php">Profile</a>
    </div>
    <?php if (has_role('admin')): ?>
      <div class="dashboard-card">
        <div class="dashboard-icon"><i class="fa fa-gears"></i></div>
        <a href="<?= BASE_URL ?>/pages/api_management.php">API Management</a>
      </div>
      <div class="dashboard-card">
        <div class="dashboard-icon"><i class="fa fa-plus"></i></div>
        <a href="<?= BASE_URL ?>/pages/create_league.php">Create League</a>
      </div>
      <div class="dashboard-card">
        <div class="dashboard-icon"><i class="fa fa-plus"></i></div>
        <a href="<?= BASE_URL ?>/pages/create_team.php">Create Team</a>
      </div>
    <?php endif; ?>
    <div class="dashboard-card">
      <div class="dashboard-icon"><i class="fa fa-right-from-bracket"></i></div>
      <a href="<?= BASE_URL ?>/pages/logout.php">Logout</a>
    </div>
  </div>
</div>
</body>
</html>
