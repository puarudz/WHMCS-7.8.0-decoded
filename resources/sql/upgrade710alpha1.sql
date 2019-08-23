-- password_reset_key to tbladmins --
set @query = if ((select count(*) from information_schema.columns where table_schema=database() and table_name='tbladmins' and column_name='password_reset_key') = 0, 'ALTER TABLE tbladmins ADD `password_reset_key` VARCHAR(255) COLLATE utf8_unicode_ci NOT NULL DEFAULT \'\'', 'DO 0');
prepare statement from @query;
execute statement;
deallocate prepare statement;

-- password_reset_data to tbladmins --
set @query = if ((select count(*) from information_schema.columns where table_schema=database() and table_name='tbladmins' and column_name='password_reset_data') = 0, 'ALTER TABLE tbladmins ADD `password_reset_data` text COLLATE utf8_unicode_ci NOT NULL', 'DO 0');
prepare statement from @query;
execute statement;
deallocate prepare statement;

-- password_reset_expiry to tbladmins --
set @query = if ((select count(*) from information_schema.columns where table_schema=database() and table_name='tbladmins' and column_name='password_reset_expiry') = 0, 'ALTER TABLE tbladmins ADD `password_reset_expiry` timestamp NOT NULL DEFAULT \'0000-00-00 00:00:00\'', 'DO 0');
prepare statement from @query;
execute statement;
deallocate prepare statement;

-- Add the created_at column to tbladmins
set @query = if ((select count(*) from information_schema.columns where table_schema=database() and table_name='tbladmins' and column_name='created_at') = 0, 'alter table tbladmins add `created_at` timestamp NOT NULL DEFAULT \'0000-00-00 00:00:00\'', 'DO 0');
prepare statement from @query;
execute statement;
deallocate prepare statement;

-- Add the updated_at column to tbladmins
set @query = if ((select count(*) from information_schema.columns where table_schema=database() and table_name='tbladmins' and column_name='updated_at') = 0, 'alter table tbladmins add `updated_at` timestamp NOT NULL DEFAULT \'0000-00-00 00:00:00\'', 'DO 0');
prepare statement from @query;
execute statement;
deallocate prepare statement;

-- Add Transferred Away to tbldomains
ALTER TABLE `tbldomains` CHANGE `status` `status` ENUM('Pending','Pending Transfer','Active','Expired','Cancelled','Fraud','Transferred Away') NOT NULL DEFAULT 'Pending';

-- Add Completed to tblhosting
ALTER TABLE `tblhosting` CHANGE `domainstatus` `domainstatus` ENUM('Pending','Active','Suspended','Terminated','Cancelled','Fraud','Completed') NOT NULL DEFAULT 'Pending';

-- Add Completed Date to tblhosting --
set @query = if ((select count(*) from information_schema.columns where table_schema=database() and table_name='tblhosting' and column_name='completed_date') = 0, 'ALTER TABLE `tblhosting` ADD `completed_date` DATE NOT NULL DEFAULT \'0000-00-00\' AFTER `termination_date`', 'DO 0');
prepare statement from @query;
execute statement;
deallocate prepare statement;

-- Create tbldomains_extra
CREATE TABLE IF NOT EXISTS `tbldomains_extra` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `domain_id` int(11) NOT NULL DEFAULT '0',
  `name` varchar(32) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  `value` varchar(255) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  `created_at` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00',
  `updated_at` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00',
  PRIMARY KEY (`id`),
  UNIQUE KEY `tbldomains_extra_domain_id_type_unique` (`domain_id`,`name`),
  KEY `tbldomains_extra_type_index` (`name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- Create tbldomainpricing_premium
CREATE TABLE IF NOT EXISTS `tbldomainpricing_premium` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `to_amount` decimal(10,2) NOT NULL DEFAULT '0.00',
  `markup` decimal(8,5) NOT NULL DEFAULT '0.00000',
  `created_at` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00',
  `updated_at` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00',
  PRIMARY KEY (`id`),
  UNIQUE KEY `tbldomain_pricing_premium_to_amount_unique` (`to_amount`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- truncate tbldomainpricing_premium to ensure no accidental duplicates
TRUNCATE tbldomainpricing_premium;
INSERT INTO `tbldomainpricing_premium` (`to_amount`, `markup`, `created_at`, `updated_at`)
VALUES ('200', '20', now(), now()), ('500', '25', now(), now()), ('1000', '30', now(), now()), ('-1', '20', now(), now());

-- Premium Domains in tblconfiguration
INSERT INTO `tblconfiguration` (`setting`, `value`, `created_at`, `updated_at`) VALUES ('PremiumDomains', 0, NOW(), NOW());

-- is_premium field on tbldomains
set @query = if ((select count(*) from information_schema.columns where table_schema=database() and table_name='tbldomains' and column_name='is_premium') = 0, 'ALTER TABLE tbldomains ADD `is_premium` TINYINT(1) AFTER `idprotection`', 'DO 0');
prepare statement from @query;
execute statement;
deallocate prepare statement;

-- Create tbldomain_lookup_configuration
CREATE TABLE IF NOT EXISTS `tbldomain_lookup_configuration` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `registrar` varchar(32) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  `setting` varchar(128) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  `value` text COLLATE utf8_unicode_ci NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00',
  `updated_at` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00',
  PRIMARY KEY (`id`),
  KEY `registrar_setting_index` (`registrar`,`setting`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- Create tblmodulequeue
CREATE TABLE IF NOT EXISTS `tblmodulequeue` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `service_type` varchar(20) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  `service_id` int(10) unsigned NOT NULL DEFAULT '0',
  `module_name` varchar(64) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  `module_action` varchar(64) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  `last_attempt` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00',
  `last_attempt_error` text COLLATE utf8_unicode_ci NOT NULL,
  `num_retries` smallint(5) unsigned NOT NULL DEFAULT '0',
  `completed` tinyint(1) NOT NULL DEFAULT '0',
  `created_at` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00',
  `updated_at` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- Insert Module Queue Permission to admin role 1
INSERT INTO `tbladminperms` (`roleid`, `permid`) VALUES (1, 137);
