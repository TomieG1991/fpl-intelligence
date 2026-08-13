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