-- phpMyAdmin SQL Dump
-- version 2.11.3deb1ubuntu1.1
-- http://www.phpmyadmin.net
--
-- Host: localhost
-- Generation Time: Jan 07, 2009 at 10:00 PM
-- Server version: 5.0.51
-- PHP Version: 5.2.4-2ubuntu5.4

SET SQL_MODE="NO_AUTO_VALUE_ON_ZERO";

--
-- Database: `silk`
--

-- --------------------------------------------------------

--
-- Table structure for table `photos_exif`
--

CREATE TABLE IF NOT EXISTS `photos_exif` (
  `id` int(11) NOT NULL auto_increment,
  `photo_id` int(11) NOT NULL,
  `height` int(11) NOT NULL,
  `width` int(11) NOT NULL,
  `taken` date NOT NULL,
  PRIMARY KEY  (`id`),
  KEY `photo_id` (`photo_id`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1 AUTO_INCREMENT=1 ;

--
-- Dumping data for table `photos_exif`
--


-- --------------------------------------------------------

--
-- Table structure for table `silk_games`
--

CREATE TABLE IF NOT EXISTS `silk_games` (
  `id` int(10) unsigned NOT NULL auto_increment,
  `teams_id` int(10) unsigned NOT NULL,
  `home_team_id` int(10) unsigned default NULL,
  `away_team_id` int(10) unsigned default NULL,
  `segment_id` int(10) unsigned default NULL,
  `home_team_pts` int(10) unsigned default NULL,
  `away_team_pts` int(10) unsigned default NULL,
  `home_team_result_id` int(10) unsigned default NULL,
  `away_team_result_id` int(10) unsigned default NULL,
  PRIMARY KEY  (`id`),
  KEY `games_FKIndex1` (`teams_id`),
  KEY `games_FKIndex2` (`teams_id`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1 AUTO_INCREMENT=1 ;

--
-- Dumping data for table `silk_games`
--


-- --------------------------------------------------------

--
-- Table structure for table `silk_photos`
--

CREATE TABLE IF NOT EXISTS `silk_photos` (
  `id` int(11) NOT NULL auto_increment,
  `filename` varchar(255) NOT NULL,
  `path` varchar(500) NOT NULL,
  PRIMARY KEY  (`id`),
  KEY `filename` (`filename`,`path`)
) ENGINE=MyISAM  DEFAULT CHARSET=latin1 AUTO_INCREMENT=3 ;

--
-- Dumping data for table `silk_photos`
--

INSERT INTO `silk_photos` (`id`, `filename`, `path`) VALUES
(1, '671151277_ccbfd5de72_o.jpg', '/pics/2007.06.29 Volleyball/'),
(2, '671662338_8781431ac7_o.jpg', '/pics/2007.06.29 Volleyball/');

-- --------------------------------------------------------

--
-- Table structure for table `silk_results`
--

CREATE TABLE IF NOT EXISTS `silk_results` (
  `id` int(10) unsigned NOT NULL auto_increment,
  `games_id` int(10) unsigned NOT NULL,
  `game_id` int(10) unsigned default NULL,
  `name` varchar(255) default NULL,
  PRIMARY KEY  (`id`),
  KEY `results_FKIndex1` (`games_id`),
  KEY `results_FKIndex2` (`games_id`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1 AUTO_INCREMENT=1 ;

--
-- Dumping data for table `silk_results`
--


-- --------------------------------------------------------

--
-- Table structure for table `silk_seasons`
--

CREATE TABLE IF NOT EXISTS `silk_seasons` (
  `id` int(10) unsigned NOT NULL auto_increment,
  `start_year` int(10) unsigned default NULL,
  `end_year` int(10) unsigned default NULL,
  `name` varchar(255) default NULL,
  `status_id` int(10) unsigned default NULL,
  PRIMARY KEY  (`id`)
) ENGINE=MyISAM  DEFAULT CHARSET=latin1 AUTO_INCREMENT=4 ;

--
-- Dumping data for table `silk_seasons`
--

INSERT INTO `silk_seasons` (`id`, `start_year`, `end_year`, `name`, `status_id`) VALUES
(1, 2008, 2009, 'NFL', 1),
(2, 2008, 2009, 'NBA', NULL),
(3, 2008, 2009, 'NHL', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `silk_segment`
--

CREATE TABLE IF NOT EXISTS `silk_segment` (
  `id` int(10) unsigned NOT NULL auto_increment,
  `seasons_id` int(10) unsigned NOT NULL,
  `name` varchar(255) default NULL,
  `season_id` int(10) unsigned default NULL,
  `status_id` int(10) unsigned default NULL,
  PRIMARY KEY  (`id`),
  KEY `segment_FKIndex1` (`seasons_id`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1 AUTO_INCREMENT=1 ;

--
-- Dumping data for table `silk_segment`
--


-- --------------------------------------------------------

--
-- Table structure for table `silk_stages`
--

CREATE TABLE IF NOT EXISTS `silk_stages` (
  `id` int(11) NOT NULL auto_increment,
  `season_id` int(11) NOT NULL,
  `name` varchar(255) NOT NULL,
  `status` int(11) NOT NULL,
  PRIMARY KEY  (`id`)
) ENGINE=MyISAM  DEFAULT CHARSET=latin1 AUTO_INCREMENT=8 ;

--
-- Dumping data for table `silk_stages`
--

INSERT INTO `silk_stages` (`id`, `season_id`, `name`, `status`) VALUES
(1, 1, 'Week 1', 1),
(2, 1, 'Week 2', 1),
(3, 2, 'NBA Regular Season', 1),
(4, 3, 'NHL Regular Season', 1),
(5, 1, 'Week 3', 1),
(6, 1, 'Week 4', 1),
(7, 2, 'NBA Playoffs', 1);

-- --------------------------------------------------------

--
-- Table structure for table `silk_statuses`
--

CREATE TABLE IF NOT EXISTS `silk_statuses` (
  `id` int(10) unsigned NOT NULL auto_increment,
  `name` varchar(255) default NULL,
  PRIMARY KEY  (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1 AUTO_INCREMENT=1 ;

--
-- Dumping data for table `silk_statuses`
--


-- --------------------------------------------------------

--
-- Table structure for table `silk_teams`
--

CREATE TABLE IF NOT EXISTS `silk_teams` (
  `id` int(10) unsigned NOT NULL auto_increment,
  `users_id` int(10) unsigned NOT NULL,
  `seasons_id` int(10) unsigned NOT NULL,
  `user_id` int(10) unsigned default NULL,
  `name` varchar(255) default NULL,
  `status_id` int(10) unsigned default NULL,
  `season_id` int(10) unsigned default NULL,
  PRIMARY KEY  (`id`),
  KEY `Teams_FKIndex1` (`seasons_id`),
  KEY `teams_FKIndex2` (`users_id`)
) ENGINE=MyISAM  DEFAULT CHARSET=latin1 AUTO_INCREMENT=6 ;

--
-- Dumping data for table `silk_teams`
--

INSERT INTO `silk_teams` (`id`, `users_id`, `seasons_id`, `user_id`, `name`, `status_id`, `season_id`) VALUES
(1, 1, 1, 1, 'Bulls', 1, 1),
(2, 1, 1, 1, 'Lakers', 1, 1),
(3, 1, 1, 1, 'Celtics', 1, 1),
(4, 1, 1, 1, 'Warriors', 1, 1),
(5, 1, 1, 1, 'Heat', 1, 1);

-- --------------------------------------------------------

--
-- Table structure for table `silk_users`
--

CREATE TABLE IF NOT EXISTS `silk_users` (
  `id` int(10) unsigned NOT NULL auto_increment,
  `statuses_id` int(10) unsigned NOT NULL,
  `usertypes_id` int(10) unsigned NOT NULL,
  `usertype_id` int(10) unsigned default NULL,
  `name` varchar(255) default NULL,
  `status_id` int(10) unsigned default NULL,
  PRIMARY KEY  (`id`),
  KEY `users_FKIndex1` (`usertypes_id`),
  KEY `users_FKIndex2` (`statuses_id`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1 AUTO_INCREMENT=1 ;

--
-- Dumping data for table `silk_users`
--


-- --------------------------------------------------------

--
-- Table structure for table `silk_usertypes`
--

CREATE TABLE IF NOT EXISTS `silk_usertypes` (
  `id` int(10) unsigned NOT NULL auto_increment,
  `statuses_id` int(10) unsigned NOT NULL,
  `usertype` varchar(255) default NULL,
  `status_id` int(10) unsigned default NULL,
  PRIMARY KEY  (`id`),
  KEY `usertypes_FKIndex1` (`statuses_id`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1 AUTO_INCREMENT=1 ;

--
-- Dumping data for table `silk_usertypes`
--


