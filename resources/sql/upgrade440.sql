INSERT INTO `tbladminperms` (`roleid` ,`permid` )VALUES ('1', '97'),('1', '98'),('1', '99'),('1', '100'),('2', '98'),('2', '99');

ALTER TABLE `tblclientgroups` ADD COLUMN `separateinvoices` TEXT NOT NULL AFTER `susptermexempt`;
ALTER TABLE `tblclients` ADD COLUMN `separateinvoices` TEXT NOT NULL AFTER `overideduenotices`;
ALTER TABLE `tblclients` ADD COLUMN `disableautocc` TEXT NOT NULL AFTER `separateinvoices`;

INSERT INTO `tblconfiguration` (`setting`, `value`) VALUES ('ClientDisplayFormat', '1');

ALTER TABLE `tbladmins` ADD `language` TEXT NOT NULL AFTER `template` ;

ALTER TABLE `tblactivitylog` ADD `userid` INT( 10 ) NOT NULL AFTER `user` ,
ADD `ipaddr` TEXT NOT NULL AFTER `userid` ;

ALTER TABLE `tblproducts` ADD `upgradeemail` TEXT NOT NULL AFTER `billingcycleupgrade`;
UPDATE tblproducts SET upgradeemail=welcomeemail;

CREATE TABLE IF NOT EXISTS `tbladdonmodules` (
`module` TEXT COLLATE utf8_unicode_ci NOT NULL ,
`setting` TEXT COLLATE utf8_unicode_ci NOT NULL ,
`value` TEXT COLLATE utf8_unicode_ci NOT NULL
) DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
