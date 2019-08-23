INSERT INTO `tblconfiguration` (`setting` ,`value` )VALUES ('SEOFriendlyUrls', '');
INSERT INTO `tblconfiguration` (`setting` ,`value` )VALUES ('ShowCCIssueStart', '');

ALTER TABLE `tblcustomfields` CHANGE `relid` `relid` INT( 10 ) NOT NULL DEFAULT '0' ;
ALTER TABLE `tblcustomfields` ADD `sortorder` INT( 10 ) NOT NULL DEFAULT '0';

ALTER TABLE `tblproductconfigoptionssub` ADD `sortorder` INT( 10 ) NOT NULL DEFAULT '0';

ALTER TABLE `tbladdons` ADD `tax` TEXT NOT NULL AFTER `billingcycle` ;
UPDATE tbladdons SET tax='on';
ALTER TABLE `tblhostingaddons` ADD `tax` TEXT NOT NULL AFTER `billingcycle` ;
UPDATE tblhostingaddons SET tax='on';

INSERT INTO `tblconfiguration` (`setting`, `value`) VALUES ('ClientDropdownFormat', '1');

INSERT INTO `tblconfiguration` (`setting` ,`value` )VALUES ('TicketRatingEnabled', 'on');
ALTER TABLE `tblticketreplies` ADD `rating` INT( 5 ) NOT NULL ;

CREATE TABLE IF NOT EXISTS `tblproductconfiggroups` (
  `id` int(10) NOT NULL auto_increment,
  `name` text COLLATE utf8_unicode_ci NOT NULL,
  `description` text COLLATE utf8_unicode_ci NOT NULL,
  PRIMARY KEY  (`id`)
) DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

CREATE TABLE IF NOT EXISTS `tblproductconfiglinks` (
  `gid` int(10) NOT NULL,
  `pid` int(10) NOT NULL
) DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

ALTER TABLE `tblproductconfigoptions` CHANGE `productid` `gid` INT( 10 ) NOT NULL DEFAULT '0' ;

CREATE TABLE IF NOT EXISTS `tblnetworkissues` (
  `id` int(10) unsigned NOT NULL auto_increment,
  `title` varchar(45) NOT NULL,
  `description` text COLLATE utf8_unicode_ci NOT NULL,
  `type` enum('Server','System','Other') NOT NULL,
  `affecting` varchar(100) default NULL,
  `server` int(10) unsigned default NULL,
  `priority` enum('Critical','Low','Medium','High') NOT NULL,
  `startdate` datetime NOT NULL,
  `enddate` datetime default NULL,
  `status` enum('Reported','Investigating','In Progress','Outage','Scheduled','Resolved') NOT NULL,
  `lastupdate` datetime NOT NULL,
  PRIMARY KEY  (`id`)
) DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
INSERT INTO `tblconfiguration` (`setting`, `value`) VALUES ('NetworkIssuesRequireLogin', 'on');

INSERT INTO `tblconfiguration` (`setting` ,`value` )VALUES ('ShowNotesFieldonCheckout', 'on');
ALTER TABLE `tblorders` ADD `notes` TEXT NOT NULL ;

INSERT INTO `tblconfiguration` (`setting` ,`value` )VALUES ('RequireLoginforClientTickets', 'on');

ALTER TABLE `tblhostingaddons` CHANGE `subscriptionid` `notes` TEXT NOT NULL ;

CREATE TABLE IF NOT EXISTS `tblquotes` (
`id` INT( 10 ) NOT NULL AUTO_INCREMENT PRIMARY KEY ,
`subject` TEXT COLLATE utf8_unicode_ci NOT NULL ,
`stage` ENUM( 'Draft', 'Delivered', 'On Hold', 'Accepted', 'Lost', 'Dead' ) NOT NULL ,
`validuntil` DATE NOT NULL ,
`userid` INT( 10 ) NOT NULL ,
`firstname` TEXT COLLATE utf8_unicode_ci NOT NULL ,
`lastname` TEXT COLLATE utf8_unicode_ci NOT NULL ,
`companyname` TEXT COLLATE utf8_unicode_ci NOT NULL ,
`email` TEXT COLLATE utf8_unicode_ci NOT NULL ,
`address1` TEXT COLLATE utf8_unicode_ci NOT NULL ,
`address2` TEXT COLLATE utf8_unicode_ci NOT NULL ,
`city` TEXT COLLATE utf8_unicode_ci NOT NULL ,
`state` TEXT COLLATE utf8_unicode_ci NOT NULL ,
`postcode` TEXT COLLATE utf8_unicode_ci NOT NULL ,
`country` TEXT COLLATE utf8_unicode_ci NOT NULL ,
`phonenumber` TEXT COLLATE utf8_unicode_ci NOT NULL ,
`subtotal` DECIMAL( 10, 2 ) NOT NULL ,
`tax1` DECIMAL( 10, 2 ) NOT NULL ,
`tax2` DECIMAL( 10, 2 ) NOT NULL ,
`total` DECIMAL( 10, 2 ) NOT NULL ,
`customernotes` TEXT COLLATE utf8_unicode_ci NOT NULL ,
`adminnotes` TEXT COLLATE utf8_unicode_ci NOT NULL ,
`datecreated` DATE NOT NULL ,
`lastmodified` DATE NOT NULL
) DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

CREATE TABLE IF NOT EXISTS `tblquoteitems` (
`id` INT( 10 ) NOT NULL AUTO_INCREMENT PRIMARY KEY ,
`quoteid` INT( 10 ) NOT NULL ,
`description` TEXT COLLATE utf8_unicode_ci NOT NULL ,
`quantity` TEXT COLLATE utf8_unicode_ci NOT NULL ,
`unitprice` DECIMAL( 10, 2 ) NOT NULL ,
`discount` DECIMAL( 10, 2 ) NOT NULL ,
`taxable` INT( 1 ) NOT NULL
) DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

INSERT INTO `tblemailtemplates` (`id`, `type`, `name`, `subject`, `message`, `fromname`, `fromemail`, `disabled`, `custom`, `language`, `copyto`, `plaintext`) VALUES('', 'general', 'Quote Delivery with PDF', 'Quote #{$quote_number} - {$quote_subject}', '<p>Dear {$client_name},</p>\r\n<p>Here is the quote you requested for {$quote_subject}. The quote is valid until {$quote_valid_until}. You may simply reply to this email with any furthur questions or requirement.</p>\r\n<p>{$signature}</p>', '', '', '', '', '', '', 0);

INSERT INTO `tbladminperms` (`roleid` ,`permid` ) VALUES ('1', '84'), ('1', '85'), ('2', '85');
