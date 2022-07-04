SET NAMES utf8;
SET time_zone = '+00:00';
SET foreign_key_checks = 0;
SET sql_mode = 'NO_AUTO_VALUE_ON_ZERO';

SET NAMES utf8mb4;

CREATE DATABASE api;

DROP TABLE IF EXISTS `Books`;
CREATE TABLE `Books` (
  `bookID` bigint NOT NULL AUTO_INCREMENT,
  `title` varchar(255) NOT NULL,
  `description` mediumtext,
  `date` date DEFAULT NULL,
  `start_time` time DEFAULT NULL,
  `end_time` time DEFAULT NULL,
  `deadline` datetime DEFAULT NULL,
  `complete` enum('Y','N') CHARACTER SET utf8mb3 COLLATE utf8_general_ci NOT NULL DEFAULT 'N',
  `userID` int NOT NULL,
  PRIMARY KEY (`bookID`),
  KEY `userID` (`userID`),
  CONSTRAINT `Books_ibfk_1` FOREIGN KEY (`userID`) REFERENCES `Users` (`userID`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

INSERT INTO `Books` (`bookID`, `title`, `description`, `date`, `start_time`, `end_time`, `deadline`, `complete`, `userID`) VALUES
(1,	'The Famous 5',	'This is a new description',	'2020-03-26',	'09:00:00',	'12:00:00',	'2020-04-30 00:00:00',	'N',	1),
(2,	'Waldo and his Adventures',	NULL,	'2022-06-18',	NULL,	NULL,	NULL,	'N',	1),
(4,	'The Lion King',	NULL,	'2022-06-18',	'23:00:00',	'00:00:00',	'2022-06-19 00:00:00',	'Y',	1),
(5,	'The Seven',	NULL,	'2022-07-04',	NULL,	NULL,	NULL,	'N',	1),
(6,	'This is a book on the Test User!',	NULL,	'2022-07-04',	NULL,	NULL,	NULL,	'N',	2),
(7,	'This is a book on the Test User 2!',	NULL,	'2022-07-04',	NULL,	NULL,	NULL,	'N',	2);

DELIMITER ;;

CREATE TRIGGER `api` BEFORE INSERT ON `Books` FOR EACH ROW
BEGIN
    SET NEW.date = NOW();
END;;

DELIMITER ;

DROP TABLE IF EXISTS `Users`;
CREATE TABLE `Users` (
  `userID` int NOT NULL AUTO_INCREMENT,
  `username` varchar(30) NOT NULL,
  `password` varchar(255) NOT NULL,
  PRIMARY KEY (`userID`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

INSERT INTO `Users` (`userID`, `username`, `password`) VALUES
(1,	'harry',	'$2a$12$PKSODAJs.x2ANU0ztKTbt.OOlERKgTuJoXpFSODjF0zHZAjmMiAs6'),
(2,	'test',	'$2a$12$Yhn3SHVevs872tmBaNjP9.BT8Eqx.wcIIe9dm2830G.Z.SCPJXJSy');