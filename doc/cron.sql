-- MySQL dump 10.13  Distrib 5.6.32, for Linux (x86_64)
--
-- Host: localhost    Database: cron
-- ------------------------------------------------------
-- Server version	5.6.32-log

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8 */;
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;

--
-- Table structure for table `crontab`
--

DROP TABLE IF EXISTS `crontab`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `crontab` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT COMMENT 'id',
  `taskid` varchar(32) NOT NULL COMMENT '任务id',
  `taskname` varchar(2000) NOT NULL,
  `rule` text NOT NULL COMMENT '规则 可以是crontab规则也可以是json类型的精确时间任务',
  `unique` tinyint(5) NOT NULL DEFAULT '0' COMMENT '0 唯一任务 大于0表示同时可并行的任务进程个数',
  `execute` varchar(32) NOT NULL COMMENT '运行这个任务的类',
  `args` text NOT NULL COMMENT '任务参数',
  `status` tinyint(5) NOT NULL DEFAULT '0' COMMENT '0 正常  1 暂停  2 删除',
  `createtime` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00',
  `updatetime` timestamp NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=19 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `crontab`
--

LOCK TABLES `crontab` WRITE;
/*!40000 ALTER TABLE `crontab` DISABLE KEYS */;
INSERT INTO `crontab` VALUES (1,'taskid','taskname1-sec2-x','*/2 * * * * *',6,'Cmd','{\"cmd\":\"\\/usr\\/local\\/bin\\/php \\/root\\/CYZSStat\\/Web\\/cron.php method=cron.errTest  inner=1 err=1 file=sec2\",\"ext\":\"\"}',1,'0000-00-00 00:00:00','2016-10-24 06:30:31'),(2,'taskid','taskname2-min1-x','0 */1 * * * *',1,'Cmd','{\"cmd\":\"\\/usr\\/local\\/bin\\/php \\/root\\/CYZSStat\\/Web\\/cron.php method=cron.errTest  inner=1 err=1 file=min1\",\"ext\":\"\"}',1,'0000-00-00 00:00:00','2016-10-24 06:30:33'),(3,'taskid-one','taskname-one-x','*/1 * * * * *',1,'Cmd','{\"cmd\":\"\\/usr\\/local\\/bin\\/php \\/root\\/CYZSStat\\/Web\\/cron.php method=cron.errTest  inner=1 err=1 file=tt\",\"ext\":\"\"}',1,'0000-00-00 00:00:00','2016-10-26 07:11:41'),(4,'taskid-five','taskname-five-x','*/5 * * * * *',2,'Cmd','{\"cmd\":\"\\/usr\\/local\\/bin\\/php \\/root\\/CYZSStat\\/Web\\/cron.php method=cron.errTest  inner=1 err=1 file=sec5\",\"ext\":\"\"}',1,'0000-00-00 00:00:00','2016-10-24 06:30:36'),(9,'taskid-3,15','taskname-3,15-x','0 3,15 * * * *',2,'Cmd','{\"cmd\":\"\\/usr\\/local\\/bin\\/php \\/root\\/CYZSStat\\/Web\\/cron.php method=cron.errTest  inner=1 err=1 file=min315\",\"ext\":\"\"}',1,'0000-00-00 00:00:00','2016-10-24 06:30:37'),(10,'taskid-3,15-8,11','taskname-3,15-x','0 3,15 8-11 * * *',2,'Cmd','{\"cmd\":\"\\/usr\\/local\\/bin\\/php \\/root\\/CYZSStat\\/Web\\/cron.php method=cron.errTest  inner=1 err=1 file=min315.811\",\"ext\":\"\"}',1,'0000-00-00 00:00:00','2016-10-24 06:30:40'),(11,'taskid-3,15-8,11-1','taskname-3,15-8,11-1-x','0 3,15 8-11 * * 1',2,'Cmd','{\"cmd\":\"\\/usr\\/local\\/bin\\/php \\/root\\/CYZSStat\\/Web\\/cron.php method=cron.errTest  inner=1 err=1 file=min315.811.1\",\"ext\":\"\"}',0,'0000-00-00 00:00:00','2016-09-23 10:44:33'),(12,'taskid-30-11','taskname-30-11-x','0 30 11 * * *',2,'Cmd','{\"cmd\":\"\\/usr\\/local\\/bin\\/php \\/root\\/CYZSStat\\/Web\\/cron.php method=cron.errTest  inner=1 err=1 file=min30-11\",\"ext\":\"\"}',0,'0000-00-00 00:00:00','2016-09-28 02:54:33'),(13,'taskid-22.23.24-18.15','每月22,23,24,18:15-x','0 15 18 22,23,24 * *',2,'Cmd','{\"cmd\":\"\\/usr\\/local\\/bin\\/php \\/root\\/CYZSStat\\/Web\\/cron.php method=cron.errTest  inner=1 err=1 file=test18.15-22.23\",\"ext\":\"\"}',0,'0000-00-00 00:00:00','2016-09-28 02:55:25'),(14,'taskid-4.5.6-18.30','每周3456,16:30-x','0 10 16 * * 6,4,5,3',2,'Cmd','{\"cmd\":\"\\/usr\\/local\\/bin\\/php \\/root\\/CYZSStat\\/Web\\/cron.php method=cron.errTest  inner=1 err=1 file=test16.10-3.4.5.6\",\"ext\":\"\"}',0,'0000-00-00 00:00:00','2016-09-28 08:10:31'),(15,'taskid-18.23-30','每天18 : 00至23 : 00之间每隔30分钟-x','0 0,30 18-23 * * *',2,'Cmd','{\"cmd\":\"\\/usr\\/local\\/bin\\/php \\/root\\/CYZSStat\\/Web\\/cron.php method=cron.errTest  inner=1 err=1 file=test18.30-30\",\"ext\":\"\"}',0,'0000-00-00 00:00:00','2016-09-28 08:10:53'),(16,'taskid-18.10-w5','每星期3,4,5的晚上16:20-x','0 20 16 * * 5,4,3',2,'Cmd','{\"cmd\":\"\\/usr\\/local\\/bin\\/php \\/root\\/CYZSStat\\/Web\\/cron.php method=cron.errTest  inner=1 err=1 file=test16.20-w345\",\"ext\":\"\"}',1,'0000-00-00 00:00:00','2016-10-24 06:30:43'),(17,'taskid-18-7-h1','13点到16点之间，每隔一小时','0 0 9-16/1 * * * ',2,'Cmd','{\"cmd\":\"\\/usr\\/local\\/bin\\/php \\/root\\/CYZSStat\\/Web\\/cron.php method=cron.errTest  inner=1 err=1 file=test16.12-h1 >>/tmp/redirect.log 2>&1\",\"ext\":\"\"}',1,'0000-00-00 00:00:00','2016-10-24 06:30:41'),(18,'taskid-time','时间-x','[\"14:10\",\"2016-9-28 14:33:33 \",\"13:40:39\",\"15:27\",\"15:44\"]',2,'Cmd','{\"cmd\":\"\\/usr\\/local\\/bin\\/php \\/root\\/CYZSStat\\/Web\\/cron.php method=cron.errTest  inner=1 err=1 file=time\",\"ext\":\"\"}',0,'0000-00-00 00:00:00','2016-10-18 07:13:14');
/*!40000 ALTER TABLE `crontab` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `host_cron`
--

DROP TABLE IF EXISTS `host_cron`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `host_cron` (
  `ipHost` bigint(20) NOT NULL DEFAULT '0',
  `id` bigint(20) NOT NULL DEFAULT '0',
  PRIMARY KEY (`ipHost`,`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `host_cron`
--

LOCK TABLES `host_cron` WRITE;
/*!40000 ALTER TABLE `host_cron` DISABLE KEYS */;
/*!40000 ALTER TABLE `host_cron` ENABLE KEYS */;
UNLOCK TABLES;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2016-10-28  1:37:47
