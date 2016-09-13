-- phpMyAdmin SQL Dump
-- version 3.5.8.2
-- http://www.phpmyadmin.net
--
-- 主机: 127.0.0.1
-- 生成日期: 2016 年 09 月 13 日 20:08
-- 服务器版本: 5.6.32-log
-- PHP 版本: 5.4.35

SET SQL_MODE="NO_AUTO_VALUE_ON_ZERO";
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8 */;

--
-- 数据库: `cron`
--

-- --------------------------------------------------------

--
-- 表的结构 `crontab`
--

CREATE TABLE IF NOT EXISTS `crontab` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT COMMENT 'id',
  `taskid` varchar(32) NOT NULL COMMENT '任务id',
  `taskname` varchar(32) NOT NULL,
  `rule` text NOT NULL COMMENT '规则 可以是crontab规则也可以是json类型的精确时间任务',
  `unique` tinyint(5) NOT NULL DEFAULT '0' COMMENT '0 唯一任务 大于0表示同时可并行的任务进程个数',
  `execute` varchar(32) NOT NULL COMMENT '运行这个任务的类',
  `args` text NOT NULL COMMENT '任务参数',
  `status` tinyint(5) NOT NULL DEFAULT '0' COMMENT '0 正常  1 暂停  2 删除',
  `createtime` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00',
  `updatetime` timestamp NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 AUTO_INCREMENT=3 ;

--
-- 转存表中的数据 `crontab`
--

INSERT INTO `crontab` (`id`, `taskid`, `taskname`, `rule`, `unique`, `execute`, `args`, `status`, `createtime`, `updatetime`) VALUES
(1, 'taskid', 'taskname1-sec2', '*/2 * * * * *', 1, 'Cmd', '{"cmd":"\\/usr\\/local\\/bin\\/php \\/root\\/CYZSStat\\/Web\\/cron.php method=cron.errTest  inner=1 err=1 file=sec2","ext":""}', 0, '0000-00-00 00:00:00', '2016-09-13 11:15:22'),
(2, 'taskid', 'taskname2-min1', '0 */1 * * * *', 1, 'Cmd', '{"cmd":"\\/usr\\/local\\/bin\\/php \\/root\\/CYZSStat\\/Web\\/cron.php method=cron.errTest  inner=1 err=1 file=min1","ext":""}', 0, '0000-00-00 00:00:00', '2016-09-13 11:58:57');

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
