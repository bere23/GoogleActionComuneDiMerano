# ************************************************************
# Sequel Pro SQL dump
# Version 4541
#
# http://www.sequelpro.com/
# https://github.com/sequelpro/sequelpro
#
# Host: strayk.cbmi0d6xlitt.eu-south-1.rds.amazonaws.com (MySQL 5.5.5-10.3.20-MariaDB-log)
# Database: comunemerano
# Generation Time: 2020-09-27 10:05:57 +0000
# ************************************************************


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;


# Dump of table eventsde
# ------------------------------------------------------------

DROP TABLE IF EXISTS `eventsde`;

CREATE TABLE `eventsde` (
  `ev_id` bigint(20) NOT NULL AUTO_INCREMENT,
  `ev_title` varchar(255) DEFAULT NULL,
  `ev_type` varchar(255) DEFAULT NULL,
  `ev_where` varchar(255) DEFAULT NULL,
  `ev_start_date` date DEFAULT NULL,
  `ev_end_date` date DEFAULT NULL,
  `ev_descr` text DEFAULT NULL,
  PRIMARY KEY (`ev_id`),
  KEY `ev_type_it` (`ev_type`),
  KEY `ev_where_it` (`ev_where`),
  KEY `ev_start_date` (`ev_start_date`),
  KEY `ev_end_date` (`ev_end_date`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;



# Dump of table eventsit
# ------------------------------------------------------------

DROP TABLE IF EXISTS `eventsit`;

CREATE TABLE `eventsit` (
  `ev_id` bigint(20) NOT NULL AUTO_INCREMENT,
  `ev_title` varchar(255) DEFAULT NULL,
  `ev_type` varchar(255) DEFAULT NULL,
  `ev_where` varchar(255) DEFAULT NULL,
  `ev_start_date` date DEFAULT NULL,
  `ev_end_date` date DEFAULT NULL,
  `ev_descr` text DEFAULT NULL,
  PRIMARY KEY (`ev_id`),
  KEY `ev_type_it` (`ev_type`),
  KEY `ev_where_it` (`ev_where`),
  KEY `ev_start_date` (`ev_start_date`),
  KEY `ev_end_date` (`ev_end_date`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;



# Dump of table incarichi
# ------------------------------------------------------------

DROP TABLE IF EXISTS `incarichi`;

CREATE TABLE `incarichi` (
  `in_id` bigint(20) NOT NULL AUTO_INCREMENT,
  `in_title_it` varchar(255) DEFAULT NULL,
  `in_title_de` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`in_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;



# Dump of table personale
# ------------------------------------------------------------

DROP TABLE IF EXISTS `personale`;

CREATE TABLE `personale` (
  `pe_id` bigint(20) NOT NULL AUTO_INCREMENT,
  `pe_uf_id` bigint(20) DEFAULT NULL,
  `pe_in_id` bigint(20) DEFAULT NULL,
  `pe_nome` varchar(255) DEFAULT NULL,
  `pe_email` varchar(255) DEFAULT NULL,
  `pe_tel` varchar(255) DEFAULT NULL,
  `pe_stanza_it` varchar(255) DEFAULT NULL,
  `pe_stanza_de` varchar(255) DEFAULT NULL,
  `pe_competenze_it` text DEFAULT NULL,
  `pe_competenze_de` text DEFAULT NULL,
  PRIMARY KEY (`pe_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;



# Dump of table querylog
# ------------------------------------------------------------

DROP TABLE IF EXISTS `querylog`;

CREATE TABLE `querylog` (
  `ql_id` bigint(20) NOT NULL AUTO_INCREMENT,
  `ql_lang` varchar(3) DEFAULT NULL,
  `ql_query` text DEFAULT NULL,
  `ql_answer` text DEFAULT NULL,
  `ql_intent` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`ql_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;



# Dump of table ripartizioni
# ------------------------------------------------------------

DROP TABLE IF EXISTS `ripartizioni`;

CREATE TABLE `ripartizioni` (
  `ri_id` bigint(20) NOT NULL AUTO_INCREMENT,
  `ri_name_it` varchar(255) DEFAULT NULL,
  `ri_name_de` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`ri_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;



# Dump of table servizide
# ------------------------------------------------------------

DROP TABLE IF EXISTS `servizide`;

CREATE TABLE `servizide` (
  `se_id` bigint(20) NOT NULL AUTO_INCREMENT,
  `se_titolo` varchar(255) DEFAULT NULL,
  `se_responsabili` varchar(255) DEFAULT NULL,
  `se_text` text DEFAULT NULL,
  PRIMARY KEY (`se_id`),
  KEY `se_titolo` (`se_titolo`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;



# Dump of table serviziit
# ------------------------------------------------------------

DROP TABLE IF EXISTS `serviziit`;

CREATE TABLE `serviziit` (
  `se_id` bigint(20) NOT NULL AUTO_INCREMENT,
  `se_titolo` varchar(255) DEFAULT NULL,
  `se_responsabili` varchar(255) DEFAULT NULL,
  `se_text` text DEFAULT NULL,
  PRIMARY KEY (`se_id`),
  KEY `se_titolo` (`se_titolo`),
  FULLTEXT KEY `se_text` (`se_text`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;



# Dump of table uffici
# ------------------------------------------------------------

DROP TABLE IF EXISTS `uffici`;

CREATE TABLE `uffici` (
  `uf_id` bigint(20) NOT NULL AUTO_INCREMENT,
  `uf_ri_id` bigint(20) DEFAULT NULL,
  `uf_title_it` varchar(255) DEFAULT NULL,
  `uf_title_de` varchar(255) DEFAULT NULL,
  `uf_stanza_it` varchar(255) DEFAULT NULL,
  `uf_stanza_de` varchar(255) DEFAULT NULL,
  `uf_tel` varchar(255) DEFAULT NULL,
  `uf_competenze_it` text DEFAULT NULL,
  `uf_competenze_de` text DEFAULT NULL,
  `uf_apertura_it` text DEFAULT NULL,
  `uf_apertura_de` text DEFAULT NULL,
  PRIMARY KEY (`uf_id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;




/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;
/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
