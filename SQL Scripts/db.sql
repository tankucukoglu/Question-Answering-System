-- --------------------------------------------------------
-- Host:                         dijkstra.ug.bcc.bilkent.edu.tr
-- Server version:               5.5.16-log - Source distribution
-- Server OS:                    Linux
-- HeidiSQL Version:             9.2.0.4952
-- --------------------------------------------------------

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET NAMES utf8mb4 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;

-- Dumping database structure for tan_kucukoglu
CREATE DATABASE IF NOT EXISTS `tan_kucukoglu` /*!40100 DEFAULT CHARACTER SET latin5 */;
USE `tan_kucukoglu`;


-- Dumping structure for view tan_kucukoglu.approved_questions
-- Creating temporary table to overcome VIEW dependency errors
CREATE TABLE `approved_questions` (
	`entryID` INT(11) NOT NULL
) ENGINE=MyISAM;


-- Dumping structure for table tan_kucukoglu.approves
CREATE TABLE IF NOT EXISTS `approves` (
  `answerID` int(11) NOT NULL,
  `username` varchar(50) NOT NULL,
  PRIMARY KEY (`answerID`),
  KEY `username` (`username`),
  CONSTRAINT `approves_ibfk_1` FOREIGN KEY (`answerID`) REFERENCES `Entry` (`entryID`),
  CONSTRAINT `approves_ibfk_2` FOREIGN KEY (`username`) REFERENCES `User` (`username`)
) ENGINE=InnoDB DEFAULT CHARSET=latin5;

-- Dumping data for table tan_kucukoglu.approves: ~2 rows (approximately)
DELETE FROM `approves`;
/*!40000 ALTER TABLE `approves` DISABLE KEYS */;
INSERT INTO `approves` (`answerID`, `username`) VALUES
	(39, 'admin'),
	(74, 'admin');
/*!40000 ALTER TABLE `approves` ENABLE KEYS */;


-- Dumping structure for table tan_kucukoglu.Badge
CREATE TABLE IF NOT EXISTS `Badge` (
  `name` varchar(50) NOT NULL,
  `description` varchar(250) DEFAULT NULL,
  `badgeType` enum('Gold','Silver','Bronze') NOT NULL,
  PRIMARY KEY (`name`)
) ENGINE=InnoDB DEFAULT CHARSET=latin5;

-- Dumping data for table tan_kucukoglu.Badge: ~0 rows (approximately)
DELETE FROM `Badge`;
/*!40000 ALTER TABLE `Badge` DISABLE KEYS */;
/*!40000 ALTER TABLE `Badge` ENABLE KEYS */;


-- Dumping structure for procedure tan_kucukoglu.canCloseEntry
DELIMITER //
CREATE DEFINER=`tan.kucukoglu`@`%` PROCEDURE `canCloseEntry`( in p_entryID int, in p_user varchar(50), out result int )
begin
	select count(*) into result
	from ((select E.username as name from Entry E where E.username=p_user and entryID=p_entryID and E.username in(
	select U1.username as name from User U1 natural join user_permission where U1.username=p_user
	 and permission_type='close_own_entry' ))
	union
	(select U.username as name from User U natural join user_permission where U.username=p_user
	 and permission_type='close_all_entries')) as r;
end//
DELIMITER ;


-- Dumping structure for procedure tan_kucukoglu.canDeleteEntry
DELIMITER //
CREATE DEFINER=`tan.kucukoglu`@`%` PROCEDURE `canDeleteEntry`(IN `p_entryID` int, IN `p_user` varchar(50), OUT `result` int )
begin
	select count(*) into result
	from ((select E.username as name from Entry E where E.username=p_user and entryID=p_entryID and E.username in(
	select U1.username as name from User U1 natural join user_permission where U1.username=p_user
	 and permission_type='delete_own_entry' ))
	union
	(select U.username as name from User U natural join user_permission where U.username=p_user
	 and permission_type='delete_all_entries')) as r;
end//
DELIMITER ;


-- Dumping structure for procedure tan_kucukoglu.canEditEntry
DELIMITER //
CREATE DEFINER=`tan.kucukoglu`@`%` PROCEDURE `canEditEntry`(IN `p_entryID` int, IN `p_user` varchar(50), OUT `result` int )
begin
	select count(*) into result
	from ((select E.username as name from Entry E where E.username=p_user and entryID=p_entryID)
	union
	(select U.username as name from User U natural join user_permission where U.username=p_user
	 and permission_type='edit_all_entries')) as r;
end//
DELIMITER ;


-- Dumping structure for table tan_kucukoglu.Category
CREATE TABLE IF NOT EXISTS `Category` (
  `catID` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(50) NOT NULL,
  `description` varchar(250) DEFAULT NULL,
  PRIMARY KEY (`catID`)
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=latin5;

-- Dumping data for table tan_kucukoglu.Category: ~6 rows (approximately)
DELETE FROM `Category`;
/*!40000 ALTER TABLE `Category` DISABLE KEYS */;
INSERT INTO `Category` (`catID`, `name`, `description`) VALUES
	(1, 'Programming', 'Programming category'),
	(2, 'Java', 'Lovely Java'),
	(3, 'Life', 'About daily life'),
	(4, 'Sports', 'Spor yapmak iyidir ;)'),
	(5, 'C', 'Ba?a bela'),
	(6, 'Entertainment', 'Entertainment was here');
/*!40000 ALTER TABLE `Category` ENABLE KEYS */;


-- Dumping structure for procedure tan_kucukoglu.changeUsername
DELIMITER //
CREATE DEFINER=`tan.kucukoglu`@`%` PROCEDURE `changeUsername`(in p_old varchar(50), in p_new varchar(50))
begin
	SET FOREIGN_KEY_CHECKS = 0;
	
	update approves set username=p_new where username=p_old;
	update collects set username=p_new where username=p_old;
	update Event set username=p_new where username=p_old;
	update follows set username=p_new where username=p_old;
	update votes set username=p_new where username=p_old;
	update closed_by set username=p_new where username=p_old;
	update edits set username=p_new where username=p_old;
	update Entry set username=p_new where username=p_old;
	update User set username=p_new where username=p_old;
	
	SET FOREIGN_KEY_CHECKS = 1;
end//
DELIMITER ;


-- Dumping structure for procedure tan_kucukoglu.closeAccount
DELIMITER //
CREATE DEFINER=`tan.kucukoglu`@`%` PROCEDURE `closeAccount`(in user varchar(50))
begin
	delete from approves where username=user;
	delete from collects where username=user;
	delete from Event where username=user;
	delete from follows where username=user;
	delete from votes where username=user;
	update closed_by set username='*CLOSED_ACCOUNT*' where username=user;
	update edits set username='*CLOSED_ACCOUNT*' where username=user;
	update Entry set username='*CLOSED_ACCOUNT*' where username=user;
	
	delete from User where username=user;
end//
DELIMITER ;


-- Dumping structure for table tan_kucukoglu.closed_by
CREATE TABLE IF NOT EXISTS `closed_by` (
  `entryID` int(11) NOT NULL,
  `username` varchar(50) NOT NULL,
  `timestamp` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `description` varchar(250) DEFAULT NULL,
  PRIMARY KEY (`entryID`),
  KEY `username` (`username`),
  CONSTRAINT `closed_by_ibfk_1` FOREIGN KEY (`username`) REFERENCES `User` (`username`)
) ENGINE=InnoDB DEFAULT CHARSET=latin5;

-- Dumping data for table tan_kucukoglu.closed_by: ~3 rows (approximately)
DELETE FROM `closed_by`;
/*!40000 ALTER TABLE `closed_by` DISABLE KEYS */;
INSERT INTO `closed_by` (`entryID`, `username`, `timestamp`, `description`) VALUES
	(19, 'admin', '2015-05-19 21:05:53', ''),
	(23, 'admin', '2015-05-19 23:48:07', 'no reason to close but i did :/'),
	(70, 'admin', '2015-05-20 08:40:44', '');
/*!40000 ALTER TABLE `closed_by` ENABLE KEYS */;


-- Dumping structure for view tan_kucukoglu.closed_questions
-- Creating temporary table to overcome VIEW dependency errors
CREATE TABLE `closed_questions` (
	`entryID` INT(11) NOT NULL
) ENGINE=MyISAM;


-- Dumping structure for table tan_kucukoglu.collects
CREATE TABLE IF NOT EXISTS `collects` (
  `username` varchar(50) NOT NULL,
  `badgeName` varchar(50) NOT NULL,
  `count` int(11) NOT NULL,
  PRIMARY KEY (`username`,`badgeName`),
  KEY `badgeName` (`badgeName`),
  CONSTRAINT `collects_ibfk_1` FOREIGN KEY (`username`) REFERENCES `User` (`username`),
  CONSTRAINT `collects_ibfk_2` FOREIGN KEY (`badgeName`) REFERENCES `Badge` (`name`)
) ENGINE=InnoDB DEFAULT CHARSET=latin5;

-- Dumping data for table tan_kucukoglu.collects: ~0 rows (approximately)
DELETE FROM `collects`;
/*!40000 ALTER TABLE `collects` DISABLE KEYS */;
/*!40000 ALTER TABLE `collects` ENABLE KEYS */;


-- Dumping structure for procedure tan_kucukoglu.deleteEntry
DELIMITER //
CREATE DEFINER=`tan.kucukoglu`@`%` PROCEDURE `deleteEntry`(in id int)
begin
	declare finished int default false;
	declare p_row int;
	declare curr cursor for select childEntryID from has_parent where parentEntryID = id;
	declare continue handler for not found set finished = 1;
	
	delete from approves where answerID=id;
	delete from closed_by where entryID=id;
	delete from edits where entryID=id;
	delete from entry_tag where entryID=id;
	delete from votes where entryID=id;
	delete from has_parent where childEntryID=id;
	
	-- Update user types for users
	open curr;
	repeat
		FETCH curr INTO p_row;
		IF finished <> 1 THEN
		call deleteEntry( p_row );
		END IF;
		until finished = 1
	end repeat;
	close curr;
	
	delete from Entry where entryID=id;
end//
DELIMITER ;


-- Dumping structure for procedure tan_kucukoglu.deleteUserType
DELIMITER //
CREATE DEFINER=`tan.kucukoglu`@`%` PROCEDURE `deleteUserType`(IN `type` varchar(50))
begin
	declare finished int default false;
	declare p_row varchar(50);
	declare curr cursor for select username from User where userType = type or userType is null;
	declare continue handler for not found set finished = 1;

	-- First get rid of foreign connections
	update User set userType = NULL where userType=type;
	
	-- Delete the user type
	delete from UserType where userType = type;
	
	-- Update user types for users
	open curr;
	repeat
		FETCH curr INTO p_row;
		IF finished <> 1 THEN
		select( p_row );
		call updateUserType( p_row );
		END IF;
		until finished = 1
	end repeat;
	close curr;
end//
DELIMITER ;


-- Dumping structure for table tan_kucukoglu.edits
CREATE TABLE IF NOT EXISTS `edits` (
  `entryID` int(11) NOT NULL,
  `username` varchar(50) NOT NULL,
  `timestamp` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `description` varchar(250) DEFAULT NULL,
  PRIMARY KEY (`entryID`,`username`,`timestamp`),
  KEY `username` (`username`),
  CONSTRAINT `edits_ibfk_1` FOREIGN KEY (`entryID`) REFERENCES `Entry` (`entryID`),
  CONSTRAINT `edits_ibfk_2` FOREIGN KEY (`username`) REFERENCES `User` (`username`)
) ENGINE=InnoDB DEFAULT CHARSET=latin5;

-- Dumping data for table tan_kucukoglu.edits: ~10 rows (approximately)
DELETE FROM `edits`;
/*!40000 ALTER TABLE `edits` DISABLE KEYS */;
INSERT INTO `edits` (`entryID`, `username`, `timestamp`, `description`) VALUES
	(4, 'admin', '2015-05-20 08:14:49', ''),
	(23, 'admin', '2015-05-19 19:42:30', ''),
	(23, 'admin', '2015-05-19 19:43:07', 'i have no reason for that :('),
	(23, 'admin', '2015-05-19 23:47:44', 'i have no reason for that edit :('),
	(38, 'admin', '2015-05-20 02:10:42', 'admin edited it'),
	(39, 'admin', '2015-05-20 02:23:23', 'wqeqwew'),
	(39, 'admin', '2015-05-20 03:18:54', 'long answer'),
	(47, 'admin', '2015-05-20 03:39:51', 'second comment edit'),
	(54, 'admin', '2015-05-20 08:17:26', ''),
	(77, 'admin', '2015-05-20 08:33:14', '');
/*!40000 ALTER TABLE `edits` ENABLE KEYS */;


-- Dumping structure for table tan_kucukoglu.Entry
CREATE TABLE IF NOT EXISTS `Entry` (
  `entryID` int(11) NOT NULL AUTO_INCREMENT,
  `title` varchar(50) NOT NULL,
  `timestamp` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `text` longtext NOT NULL,
  `entryType` enum('Q','Q_C','A','A_C') NOT NULL,
  `noOfViews` int(11) NOT NULL,
  `upvotes` int(11) NOT NULL,
  `downvotes` int(11) NOT NULL,
  `username` varchar(50) NOT NULL,
  `catID` int(11) NOT NULL,
  PRIMARY KEY (`entryID`),
  KEY `username` (`username`),
  KEY `catID` (`catID`),
  CONSTRAINT `Entry_ibfk_1` FOREIGN KEY (`username`) REFERENCES `User` (`username`),
  CONSTRAINT `Entry_ibfk_2` FOREIGN KEY (`catID`) REFERENCES `Category` (`catID`)
) ENGINE=InnoDB AUTO_INCREMENT=80 DEFAULT CHARSET=latin5;

-- Dumping data for table tan_kucukoglu.Entry: ~42 rows (approximately)
DELETE FROM `Entry`;
/*!40000 ALTER TABLE `Entry` DISABLE KEYS */;
INSERT INTO `Entry` (`entryID`, `title`, `timestamp`, `text`, `entryType`, `noOfViews`, `upvotes`, `downvotes`, `username`, `catID`) VALUES
	(2, 'Title1', '2015-05-19 12:28:37', 'Desc1\r\nnewline', 'Q', 42, 3, 0, '*CLOSED_ACCOUNT*', 1),
	(3, 'Title2', '2015-05-19 12:18:30', 'i?erik\r\nzuahaha\r\n\\m\\m\\n\r\nhmm', 'Q', 23, 3, 0, '*CLOSED_ACCOUNT*', 1),
	(4, 'What is Java?', '2015-05-19 12:26:51', 'What is java? Anyone knows?', 'Q', 1377, 4, 0, '*CLOSED_ACCOUNT*', 2),
	(5, 'What are the use of database systems?', '2015-05-19 03:20:31', 'veritaban? soru a??klama', 'Q', 38, 3, 0, '*CLOSED_ACCOUNT*', 1),
	(19, 'yeni2', '2015-05-19 16:28:38', 'wqe', 'Q', 111, 114, 22, 'admin', 1),
	(20, 'yeni1', '2015-05-19 16:28:46', 'qwe', 'Q', 34, 3, 0, 'admin', 1),
	(21, 'qwe', '2015-05-19 16:36:43', 'qwe', 'Q', 10, 4, 0, 'admin', 1),
	(22, 'Java gives 404', '2015-05-19 18:03:05', 'what is happenin?!?!', 'Q', 225, 3, 1, 'admin', 2),
	(23, 'multi line test#1', '2015-05-19 19:34:07', 'content\r\n  asd\r\n 12   test\r\nnoooo\r\nolduk?a uzun bir yazi peh', 'Q', 218, 12, 4, 'admin', 2),
	(27, '', '2015-05-20 00:39:36', 'simple answer #1', 'A', 9, 1, 0, 'admin', 2),
	(38, '', '2015-05-20 01:50:26', 'my qwqw anser lol #2', 'A', 4, 0, 0, 'qw', 2),
	(39, '', '2015-05-20 02:08:26', 'qw answer #2\r\n\r\n', 'A', 2, 1, 0, 'qw', 2),
	(47, '', '2015-05-20 03:39:39', 'second comment edited', 'Q_C', 5, 0, 0, 'admin', 2),
	(48, '', '2015-05-20 03:40:25', 'answer comment #1', 'A_C', 0, 0, 0, 'admin', 2),
	(52, '', '2015-05-20 04:10:09', 'hmm', 'Q_C', 0, 0, 0, 'bahadir', 2),
	(53, '', '2015-05-20 08:04:48', 'rep test', 'Q_C', 0, 0, 0, 'admin', 2),
	(54, '', '2015-05-20 08:05:07', 'Kind of coffee, as far as I know...', 'A', 1, 1, 0, 'admin', 2),
	(55, '', '2015-05-20 08:08:56', 'indeed', 'A_C', 0, 0, 0, 'admin', 2),
	(56, '', '2015-05-20 08:09:02', 'rep test', 'A', 0, 0, 0, 'admin', 1),
	(57, '', '2015-05-20 08:09:05', 'indeed', 'A_C', 0, 0, 0, 'admin', 2),
	(58, '', '2015-05-20 08:09:09', 'indeed', 'Q_C', 0, 0, 0, 'admin', 2),
	(59, '', '2015-05-20 08:10:16', 'account fix', 'A', 0, 0, 0, 'admin', 1),
	(60, '', '2015-05-20 08:11:37', 'does it work?', 'A', 0, 0, 0, 'admin', 1),
	(61, '', '2015-05-20 08:13:21', 'answer', 'A', 0, 0, 0, 'admin', 1),
	(62, '', '2015-05-20 08:14:42', 'answer 2', 'A', 0, 0, 0, 'admin', 1),
	(63, '', '2015-05-20 08:17:10', 'this is a good question', 'Q_C', 0, 0, 0, 'admin', 1),
	(64, '', '2015-05-20 08:19:57', 'coffee', 'A_C', 0, 0, 0, 'admin', 2),
	(65, '', '2015-05-20 08:21:27', 'coffee', 'A_C', 0, 0, 0, 'admin', 2),
	(66, '', '2015-05-20 08:22:05', '123', 'A_C', 0, 0, 0, 'admin', 2),
	(67, '', '2015-05-20 08:22:51', 'comment', 'A_C', 0, 0, 0, 'admin', 2),
	(68, '', '2015-05-20 08:24:33', 'answer', 'A', 0, 0, 0, 'admin', 2),
	(69, '', '2015-05-20 08:28:06', '123', 'Q_C', 0, 0, 0, 'admin', 1),
	(70, 'Hello World', '2015-05-20 08:28:46', 'How can I print \'Hello World\'?', 'Q', 33, 1, 0, 'admin', 1),
	(71, '', '2015-05-20 08:28:52', 'answer test', 'A', 0, 0, 0, 'admin', 1),
	(72, '', '2015-05-20 08:29:35', '123', 'A_C', 0, 0, 0, 'admin', 1),
	(73, '', '2015-05-20 08:31:11', '321', 'A_C', 0, 0, 0, 'admin', 1),
	(74, '', '2015-05-20 08:32:12', 'cout << "Hello World" << endl;', 'A', 0, 1, 0, 'bahadir', 1),
	(75, '', '2015-05-20 08:32:20', 'another answer', 'A', 0, 0, 0, 'admin', 1),
	(76, '', '2015-05-20 08:32:33', 'another comment', 'A_C', 0, 0, 0, 'admin', 1),
	(77, '', '2015-05-20 08:32:47', 'thank you bahadir!', 'A_C', 1, 0, 0, 'admin', 1),
	(78, 'Hey', '2015-05-20 08:52:48', 'What is programming?', 'Q', 2, 0, 0, 'admin', 1),
	(79, 'dfsf', '2015-05-20 09:26:34', 'sdgg', 'Q', 2, 0, 0, 'bahadir', 1);
/*!40000 ALTER TABLE `Entry` ENABLE KEYS */;


-- Dumping structure for table tan_kucukoglu.entry_tag
CREATE TABLE IF NOT EXISTS `entry_tag` (
  `tagName` varchar(50) NOT NULL,
  `entryID` int(11) NOT NULL,
  PRIMARY KEY (`tagName`,`entryID`),
  KEY `entryID` (`entryID`),
  CONSTRAINT `entry_tag_ibfk_1` FOREIGN KEY (`tagName`) REFERENCES `Tag` (`name`),
  CONSTRAINT `entry_tag_ibfk_2` FOREIGN KEY (`entryID`) REFERENCES `Entry` (`entryID`)
) ENGINE=InnoDB DEFAULT CHARSET=latin5;

-- Dumping data for table tan_kucukoglu.entry_tag: ~16 rows (approximately)
DELETE FROM `entry_tag`;
/*!40000 ALTER TABLE `entry_tag` DISABLE KEYS */;
INSERT INTO `entry_tag` (`tagName`, `entryID`) VALUES
	('tag1', 2),
	('tag2', 2),
	('tag2', 3),
	('tag3', 3),
	('hava', 4),
	('g?zel', 5),
	('soru', 5),
	('java', 19),
	('java', 20),
	('qwe', 21),
	('question', 22),
	('hava', 23),
	('multi', 23),
	('C++', 70),
	('curiosity', 78),
	('java', 79);
/*!40000 ALTER TABLE `entry_tag` ENABLE KEYS */;


-- Dumping structure for table tan_kucukoglu.Event
CREATE TABLE IF NOT EXISTS `Event` (
  `eventID` int(11) NOT NULL AUTO_INCREMENT,
  `username` varchar(50) NOT NULL,
  `event_type` varchar(50) NOT NULL,
  `timestamp` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`eventID`),
  KEY `username` (`username`),
  KEY `event_type` (`event_type`),
  CONSTRAINT `Event_ibfk_1` FOREIGN KEY (`username`) REFERENCES `User` (`username`),
  CONSTRAINT `Event_ibfk_2` FOREIGN KEY (`event_type`) REFERENCES `EventType` (`event_type`)
) ENGINE=InnoDB AUTO_INCREMENT=35 DEFAULT CHARSET=latin5;

-- Dumping data for table tan_kucukoglu.Event: ~34 rows (approximately)
DELETE FROM `Event`;
/*!40000 ALTER TABLE `Event` DISABLE KEYS */;
INSERT INTO `Event` (`eventID`, `username`, `event_type`, `timestamp`) VALUES
	(1, 'admin', 'post_question', '2015-05-20 00:58:06'),
	(2, 'admin', 'post_question', '2015-05-20 01:19:38'),
	(3, 'admin', 'post_question', '2015-05-20 01:23:18'),
	(4, 'admin', 'post_question', '2015-05-20 01:26:09'),
	(5, 'admin', 'post_question', '2015-05-20 01:26:56'),
	(6, 'admin', 'post_question', '2015-05-20 01:27:56'),
	(7, 'admin', 'post_question', '2015-05-20 01:29:49'),
	(8, 'admin', 'post_question', '2015-05-20 01:30:39'),
	(9, 'admin', 'post_question', '2015-05-20 01:34:07'),
	(10, 'admin', 'post_question', '2015-05-20 01:40:36'),
	(11, 'admin', 'post_question', '2015-05-20 02:21:05'),
	(12, 'admin', 'post_question', '2015-05-20 02:25:16'),
	(13, 'admin', 'post_question', '2015-05-20 02:25:32'),
	(14, 'admin', 'post_question', '2015-05-20 02:32:54'),
	(15, 'admin', 'post_question', '2015-05-20 02:33:39'),
	(16, '*CLOSED_ACCOUNT*', 'post_answer', '2015-05-20 08:05:07'),
	(17, '*CLOSED_ACCOUNT*', 'post_answer', '2015-05-20 08:09:02'),
	(18, 'admin', 'post_answer', '2015-05-20 08:10:16'),
	(19, 'admin', 'post_answer', '2015-05-20 08:11:37'),
	(20, 'admin', 'post_answer', '2015-05-20 08:13:21'),
	(21, 'admin', 'post_answer', '2015-05-20 08:14:42'),
	(22, 'admin', 'post_comment', '2015-05-20 08:17:11'),
	(23, 'admin', 'post_comment', '2015-05-20 08:19:57'),
	(24, 'admin', 'post_answer', '2015-05-20 08:24:33'),
	(25, 'admin', 'post_question', '2015-05-20 08:28:46'),
	(26, 'admin', 'post_answer', '2015-05-20 08:28:52'),
	(27, 'admin', 'post_answer', '2015-05-20 08:29:35'),
	(28, 'admin', 'post_comment', '2015-05-20 08:31:11'),
	(29, 'bahadir', 'post_answer', '2015-05-20 08:32:12'),
	(30, 'admin', 'post_answer', '2015-05-20 08:32:20'),
	(31, 'admin', 'post_comment', '2015-05-20 08:32:33'),
	(32, 'admin', 'post_comment', '2015-05-20 08:32:47'),
	(33, 'admin', 'post_question', '2015-05-20 08:52:48'),
	(34, 'bahadir', 'post_question', '2015-05-20 09:26:34');
/*!40000 ALTER TABLE `Event` ENABLE KEYS */;


-- Dumping structure for table tan_kucukoglu.EventType
CREATE TABLE IF NOT EXISTS `EventType` (
  `event_type` varchar(50) NOT NULL,
  `name` varchar(50) NOT NULL,
  `description` varchar(250) NOT NULL,
  `points` int(11) NOT NULL,
  PRIMARY KEY (`event_type`)
) ENGINE=InnoDB DEFAULT CHARSET=latin5;

-- Dumping data for table tan_kucukoglu.EventType: ~4 rows (approximately)
DELETE FROM `EventType`;
/*!40000 ALTER TABLE `EventType` DISABLE KEYS */;
INSERT INTO `EventType` (`event_type`, `name`, `description`, `points`) VALUES
	('accept_answer', 'Accept Answer', 'Question asker accepts the answer', 5),
	('post_answer', 'Post Answer', 'Allows the user to post an answer', 25),
	('post_comment', 'Post Comment', 'Allows the user to post a comment', 10),
	('post_question', 'Post Question', 'This event allows the user to post a question and award points', 50);
/*!40000 ALTER TABLE `EventType` ENABLE KEYS */;


-- Dumping structure for table tan_kucukoglu.follows
CREATE TABLE IF NOT EXISTS `follows` (
  `tagName` varchar(50) NOT NULL,
  `username` varchar(50) NOT NULL,
  PRIMARY KEY (`tagName`,`username`),
  KEY `username` (`username`),
  CONSTRAINT `follows_ibfk_1` FOREIGN KEY (`tagName`) REFERENCES `Tag` (`name`),
  CONSTRAINT `follows_ibfk_2` FOREIGN KEY (`username`) REFERENCES `User` (`username`)
) ENGINE=InnoDB DEFAULT CHARSET=latin5;

-- Dumping data for table tan_kucukoglu.follows: ~1 rows (approximately)
DELETE FROM `follows`;
/*!40000 ALTER TABLE `follows` DISABLE KEYS */;
INSERT INTO `follows` (`tagName`, `username`) VALUES
	('java', 'qw');
/*!40000 ALTER TABLE `follows` ENABLE KEYS */;


-- Dumping structure for table tan_kucukoglu.has_parent
CREATE TABLE IF NOT EXISTS `has_parent` (
  `childEntryID` int(11) NOT NULL,
  `parentEntryID` int(11) NOT NULL,
  PRIMARY KEY (`childEntryID`),
  KEY `parentEntryID` (`parentEntryID`),
  CONSTRAINT `has_parent_ibfk_1` FOREIGN KEY (`childEntryID`) REFERENCES `Entry` (`entryID`),
  CONSTRAINT `has_parent_ibfk_2` FOREIGN KEY (`parentEntryID`) REFERENCES `Entry` (`entryID`)
) ENGINE=InnoDB DEFAULT CHARSET=latin5;

-- Dumping data for table tan_kucukoglu.has_parent: ~23 rows (approximately)
DELETE FROM `has_parent`;
/*!40000 ALTER TABLE `has_parent` DISABLE KEYS */;
INSERT INTO `has_parent` (`childEntryID`, `parentEntryID`) VALUES
	(61, 2),
	(62, 2),
	(56, 3),
	(59, 3),
	(60, 3),
	(52, 4),
	(53, 4),
	(54, 4),
	(66, 4),
	(68, 4),
	(69, 5),
	(71, 5),
	(75, 5),
	(27, 22),
	(38, 22),
	(39, 22),
	(47, 22),
	(48, 27),
	(74, 70),
	(72, 71),
	(73, 71),
	(77, 74),
	(76, 75);
/*!40000 ALTER TABLE `has_parent` ENABLE KEYS */;


-- Dumping structure for view tan_kucukoglu.hot_questions
-- Creating temporary table to overcome VIEW dependency errors
CREATE TABLE `hot_questions` (
	`entryID` INT(11) NOT NULL,
	`title` VARCHAR(50) NOT NULL COLLATE 'latin5_turkish_ci',
	`timestamp` TIMESTAMP NOT NULL,
	`text` LONGTEXT NOT NULL COLLATE 'latin5_turkish_ci',
	`entryType` ENUM('Q','Q_C','A','A_C') NOT NULL COLLATE 'latin5_turkish_ci',
	`noOfViews` INT(11) NOT NULL,
	`upvotes` INT(11) NOT NULL,
	`downvotes` INT(11) NOT NULL,
	`username` VARCHAR(50) NOT NULL COLLATE 'latin5_turkish_ci',
	`catID` INT(11) NOT NULL
) ENGINE=MyISAM;


-- Dumping structure for view tan_kucukoglu.new_questions
-- Creating temporary table to overcome VIEW dependency errors
CREATE TABLE `new_questions` (
	`entryID` INT(11) NOT NULL,
	`title` VARCHAR(50) NOT NULL COLLATE 'latin5_turkish_ci',
	`timestamp` TIMESTAMP NOT NULL,
	`text` LONGTEXT NOT NULL COLLATE 'latin5_turkish_ci',
	`entryType` ENUM('Q','Q_C','A','A_C') NOT NULL COLLATE 'latin5_turkish_ci',
	`noOfViews` INT(11) NOT NULL,
	`upvotes` INT(11) NOT NULL,
	`downvotes` INT(11) NOT NULL,
	`username` VARCHAR(50) NOT NULL COLLATE 'latin5_turkish_ci',
	`catID` INT(11) NOT NULL
) ENGINE=MyISAM;


-- Dumping structure for table tan_kucukoglu.Permissions
CREATE TABLE IF NOT EXISTS `Permissions` (
  `permission_type` varchar(50) NOT NULL,
  `name` varchar(50) NOT NULL,
  `description` varchar(250) NOT NULL,
  PRIMARY KEY (`permission_type`)
) ENGINE=InnoDB DEFAULT CHARSET=latin5;

-- Dumping data for table tan_kucukoglu.Permissions: ~14 rows (approximately)
DELETE FROM `Permissions`;
/*!40000 ALTER TABLE `Permissions` DISABLE KEYS */;
INSERT INTO `Permissions` (`permission_type`, `name`, `description`) VALUES
	('close_all_entries', 'Close Others\' Entries', 'Allows closing entries of other users'),
	('close_own_entry', 'Close Own Entry', 'Allows closing your own entries'),
	('create_badge', 'Create Badge', 'Allows creating a badge'),
	('create_category', 'Create Category', 'Allows creating a category'),
	('create_tag', 'Create Tag', 'Allows creating a tag'),
	('delete_all_entries', 'Delete Others\' Entries', 'Allows deleting entries of other users'),
	('delete_badge', 'Delete Badge', 'Allows deleting a badge'),
	('delete_category', 'Delete Category', 'Allows deleting a category'),
	('delete_own_entry', 'Delete Own Entry', 'Allows deleting your own entries'),
	('edit_all_entries', 'Edit Others\' Entries', 'Allows editing entries of other users'),
	('edit_badge', 'Edit Badge', 'Allows editing a badge'),
	('edit_category', 'Edit Category', 'Allows editing a category'),
	('edit_tag', 'Edit Tag', 'Allows editing a tag'),
	('upvote_downvote', 'Vote Entries', 'Allows upvoting or downvoting entries');
/*!40000 ALTER TABLE `Permissions` ENABLE KEYS */;


-- Dumping structure for table tan_kucukoglu.sub_category
CREATE TABLE IF NOT EXISTS `sub_category` (
  `childCatID` int(11) NOT NULL,
  `parentCatID` int(11) NOT NULL,
  PRIMARY KEY (`childCatID`),
  KEY `parentCatID` (`parentCatID`),
  CONSTRAINT `sub_category_ibfk_1` FOREIGN KEY (`childCatID`) REFERENCES `Category` (`catID`),
  CONSTRAINT `sub_category_ibfk_2` FOREIGN KEY (`parentCatID`) REFERENCES `Category` (`catID`)
) ENGINE=InnoDB DEFAULT CHARSET=latin5;

-- Dumping data for table tan_kucukoglu.sub_category: ~3 rows (approximately)
DELETE FROM `sub_category`;
/*!40000 ALTER TABLE `sub_category` DISABLE KEYS */;
INSERT INTO `sub_category` (`childCatID`, `parentCatID`) VALUES
	(2, 1),
	(5, 1),
	(4, 3);
/*!40000 ALTER TABLE `sub_category` ENABLE KEYS */;


-- Dumping structure for table tan_kucukoglu.Tag
CREATE TABLE IF NOT EXISTS `Tag` (
  `name` varchar(50) NOT NULL,
  `description` varchar(250) DEFAULT NULL,
  PRIMARY KEY (`name`)
) ENGINE=InnoDB DEFAULT CHARSET=latin5;

-- Dumping data for table tan_kucukoglu.Tag: ~16 rows (approximately)
DELETE FROM `Tag`;
/*!40000 ALTER TABLE `Tag` DISABLE KEYS */;
INSERT INTO `Tag` (`name`, `description`) VALUES
	('C++', NULL),
	('curiosity', NULL),
	('g?zel', NULL),
	('hava', NULL),
	('java', NULL),
	('multi', NULL),
	('question', NULL),
	('qwe', NULL),
	('qwer', NULL),
	('rep', NULL),
	('soru', NULL),
	('tag1', NULL),
	('tag2', NULL),
	('tag3', NULL),
	('ttteaw', NULL),
	('ww', NULL);
/*!40000 ALTER TABLE `Tag` ENABLE KEYS */;


-- Dumping structure for view tan_kucukoglu.unanswered_questions
-- Creating temporary table to overcome VIEW dependency errors
CREATE TABLE `unanswered_questions` (
	`entryID` INT(11) NOT NULL,
	`title` VARCHAR(50) NOT NULL COLLATE 'latin5_turkish_ci',
	`timestamp` TIMESTAMP NOT NULL,
	`text` LONGTEXT NOT NULL COLLATE 'latin5_turkish_ci',
	`entryType` ENUM('Q','Q_C','A','A_C') NOT NULL COLLATE 'latin5_turkish_ci',
	`noOfViews` INT(11) NOT NULL,
	`upvotes` INT(11) NOT NULL,
	`downvotes` INT(11) NOT NULL,
	`username` VARCHAR(50) NOT NULL COLLATE 'latin5_turkish_ci',
	`catID` INT(11) NOT NULL
) ENGINE=MyISAM;


-- Dumping structure for procedure tan_kucukoglu.updateAllUserTypes
DELIMITER //
CREATE DEFINER=`tan.kucukoglu`@`%` PROCEDURE `updateAllUserTypes`()
begin
	declare finished int default false;
	declare p_row varchar(50);
	declare curr cursor for select username from User where userType <> 'Admin' or userType is NULL;
	declare continue handler for not found set finished = 1;

	-- First get rid of foreign connections
	update User set userType = NULL where userType <> 'Admin';
	
	-- Update user types for users
	open curr;
	repeat
		FETCH curr INTO p_row;
		IF finished <> 1 THEN
		call updateUserType( p_row );
		END IF;
		until finished = 1
	end repeat;
	close curr;
end//
DELIMITER ;


-- Dumping structure for procedure tan_kucukoglu.updateUserType
DELIMITER //
CREATE DEFINER=`tan.kucukoglu`@`%` PROCEDURE `updateUserType`(in user varchar(50))
begin
	-- Find rep points of user
	set @rep := 0;
	select rep into @rep from User where username=user;
	
	-- Find user type that best fits that user
	set @type := 'Newcomer';
	select userType into @type from UserType where repThreshold <= @rep order by repThreshold desc limit 1 ;
	
	update User set userType=@type where username=user;
end//
DELIMITER ;


-- Dumping structure for table tan_kucukoglu.User
CREATE TABLE IF NOT EXISTS `User` (
  `username` varchar(50) NOT NULL,
  `password` varchar(50) NOT NULL,
  `rep` int(11) NOT NULL,
  `email` varchar(50) NOT NULL,
  `userType` varchar(50) DEFAULT NULL,
  PRIMARY KEY (`username`),
  KEY `userType` (`userType`),
  CONSTRAINT `User_ibfk_1` FOREIGN KEY (`userType`) REFERENCES `UserType` (`userType`)
) ENGINE=InnoDB DEFAULT CHARSET=latin5;

-- Dumping data for table tan_kucukoglu.User: ~5 rows (approximately)
DELETE FROM `User`;
/*!40000 ALTER TABLE `User` DISABLE KEYS */;
INSERT INTO `User` (`username`, `password`, `rep`, `email`, `userType`) VALUES
	('*CLOSED_ACCOUNT*', 'none', 100000, 'none', 'Moderator'),
	('admin', 'q1w2e3', 2000000230, 'admin@admin.com', 'Admin'),
	('bahadir', '236405', 75, 'bahadir94@gmail.com', 'Newcomer'),
	('qw', 'qw', 0, 'qw', 'Newcomer'),
	('test', 'test', 20, '23', 'Newcomer');
/*!40000 ALTER TABLE `User` ENABLE KEYS */;


-- Dumping structure for table tan_kucukoglu.UserType
CREATE TABLE IF NOT EXISTS `UserType` (
  `userType` varchar(50) NOT NULL,
  `repThreshold` int(11) NOT NULL,
  PRIMARY KEY (`userType`)
) ENGINE=InnoDB DEFAULT CHARSET=latin5;

-- Dumping data for table tan_kucukoglu.UserType: ~4 rows (approximately)
DELETE FROM `UserType`;
/*!40000 ALTER TABLE `UserType` DISABLE KEYS */;
INSERT INTO `UserType` (`userType`, `repThreshold`) VALUES
	('Admin', 1000000000),
	('Moderator', 1000000),
	('Newcomer', 0),
	('Super User', 100000);
/*!40000 ALTER TABLE `UserType` ENABLE KEYS */;


-- Dumping structure for table tan_kucukoglu.user_permission
CREATE TABLE IF NOT EXISTS `user_permission` (
  `userType` varchar(50) NOT NULL,
  `permission_type` varchar(50) NOT NULL,
  PRIMARY KEY (`userType`,`permission_type`),
  KEY `permission_type` (`permission_type`),
  CONSTRAINT `user_permission_ibfk_1` FOREIGN KEY (`userType`) REFERENCES `UserType` (`userType`),
  CONSTRAINT `user_permission_ibfk_2` FOREIGN KEY (`permission_type`) REFERENCES `Permissions` (`permission_type`)
) ENGINE=InnoDB DEFAULT CHARSET=latin5;

-- Dumping data for table tan_kucukoglu.user_permission: ~3 rows (approximately)
DELETE FROM `user_permission`;
/*!40000 ALTER TABLE `user_permission` DISABLE KEYS */;
INSERT INTO `user_permission` (`userType`, `permission_type`) VALUES
	('Admin', 'close_all_entries'),
	('Admin', 'delete_all_entries'),
	('Admin', 'edit_all_entries');
/*!40000 ALTER TABLE `user_permission` ENABLE KEYS */;


-- Dumping structure for procedure tan_kucukoglu.voteEntry
DELIMITER //
CREATE DEFINER=`tan.kucukoglu`@`%` PROCEDURE `voteEntry`(IN `id` int, IN `user` varchar(50), IN `value` int)
begin
	set @prev := 0;
	select V.value into @prev from votes V where V.entryID=id and V.username=user;
	delete from votes where entryID=id and username=user;
	
	IF @prev > 0 THEN
	update Entry set upvotes=upvotes-1 where entryID=id;
	ELSEIF @prev < 0 THEN
	update Entry set downvotes=downvotes-1 where entryID=id;
	END IF;
	
	IF value > 0 THEN
	update Entry set upvotes=upvotes+1 where entryID=id;
	insert into votes values(id,user,value);
	ELSEIF value < 0 THEN
	update Entry set downvotes=downvotes+1 where entryID=id;
	insert into votes values(id,user,value);
	END IF;
end//
DELIMITER ;


-- Dumping structure for table tan_kucukoglu.votes
CREATE TABLE IF NOT EXISTS `votes` (
  `entryID` int(11) NOT NULL,
  `username` varchar(50) NOT NULL,
  `value` tinyint(4) NOT NULL,
  PRIMARY KEY (`entryID`,`username`),
  KEY `username` (`username`),
  CONSTRAINT `votes_ibfk_1` FOREIGN KEY (`entryID`) REFERENCES `Entry` (`entryID`),
  CONSTRAINT `votes_ibfk_2` FOREIGN KEY (`username`) REFERENCES `User` (`username`)
) ENGINE=InnoDB DEFAULT CHARSET=latin5;

-- Dumping data for table tan_kucukoglu.votes: ~10 rows (approximately)
DELETE FROM `votes`;
/*!40000 ALTER TABLE `votes` DISABLE KEYS */;
INSERT INTO `votes` (`entryID`, `username`, `value`) VALUES
	(4, '*CLOSED_ACCOUNT*', 1),
	(19, 'admin', 1),
	(21, 'admin', 1),
	(22, 'admin', -1),
	(23, 'admin', 1),
	(27, 'admin', 1),
	(39, 'admin', 1),
	(54, '*CLOSED_ACCOUNT*', 1),
	(70, 'admin', 1),
	(74, 'admin', 1);
/*!40000 ALTER TABLE `votes` ENABLE KEYS */;


-- Dumping structure for view tan_kucukoglu.approved_questions
-- Removing temporary table and create final VIEW structure
DROP TABLE IF EXISTS `approved_questions`;
CREATE ALGORITHM=UNDEFINED DEFINER=`tan.kucukoglu`@`%` SQL SECURITY DEFINER VIEW `approved_questions` AS select `Entry`.`entryID` AS `entryID` from ((`Entry` join `approves`) join `has_parent`) where ((`Entry`.`entryID` = `has_parent`.`parentEntryID`) and (`has_parent`.`childEntryID` = `approves`.`answerID`));


-- Dumping structure for view tan_kucukoglu.closed_questions
-- Removing temporary table and create final VIEW structure
DROP TABLE IF EXISTS `closed_questions`;
CREATE ALGORITHM=UNDEFINED DEFINER=`tan.kucukoglu`@`%` SQL SECURITY DEFINER VIEW `closed_questions` AS select `E`.`entryID` AS `entryID` from (`Entry` `E` join `closed_by` `C`) where (`E`.`entryID` = `C`.`entryID`);


-- Dumping structure for view tan_kucukoglu.hot_questions
-- Removing temporary table and create final VIEW structure
DROP TABLE IF EXISTS `hot_questions`;
CREATE ALGORITHM=UNDEFINED DEFINER=`tan.kucukoglu`@`%` SQL SECURITY DEFINER VIEW `hot_questions` AS select `E`.`entryID` AS `entryID`,`E`.`title` AS `title`,`E`.`timestamp` AS `timestamp`,`E`.`text` AS `text`,`E`.`entryType` AS `entryType`,`E`.`noOfViews` AS `noOfViews`,`E`.`upvotes` AS `upvotes`,`E`.`downvotes` AS `downvotes`,`E`.`username` AS `username`,`E`.`catID` AS `catID` from `Entry` `E` where (`E`.`noOfViews` > 1000);


-- Dumping structure for view tan_kucukoglu.new_questions
-- Removing temporary table and create final VIEW structure
DROP TABLE IF EXISTS `new_questions`;
CREATE ALGORITHM=UNDEFINED DEFINER=`tan.kucukoglu`@`%` SQL SECURITY DEFINER VIEW `new_questions` AS select `E`.`entryID` AS `entryID`,`E`.`title` AS `title`,`E`.`timestamp` AS `timestamp`,`E`.`text` AS `text`,`E`.`entryType` AS `entryType`,`E`.`noOfViews` AS `noOfViews`,`E`.`upvotes` AS `upvotes`,`E`.`downvotes` AS `downvotes`,`E`.`username` AS `username`,`E`.`catID` AS `catID` from `Entry` `E` where (timestampdiff(HOUR,`E`.`timestamp`,now()) < 1) order by `E`.`timestamp` desc;


-- Dumping structure for view tan_kucukoglu.unanswered_questions
-- Removing temporary table and create final VIEW structure
DROP TABLE IF EXISTS `unanswered_questions`;
CREATE ALGORITHM=UNDEFINED DEFINER=`tan.kucukoglu`@`%` SQL SECURITY DEFINER VIEW `unanswered_questions` AS select `E`.`entryID` AS `entryID`,`E`.`title` AS `title`,`E`.`timestamp` AS `timestamp`,`E`.`text` AS `text`,`E`.`entryType` AS `entryType`,`E`.`noOfViews` AS `noOfViews`,`E`.`upvotes` AS `upvotes`,`E`.`downvotes` AS `downvotes`,`E`.`username` AS `username`,`E`.`catID` AS `catID` from `Entry` `E` where (not(exists(select `H`.`parentEntryID` from `has_parent` `H` where (`E`.`entryID` = `H`.`parentEntryID`))));
/*!40101 SET SQL_MODE=IFNULL(@OLD_SQL_MODE, '') */;
/*!40014 SET FOREIGN_KEY_CHECKS=IF(@OLD_FOREIGN_KEY_CHECKS IS NULL, 1, @OLD_FOREIGN_KEY_CHECKS) */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
