

DROP TABLE IF EXISTS `messages`;

GRANT SELECT, INSERT, UPDATE, DELETE, DROP ON coursework.* TO user@localhost IDENTIFIED BY'password';

CREATE TABLE `messages` (
  `message_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `switch_01` BOOLEAN ,
  `switch_02` BOOLEAN ,
  `switch_03` BOOLEAN ,
  `switch_04` BOOLEAN ,
  `heater` float(10) ,
  `keypad` int(1),
  `fan` varchar(7) NOT NULL,
  `timestamp` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `unique_message` varchar(10),
  `src_number` varchar(12),
  PRIMARY KEY(`message_id`)
)ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DROP TABLE IF EXISTS `users`;
CREATE TABLE users (
  id INT NOT NULL AUTO_INCREMENT ,
  password VARCHAR(255) NOT NULL ,
  email VARCHAR(50) NOT NULL ,
  passwordresetkey VARCHAR(255),
  role VARCHAR(15) NOT NULL,
  barred BOOLEAN NOT NULL DEFAULT false,
  createdate TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id)) ENGINE = MyISAM;

DROP TABLE IF EXISTS `activity`;
CREATE TABLE activity (
  id INT NOT NULL AUTO_INCREMENT ,
  userId INT NOT NULL ,
  activity VARCHAR(50) NOT NULL ,
  activitydate TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  result BOOLEAN NOT NULL,
  PRIMARY KEY (id)) ENGINE = MyISAM;