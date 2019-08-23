INSERT INTO `tbladminperms` (`roleid` ,`permid` )VALUES ('1', '95'),('1', '96');

ALTER TABLE `tblcontacts` ADD `pwresetkey` TEXT NOT NULL , ADD `pwresetexpiry` INT( 10 ) NOT NULL ;

INSERT INTO `tblconfiguration` (`setting`, `value`) VALUES ('NoAutoApplyCredit', '');

UPDATE tblemailtemplates SET type='general' WHERE name='Order Confirmation';
