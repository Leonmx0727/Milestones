<?php
/**
 * UCID: lm64 | Date: 10/08/2025
 * Details: admin page to get data from api
 */

require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/db.php';
require_login();
if (!has_role('admin')) {
    flash('error','You do not have permission to access this page.');
    redirect('/pages/home.php');
}

$league_result_msg = null;
$team_result_msg   = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        if (isset($_POST['action']) && $_POST['action'] === 'fetch_leagues') {
            // PHP validation
            $country = trim($_POST['country'] ?? '');
            $name    = trim($_POST['name'] ?? '');
            $season  = trim($_POST['season'] ?? '');
            $current = !empty($_POST['current']) ? 'true' : null;
            $limit   = (int)($_POST['limit'] ?? 10);
            if ($limit < 1 || $limit > 100) $limit = 10;
            if ($season !== '' && !preg_match('/^\d{4}$/', $season)) {
                throw new Exception('Season must be a 4-digit year.');
            }

            // Build query for /leagues
            $query = [];
            /*
            Rule: If search (name) is provided, we MUST NOT send season/country/current.
            Otherwise, we may send country/season/current combo.
            */
            $usingSearch = ($name !== '');

            if ($usingSearch) {
                $query['search'] = $name; // text search by league name
            } else {
                if ($country !== '') $query['country'] = $country;
                if ($season !== '')  $query['season']  = (int)$season;
                if ($current)        $query['current'] = 'true';
            }


            [$ok, $payload] = api_get('/leagues', $query);

            if (!$ok) throw new Exception($payload);
            
            $inserted = $updated = 0;
            $items = $payload['response'] ?? [];
            foreach ($items as $i => $row) {
                if ($i >= $limit) break;
                // Map API -> DB
                $league = [
                    'api_league_id'  => $row['league']['id']   ?? null,
                    'name'           => $row['league']['name'] ?? null,
                    'type'           => $row['league']['type'] ?? null, // 'League'/'Cup'
                    'country'        => $row['country']['name'] ?? null,
                    'logo_url'       => $row['league']['logo'] ?? null,
                    'season_current' => !empty($row['seasons'][0]['current']) ? 1 : 0,
                ];
                $r = upsert_league($league);
                $inserted += $r['inserted'] ? 1 : 0;
                // On duplicate, rowCount may still be >0; we treat each as updated for UX simplicity
                $updated  += $r['updated'] ? 1 : 0;
            }
            $league_result_msg = "Leagues: imported/updated successfully. Inserted: $inserted, Updated: $updated.";
            flash('success', $league_result_msg);
        }

        if (isset($_POST['action']) && $_POST['action'] === 'fetch_teams') {
            // PHP validation
            $league_id_api = trim($_POST['league_id_api'] ?? '');
            $season        = trim($_POST['season'] ?? '');
            $name          = trim($_POST['name'] ?? '');
            $country       = trim($_POST['country'] ?? '');
            $limit         = (int)($_POST['limit'] ?? 10);
            if ($limit < 1 || $limit > 100) $limit = 10;

            $haveContext = ($league_id_api !== '' && $season !== '') || $name !== '' || $country !== '';
            if (!$haveContext) throw new Exception('Provide league+season or name/country to fetch teams.');
            if ($season !== '' && !preg_match('/^\d{4}$/', $season)) {
                throw new Exception('Season must be a 4-digit year.');
            }

            // Build query for /teams
            $query = [];

            /*
            Valid patterns:
            - league + season       (common and recommended)
            - OR name / country     (search-style)
            Invalid:
            - season alone (reject at PHP validation)
            */
            if ($league_id_api !== '') {
                $query['league'] = (int)$league_id_api;
                if ($season !== '') {
                    $query['season'] = (int)$season;
                } else {
                    // Shouldn't happen due to validation, but be defensive
                    throw new Exception('Season is required when League ID is provided.');
                }
            } else {
                // No league: allow name/country only
                if ($name !== '')    $query['name']    = $name;
                if ($country !== '') $query['country'] = $country;
                if ($season !== '') {
                    // Disallow season without league
                    throw new Exception('Season can only be used when League ID is provided.');
                }
            }
            [$ok, $payload] = api_get('/teams', $query);
            if (!$ok) throw new Exception($payload);
            
            $inserted = $updated = 0;
            $items = $payload['response'] ?? [];
            foreach ($items as $i => $row) {
                if ($i >= $limit) break;
                // Map API -> DB
                $team = [
                    'api_team_id'        => $row['team']['id'] ?? null,
                    'name'               => $row['team']['name'] ?? null,
                    'code'               => $row['team']['code'] ?? null,
                    'country'            => $row['team']['country'] ?? ($row['venue']['country'] ?? null),
                    'founded'            => $row['team']['founded'] ?? null,
                    'venue_name'         => $row['venue']['name'] ?? null,
                    'logo_url'           => $row['team']['logo'] ?? null,
                    'last_league_api_id' => $league_id_api !== '' ? (int)$league_id_api : null,
                    'last_season_hint'   => $season !== '' ? (int)$season : null,
                ];
                $r = upsert_team($team);
                $inserted += $r['inserted'] ? 1 : 0;
                $updated  += $r['updated'] ? 1 : 0;
            }
            $team_result_msg = "Teams: imported/updated successfully. Inserted: $inserted, Updated: $updated.";
            flash('success', $team_result_msg);
        }
    } catch (Throwable $e) {
        error_log('admin_api_fetch: ' . $e->getMessage());
        flash('error', $e->getMessage());
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Admin • API Fetch</title>
  <link rel="stylesheet" href="<?= BASE_URL ?>/css/styles.css">
  <script src="<?= BASE_URL ?>/js/scripts.js" defer></script>
</head>
<body>
<div class="nav">
  <a href="<?= BASE_URL ?>/pages/home.php">Home</a>
  <a href="<?= BASE_URL ?>/pages/dashboard.php">Dashboard</a>
  <div class="nav-right">
    <a href="<?= BASE_URL ?>/pages/profile.php">Profile</a>
    <a href="<?= BASE_URL ?>/pages/logout.php">Logout</a>
  </div>
</div>

<div class="container">
  <?php render_flash(); ?>
  <h1>Admin: Fetch from API</h1>
  <p class="help">Use these forms to import <strong>Leagues</strong> and <strong>Teams</strong> from API-Football. Results are upserted (insert/update) with friendly messages.</p>

  <h2>Fetch Leagues</h2>
  <form method="post" onsubmit="return validateFetchLeagues(this)">
    <input type="hidden" name="action" value="fetch_leagues">
    <label>Country
      <input type="text" name="country" placeholder="e.g., England" value="<?= e($_POST['country'] ?? '') ?>">
    </label>
    <label>Name (search)
      <input type="text" name="name" placeholder="e.g., Premier" value="<?= e($_POST['name'] ?? '') ?>">
    </label>
    <label>Season
      <input type="number" name="season" placeholder="e.g., 2024" min="1900" max="2100" value="<?= e($_POST['season'] ?? '') ?>">
    </label>
    <label><input type="checkbox" name="current" <?= !empty($_POST['current']) ? 'checked' : '' ?>> Current season only</label>
    <label>Limit (1–100)
      <input type="number" name="limit" required min="1" max="100" value="<?= e($_POST['limit'] ?? '10') ?>">
    </label>
    <button type="submit">Fetch Leagues</button>
  </form>

  <h2>Fetch Teams</h2>
  <form method="post" onsubmit="return validateFetchTeams(this)">
    <input type="hidden" name="action" value="fetch_teams">
    <label>League ID (API)
      <input type="number" name="league_id_api" placeholder="e.g., 39 for EPL" value="<?= e($_POST['league_id_api'] ?? '') ?>">
    </label>
    <label>Season
      <input type="number" name="season" placeholder="e.g., 2024" min="1900" max="2100" value="<?= e($_POST['season'] ?? '') ?>">
    </label>
    <div class="help">Or search by name/country:</div>
    <label>Team Name
      <input type="text" name="name" placeholder="e.g., Manchester" value="<?= e($_POST['name'] ?? '') ?>">
    </label>
    <label>Country
      <input type="text" name="country" placeholder="e.g., England" value="<?= e($_POST['country'] ?? '') ?>">
    </label>
    <label>Limit (1–100)
      <input type="number" name="limit" required min="1" max="100" value="<?= e($_POST['limit'] ?? '10') ?>">
    </label>
    <button type="submit">Fetch Teams</button>
  </form>
</div>
</body>
</html>
