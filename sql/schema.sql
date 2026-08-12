-- phpMyAdmin SQL Dump
-- version 5.2.3
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1:3306
-- Generation Time: Aug 12, 2026 at 11:33 AM
-- Server version: 5.7.31
-- PHP Version: 8.2.30

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `fpl_intelligence`
--
CREATE DATABASE IF NOT EXISTS `fpl_intelligence` DEFAULT CHARACTER SET utf8 COLLATE utf8_general_ci;
USE `fpl_intelligence`;

-- --------------------------------------------------------

--
-- Table structure for table `fixtures`
--

DROP TABLE IF EXISTS `fixtures`;
CREATE TABLE IF NOT EXISTS `fixtures` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `fpl_fixture_id` int(11) NOT NULL,
  `gameweek` int(11) NOT NULL,
  `home_team_id` int(11) NOT NULL,
  `away_team_id` int(11) NOT NULL,
  `kickoff_time` datetime DEFAULT NULL,
  `finished` tinyint(1) NOT NULL DEFAULT '0',
  `finished_provisional` tinyint(1) NOT NULL DEFAULT '0',
  `home_score` int(11) DEFAULT NULL,
  `away_score` int(11) DEFAULT NULL,
  `home_difficulty` int(11) DEFAULT NULL,
  `away_difficulty` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_fpl_fixture` (`fpl_fixture_id`),
  KEY `idx_gameweek` (`gameweek`),
  KEY `idx_home_team` (`home_team_id`),
  KEY `idx_away_team` (`away_team_id`),
  KEY `idx_kickoff` (`kickoff_time`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `players`
--

DROP TABLE IF EXISTS `players`;
CREATE TABLE IF NOT EXISTS `players` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `fpl_player_id` int(11) NOT NULL,
  `team_id` int(11) NOT NULL,
  `position` varchar(20) DEFAULT NULL,
  `first_name` varchar(100) DEFAULT NULL,
  `second_name` varchar(100) DEFAULT NULL,
  `web_name` varchar(100) DEFAULT NULL,
  `price` decimal(4,1) DEFAULT NULL,
  `selected_by_percent` decimal(5,2) DEFAULT NULL,
  `minutes` int(11) DEFAULT '0',
  `goals` int(11) DEFAULT '0',
  `assists` int(11) DEFAULT '0',
  `clean_sheets` int(11) DEFAULT '0',
  `bonus` int(11) DEFAULT '0',
  `bps` int(11) DEFAULT '0',
  `ict_index` decimal(6,2) DEFAULT NULL,
  `expected_goals` decimal(6,2) DEFAULT NULL,
  `expected_assists` decimal(6,2) DEFAULT NULL,
  `expected_goal_involvements` decimal(6,2) DEFAULT NULL,
  `chance_of_playing` int(11) DEFAULT NULL,
  `status` varchar(10) DEFAULT NULL,
  `news` text,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_fpl_player_id` (`fpl_player_id`),
  KEY `team_id` (`team_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `teams`
--

DROP TABLE IF EXISTS `teams`;
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
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_fpl_team_id` (`fpl_team_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
