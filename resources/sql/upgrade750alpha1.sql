-- Create affiliates hits table.
CREATE TABLE IF NOT EXISTS `tblaffiliates_hits` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `affiliate_id` int(10) unsigned NOT NULL DEFAULT 0,
  `referrer_id` int(10) unsigned NOT NULL DEFAULT 0,
  `created_at` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  PRIMARY KEY (`id`),
  KEY `affiliate_id` (`affiliate_id`,`referrer_id`)
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci ;

-- Create affiliates referrers table.
CREATE TABLE IF NOT EXISTS `tblaffiliates_referrers` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `affiliate_id` int(10) unsigned NOT NULL DEFAULT 0,
  `referrer` varchar(500) NOT NULL DEFAULT '',
  `created_at` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `updated_at` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  PRIMARY KEY (`id`),
  KEY `affiliate_id` (`affiliate_id`)
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci ;

-- Create marketing consent table.
CREATE TABLE IF NOT EXISTS `tblmarketing_consent` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `userid` int(10) unsigned NOT NULL DEFAULT 0,
  `opt_in` tinyint(1) NOT NULL DEFAULT 0,
  `admin` tinyint(1) NOT NULL DEFAULT 0,
  `ip_address` varchar(32) NOT NULL DEFAULT '',
  `created_at` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `updated_at` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  PRIMARY KEY (`id`),
  KEY `userid` (`userid`)
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci ;

-- Update permissable addon status values
set @query = if ((select count(*) from information_schema.columns where table_schema=database() and table_name='tblhostingaddons' and column_name='status' and column_type="enum('Pending','Active','Suspended','Terminated','Cancelled','Fraud','Completed')") = 0, "ALTER TABLE `tblhostingaddons` CHANGE `status` `status` ENUM('Pending','Active','Suspended','Terminated','Cancelled','Fraud','Completed') NOT NULL DEFAULT 'Pending'",'DO 0;');
prepare statement from @query;
execute statement;
deallocate prepare statement;

-- Fix state spelling
UPDATE `tblclients` SET `state`='Chhattisgarh' WHERE `state`='Chattisgarh';
UPDATE `tblcontacts` SET `state`='Chhattisgarh' WHERE `state`='Chattisgarh';
UPDATE `tblquotes` SET `state`='Chhattisgarh' WHERE `state`='Chattisgarh';
UPDATE `tbltax` SET `state`='Chhattisgarh' WHERE `state`='Chattisgarh';

-- Add the updated_at column to tbltickets
set @query = if ((select count(*) from information_schema.columns where table_schema=database() and table_name='tbltickets' and column_name='updated_at') = 0, 'alter table tbltickets add `updated_at` timestamp NOT NULL DEFAULT \'0000-00-00 00:00:00\'', 'DO 0');
prepare statement from @query;
execute statement;
deallocate prepare statement;

-- Add View MarketConnect Balance permission to existing roles.
INSERT INTO `tbladminperms` (`roleid`, `permid`) VALUES ('1', '145'), ('2', '145');

-- Add email marketing opt in default message
set @query = if ((select count(*) from `tblconfiguration` where `setting` = 'EmailMarketingOptInMessage') = 0, "INSERT INTO `tblconfiguration` (`setting`, `value`, `created_at`, `updated_at`) VALUES ('EmailMarketingOptInMessage', 'We would like to send you occasional news, information and special offers by email. To join our mailing list, simply tick the box below. You can unsubscribe at any time.', now(), now());",'DO 0;');
prepare statement from @query;
execute statement;
deallocate prepare statement;

-- Add the grace_period column to tbldomainpricing
-- grace period can be overridden to '0' so -1 is default
set @query = if ((select count(*) from information_schema.columns where table_schema=database() and table_name='tbldomainpricing' and column_name='grace_period') = 0, 'alter table tbldomainpricing add `grace_period` INT(1) NOT NULL DEFAULT \'-1\';', 'DO 0');
prepare statement from @query;
execute statement;
deallocate prepare statement;

-- created_at to tbldomainpricing --
set @query = if ((select count(*) from information_schema.columns where table_schema=database() and table_name='tbldomainpricing' and column_name='created_at') = 0, 'ALTER TABLE `tbldomainpricing` ADD COLUMN `created_at` timestamp NOT NULL DEFAULT \'0000-00-00 00:00:00\';', 'DO 0;');
prepare statement from @query;
execute statement;
deallocate prepare statement;

-- updated_at to tbldomainpricing --
set @query = if ((select count(*) from information_schema.columns where table_schema=database() and table_name='tbldomainpricing' and column_name='updated_at') = 0, 'ALTER TABLE `tbldomainpricing` ADD COLUMN `updated_at` timestamp NOT NULL DEFAULT \'0000-00-00 00:00:00\';', 'DO 0;');
prepare statement from @query;
execute statement;
deallocate prepare statement;

-- Add the redemption_grace_period column to tbldomainpricing
-- redemption grace period can be overridden to '0' so -1 is default
set @query = if ((select count(*) from information_schema.columns where table_schema=database() and table_name='tbldomainpricing' and column_name='redemption_grace_period') = 0, 'alter table tbldomainpricing add `redemption_grace_period` INT(1) NOT NULL DEFAULT \'-1\' AFTER `grace_period`;', 'DO 0');
prepare statement from @query;
execute statement;
deallocate prepare statement;

-- Add new DomainExpirationFeeHandling setting to tblconfiguration
set @query = if ((select count(*) from `tblconfiguration` where `setting` = 'DomainExpirationFeeHandling') = 0, "INSERT INTO `tblconfiguration` (`setting`, `value`, `created_at`, `updated_at`) VALUES ('DomainExpirationFeeHandling', 'existing', now(), now());",'DO 0;');
prepare statement from @query;
execute statement;
deallocate prepare statement;

-- Add new statuses to tbldomains
ALTER TABLE `tbldomains` CHANGE `status` `status` ENUM('Pending','Pending Transfer','Active','Grace','Redemption','Expired','Cancelled','Fraud','Transferred Away') NOT NULL DEFAULT 'Pending';

-- Add the grace_period_fee column to tbldomainpricing
set @query = if ((select count(*) from information_schema.columns where table_schema=database() and table_name='tbldomainpricing' and column_name='grace_period_fee') = 0, 'alter table tbldomainpricing add `grace_period_fee` DECIMAL(10,2) NOT NULL DEFAULT \'0.00\' AFTER `grace_period`;', 'DO 0');
prepare statement from @query;
execute statement;
deallocate prepare statement;

-- Add the redemption_grace_period_fee column to tbldomainpricing
set @query = if ((select count(*) from information_schema.columns where table_schema=database() and table_name='tbldomainpricing' and column_name='redemption_grace_period_fee') = 0, 'alter table tbldomainpricing add `redemption_grace_period_fee` DECIMAL(10,2) NOT NULL DEFAULT \'-1.00\' AFTER `redemption_grace_period`;', 'DO 0');
prepare statement from @query;
execute statement;
deallocate prepare statement;
