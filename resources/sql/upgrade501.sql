ALTER TABLE `tblclients` ADD `defaultgateway` TEXT NOT NULL AFTER `currency` ;

ALTER TABLE `tbltickets`  ADD `contactid` INT(10) NOT NULL AFTER `userid`;
ALTER TABLE `tblticketreplies`  ADD `contactid` INT(10) NOT NULL AFTER `userid`;

ALTER TABLE `tbldownloads`  ADD `hidden` TEXT NOT NULL AFTER `clientsonly`;

ALTER TABLE `tblorders` ADD `orderdata` TEXT NOT NULL AFTER `promovalue` ;

INSERT INTO `tbladminperms` (`roleid`, `permid`) VALUES ('2', '71'), ('2', '73'), ('1', '104'), ('2', '104'), ('1', '105'), ('2', '105'), ('3', '105'), ('1', '106'), ('1', '107'), ('1', '108'), ('1', '109'), ('1', '110'), ('2', '110'), ('1', '111'), ('1', '112'), ('1', '113'), ('1', '114'), ('1', '115'), ('1', '116'), ('1', '117'), ('1', '118'), ('1', '119'), ('1', '120'), ('2', '120');

UPDATE `tbladmins` SET template='blend' WHERE template='simple';
ALTER TABLE `tblclients`  ADD `bankname` TEXT NOT NULL AFTER `issuenumber`,  ADD `banktype` TEXT NOT NULL AFTER `bankname`,  ADD `bankcode` BLOB NOT NULL AFTER `banktype`,  ADD `bankacct` BLOB NOT NULL AFTER `bankcode`;
