-- phpMyAdmin SQL Dump
-- version 2.11.3deb1ubuntu1.1
-- http://www.phpmyadmin.net
--
-- Host: localhost
-- Generation Time: Jan 27, 2009 at 02:09 PM
-- Server version: 5.0.51
-- PHP Version: 5.2.4-2ubuntu5.4
 
SET SQL_MODE="NO_AUTO_VALUE_ON_ZERO";
 
--
-- Database: `silk`
--
 
-- --------------------------------------------------------
 
--
-- Table structure for table `silk_proof_users`
--
 
CREATE TABLE IF NOT EXISTS `silk_proof_users` (
  `id` int(11) NOT NULL auto_increment,
  `first_name` varchar(255) character set utf8 NOT NULL,
  `last_name` varchar(255) character set utf8 NOT NULL,
  `status` int(11) NOT NULL default '0',
  `create_date` datetime NOT NULL default '0000-00-00 00:00:00',
  `join_date` datetime NOT NULL default '0000-00-00 00:00:00',
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1 AUTO_INCREMENT=29 ;
 
--
-- Dumping data for table `silk_proof_users`
--
 
INSERT INTO `silk_proof_users` (`id`, `first_name`, `last_name`, `status`, `create_date`, `join_date`) VALUES
(1, 'Jenny', 'Jones', 0, '2009-01-16 00:00:00', '2009-01-16 23:22:00'),
(2, 'Marky', 'Mark', 0, '2009-01-16 00:00:00', '2009-01-16 21:55:00'),
(3, 'John', 'Sample', 0, '2009-01-18 23:32:14', '0000-00-00 00:00:00'),
(8, 'Bob', 'Barker', 0, '2009-01-19 00:10:47', '0000-00-00 00:00:00'),
(5, 'Jane', 'Sample', 2, '2009-01-18 23:47:19', '0000-00-00 00:00:00'),
(6, 'Frank', 'Sample', 0, '2009-01-18 23:47:39', '0000-00-00 00:00:00'),
(13, 'John', 'Doe', 0, '2009-01-19 09:28:52', '2009-01-19 09:28:52'),
(9, 'Carl', 'Weathers', 0, '2009-01-19 00:11:15', '0000-00-00 00:00:00'),
(12, 'Joe', 'Blow', 0, '2009-01-19 00:30:31', '2009-01-19 00:30:31'),
(14, 'kobe', 'bryant', 0, '2009-01-19 21:54:19', '2009-01-19 21:54:19'),
(15, 'derek', 'fisher', 0, '2009-01-19 21:54:36', '2009-01-19 21:54:36'),
(16, 'derek', 'fisher', 0, '2009-01-19 22:03:41', '2009-01-19 22:03:41'),
(17, 'jon', 'jon', 0, '2009-01-19 22:06:07', '2009-01-19 22:06:07'),
(18, '', '', 0, '2009-01-19 22:11:22', '2009-01-19 22:11:22'),
(19, '', '', 0, '2009-01-19 22:11:29', '2009-01-19 22:11:29'),
(20, 'greg', 'froese', 0, '2009-01-19 22:23:51', '2009-01-19 22:23:51'),
(21, 'greg', 'froese', 0, '2009-01-19 22:29:25', '2009-01-19 22:29:25'),
(22, 'greg', 'froese', 0, '2009-01-19 22:29:35', '2009-01-19 22:29:35'),
(23, 'greg', 'froese', 0, '2009-01-19 22:30:07', '2009-01-19 22:30:07'),
(24, 'lamar', 'odom', 0, '2009-01-19 22:30:25', '2009-01-19 22:30:25'),
(25, '', '', 0, '2009-01-19 22:31:01', '2009-01-19 22:31:01'),
(26, '', '', 0, '2009-01-19 22:31:51', '2009-01-19 22:31:51'),
(27, 'Anna', 'Kournikova', 0, '2009-01-19 22:32:02', '2009-01-19 22:32:02'),
(28, 'Monica', 'Seles', 3, '2009-01-19 23:09:25', '2009-01-19 23:09:25');
 
-- --------------------------------------------------------
 
--
-- Table structure for table `silk_proof_users_vs_usertypes`
--
 
CREATE TABLE IF NOT EXISTS `silk_proof_users_vs_usertypes` (
  `id` int(11) NOT NULL auto_increment,
  `user_id` int(11) NOT NULL,
  `usertype_id` int(11) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`,`usertype_id`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1 AUTO_INCREMENT=11 ;
 
--
-- Dumping data for table `silk_proof_users_vs_usertypes`
--
 
INSERT INTO `silk_proof_users_vs_usertypes` (`id`, `user_id`, `usertype_id`) VALUES
(1, 1, 1),
(2, 1, 2),
(3, 2, 2),
(4, 26, 2),
(5, 27, 1),
(6, 27, 2),
(7, 27, 3),
(8, 28, 1),
(9, 28, 2),
(10, 28, 4);
 
-- --------------------------------------------------------
 
--
-- Table structure for table `silk_proof_usertypes`
--
 
CREATE TABLE IF NOT EXISTS `silk_proof_usertypes` (
  `id` int(10) unsigned NOT NULL auto_increment,
  `usertype` varchar(255) default NULL,
  `status_id` int(10) unsigned default NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1 AUTO_INCREMENT=16 ;
 
--
-- Dumping data for table `silk_proof_usertypes`
--
 
INSERT INTO `silk_proof_usertypes` (`id`, `usertype`, `status_id`) VALUES
(1, 'Photographer', 1),
(2, 'Model', 1),
(3, 'Studio', 1),
(4, 'Make-up Artist', NULL),
(15, 'Wardrobe', 1);
 
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
  `description` text NOT NULL,
  `first_name` varchar(255) NOT NULL,
  `last_name` varchar(255) NOT NULL,
  `yet_another_field` varchar(255) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1 AUTO_INCREMENT=52 ;
 
--
-- Dumping data for table `silk_seasons`
--
 
INSERT INTO `silk_seasons` (`id`, `start_year`, `end_year`, `name`, `status_id`, `description`, `first_name`, `last_name`, `yet_another_field`) VALUES
(1, 2008, 2009, 'NFL', 1, 'Welcome to the description.', '', '', ''),
(2, 2008, 2009, 'NBA', NULL, '', '', '', ''),
(3, 2008, 2009, 'NHL', NULL, '', '', '', ''),
(15, 3000, 3000, 'season', NULL, '', '', '', ''),
(16, 3000, 3000, 'season', NULL, '', '', '', ''),
(17, 3000, 3000, 'season', NULL, '', '', '', ''),
(18, 200, 2000, 'Testing', NULL, '', '', '', ''),
(19, 200, 2000, 'Testing', NULL, '', '', '', ''),
(20, 200, 2000, 'Testing', NULL, '', '', '', ''),
(21, 200, 2000, 'Testing', NULL, '', '', '', ''),
(22, 200, 2000, 'Testing', NULL, '', '', '', ''),
(23, 200, 2000, 'Testing', NULL, '', '', '', ''),
(24, 200, 2000, 'Testing', NULL, '', '', '', ''),
(25, 200, 2000, 'Testing', NULL, '', '', '', ''),
(26, 200, 2000, 'Testing', NULL, '', '', '', ''),
(27, 200, 2000, 'Testing', NULL, '', '', '', ''),
(28, 200, 2000, 'Testing', NULL, '', '', '', ''),
(29, 200, 2000, 'Testing', NULL, '', '', '', ''),
(30, 200, 2000, 'Testing', NULL, '', '', '', ''),
(31, 200, 2000, 'Testing', NULL, '', '', '', ''),
(32, 200, 2000, 'Testing', NULL, '', '', '', ''),
(33, 200, 2000, 'Testing', NULL, '', '', '', ''),
(34, 235, 235, '235235', NULL, '', '', '', ''),
(35, 235, 235, '235235', NULL, '', '', '', ''),
(36, 235, 235, '235235', NULL, '', '', '', ''),
(37, 235, 235, '235235', NULL, '', '', '', ''),
(38, 235, 235, '235235', NULL, '', '', '', ''),
(39, 235, 235, '235235', NULL, '', '', '', ''),
(40, 235, 235, '235235', NULL, '', '', '', ''),
(41, 235, 235, '235235', NULL, '', '', '', ''),
(42, 235, 235, '235235', NULL, '', '', '', ''),
(43, 235, 235, '235235', NULL, '', '', '', ''),
(44, 2008, 2009, 'Testing CSS', NULL, '', '', '', ''),
(45, 2008, 2009, 'Testing CSS', NULL, '', '', '', ''),
(46, 2008, 2009, 'Testing CSS', NULL, '', '', '', ''),
(47, 2008, 2009, 'Testing CSS', NULL, '', '', '', ''),
(48, 2008, 2009, 'Testing CSS', NULL, '', '', '', ''),
(49, 2008, 2009, 'Testing CSS', NULL, '', '', '', ''),
(50, 2008, 2009, 'Testing CSS', NULL, '', '', '', ''),
(51, 2008, 2009, 'secoif''s season', NULL, '', '', '', '');
 
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
  PRIMARY KEY (`id`),
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
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1 AUTO_INCREMENT=8 ;
 
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
  PRIMARY KEY (`id`)
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
  PRIMARY KEY (`id`),
  KEY `Teams_FKIndex1` (`seasons_id`),
  KEY `teams_FKIndex2` (`users_id`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1 AUTO_INCREMENT=6 ;
 
--
-- Dumping data for table `silk_teams`
--
 
INSERT INTO `silk_teams` (`id`, `users_id`, `seasons_id`, `user_id`, `name`, `status_id`, `season_id`) VALUES
(1, 1, 1, 1, 'Bulls', 1, 1),
(2, 1, 1, 1, 'Lakers', 1, 1),
(3, 1, 1, 1, 'Celtics', 1, 1),
(4, 1, 1, 1, 'Warriors', 1, 1),
(5, 1, 1, 1, 'Heat', 1, 1);
