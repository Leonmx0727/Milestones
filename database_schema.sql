CREATE TABLE users (
  id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
  username VARCHAR(50) NOT NULL UNIQUE,
  email VARCHAR(120) NOT NULL UNIQUE,
  password CHAR(60) NOT NULL,        -- bcrypt/argon2 hash
  is_active TINYINT(1) NOT NULL DEFAULT 1,
  created DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  modified DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

CREATE TABLE roles (
  id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
  name VARCHAR(50) NOT NULL UNIQUE,
  description VARCHAR(255),
  is_active TINYINT(1) NOT NULL DEFAULT 1,
  created DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  modified DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

CREATE TABLE user_roles (
  id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
  user_id BIGINT UNSIGNED NOT NULL,
  role_id BIGINT UNSIGNED NOT NULL,
  is_active TINYINT(1) NOT NULL DEFAULT 1,
  created DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  modified DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  UNIQUE KEY uq_user_role (user_id, role_id),
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
  FOREIGN KEY (role_id) REFERENCES roles(id) ON DELETE CASCADE
);

INSERT IGNORE INTO roles (name, description) VALUES ('user','Basic user');
INSERT IGNORE INTO roles (name, description) VALUES ('admin','Administrator');

CREATE TABLE IF NOT EXISTS leagues (
    id                BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    api_league_id     INT UNSIGNED NULL,                -- API-Football league.id (unique when present)
    name              VARCHAR(120) NOT NULL,
    type              ENUM('League','Cup') NULL,
    country           VARCHAR(80)  NULL,
    logo_url          VARCHAR(255) NULL,
    season_current    TINYINT(1)   NOT NULL DEFAULT 0,  -- 1 if current season in API result
    is_api            TINYINT(1)   NOT NULL DEFAULT 0,  -- 1=imported from API, 0=manual
    api_last_fetched  DATETIME     NULL,
    created           DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    modified          DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uq_leagues_api (api_league_id),
    KEY idx_leagues_name (name),
    KEY idx_leagues_country (country),
    KEY idx_leagues_created (created)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS teams (
    id                 BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    api_team_id        INT UNSIGNED NULL,               -- API-Football team.id (unique when present)
    name               VARCHAR(120) NOT NULL,
    code               VARCHAR(10)  NULL,               -- short code (e.g., MCI)
    country            VARCHAR(80)  NULL,
    founded            SMALLINT UNSIGNED NULL,          -- year
    city               VARCHAR(120) NULL,
    venue_name         VARCHAR(150) NULL,
    logo_url           VARCHAR(255) NULL,

    -- Hints to support filtering (since API usually fetched by league+season)
    last_league_api_id INT UNSIGNED NULL,
    last_season_hint   SMALLINT UNSIGNED NULL,

    is_api             TINYINT(1)   NOT NULL DEFAULT 0,
    api_last_fetched   DATETIME     NULL,
    created            DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    modified           DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uq_teams_api (api_team_id),
    KEY idx_teams_name (name),
    KEY idx_teams_country (country),
    KEY idx_teams_lastctx (last_league_api_id, last_season_hint),
    KEY idx_teams_created (created)
) ENGINE=InnoDB;



-- Milestone 3
-- Users - Teams (favorites)
CREATE TABLE IF NOT EXISTS user_team_favorites (
  id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
  user_id BIGINT UNSIGNED NOT NULL,
  team_id BIGINT UNSIGNED NOT NULL,
  created DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  modified DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  UNIQUE KEY uq_user_team (user_id, team_id),
  CONSTRAINT fk_utf_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
  CONSTRAINT fk_utf_team  FOREIGN KEY (team_id) REFERENCES teams(id)  ON DELETE CASCADE
) ENGINE=InnoDB;

-- Users - Leagues (follows)
CREATE TABLE IF NOT EXISTS user_league_follows (
  id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
  user_id BIGINT UNSIGNED NOT NULL,
  league_id BIGINT UNSIGNED NOT NULL,
  created DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  modified DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  UNIQUE KEY uq_user_league (user_id, league_id),
  CONSTRAINT fk_ulf_user   FOREIGN KEY (user_id)   REFERENCES users(id)    ON DELETE CASCADE,
  CONSTRAINT fk_ulf_league FOREIGN KEY (league_id) REFERENCES leagues(id) ON DELETE CASCADE
) ENGINE=InnoDB;
