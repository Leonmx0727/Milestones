<?php
/**
 * UCID: LM64 | Date: 08/08/2025
 * Details: Common helpers: flash messages, redirects, sanitization, sticky form.
 */

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

function e(string $s): string {
    return htmlspecialchars($s, ENT_QUOTES, 'UTF-8');
}

function flash(string $key, ?string $message = null) {
    if ($message === null) {
        if (!empty($_SESSION['flash'][$key])) {
            $msg = $_SESSION['flash'][$key];
            unset($_SESSION['flash'][$key]);
            return $msg;
        }
        return null;
    }
    $_SESSION['flash'][$key] = $message;
}

function redirect(string $path): void {
    header('Location: ' . BASE_URL . $path);
    exit;
}

/** Keep previous POST values (sticky form) */
function old(string $key, string $default = ''): string {
    return isset($_POST[$key]) ? e($_POST[$key]) : $default;
}

/** Simple banner rendering */
function render_flash(): void {
    foreach (['success','error','info'] as $type) {
        $msg = flash($type);
        if ($msg) {
            echo '<div class="alert '.$type.'">'.e($msg).'</div>';
        }
    }
}



/**
 * UCID: lm64 | Date: 10/08/2025
 * Details: Minimal RapidAPI GET helper; returns [success, data|message].
 */

function api_get(string $path, array $query = []): array {
    $url = API_BASE_URL . $path;
    if (!empty($query)) {
        $url .= '?' . http_build_query($query);
    }
    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => 20,
        CURLOPT_HTTPHEADER => [
            'X-RapidAPI-Key: ' . RAPIDAPI_KEY,
            'X-RapidAPI-Host: ' . RAPIDAPI_HOST,
        ],
    ]);
    $raw = curl_exec($ch);
    $err = curl_error($ch);
    $status = curl_getinfo($ch, CURLINFO_RESPONSE_CODE);
    curl_close($ch);

    if ($err) {
        error_log('api_get curl error: ' . $err);
        return [false, 'We could not reach the football API. Please try again.'];
    }
    $json = json_decode($raw, true);
    if ($status >= 400 || !is_array($json)) {
        error_log('api_get bad response (' . $status . '): ' . substr($raw,0,500));
        return [false, 'The football API returned an unexpected response.'];
    }
    return [true, $json];
}

/**
 * UCID: lm64 | Date: 10/08/2025
 * Details: Upsert a league row by api_league_id; returns ['inserted'=>n,'updated'=>n]
 * Expected $row keys: api_league_id, name, type, country, logo_url, season_current, is_api, api_last_fetched
 */
function upsert_league(array $row): array {
    $sql = "INSERT INTO leagues (api_league_id,name,type,country,logo_url,season_current,is_api,api_last_fetched)
            VALUES (:api_league_id,:name,:type,:country,:logo_url,:season_current,:is_api,:api_last_fetched)
            ON DUPLICATE KEY UPDATE
              name=VALUES(name),
              type=VALUES(type),
              country=VALUES(country),
              logo_url=VALUES(logo_url),
              season_current=VALUES(season_current),
              is_api=VALUES(is_api),
              api_last_fetched=VALUES(api_last_fetched)";
    $stmt = db()->prepare($sql);
    $stmt->execute([
        ':api_league_id'   => $row['api_league_id'] ?? null,
        ':name'            => $row['name'] ?? null,
        ':type'            => $row['type'] ?? null,
        ':country'         => $row['country'] ?? null,
        ':logo_url'        => $row['logo_url'] ?? null,
        ':season_current'  => !empty($row['season_current']) ? 1 : 0,
        ':is_api'          => 1,
        ':api_last_fetched'=> date('Y-m-d H:i:s'),
    ]);
    return ['inserted' => (int)db()->lastInsertId() ? 1 : 0, 'updated' => $stmt->rowCount() > 0 ? 1 : 0];
}

/**
 * UCID: lm64 | Date: 10/08/2025
 * Details: Upsert a team row by api_team_id.
 * Expected $row keys: api_team_id,name,code,country,founded,city,venue_name,logo_url,last_league_api_id,last_season_hint
 */
function upsert_team(array $row): array {
    $sql = "INSERT INTO teams (api_team_id,name,code,country,founded,city,venue_name,logo_url,last_league_api_id,last_season_hint,is_api,api_last_fetched)
            VALUES (:api_team_id,:name,:code,:country,:founded,:city,:venue_name,:logo_url,:last_league_api_id,:last_season_hint,1,:api_last_fetched)
            ON DUPLICATE KEY UPDATE
              name=VALUES(name),
              code=VALUES(code),
              country=VALUES(country),
              founded=VALUES(founded),
              city=VALUES(city),
              venue_name=VALUES(venue_name),
              logo_url=VALUES(logo_url),
              last_league_api_id=VALUES(last_league_api_id),
              last_season_hint=VALUES(last_season_hint),
              is_api=VALUES(is_api),
              api_last_fetched=VALUES(api_last_fetched)";
    $stmt = db()->prepare($sql);
    $stmt->execute([
        ':api_team_id'        => $row['api_team_id'] ?? null,
        ':name'               => $row['name'] ?? null,
        ':code'               => $row['code'] ?? null,
        ':country'            => $row['country'] ?? null,
        ':founded'            => $row['founded'] ?? null,
        ':city'               => $row['city'] ?? null,
        ':venue_name'         => $row['venue_name'] ?? null,
        ':logo_url'           => $row['logo_url'] ?? null,
        ':last_league_api_id' => $row['last_league_api_id'] ?? null,
        ':last_season_hint'   => $row['last_season_hint'] ?? null,
        ':api_last_fetched'   => date('Y-m-d H:i:s'),
    ]);
    return ['inserted' => (int)db()->lastInsertId() ? 1 : 0, 'updated' => $stmt->rowCount() > 0 ? 1 : 0];
}

/**
 * Render navigation bar based on user role
 */
function render_navbar(string $current_page = ''): void {
    require_once __DIR__ . '/auth.php';
    $user = current_user();
    $isAdmin = has_role('admin');
    
    echo '<div class="nav">';
    echo '<a href="' . BASE_URL . '/pages/home.php"' . ($current_page === 'home' ? ' class="active"' : '') . '>Home</a>';
    echo '<a href="' . BASE_URL . '/pages/dashboard.php"' . ($current_page === 'dashboard' ? ' class="active"' : '') . '>Dashboard</a>';
    echo '<a href="' . BASE_URL . '/pages/my_teams.php"' . ($current_page === 'my_teams' ? ' class="active"' : '') . '>My Teams</a>';
    echo '<a href="' . BASE_URL . '/pages/my_leagues.php"' . ($current_page === 'my_leagues' ? ' class="active"' : '') . '>My Leagues</a>';
    echo '<a href="' . BASE_URL . '/pages/leagues_list.php"' . ($current_page === 'leagues' ? ' class="active"' : '') . '>Leagues</a>';
    echo '<a href="' . BASE_URL . '/pages/teams_list.php"' . ($current_page === 'teams' ? ' class="active"' : '') . '>Teams</a>';
    
    if ($isAdmin) {
        echo '<div class="nav-dropdown">';
        echo '<a href="#" class="nav-dropdown-toggle">Admin ▼</a>';
        echo '<div class="nav-dropdown-menu">';
        echo '<a href="' . BASE_URL . '/pages/api_management.php">API Management</a>';
        echo '<a href="' . BASE_URL . '/pages/admin_associate.php">Bulk Associate</a>';
        echo '<a href="' . BASE_URL . '/pages/admin_associations_teams.php">All User-Team Associations</a>';
        echo '<a href="' . BASE_URL . '/pages/admin_associations_leagues.php">All User-League Associations</a>';
        echo '<a href="' . BASE_URL . '/pages/admin_user_roles.php">User Role Associations</a>';
        echo '<a href="' . BASE_URL . '/pages/teams_unassociated.php">Unassociated Teams</a>';
        echo '<a href="' . BASE_URL . '/pages/leagues_unassociated.php">Unassociated Leagues</a>';
        echo '<a href="' . BASE_URL . '/pages/leagues_create.php">Create League</a>';
        echo '<a href="' . BASE_URL . '/pages/teams_create.php">Create Team</a>';
        echo '</div>';
        echo '</div>';
    }
    
    echo '<div class="nav-right">';
    echo '<span>Welcome, ' . e($user['username']) . '</span>';
    echo '<a href="' . BASE_URL . '/pages/profile.php"' . ($current_page === 'profile' ? ' class="active"' : '') . '>Profile</a>';
    echo '<a href="' . BASE_URL . '/pages/logout.php">Logout</a>';
    echo '</div>';
    echo '</div>';
}

/**
 * Render admin action buttons for list pages
 */
function render_admin_actions(string $page_type): void {
    if (!has_role('admin')) return;
    
    echo '<div class="admin-actions" style="margin-bottom: 20px;">';
    if ($page_type === 'leagues') {
        echo '<a href="' . BASE_URL . '/pages/leagues_create.php" class="button primary">Create League</a>';
    } elseif ($page_type === 'teams') {
        echo '<a href="' . BASE_URL . '/pages/teams_create.php" class="button primary">Create Team</a>';
    }
    echo '</div>';
}


/**
 * UCID: LM64 | Date: 11/08/2025
 * Details: Association helpers for favorites/follows.
 */
function is_team_favorited(int $user_id, int $team_id): bool {
    try {
        $s = db()->prepare('SELECT 1 FROM user_team_favorites WHERE user_id=? AND team_id=? LIMIT 1');
        $s->execute([$user_id, $team_id]);
        return (bool)$s->fetchColumn();
    } catch (Throwable $e) {
        error_log('is_team_favorited: '.$e->getMessage());
        return false;
    }
}

function is_league_followed(int $user_id, int $league_id): bool {
    try {
        $s = db()->prepare('SELECT 1 FROM user_league_follows WHERE user_id=? AND league_id=? LIMIT 1');
        $s->execute([$user_id, $league_id]);
        return (bool)$s->fetchColumn();
    } catch (Throwable $e) {
        error_log('is_league_followed: '.$e->getMessage());
        return false;
    }
}

