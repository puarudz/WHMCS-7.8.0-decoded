ALTER TABLE `tblannouncements` ADD `parentid` INT( 10 ) NOT NULL , ADD `language` TEXT NOT NULL ;

ALTER TABLE `tblknowledgebase` ADD `parentid` INT( 10 ) NOT NULL , ADD `language` TEXT NOT NULL ;
ALTER TABLE `tblknowledgebasecats` ADD `catid` INT( 10 ) NOT NULL , ADD `language` TEXT NOT NULL ;

ALTER TABLE `tbltickets` CHANGE `adminunread` `adminunread` TEXT NOT NULL ;

ALTER TABLE `tblpromotions` ADD `requires` TEXT NOT NULL AFTER `appliesto` , ADD `requiresexisting` INT( 1 ) NOT NULL AFTER `requires` ;

ALTER TABLE `tblcontacts` ADD `subaccount` INT( 1 ) NOT NULL DEFAULT '0' AFTER `phonenumber` ,
ADD `password` TEXT NOT NULL AFTER `subaccount` ,
ADD `permissions` TEXT NOT NULL AFTER `password`;

INSERT INTO `tblconfiguration` (`setting`, `value`) VALUES ('CreateDomainInvoiceDaysBefore', '');
INSERT INTO `tblconfiguration` (`setting`, `value`) VALUES ('NoInvoiceEmailOnOrder', '');
INSERT INTO `tblconfiguration` (`setting`, `value`) VALUES ('TaxInclusiveDeduct', '');
INSERT INTO `tblconfiguration` (`setting`, `value`) VALUES ('LateFeeMinimum', '0.00');
INSERT INTO `tblconfiguration` (`setting`, `value`) VALUES ('AutoProvisionExistingOnly', '');

CREATE TABLE IF NOT EXISTS `tblticketstatuses` (
  `id` int(10) NOT NULL auto_increment,
  `title` text COLLATE utf8_unicode_ci NOT NULL,
  `color` text COLLATE utf8_unicode_ci NOT NULL,
  `sortorder` int(2) NOT NULL,
  `showactive` int(1) NOT NULL,
  `showawaiting` int(1) NOT NULL,
  `autoclose` int(1) NOT NULL,
  PRIMARY KEY  (`id`)
) DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

INSERT INTO `tblticketstatuses` (`id`, `title`, `color`, `sortorder`, `showactive`, `showawaiting`, `autoclose`) VALUES
(1, 'Open', '#779500', 1, 1, 1, 0),
(2, 'Answered', '#000000', 2, 1, 0, 1),
(3, 'Customer-Reply', '#ff6600', 3, 1, 1, 1),
(4, 'Closed', '#888888', 10, 0, 0, 0),
(5, 'On Hold', '#224488', 5, 1, 0, 0),
(6, 'In Progress', '#cc0000', 6, 1, 0, 0);

ALTER TABLE `tblhosting` ADD `promoid` INT( 10 ) NOT NULL AFTER `subscriptionid` ;
ALTER TABLE `tbldomains` ADD `promoid` INT( 10 ) NOT NULL AFTER `subscriptionid` ;

ALTER TABLE `tblpricing` ADD `triennially` DECIMAL( 10, 2 ) NOT NULL ;
ALTER TABLE `tblpricing` ADD `tsetupfee` DECIMAL( 10, 2 ) NOT NULL AFTER `bsetupfee` ;
UPDATE tblpricing SET triennially='-1' WHERE type='product';

INSERT INTO `tblconfiguration` (`setting`, `value`) VALUES ('EnableDomainRenewalOrders', 'on');
INSERT INTO `tblconfiguration` (`setting`, `value`) VALUES ('EnableMassPay', 'on');

ALTER TABLE `tblorders` ADD `renewals` TEXT NOT NULL AFTER `transfersecret` ;

ALTER TABLE `tblbillableitems` ADD `hours` DECIMAL( 5, 1 ) NOT NULL AFTER `description` ;
