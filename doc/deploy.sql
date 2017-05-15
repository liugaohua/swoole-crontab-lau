-- phpMyAdmin SQL Dump
-- version 4.2.7.1
-- http://www.phpmyadmin.net
--
-- Host: 10.0.0.40:3306
-- Generation Time: 2016-12-23 15:06:55
-- 服务器版本： 5.6.24-log
-- PHP Version: 5.6.26

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8 */;

--
-- Database: `cron`
--
CREATE DATABASE IF NOT EXISTS `cron` DEFAULT CHARACTER SET utf8 COLLATE utf8_general_ci;
USE `cron`;

-- --------------------------------------------------------

--
-- 表的结构 `crontab`
--

CREATE TABLE IF NOT EXISTS `crontab` (
`id` bigint(20) unsigned NOT NULL COMMENT 'id',
  `taskid` varchar(256) NOT NULL COMMENT '任务id',
  `taskname` varchar(2000) NOT NULL,
  `rule` text NOT NULL COMMENT '规则 可以是crontab规则也可以是json类型的精确时间任务',
  `unique` tinyint(5) NOT NULL DEFAULT '0' COMMENT '0 唯一任务 大于0表示同时可并行的任务进程个数',
  `execute` varchar(32) NOT NULL COMMENT '运行这个任务的类',
  `args` text NOT NULL COMMENT '任务参数',
  `status` tinyint(5) NOT NULL DEFAULT '0' COMMENT '0暂停   1 正常  2 删除',
  `createtime` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00',
  `updatetime` timestamp NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 AUTO_INCREMENT=1815 ;

-- --------------------------------------------------------

--
-- 表的结构 `host_cron`
--

CREATE TABLE IF NOT EXISTS `host_cron` (
  `ipHost` bigint(20) NOT NULL DEFAULT '0',
  `id` bigint(20) NOT NULL DEFAULT '0'
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

--
-- Indexes for dumped tables
--

--
-- Indexes for table `crontab`
--
ALTER TABLE `crontab`
 ADD PRIMARY KEY (`id`);

--
-- Indexes for table `host_cron`
--
ALTER TABLE `host_cron`
 ADD PRIMARY KEY (`ipHost`,`id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `crontab`
--
ALTER TABLE `crontab`
MODIFY `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT COMMENT 'id',AUTO_INCREMENT=100;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
