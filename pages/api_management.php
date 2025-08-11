<?php
/**
 * UCID: lm64 | Date: 10/08/2025
 * Details: api management page
 */
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';

require_login();

// check admin
if (!has_role('admin')) {
    flash('error', 'Access denied. Admin privileges required.');
    redirect('/pages/home.php');
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>API Management</title>
  <link rel="stylesheet" href="<?= BASE_URL ?>/css/styles.css">
  <script src="<?= BASE_URL ?>/js/scripts.js" defer></script>
</head>
<body>
<?php render_navbar('api'); ?>

<div class="container">
  <?php render_flash(); ?>
  <h1>API Management</h1>
  <p class="help">Manage API integrations and data fetching.</p>
  
  <div style="display: grid; gap: 20px; margin-top: 30px;">
    <div style="border: 1px solid #ddd; border-radius: 8px; padding: 20px;">
      <h2>Football API Integration</h2>
      <p>Fetch leagues and teams data from the Football API.</p>
      
      <div style="display: flex; gap: 12px; margin-top: 15px;">
        <a href="<?= BASE_URL ?>/pages/admin_api_fetch.php?type=leagues" class="button primary">Fetch Leagues</a>
        <a href="<?= BASE_URL ?>/pages/admin_api_fetch.php?type=teams" class="button primary">Fetch Teams</a>
      </div>
    </div>
    
    <div style="border: 1px solid #ddd; border-radius: 8px; padding: 20px;">
      <h2>Data Management</h2>
      <p>Manage existing data and perform maintenance tasks.</p>
      
      <div style="display: flex; gap: 12px; margin-top: 15px;">
        <a href="<?= BASE_URL ?>/pages/leagues_list.php" class="button secondary">Manage Leagues</a>
        <a href="<?= BASE_URL ?>/pages/teams_list.php" class="button secondary">Manage Teams</a>
      </div>
    </div>
  </div>
</div>
</body>
</html>
