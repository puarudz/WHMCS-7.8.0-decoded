ALTER TABLE `tblticketdepartments` ADD `piperepliesonly` TEXT NOT NULL AFTER `clientsonly` ;

CREATE TABLE IF NOT EXISTS `tblservergroups` (
`id` INT( 10 ) NOT NULL AUTO_INCREMENT PRIMARY KEY ,
`name` TEXT COLLATE utf8_unicode_ci NOT NULL ,
`filltype` INT( 1 ) NOT NULL
) DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;


CREATE TABLE IF NOT EXISTS `tblservergroupsrel` (
`groupid` INT( 10 ) NOT NULL ,
`serverid` INT( 10 ) NOT NULL
) DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

ALTER TABLE `tblproducts` DROP `defaultserver` ;
ALTER TABLE `tblproducts` ADD `servergroup` INT( 10 ) NOT NULL AFTER `servertype` ;

INSERT INTO `tblconfiguration` (`setting`, `value`) VALUES ('DisableSessionIPCheck', '');

ALTER TABLE `tblclients` ADD `cardlastfour` TEXT NOT NULL AFTER `cardtype` ;

ALTER TABLE `tbladdons` ADD `downloads` TEXT NOT NULL ,
ADD `autoactivate` TEXT NOT NULL ,
ADD `welcomeemail` INT( 10 ) NOT NULL ;

ALTER TABLE `tblhostingaddons` ADD `addonid` INT( 10 ) NOT NULL AFTER `hostingid` ;

ALTER TABLE `tblproductconfigoptions` ADD `hidden` INT( 1 ) NOT NULL ;
ALTER TABLE `tblproductconfigoptionssub` ADD `hidden` INT( 1 ) NOT NULL ;

ALTER TABLE `tbltickets` ADD `cc` TEXT NOT NULL AFTER `email` ;
INSERT INTO `tblconfiguration` (`setting`, `value`) VALUES ('DisableSupportTicketReplyEmailsLogging', '');

CREATE TABLE IF NOT EXISTS `tblclientsfiles` (
`id` INT( 10 ) NOT NULL AUTO_INCREMENT PRIMARY KEY ,
`userid` INT( 10 ) NOT NULL ,
`title` TEXT COLLATE utf8_unicode_ci NOT NULL ,
`filename` TEXT COLLATE utf8_unicode_ci NOT NULL ,
`adminonly` INT( 1 ) NOT NULL ,
`dateadded` DATE NOT NULL
) DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

ALTER TABLE `tblclients` ADD `pwresetkey` TEXT NOT NULL , ADD `pwresetexpiry` INT( 10 ) NOT NULL ;
INSERT INTO `tblemailtemplates` (`id`, `type`, `name`, `subject`, `message`, `fromname`, `fromemail`, `disabled`, `custom`, `language`, `copyto`, `plaintext`) VALUES (NULL, 'general', 'Password Reset Validation', 'Your login details for {$company_name}', '<p>Dear {$client_name},</p><p>Recently a request was submitted to reset your password for our client area. If you did not request this, please ignore this email. It will expire and become useless in 2 hours time.</p><p>To reset your password, please visit the url below:<br /><a href="{$pw_reset_url}">{$pw_reset_url}</a></p><p>When you visit the link above, your password will be reset, and the new password will be emailed to you.</p><p>{$signature}</p>', '', '', '', '', '', '', '0');
INSERT INTO `tblemailtemplates` (`id`, `type`, `name`, `subject`, `message`, `fromname`, `fromemail`, `disabled`, `custom`, `language`, `copyto`, `plaintext`) VALUES (NULL, 'general', 'Password Reset Confirmation', 'Your new password for {$company_name}', '<p>Dear {$client_name},</p><p>As you requested, your password for our client area has now been reset.  Your new login details are as follows:</p><p>{$whmcs_link}<br />Email: {$client_email}<br />Password: {$client_password}</p><p>To change your password to something more memorable, after logging in go to My Details > Change Password.</p><p>{$signature}</p>', '', '', '', '', '', '', '0');

ALTER TABLE `tblproducts` ADD `overagesenabled` INT( 1 ) NOT NULL AFTER `billingcycleupgrade` ,
ADD `overagesdisklimit` INT( 10 ) NOT NULL AFTER `overagesenabled` ,
ADD `overagesbwlimit` INT( 10 ) NOT NULL AFTER `overagesdisklimit` ,
ADD `overagesdiskprice` DECIMAL( 6, 4 ) NOT NULL AFTER `overagesbwlimit` ,
ADD `overagesbwprice` DECIMAL( 6, 4 ) NOT NULL AFTER `overagesdiskprice` ;
INSERT INTO `tblconfiguration` (`setting`, `value`) VALUES ('OverageBillingMethod', '1');

ALTER TABLE `tblproductconfigoptions` ADD `qtyminimum` INT( 10 ) NOT NULL AFTER `optiontype` ,
ADD `qtymaximum` INT( 10 ) NOT NULL AFTER `qtyminimum` ;

ALTER TABLE `tblcustomfields` ADD `description` TEXT NOT NULL AFTER `fieldtype` ;

INSERT INTO `tblconfiguration` (`setting` ,`value`)VALUES ('CCNeverStore', '');
INSERT INTO `tblconfiguration` (`setting` ,`value`)VALUES ('CCAllowCustomerDelete', '');
