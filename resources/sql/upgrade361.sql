ALTER TABLE `tblproductconfigoptionssub` CHANGE `price` `monthly` DECIMAL( 10, 2 ) NOT NULL DEFAULT '0.00' ;
ALTER TABLE `tblproductconfigoptionssub` ADD `quarterly` DECIMAL( 10, 2 ) NOT NULL ,ADD `semiannual` DECIMAL( 10, 2 ) NOT NULL ,ADD `annual` DECIMAL( 10, 2 ) NOT NULL ,ADD `biennial` DECIMAL( 10, 2 ) NOT NULL ;
UPDATE tblproductconfigoptionssub SET quarterly=monthly*3,semiannual=monthly*6,annual=monthly*12,biennial=monthly*24;
ALTER TABLE `tblclients` CHANGE `status` `status` ENUM( 'Active', 'Inactive', 'Closed' ) NOT NULL DEFAULT 'Active' ;
ALTER TABLE `tbldomains` CHANGE `status` `status` ENUM( 'Pending', 'Pending Transfer', 'Active', 'Expired', 'Cancelled', 'Fraud' ) NOT NULL DEFAULT 'Pending';
ALTER TABLE `tbldomains` ADD `donotrenew` TEXT NOT NULL ;
INSERT INTO `tblconfiguration` (`setting`, `value`) VALUES ('BulkDomainSearchEnabled', 'on');
INSERT INTO `tblconfiguration` (`setting`,`value`) VALUES ('AutoRenewDomainsonPayment','on');
INSERT INTO `tblconfiguration` (`setting`,`value`) VALUES ('DomainAutoRenewDefault','on');
