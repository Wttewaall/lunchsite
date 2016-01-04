-- --------------------------------------------------------
-- Host:                         127.0.0.1
-- Server version:               5.6.25 - MySQL Community Server (GPL)
-- Server OS:                    Win32
-- HeidiSQL Version:             9.3.0.4984
-- --------------------------------------------------------

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET NAMES utf8mb4 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;

-- Dumping structure for table lunch.accounts
CREATE TABLE IF NOT EXISTS `accounts` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `first_name` varchar(255) NOT NULL,
  `last_name` varchar(255) NOT NULL,
  `is_user` tinyint(1) NOT NULL DEFAULT '1',
  `account_name` varchar(255) DEFAULT NULL,
  `iban` varchar(20) DEFAULT NULL COMMENT 'NL[0-9]{2}[A-Z]{4}[0-9]{10}',
  `image` text,
  `created_date` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `deleted_date` datetime DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- Data exporting was unselected.


-- Dumping structure for table lunch.account_state
CREATE TABLE IF NOT EXISTS `account_state` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `fk_account_id` int(11) DEFAULT NULL,
  `fk_type_id` int(11) DEFAULT NULL,
  `participation` tinyint(3) NOT NULL DEFAULT '100' COMMENT 'percentage',
  `modified_date` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `FK_account_state_account` (`fk_account_id`),
  KEY `FK_account_state_account_type` (`fk_type_id`),
  CONSTRAINT `FK_account_state_account` FOREIGN KEY (`fk_account_id`) REFERENCES `accounts` (`id`) ON UPDATE CASCADE,
  CONSTRAINT `FK_account_state_account_type` FOREIGN KEY (`fk_type_id`) REFERENCES `account_type` (`id`) ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- Data exporting was unselected.


-- Dumping structure for table lunch.account_type
CREATE TABLE IF NOT EXISTS `account_type` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `code` varchar(50) NOT NULL,
  `description` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `code` (`code`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- Dumping data for table lunch.account_type: ~5 rows (approximately)
DELETE FROM `account_type`;
/*!40000 ALTER TABLE `account_type` DISABLE KEYS */;
INSERT INTO `account_type` (`id`, `code`, `description`) VALUES
	(1, 'POT', 'Gezamelijke pot'),
	(2, 'EMPLOYEE', 'Werknemer'),
	(3, 'INTERN', 'Stagiair of vrijwilliger'),
	(4, 'SUPERMARKET', 'Supermarkt'),
	(5, 'BANK', 'Bank');
/*!40000 ALTER TABLE `account_type` ENABLE KEYS */;


-- Dumping structure for table lunch.monthly_rounds
CREATE TABLE IF NOT EXISTS `monthly_rounds` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `date` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `amount` int(11) NOT NULL DEFAULT '2500',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- Data exporting was unselected.


-- Dumping structure for table lunch.transactions
CREATE TABLE IF NOT EXISTS `transactions` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `fk_account_id` int(11) NOT NULL,
  `fk_counterparty_account_id` int(11) NOT NULL,
  `fk_transaction_type` int(11) NOT NULL,
  `fk_transaction_status` int(11) NOT NULL,
  `amount` int(11) NOT NULL COMMENT 'in 2 decimalen',
  `description` varchar(255) DEFAULT NULL,
  `date` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `hidden` tinyint(1) NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  KEY `FK_transactions_transaction_type` (`fk_transaction_type`),
  KEY `FK_transactions_transaction_status` (`fk_transaction_status`),
  KEY `FK_transactions_account` (`fk_account_id`),
  KEY `FK_transactions_account_2` (`fk_counterparty_account_id`),
  CONSTRAINT `FK_transactions_account` FOREIGN KEY (`fk_account_id`) REFERENCES `accounts` (`id`) ON DELETE NO ACTION ON UPDATE CASCADE,
  CONSTRAINT `FK_transactions_account_2` FOREIGN KEY (`fk_counterparty_account_id`) REFERENCES `accounts` (`id`) ON DELETE NO ACTION ON UPDATE CASCADE,
  CONSTRAINT `FK_transactions_transaction_status` FOREIGN KEY (`fk_transaction_status`) REFERENCES `transaction_status` (`id`),
  CONSTRAINT `FK_transactions_transaction_type` FOREIGN KEY (`fk_transaction_type`) REFERENCES `transaction_type` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- Data exporting was unselected.


-- Dumping structure for table lunch.transaction_status
CREATE TABLE IF NOT EXISTS `transaction_status` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `code` varchar(50) NOT NULL,
  `description` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `code` (`code`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- Dumping data for table lunch.transaction_status: ~2 rows (approximately)
DELETE FROM `transaction_status`;
/*!40000 ALTER TABLE `transaction_status` DISABLE KEYS */;
INSERT INTO `transaction_status` (`id`, `code`, `description`) VALUES
	(1, 'BIJ', 'Op aangegeven rekening bijgeboekt'),
	(2, 'AF', 'Van aangegeven rekening afgeboekt');
/*!40000 ALTER TABLE `transaction_status` ENABLE KEYS */;


-- Dumping structure for table lunch.transaction_type
CREATE TABLE IF NOT EXISTS `transaction_type` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `code` varchar(50) NOT NULL,
  `description` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `code` (`code`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- Dumping data for table lunch.transaction_type: ~3 rows (approximately)
DELETE FROM `transaction_type`;
/*!40000 ALTER TABLE `transaction_type` DISABLE KEYS */;
INSERT INTO `transaction_type` (`id`, `code`, `description`) VALUES
	(1, 'BANK', 'Via bank overgemaakt'),
	(2, 'CASH', 'Cash in de pot gedaan'),
	(3, 'NATURA', 'In natura.. Ooh la la');
/*!40000 ALTER TABLE `transaction_type` ENABLE KEYS */;


-- Dumping structure for view lunch.view_all_transactions
-- Creating temporary table to overcome VIEW dependency errors
CREATE TABLE `view_all_transactions` (
	`date` DATETIME NOT NULL,
	`acc_id` INT(11) NULL,
	`acc_name` VARCHAR(511) NULL COLLATE 'latin1_swedish_ci',
	`acc_type` VARCHAR(50) NULL COLLATE 'latin1_swedish_ci',
	`cacc_id` INT(11) NULL,
	`cacc_name` VARCHAR(511) NULL COLLATE 'latin1_swedish_ci',
	`cacc_type` VARCHAR(50) NULL COLLATE 'latin1_swedish_ci',
	`amount` INT(11) NOT NULL COMMENT 'in 2 decimalen',
	`status` VARCHAR(50) NULL COLLATE 'latin1_swedish_ci',
	`type` VARCHAR(50) NULL COLLATE 'latin1_swedish_ci',
	`description` VARCHAR(255) NOT NULL COLLATE 'latin1_swedish_ci'
) ENGINE=MyISAM;


-- Dumping structure for view lunch.view_total_for_others
-- Creating temporary table to overcome VIEW dependency errors
CREATE TABLE `view_total_for_others` (
	`id` INT(11) NOT NULL,
	`name` VARCHAR(511) NOT NULL COLLATE 'latin1_swedish_ci',
	`totaal` DECIMAL(36,2) NULL
) ENGINE=MyISAM;


-- Dumping structure for view lunch.view_total_for_users
-- Creating temporary table to overcome VIEW dependency errors
CREATE TABLE `view_total_for_users` (
	`id` INT(11) NOT NULL,
	`name` VARCHAR(511) NOT NULL COLLATE 'latin1_swedish_ci',
	`type` VARCHAR(50) NULL COLLATE 'latin1_swedish_ci',
	`amount` DECIMAL(39,2) NULL
) ENGINE=MyISAM;


-- Dumping structure for view lunch.view_all_transactions
-- Removing temporary table and create final VIEW structure
DROP TABLE IF EXISTS `view_all_transactions`;
CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` VIEW `view_all_transactions` AS SELECT
	trans.date,
	
	acc.id AS acc_id,
	CONCAT(acc.first_name, ' ', acc.last_name) AS acc_name,
	(SELECT acc_t.code
		FROM account_state state
		LEFT JOIN account_type acc_t ON acc_t.id = state.fk_type_id
		WHERE state.fk_account_id = acc.id
		ORDER BY state.modified_date DESC
		LIMIT 1
	) AS acc_type,
	
	cacc.id AS cacc_id,
	CONCAT(cacc.first_name, ' ', cacc.last_name) AS cacc_name,
	(SELECT cacc_t.code
		FROM account_state state
		LEFT JOIN account_type cacc_t ON cacc_t.id = state.fk_type_id
		WHERE state.fk_account_id = cacc.id
		ORDER BY state.modified_date DESC
		LIMIT 1
	) AS cacc_type,
	
	trans.amount,
	tstatus.code AS `status`,
	ttype.code AS `type`,
	IFNULL(trans.description, '') AS description
	
FROM transactions trans
LEFT JOIN accounts acc ON acc.id = trans.fk_account_id
LEFT JOIN accounts cacc ON cacc.id = trans.fk_counterparty_account_id
LEFT JOIN transaction_status tstatus ON tstatus.id = trans.fk_transaction_status
LEFT JOIN transaction_type ttype ON ttype.id = trans.fk_transaction_type

WHERE NOT trans.hidden

ORDER BY trans.date DESC ;


-- Dumping structure for view lunch.view_total_for_others
-- Removing temporary table and create final VIEW structure
DROP TABLE IF EXISTS `view_total_for_others`;
CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` VIEW `view_total_for_others` AS SELECT
	DISTINCT account.id,
	CONCAT(account.first_name, ' ', account.last_name) AS name,
	
	ROUND(
		
		(
			SELECT
				IFNULL(SUM(CASE
					WHEN trans.acc_id = account.id THEN IF(trans.`status` = 'BIJ', trans.amount, trans.amount * -1)
					WHEN trans.cacc_id = account.id THEN IF(trans.`status` = 'AF', trans.amount, trans.amount * -1)
					ELSE 0
				END), 0)
			FROM view_all_transactions trans 
			WHERE trans.acc_id = account.id OR trans.cacc_id = account.id
		)
	
	# Divide by 100 and round down to 2 decimals
	/100, 2) AS totaal
	
FROM accounts account
LEFT JOIN account_state ON account_state.fk_account_id = account.id
LEFT JOIN account_type ON account_type.id = account_state.fk_type_id

WHERE account_type.code NOT IN ('EMPLOYEE', 'INTERN') ;


-- Dumping structure for view lunch.view_total_for_users
-- Removing temporary table and create final VIEW structure
DROP TABLE IF EXISTS `view_total_for_users`;
CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` VIEW `view_total_for_users` AS SELECT
	DISTINCT account.id,
	CONCAT(account.first_name, ' ', account.last_name) AS name,
	(SELECT acc_t.code
		FROM account_state state
		LEFT JOIN account_type acc_t ON acc_t.id = state.fk_type_id
		WHERE state.fk_account_id = account.id
		ORDER BY state.modified_date DESC
		LIMIT 1
	) AS `type`,
	
	ROUND(
		(
			# Get the sum of all rounds
			(	SELECT SUM(rounds.amount)
				FROM monthly_rounds rounds
			)
			
			* # Multiply by the participation percentage
			IFNULL((
				SELECT state.participation / 100
					FROM account_state state
					WHERE state.fk_account_id = account.id
					ORDER BY state.modified_date DESC
					LIMIT 1
			), 0)
		
			- # Subtract the user's sum of transactions
			(	SELECT
					IFNULL(SUM(CASE
						WHEN trans.acc_id = account.id THEN IF(trans.`status` = 'AF', trans.amount, trans.amount * -1)
						WHEN trans.cacc_id = account.id THEN IF(trans.`status` = 'BIJ', trans.amount, trans.amount * -1)
						ELSE 0
					END), 0)
				FROM view_all_transactions trans
				WHERE trans.acc_id = account.id OR trans.cacc_id = account.id
			)
		)
	# Divide by 100 and round down to 2 decimals
	/100, 2) AS amount
	
FROM accounts account
LEFT JOIN account_state ON account_state.fk_account_id = account.id
LEFT JOIN account_type ON account_type.id = account_state.fk_type_id

WHERE account_type.code in ('EMPLOYEE', 'INTERN') AND account.deleted_date IS NULL

ORDER BY amount DESC ;
/*!40101 SET SQL_MODE=IFNULL(@OLD_SQL_MODE, '') */;
/*!40014 SET FOREIGN_KEY_CHECKS=IF(@OLD_FOREIGN_KEY_CHECKS IS NULL, 1, @OLD_FOREIGN_KEY_CHECKS) */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
