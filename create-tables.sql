DROP TABLE IF EXISTS `messages`;

GRANT SELECT, INSERT, UPDATE, DELETE, DROP ON coursework.* TO user@localhost IDENTIFIED BY'password';

CREATE TABLE `messages` (
  `message_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `switch_01` BOOLEAN NOT NULL,
  `switch_02` BOOLEAN NOT NULL,
  `switch_03` BOOLEAN NOT NULL,
  `switch_04` BOOLEAN NOT NULL,
  `heater` int(3) NOT NULL,
  `keypad` int(1) NOT NULL,
  `fan` ENUM('forward', 'reverse') NOT NULL,
  `timestamp` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY(`message_id`)
)ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

//Inserting

INSERT INTO messages
(switch_01, switch_02, switch_03, switch_04, heater, keypad, fan)
VALUES
(false, false, false, true , 411, 0, 'reverse'),
(false, false, false, false , 51, 3, 'forward');