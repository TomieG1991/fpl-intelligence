-- FPL Intelligence Database Schema
--
-- MySQL 5.7+
-- Character set: utf8mb4
-- Storage engine: InnoDB

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET time_zone = "+00:00";

SET NAMES utf8mb4;


CREATE DATABASE IF NOT EXISTS `fpl_intelligence`
    DEFAULT CHARACTER SET utf8mb4
    COLLATE utf8mb4_unicode_ci;

USE `fpl_intelligence`;


/*
 * ============================================================
 * TEAMS
 * ============================================================
 */

CREATE TABLE IF NOT EXISTS `teams` (

    `id` int(11) NOT NULL AUTO_INCREMENT,

    `fpl_team_id` int(11) NOT NULL,

    `name` varchar(100) NOT NULL,

    `short_name` varchar(10) DEFAULT NULL,

    `strength_overall_home` int(11) DEFAULT NULL,

    `strength_overall_away` int(11) DEFAULT NULL,

    `strength_attack_home` int(11) DEFAULT NULL,

    `strength_attack_away` int(11) DEFAULT NULL,

    `strength_defence_home` int(11) DEFAULT NULL,

    `strength_defence_away` int(11) DEFAULT NULL,

    `updated_at`
        timestamp NULL
        DEFAULT CURRENT_TIMESTAMP
        ON UPDATE CURRENT_TIMESTAMP,

    PRIMARY KEY (`id`),

    UNIQUE KEY `unique_fpl_team_id`
        (`fpl_team_id`),

    KEY `idx_team_name`
        (`name`)

) ENGINE=InnoDB
  DEFAULT CHARSET=utf8mb4
  COLLATE=utf8mb4_unicode_ci;


/*
 * ============================================================
 * PLAYERS
 * ============================================================
 */

CREATE TABLE IF NOT EXISTS `players` (

    `id` int(11) NOT NULL AUTO_INCREMENT,

    `fpl_player_id` int(11) NOT NULL,

    `team_id` int(11) NOT NULL,

    `position` varchar(3) DEFAULT NULL,

    `first_name` varchar(100) DEFAULT NULL,

    `second_name` varchar(100) DEFAULT NULL,

    `web_name` varchar(100) DEFAULT NULL,

    `price` decimal(4,1) DEFAULT NULL,

    `selected_by_percent` decimal(5,2) DEFAULT NULL,

    `minutes` int(11) NOT NULL DEFAULT 0,

    `goals` int(11) NOT NULL DEFAULT 0,

    `assists` int(11) NOT NULL DEFAULT 0,

    `clean_sheets` int(11) NOT NULL DEFAULT 0,

    `bonus` int(11) NOT NULL DEFAULT 0,

    `bps` int(11) NOT NULL DEFAULT 0,

    `ict_index` decimal(7,2) DEFAULT NULL,

    `expected_goals` decimal(7,2) DEFAULT NULL,

    `expected_assists` decimal(7,2) DEFAULT NULL,

    `expected_goal_involvements`
        decimal(7,2)
        DEFAULT NULL,

    `chance_of_playing` int(11) DEFAULT NULL,

    `status` varchar(10) DEFAULT NULL,

    `news` text,

    `updated_at`
        timestamp NULL
        DEFAULT CURRENT_TIMESTAMP
        ON UPDATE CURRENT_TIMESTAMP,

    PRIMARY KEY (`id`),

    UNIQUE KEY `unique_fpl_player_id`
        (`fpl_player_id`),

    KEY `idx_player_team`
        (`team_id`),

    KEY `idx_player_position`
        (`position`),

    KEY `idx_player_price`
        (`price`),

    KEY `idx_player_web_name`
        (`web_name`),

    CONSTRAINT `fk_players_team`
        FOREIGN KEY (`team_id`)
        REFERENCES `teams` (`id`)
        ON UPDATE CASCADE
        ON DELETE RESTRICT

) ENGINE=InnoDB
  DEFAULT CHARSET=utf8mb4
  COLLATE=utf8mb4_unicode_ci;


/*
 * ============================================================
 * FIXTURES
 * ============================================================
 */

CREATE TABLE IF NOT EXISTS `fixtures` (

    `id` int(11) NOT NULL AUTO_INCREMENT,

    `fpl_fixture_id` int(11) NOT NULL,

    `gameweek` int(11) DEFAULT NULL,

    `home_team_id` int(11) NOT NULL,

    `away_team_id` int(11) NOT NULL,

    `kickoff_time` datetime DEFAULT NULL,

    `finished`
        tinyint(1)
        NOT NULL
        DEFAULT 0,

    `finished_provisional`
        tinyint(1)
        NOT NULL
        DEFAULT 0,

    `home_score` int(11) DEFAULT NULL,

    `away_score` int(11) DEFAULT NULL,

    `home_difficulty` int(11) DEFAULT NULL,

    `away_difficulty` int(11) DEFAULT NULL,

    `created_at`
        timestamp NOT NULL
        DEFAULT CURRENT_TIMESTAMP,

    `updated_at`
        timestamp NOT NULL
        DEFAULT CURRENT_TIMESTAMP
        ON UPDATE CURRENT_TIMESTAMP,

    PRIMARY KEY (`id`),

    UNIQUE KEY `unique_fpl_fixture`
        (`fpl_fixture_id`),

    KEY `idx_gameweek`
        (`gameweek`),

    KEY `idx_home_team`
        (`home_team_id`),

    KEY `idx_away_team`
        (`away_team_id`),

    KEY `idx_kickoff`
        (`kickoff_time`),

    CONSTRAINT `fk_fixtures_home_team`
        FOREIGN KEY (`home_team_id`)
        REFERENCES `teams` (`id`)
        ON UPDATE CASCADE
        ON DELETE RESTRICT,

    CONSTRAINT `fk_fixtures_away_team`
        FOREIGN KEY (`away_team_id`)
        REFERENCES `teams` (`id`)
        ON UPDATE CASCADE
        ON DELETE RESTRICT

) ENGINE=InnoDB
  DEFAULT CHARSET=utf8mb4
  COLLATE=utf8mb4_unicode_ci;
  
 /*
 * ============================================================
 * GAMEWEEKS
 * ============================================================
 *
 * Stores FPL event/gameweek identity, deadline and completion
 * state.
 */

CREATE TABLE IF NOT EXISTS `gameweeks` (

    `id` int(11) NOT NULL AUTO_INCREMENT,

    `fpl_gameweek_id` int(11) NOT NULL,

    `name` varchar(50) NOT NULL,

    `deadline_time` datetime DEFAULT NULL,

    `finished`
        tinyint(1)
        NOT NULL
        DEFAULT 0,

    `data_checked`
        tinyint(1)
        NOT NULL
        DEFAULT 0,

    `is_previous`
        tinyint(1)
        NOT NULL
        DEFAULT 0,

    `is_current`
        tinyint(1)
        NOT NULL
        DEFAULT 0,

    `is_next`
        tinyint(1)
        NOT NULL
        DEFAULT 0,

    `created_at`
        timestamp NOT NULL
        DEFAULT CURRENT_TIMESTAMP,

    `updated_at`
        timestamp NOT NULL
        DEFAULT CURRENT_TIMESTAMP
        ON UPDATE CURRENT_TIMESTAMP,

    PRIMARY KEY (`id`),

    UNIQUE KEY `unique_fpl_gameweek`
        (`fpl_gameweek_id`),

    KEY `idx_gameweek_finished`
        (`finished`),

    KEY `idx_gameweek_current`
        (`is_current`)

) ENGINE=InnoDB
  DEFAULT CHARSET=utf8mb4
  COLLATE=utf8mb4_unicode_ci; 
  
  /*
 * ============================================================
 * PLAYER GAMEWEEK SNAPSHOTS
 * ============================================================
 *
 * Stores one cumulative player-state snapshot per gameweek.
 *
 * This preserves market, availability and cumulative season
 * evidence without storing derived Intelligence scores.
 */

CREATE TABLE IF NOT EXISTS `player_gameweek_snapshots` (

    `id` int(11) NOT NULL AUTO_INCREMENT,

    `gameweek_id` int(11) NOT NULL,

    `player_id` int(11) NOT NULL,

    `fpl_player_id` int(11) NOT NULL,

    `team_id` int(11) NOT NULL,

    `position`
        varchar(3)
        DEFAULT NULL,

    `price`
        decimal(4,1)
        DEFAULT NULL,

    `selected_by_percent`
        decimal(5,2)
        DEFAULT NULL,

    `chance_of_playing`
        int(11)
        DEFAULT NULL,

    `status`
        varchar(10)
        DEFAULT NULL,

    `news`
        text,

    `minutes`
        int(11)
        NOT NULL
        DEFAULT 0,

    `goals`
        int(11)
        NOT NULL
        DEFAULT 0,

    `assists`
        int(11)
        NOT NULL
        DEFAULT 0,

    `clean_sheets`
        int(11)
        NOT NULL
        DEFAULT 0,

    `bonus`
        int(11)
        NOT NULL
        DEFAULT 0,

    `bps`
        int(11)
        NOT NULL
        DEFAULT 0,

    `ict_index`
        decimal(7,2)
        DEFAULT NULL,

    `expected_goals`
        decimal(7,2)
        DEFAULT NULL,

    `expected_assists`
        decimal(7,2)
        DEFAULT NULL,

    `expected_goal_involvements`
        decimal(7,2)
        DEFAULT NULL,

    `created_at`
        timestamp NOT NULL
        DEFAULT CURRENT_TIMESTAMP,

    `updated_at`
        timestamp NOT NULL
        DEFAULT CURRENT_TIMESTAMP
        ON UPDATE CURRENT_TIMESTAMP,

    PRIMARY KEY (`id`),

    UNIQUE KEY `unique_player_gameweek_snapshot`
        (`gameweek_id`, `player_id`),

    KEY `idx_snapshot_player`
        (`player_id`),

    KEY `idx_snapshot_gameweek`
        (`gameweek_id`),

    KEY `idx_snapshot_team`
        (`team_id`),

    CONSTRAINT `fk_snapshot_gameweek`
        FOREIGN KEY (`gameweek_id`)
        REFERENCES `gameweeks` (`id`)
        ON UPDATE CASCADE
        ON DELETE RESTRICT,

    CONSTRAINT `fk_snapshot_player`
        FOREIGN KEY (`player_id`)
        REFERENCES `players` (`id`)
        ON UPDATE CASCADE
        ON DELETE RESTRICT,

    CONSTRAINT `fk_snapshot_team`
        FOREIGN KEY (`team_id`)
        REFERENCES `teams` (`id`)
        ON UPDATE CASCADE
        ON DELETE RESTRICT

) ENGINE=InnoDB
  DEFAULT CHARSET=utf8mb4
  COLLATE=utf8mb4_unicode_ci;
  
  /*
 * ============================================================
 * PLAYER FIXTURE HISTORY
 * ============================================================
 *
 * Stores authoritative per-fixture player performance from
 * FPL element-summary history.
 *
 * Per-fixture storage deliberately supports Double Gameweeks
 * without combining or overwriting individual performances.
 */

CREATE TABLE IF NOT EXISTS `player_fixture_history` (

    `id` int(11) NOT NULL AUTO_INCREMENT,

    `gameweek_id` int(11) NOT NULL,

    `player_id` int(11) NOT NULL,

    `fpl_player_id` int(11) NOT NULL,

    `fixture_id` int(11) NOT NULL,

    `fpl_fixture_id` int(11) NOT NULL,

    `team_id` int(11) NOT NULL,

    `opponent_team_id` int(11) NOT NULL,

    `was_home`
        tinyint(1)
        NOT NULL
        DEFAULT 0,

    `total_points`
        int(11)
        NOT NULL
        DEFAULT 0,

    `minutes`
        int(11)
        NOT NULL
        DEFAULT 0,

    `starts`
        int(11)
        NOT NULL
        DEFAULT 0,

    `goals`
        int(11)
        NOT NULL
        DEFAULT 0,

    `assists`
        int(11)
        NOT NULL
        DEFAULT 0,

    `expected_goals`
        decimal(7,2)
        DEFAULT NULL,

    `expected_assists`
        decimal(7,2)
        DEFAULT NULL,

    `expected_goal_involvements`
        decimal(7,2)
        DEFAULT NULL,

    `clean_sheets`
        int(11)
        NOT NULL
        DEFAULT 0,

    `goals_conceded`
        int(11)
        NOT NULL
        DEFAULT 0,

    `expected_goals_conceded`
        decimal(7,2)
        DEFAULT NULL,

    `saves`
        int(11)
        NOT NULL
        DEFAULT 0,

    `penalties_saved`
        int(11)
        NOT NULL
        DEFAULT 0,

    `clearances_blocks_interceptions`
        int(11)
        NOT NULL
        DEFAULT 0,

    `recoveries`
        int(11)
        NOT NULL
        DEFAULT 0,

    `tackles`
        int(11)
        NOT NULL
        DEFAULT 0,

    `defensive_contribution`
        int(11)
        NOT NULL
        DEFAULT 0,

    `own_goals`
        int(11)
        NOT NULL
        DEFAULT 0,

    `penalties_missed`
        int(11)
        NOT NULL
        DEFAULT 0,

    `yellow_cards`
        int(11)
        NOT NULL
        DEFAULT 0,

    `red_cards`
        int(11)
        NOT NULL
        DEFAULT 0,

    `bonus`
        int(11)
        NOT NULL
        DEFAULT 0,

    `bps`
        int(11)
        NOT NULL
        DEFAULT 0,

    `influence`
        decimal(7,2)
        DEFAULT NULL,

    `creativity`
        decimal(7,2)
        DEFAULT NULL,

    `threat`
        decimal(7,2)
        DEFAULT NULL,

    `ict_index`
        decimal(7,2)
        DEFAULT NULL,

    `price`
        decimal(4,1)
        DEFAULT NULL,

    `selected`
        int(11)
        DEFAULT NULL,

    `transfers_balance`
        int(11)
        DEFAULT NULL,

    `transfers_in`
        int(11)
        DEFAULT NULL,

    `transfers_out`
        int(11)
        DEFAULT NULL,

    `created_at`
        timestamp NOT NULL
        DEFAULT CURRENT_TIMESTAMP,

    `updated_at`
        timestamp NOT NULL
        DEFAULT CURRENT_TIMESTAMP
        ON UPDATE CURRENT_TIMESTAMP,

    PRIMARY KEY (`id`),

    UNIQUE KEY `unique_player_fixture_history`
        (`player_id`, `fixture_id`),

    KEY `idx_fixture_history_player`
        (`player_id`),

    KEY `idx_fixture_history_gameweek`
        (`gameweek_id`),

    KEY `idx_fixture_history_fixture`
        (`fixture_id`),

    KEY `idx_fixture_history_team`
        (`team_id`),

    KEY `idx_fixture_history_opponent`
        (`opponent_team_id`),

    CONSTRAINT `fk_fixture_history_gameweek`
        FOREIGN KEY (`gameweek_id`)
        REFERENCES `gameweeks` (`id`)
        ON UPDATE CASCADE
        ON DELETE RESTRICT,

    CONSTRAINT `fk_fixture_history_player`
        FOREIGN KEY (`player_id`)
        REFERENCES `players` (`id`)
        ON UPDATE CASCADE
        ON DELETE RESTRICT,

    CONSTRAINT `fk_fixture_history_fixture`
        FOREIGN KEY (`fixture_id`)
        REFERENCES `fixtures` (`id`)
        ON UPDATE CASCADE
        ON DELETE RESTRICT,

    CONSTRAINT `fk_fixture_history_team`
        FOREIGN KEY (`team_id`)
        REFERENCES `teams` (`id`)
        ON UPDATE CASCADE
        ON DELETE RESTRICT,

    CONSTRAINT `fk_fixture_history_opponent`
        FOREIGN KEY (`opponent_team_id`)
        REFERENCES `teams` (`id`)
        ON UPDATE CASCADE
        ON DELETE RESTRICT

) ENGINE=InnoDB
  DEFAULT CHARSET=utf8mb4
  COLLATE=utf8mb4_unicode_ci;