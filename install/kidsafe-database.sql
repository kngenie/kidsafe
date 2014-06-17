# The following sql commands should be entered after creating and selecting the kidsafe database
# on myphpadmin use the "SQL" tab within the kidsafe table.

# Create Users table
CREATE TABLE `kidsafe`.`users` (
`userid` MEDIUMINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY ,
`username` VARCHAR( 255 ) NOT NULL UNIQUE ,
`accesslevel` TINYINT UNSIGNED NOT NULL ,
`fullname` VARCHAR( 255 ) NOT NULL ,
`password` VARCHAR( 255 ) NOT NULL ,
`status` BOOLEAN NOT NULL ,
`loginexpiry` INT NOT NULL ,
`supervisor` BOOLEAN NOT NULL ,
`admin` BOOLEAN NOT NULL
) ENGINE = InnoDB CHARACTER SET utf8 COLLATE utf8_general_ci;


# add default user entries - admin/kidsafe adult/raspberry teen/older child/child
# passwords are stored as md5 hash
INSERT INTO `users` (`userid`, `username`, `accesslevel`, `fullname`, `password`, `status`, `loginexpiry`, `supervisor`, `admin`) VALUES
(1, 'admin', 10, 'Admin user', 'bfca278c3614d93fe3f2a1aeab7eb1ec', 1, 0, 1, 1),
(2, 'child', 1, 'Child user', '1b7d5726533ab525a8760351e9b5e415', 1, 3600, 0, 0),
(3, 'teen', 5, 'Teenager', '6a3a6f6e33217afa6373df9d6b2e8a00', 1, 3600, 0, 0),
(4, 'adult', 9, 'Adult user', 'b89749505e144b564adfe3ea8fc394aa', 1, 3600, 1, 0);


# Create table for the websites
CREATE TABLE `kidsafe`.`sites` (
`siteid` MEDIUMINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY ,
`sitename` VARCHAR( 255 ) NOT NULL ,
`title` VARCHAR( 255 ) NOT NULL ,
`comments` VARCHAR( 255 ) NOT NULL
) ENGINE = InnoDB CHARACTER SET utf8 COLLATE utf8_general_ci;


# Insert "all sites" entry 
INSERT INTO `kidsafe`.`sites` (`siteid` , `sitename` , `title` , `comments` )
VALUES (NULL , '*', 'All sites', '' );

# Create groups table 
# no autoincrement - need to set ID in code (we only allow 10 anyway)
CREATE TABLE `kidsafe`.`groups` (
`groupid` TINYINT UNSIGNED NOT NULL PRIMARY KEY ,
`groupname` VARCHAR( 255 ) NOT NULL ,
`grouptitle` VARCHAR( 255 ) NOT NULL ,
`comments` VARCHAR( 255 ) NOT NULL
) ENGINE = InnoDB;

# add default groups
INSERT INTO `groups` (`groupid`, `groupname`, `grouptitle`, `comments`) VALUES
(0, 'guest', 'Guest', 'No login required'),
(1, 'kids', 'Young children', 'Very restrictive access'),
(2, 'kids_stores', 'Young children with stores', 'eg. normally same as young children with add play.google.com amazon.com etc.'),
(5, 'teens', 'Older children', 'less restrictive for older children'),
(6, 'teens_stores', 'Older children with stores', 'normally same as older children with add play.google.com amazon.com etc.'),
(8, 'adults', 'Adults', 'adults - blacklist instead of whitelist'),
(9, 'adults_nolog', 'Adults without logs', 'Adult access - blacklist instead of whitelist - no logging of accepts'),
(10, 'unrestricted', 'Unrestricted', 'Full access - no logging of sites visited');


# create rules table
CREATE TABLE `rules` (
  `ruleid` mediumint(8) unsigned NOT NULL AUTO_INCREMENT,
  `siteid` int(10) unsigned NOT NULL,
  `users` varchar(30) NOT NULL,
  `permission` tinyint(4) NOT NULL,
  `valid_until` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `template` mediumint(8) unsigned NOT NULL DEFAULT '0',
  `log` tinyint(1) NOT NULL,
  `priority` int(10) unsigned NOT NULL,
  `comments` varchar(255) NOT NULL,
  PRIMARY KEY (`ruleid`)
) ENGINE=InnoDB;


# Add default rules - allow adults and deny all
INSERT INTO `rules` (`ruleid`, `siteid`, `users`, `permission`, `valid_until`, `template`, `log`, `priority`, `comments`) VALUES
(1, 1, '*', 0, '0000-00-00 00:00:00', 0, 1, 10000, 'Final deny all other access'),
(2, 1, '9+', 9, '0000-00-00 00:00:00', 0, 0, 1000, 'Allow adults any site');


# Create templates table
CREATE TABLE `kidsafe`.`templates` (
`templateid` MEDIUMINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY ,
`templatename` VARCHAR( 255 ) NOT NULL ,
`description` VARCHAR( 255 ) NOT NULL ,
`users` VARCHAR( 30 ) NOT NULL ,
`permission` TINYINT NOT NULL
) ENGINE = InnoDB;

# Add default templates
INSERT INTO `templates` (`templateid`, `templatename`, `description`, `users`, `permission`) VALUES
(1, 'allow-everyone', 'Allow all users including guests', '*', 9),
(2, 'deny-everyone', 'Deny all users', '*', 0),
(3, 'allow-kids', 'Allow all logged in users (kids upwards)', '1+', 9),
(4, 'allow-teens', 'Allow teens upwards', '5+', 9),
(5, 'allow-adults', 'Allow only adults', '8+', 9),
(6, 'allow-stores', 'Stores - adults allowed, kids / teens need approval', '2,6,8+', 9),
(7, 'deny-kids', 'Deny kids / guests - including store level', '3-', 0),
(8, 'deny-teens', 'Deny teens / less - including store level', '6-', 0),
(9, 'deny-guest', 'Deny not logged in', '0', 0),
(10, 'deny-store', 'Deny children not logged in as store level and guests - use to exclude stores', '0,1,3,4,5,7', 0);
