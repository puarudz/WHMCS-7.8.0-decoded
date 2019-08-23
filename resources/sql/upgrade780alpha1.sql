-- Create pay method and related tables
CREATE TABLE IF NOT EXISTS `tblpaymethods` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `userid` int(11) NOT NULL DEFAULT '0',
  `description` varchar(255) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  `contact_id` int(11) NOT NULL DEFAULT '0',
  `contact_type` varchar(255) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  `payment_id` int(11) NOT NULL DEFAULT '0',
  `payment_type` varchar(255) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  `gateway_name` varchar(255) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  `order_preference` int(11) NOT NULL DEFAULT '0',
  `created_at` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00',
  `updated_at` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00',
  `deleted_at` timestamp NULL,
  PRIMARY KEY (`id`),
  KEY `tblpaymethods_userid` (`userid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

CREATE TABLE IF NOT EXISTS `tblcreditcards` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `pay_method_id` int(11) NOT NULL DEFAULT '0',
  `card_type` varchar(255) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  `last_four` varchar(255) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  `expiry_date` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `card_data` blob NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00',
  `updated_at` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00',
  `deleted_at` timestamp NULL,
  PRIMARY KEY (`id`),
  KEY `tblcreditcards_pay_method_id` (`pay_method_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

CREATE TABLE IF NOT EXISTS `tblbankaccts` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `pay_method_id` int(11) NOT NULL DEFAULT '0',
  `bank_name` varchar(255) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  `acct_type` varchar(255) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  `bank_data` blob NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00',
  `updated_at` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00',
  `deleted_at` timestamp NULL,
  PRIMARY KEY (`id`),
  KEY `tblbankaccts_pay_method_id` (`pay_method_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- Add the admin_id column to tblcredit
set @query = if ((select count(*) from information_schema.columns where table_schema=database() and table_name='tblcredit' and column_name='admin_id') = 0, 'alter table `tblcredit` add `admin_id` int(10) unsigned NOT NULL DEFAULT \'0\' AFTER `clientid`', 'DO 0');
prepare statement from @query;
execute statement;
deallocate prepare statement;

-- Add the paymethodid column to tblinvoices
set @query = if ((select count(*) from information_schema.columns where table_schema=database() and table_name='tblinvoices' and column_name='paymethodid') = 0, 'alter table `tblinvoices` add `paymethodid` int(10) unsigned DEFAULT NULL AFTER `paymentmethod`', 'DO 0');
prepare statement from @query;
execute statement;
deallocate prepare statement;

-- Add PruneTicketAttachmentsMonths to tblconfiguration
set @query = if ((select count(*) from tblconfiguration where setting='PruneTicketAttachmentsMonths') = 0, 'INSERT INTO `tblconfiguration` (setting, value, created_at, updated_at) VALUES (\'PruneTicketAttachmentsMonths\', 0, now(), now())', 'DO 0');
prepare statement from @query;
execute statement;
deallocate prepare statement;

-- Add the attachments_removed column to tbltickets
set @query = if ((select count(*) from information_schema.columns where table_schema=database() and table_name='tbltickets' and column_name='attachments_removed') = 0, 'alter table `tbltickets` add `attachments_removed` tinyint(1) NOT NULL DEFAULT \'0\' AFTER `attachment`', 'DO 0');
prepare statement from @query;
execute statement;
deallocate prepare statement;

-- Add the attachments_removed column to tblticketreplies
set @query = if ((select count(*) from information_schema.columns where table_schema=database() and table_name='tblticketreplies' and column_name='attachments_removed') = 0, 'alter table `tblticketreplies` add `attachments_removed` tinyint(1) NOT NULL DEFAULT \'0\' AFTER `attachment`', 'DO 0');
prepare statement from @query;
execute statement;
deallocate prepare statement;

-- Add the attachments_removed column to tblticketnotes
set @query = if ((select count(*) from information_schema.columns where table_schema=database() and table_name='tblticketnotes' and column_name='attachments_removed') = 0, 'alter table `tblticketnotes` add `attachments_removed` tinyint(1) NOT NULL DEFAULT \'0\' AFTER `attachments`', 'DO 0');
prepare statement from @query;
execute statement;
deallocate prepare statement;

-- Add the hidden column to tbladdons
set @query = if ((select count(*) from information_schema.columns where table_schema=database() and table_name='tbladdons' and column_name='hidden') = 0, 'alter table `tbladdons` add `hidden` TINYINT(1) NOT NULL DEFAULT \'0\' AFTER `showorder`', 'DO 0');
prepare statement from @query;
execute statement;
deallocate prepare statement;

-- Add the retired column to tbladdons
set @query = if ((select count(*) from information_schema.columns where table_schema=database() and table_name='tbladdons' and column_name='retired') = 0, 'alter table `tbladdons` add `retired` TINYINT(1) NOT NULL DEFAULT \'0\' AFTER `hidden`', 'DO 0');
prepare statement from @query;
execute statement;
deallocate prepare statement;

-- Modify the datatype of the title column of tblnetworkissues
set @query = if ((select count(*) from information_schema.columns where table_schema=database() and table_name='tblnetworkissues' and column_name='title') = 1, 'alter table `tblnetworkissues` modify `title` varchar(150) NOT NULL', 'DO 1');
prepare statement from @query;
execute statement;
deallocate prepare statement;

-- Remove legacy settings
DELETE FROM tblconfiguration WHERE setting='AcceptedCardTypes';
DELETE FROM tblconfiguration WHERE setting = 'CCNeverStore';

-- Create server remote data storage table
CREATE TABLE IF NOT EXISTS `tblservers_remote` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `server_id` int(10) NOT NULL DEFAULT '0',
  `num_accounts` int(10) NOT NULL DEFAULT '0',
  `meta_data` text NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00',
  `updated_at` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00',
  PRIMARY KEY (`id`),
  KEY `tblservers_remote_server_id` (`server_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- Create metric usage table
CREATE TABLE IF NOT EXISTS `tblmetric_usage` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `rel_type` varchar(200) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  `rel_id` int(10) NOT NULL DEFAULT '0',
  `module_type` varchar(200) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  `module` varchar(200) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  `metric` varchar(200) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  `value` varchar(255) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  `created_at` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00',
  `updated_at` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00',
  PRIMARY KEY (`id`),
  KEY `tblmetric_usage_rel_type_id` (`rel_type`,`rel_id`),
  KEY `tblmetric_usage_module_type` (`module_type`),
  KEY `tblmetric_usage_module` (`module`),
  KEY `tblmetric_usage_metric` (`metric`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
