SET NAMES utf8;
SET time_zone = '+00:00';
SET foreign_key_checks = 0;
SET sql_mode = 'NO_AUTO_VALUE_ON_ZERO';

SET NAMES utf8mb4;

DROP TABLE IF EXISTS `login`;
CREATE TABLE `login` (
  `userID` int NOT NULL AUTO_INCREMENT,
  `username` varchar(30) NOT NULL,
  `password` varchar(255) NOT NULL,
  PRIMARY KEY (`loginID`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

INSERT INTO `login` (`loginID`, `username`, `password`) VALUES
(1,	'harry',	'$2a$12$PKSODAJs.x2ANU0ztKTbt.OOlERKgTuJoXpFSODjF0zHZAjmMiAs6');

DROP TABLE IF EXISTS `tbl_tasks`;
CREATE TABLE `tbl_tasks` (
  `id` bigint NOT NULL AUTO_INCREMENT,
  `title` varchar(255) NOT NULL,
  `description` mediumtext,
  `date` date DEFAULT NULL,
  `start_time` time DEFAULT NULL,
  `end_time` time DEFAULT NULL,
  `deadline` datetime DEFAULT NULL,
  `complete` enum('Y','N') CHARACTER SET utf8mb3 COLLATE utf8_general_ci NOT NULL DEFAULT 'N',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

INSERT INTO `tbl_tasks` (`id`, `title`, `description`, `date`, `start_time`, `end_time`, `deadline`, `complete`) VALUES
(1,	'The Famous 5',	'This is a new description',	'2020-03-26',	'09:00:00',	'12:00:00',	'2020-04-30 00:00:00',	'N'),
(2,	'Waldo and His Adventures',	NULL,	'2022-06-18',	NULL,	NULL,	NULL,	'N'),
(3,	'Stig of the Dump',	NULL,	'2022-06-18',	NULL,	NULL,	NULL,	'N'),
(4,	'The Lion King',	NULL,	'2022-06-18',	'23:00:00',	'00:00:00',	'2022-06-19 00:00:00',	'Y');

DELIMITER ;;

CREATE TRIGGER `api` BEFORE INSERT ON `tbl_tasks` FOR EACH ROW
BEGIN
    SET NEW.date = NOW();
END;;

DELIMITER ;