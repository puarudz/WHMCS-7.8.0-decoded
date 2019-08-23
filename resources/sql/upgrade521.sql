ALTER TABLE `tblclients` ADD `authmodule` TEXT NOT NULL AFTER `password` , ADD `authdata` TEXT NOT NULL AFTER `authmodule` ;

ALTER TABLE `tblproducts` ADD `upgradechargefullcycle` INT( 1 ) NOT NULL AFTER `billingcycleupgrade`;

INSERT INTO `tbladminperms` (`roleid`, `permid`) VALUES ('1', '125'), ('1', '126'), ('1', '127'), ('1', '128'), ('1', '129'), ('2', '125'), ('2', '126'), ('2', '128'), ('2', '129'), ('3', '125'), ('3', '128');
