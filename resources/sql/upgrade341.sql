CREATE TABLE IF NOT EXISTS `tblaffiliatespending` (`id` INT( 1 ) NOT NULL AUTO_INCREMENT PRIMARY KEY ,`affaccid` INT( 1 ) NOT NULL ,`amount` DECIMAL( 10, 2 ) NOT NULL ,`clearingdate` DATE NOT NULL ) DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
INSERT INTO `tblconfiguration` (`setting`, `value`) VALUES ('AffiliatesDelayCommission', '0');
ALTER TABLE `tblhosting` CHANGE `domainstatus` `domainstatus` ENUM( 'Pending', 'Active', 'Suspended', 'Terminated', 'Cancelled', 'Fraud' ) NOT NULL ;
ALTER TABLE `tblhostingaddons` CHANGE `status` `status` ENUM( 'Pending', 'Active', 'Terminated', 'Cancelled', 'Fraud' ) NOT NULL ;
ALTER TABLE `tbldomains` CHANGE `status` `status` ENUM( 'Pending', 'Active', 'Expired', 'Cancelled', 'Fraud' ) NOT NULL ;
INSERT INTO `tblemailtemplates` (`type`, `name`, `subject`, `message`) VALUES ('support', 'Bounce Message', 'Support Ticket Not Opened', '<p>[Name],</p><p>Your email to our support system could not be accepted because it was not recognized as coming from an email address belonging to one of our customers.  If you need assistance, please email from the address you registered with us that you use to login to our client area.</p><p>[Signature]</p>');
