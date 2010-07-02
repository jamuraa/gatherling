
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
DROP TABLE IF EXISTS `archetypes`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `archetypes` (
  `name` varchar(40) NOT NULL,
  `description` text,
  `priority` tinyint(3) unsigned NOT NULL DEFAULT '1',
  PRIMARY KEY (`name`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

LOCK TABLES `archetypes` WRITE;
/*!40000 ALTER TABLE `archetypes` DISABLE KEYS */;
INSERT INTO `archetypes` (`name`, `description`, `priority`) VALUES ('Aggro',NULL,2);
INSERT INTO `archetypes` (`name`, `description`, `priority`) VALUES ('Aggro-Combo',NULL,1);
INSERT INTO `archetypes` (`name`, `description`, `priority`) VALUES ('Aggro-Control',NULL,1);
INSERT INTO `archetypes` (`name`, `description`, `priority`) VALUES ('Combo',NULL,2);
INSERT INTO `archetypes` (`name`, `description`, `priority`) VALUES ('Combo-Control',NULL,1);
INSERT INTO `archetypes` (`name`, `description`, `priority`) VALUES ('Control',NULL,2);
INSERT INTO `archetypes` (`name`, `description`, `priority`) VALUES ('Unclassified',NULL,0);
/*!40000 ALTER TABLE `archetypes` ENABLE KEYS */;
UNLOCK TABLES;
DROP TABLE IF EXISTS `bans`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `bans` (
  `card` bigint(20) unsigned NOT NULL,
  `format` varchar(40) NOT NULL,
  `allowed` tinyint(3) unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`card`,`format`),
  KEY `format` (`format`),
  CONSTRAINT `bans_ibfk_1` FOREIGN KEY (`card`) REFERENCES `cards` (`id`) ON UPDATE CASCADE,
  CONSTRAINT `bans_ibfk_2` FOREIGN KEY (`format`) REFERENCES `formats` (`name`) ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

DROP TABLE IF EXISTS `cards`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `cards` (
  `cost` varchar(40) DEFAULT NULL,
  `convertedcost` tinyint(3) unsigned NOT NULL DEFAULT '0',
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `isw` tinyint(1) DEFAULT '0',
  `isr` tinyint(1) DEFAULT '0',
  `isg` tinyint(1) DEFAULT '0',
  `isu` tinyint(1) DEFAULT '0',
  `isb` tinyint(1) DEFAULT '0',
  `name` varchar(40) NOT NULL,
  `cardset` varchar(40) NOT NULL,
  `type` varchar(40) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `cardset` (`cardset`),
  CONSTRAINT `cards_ibfk_1` FOREIGN KEY (`cardset`) REFERENCES `cardsets` (`name`) ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=4603 DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

DROP TABLE IF EXISTS `cardsets`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `cardsets` (
  `released` date NOT NULL,
  `name` varchar(40) NOT NULL,
  `type` enum('Core','Block','Extra') DEFAULT 'Block',
  PRIMARY KEY (`name`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

DROP TABLE IF EXISTS `db_version`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `db_version` (
  `version` int(11) DEFAULT NULL
) ENGINE=MyISAM DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

LOCK TABLES `db_version` WRITE;
/*!40000 ALTER TABLE `db_version` DISABLE KEYS */;
INSERT INTO `db_version` (`version`) VALUES (2);
/*!40000 ALTER TABLE `db_version` ENABLE KEYS */;
UNLOCK TABLES;
DROP TABLE IF EXISTS `deckcontents`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `deckcontents` (
  `card` bigint(20) unsigned NOT NULL,
  `deck` bigint(20) unsigned NOT NULL,
  `qty` tinyint(3) unsigned NOT NULL DEFAULT '1',
  `issideboard` tinyint(4) NOT NULL DEFAULT '0',
  PRIMARY KEY (`card`,`deck`,`issideboard`),
  KEY `deck` (`deck`),
  CONSTRAINT `deckcontents_ibfk_1` FOREIGN KEY (`card`) REFERENCES `cards` (`id`),
  CONSTRAINT `deckcontents_ibfk_2` FOREIGN KEY (`deck`) REFERENCES `decks` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `decks`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `decks` (
  `archetype` varchar(40) DEFAULT NULL,
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(40) NOT NULL,
  `notes` text,
  `deck_hash` varchar(40) DEFAULT NULL,
  `sideboard_hash` varchar(40) DEFAULT NULL,
  `whole_hash` varchar(40) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `archetype` (`archetype`)
) ENGINE=InnoDB AUTO_INCREMENT=10559 DEFAULT CHARSET=latin1;

DROP TABLE IF EXISTS `decktypes`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `decktypes` (
  `name` varchar(40) COLLATE latin1_general_ci NOT NULL
) ENGINE=MyISAM DEFAULT CHARSET=latin1 COLLATE=latin1_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

DROP TABLE IF EXISTS `entries`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `entries` (
  `event` varchar(40) NOT NULL,
  `player` varchar(40) NOT NULL,
  `medal` enum('1st','2nd','t4','t8','dot') NOT NULL DEFAULT 'dot',
  `deck` bigint(20) unsigned DEFAULT NULL,
  `ignored` tinyint(1) DEFAULT NULL,
  `notes` text,
  PRIMARY KEY (`event`,`player`),
  KEY `player` (`player`),
  KEY `deck` (`deck`),
  CONSTRAINT `entries_ibfk_2` FOREIGN KEY (`player`) REFERENCES `players` (`name`) ON UPDATE CASCADE,
  CONSTRAINT `entries_ibfk_3` FOREIGN KEY (`deck`) REFERENCES `decks` (`id`),
  CONSTRAINT `entries_ibfk_4` FOREIGN KEY (`event`) REFERENCES `events` (`name`) ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

DROP TABLE IF EXISTS `events`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `events` (
  `start` datetime NOT NULL,
  `format` varchar(40) NOT NULL,
  `host` varchar(40) DEFAULT NULL,
  `kvalue` tinyint(3) unsigned NOT NULL DEFAULT '16',
  `metaurl` varchar(240) DEFAULT NULL,
  `name` varchar(40) NOT NULL,
  `number` tinyint(3) unsigned DEFAULT NULL,
  `season` tinyint(3) unsigned DEFAULT NULL,
  `series` varchar(40) DEFAULT NULL,
  `threadurl` varchar(240) DEFAULT NULL,
  `reporturl` varchar(240) DEFAULT NULL,
  `finalized` tinyint(3) unsigned NOT NULL DEFAULT '0',
  `cohost` varchar(40) DEFAULT NULL,
  PRIMARY KEY (`name`),
  KEY `format` (`format`),
  KEY `host` (`host`),
  KEY `series` (`series`),
  KEY `cohost` (`cohost`),
  CONSTRAINT `events_ibfk_1` FOREIGN KEY (`format`) REFERENCES `formats` (`name`) ON UPDATE CASCADE,
  CONSTRAINT `events_ibfk_2` FOREIGN KEY (`host`) REFERENCES `players` (`name`) ON UPDATE CASCADE,
  CONSTRAINT `events_ibfk_3` FOREIGN KEY (`series`) REFERENCES `series` (`name`) ON UPDATE CASCADE,
  CONSTRAINT `events_ibfk_4` FOREIGN KEY (`cohost`) REFERENCES `players` (`name`) ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

DROP TABLE IF EXISTS `formats`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `formats` (
  `name` varchar(40) NOT NULL,
  `description` text,
  `priority` tinyint(3) unsigned DEFAULT '1',
  PRIMARY KEY (`name`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

DROP TABLE IF EXISTS `matches`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `matches` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `playera` varchar(40) NOT NULL,
  `playerb` varchar(40) NOT NULL,
  `round` tinyint(3) unsigned NOT NULL,
  `subevent` bigint(20) unsigned NOT NULL,
  `result` enum('A','B','D') NOT NULL DEFAULT 'D',
  PRIMARY KEY (`id`),
  KEY `playera` (`playera`),
  KEY `playerb` (`playerb`),
  KEY `subevent` (`subevent`),
  CONSTRAINT `matches_ibfk_1` FOREIGN KEY (`playera`) REFERENCES `players` (`name`) ON UPDATE CASCADE,
  CONSTRAINT `matches_ibfk_2` FOREIGN KEY (`playerb`) REFERENCES `players` (`name`) ON UPDATE CASCADE,
  CONSTRAINT `matches_ibfk_3` FOREIGN KEY (`subevent`) REFERENCES `subevents` (`id`) ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=37427 DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

DROP TABLE IF EXISTS `players`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `players` (
  `name` varchar(40) CHARACTER SET latin1 NOT NULL,
  `host` tinyint(3) unsigned NOT NULL DEFAULT '0',
  `password` varchar(80) CHARACTER SET latin1 DEFAULT NULL,
  `super` tinyint(3) unsigned NOT NULL DEFAULT '0',
  `mtgo_confirmed` tinyint(1) DEFAULT NULL,
  `mtgo_challenge` varchar(5) COLLATE latin1_general_ci DEFAULT NULL,
  PRIMARY KEY (`name`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

DROP TABLE IF EXISTS `ratings`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `ratings` (
  `player` varchar(40) NOT NULL,
  `rating` smallint(5) unsigned NOT NULL,
  `format` varchar(40) NOT NULL,
  `updated` datetime NOT NULL,
  `wins` bigint(20) unsigned NOT NULL,
  `losses` bigint(20) unsigned NOT NULL,
  KEY `player` (`player`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

DROP TABLE IF EXISTS `series`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `series` (
  `name` varchar(40) NOT NULL,
  `isactive` tinyint(1) DEFAULT '0',
  `logo` blob,
  `imgtype` varchar(40) DEFAULT NULL,
  `imgsize` bigint(20) unsigned DEFAULT NULL,
  PRIMARY KEY (`name`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

DROP TABLE IF EXISTS `setlegality`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `setlegality` (
  `format` varchar(40) NOT NULL,
  `cardset` varchar(40) NOT NULL,
  PRIMARY KEY (`format`,`cardset`),
  KEY `cardset` (`cardset`),
  CONSTRAINT `setlegality_ibfk_1` FOREIGN KEY (`format`) REFERENCES `formats` (`name`) ON UPDATE CASCADE,
  CONSTRAINT `setlegality_ibfk_2` FOREIGN KEY (`cardset`) REFERENCES `cardsets` (`name`) ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

DROP TABLE IF EXISTS `stewards`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `stewards` (
  `event` varchar(40) NOT NULL,
  `player` varchar(40) NOT NULL,
  KEY `event` (`event`),
  KEY `player` (`player`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

DROP TABLE IF EXISTS `subevents`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `subevents` (
  `parent` varchar(40) NOT NULL,
  `rounds` tinyint(3) unsigned NOT NULL DEFAULT '3',
  `timing` tinyint(3) unsigned NOT NULL DEFAULT '1',
  `type` enum('Swiss','Single Elimination','League','Round Robin') DEFAULT NULL,
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  PRIMARY KEY (`id`),
  KEY `parent` (`parent`),
  CONSTRAINT `subevents_ibfk_1` FOREIGN KEY (`parent`) REFERENCES `events` (`name`) ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=1946 DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

DROP TABLE IF EXISTS `trophies`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `trophies` (
  `event` varchar(40) NOT NULL,
  `image` blob,
  `type` varchar(40) DEFAULT NULL,
  `size` bigint(20) unsigned DEFAULT NULL,
  PRIMARY KEY (`event`),
  CONSTRAINT `trophies_ibfk_1` FOREIGN KEY (`event`) REFERENCES `events` (`name`) ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

