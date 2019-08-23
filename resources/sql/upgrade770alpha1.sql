CREATE TABLE IF NOT EXISTS `tblsessions` (
 `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
 `session_id` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
 `payload` mediumtext COLLATE utf8_unicode_ci NOT NULL,
 `last_activity` int(11) unsigned NOT NULL,
 UNIQUE KEY `sessions_id_unique` (`session_id`),
 PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- Add the widget_order column to tbladmins
set @query = if ((select count(*) from information_schema.columns where table_schema=database() and table_name='tbladmins' and column_name='widget_order') = 0, 'alter table `tbladmins` add `widget_order` TEXT NOT NULL AFTER `hidden_widgets`', 'DO 0');
prepare statement from @query;
execute statement;
deallocate prepare statement;

-- Add the tax_id column to tblclients
set @query = if ((select count(*) from information_schema.columns where table_schema=database() and table_name='tblclients' and column_name='tax_id') = 0, 'alter table `tblclients` add `tax_id` VARCHAR(128) NOT NULL DEFAULT \'\' AFTER `phonenumber`', 'DO 0');
prepare statement from @query;
execute statement;
deallocate prepare statement;

-- Add the tax_id column to tblcontacts
set @query = if ((select count(*) from information_schema.columns where table_schema=database() and table_name='tblcontacts' and column_name='tax_id') = 0, 'alter table `tblcontacts` add `tax_id` VARCHAR(128) NOT NULL DEFAULT \'\' AFTER `phonenumber`', 'DO 0');
prepare statement from @query;
execute statement;
deallocate prepare statement;

-- Add the tax_id column to tblquotes
set @query = if ((select count(*) from information_schema.columns where table_schema=database() and table_name='tblquotes' and column_name='tax_id') = 0, 'alter table `tblquotes` add `tax_id` VARCHAR(128) NOT NULL DEFAULT \'\' AFTER `phonenumber`', 'DO 0');
prepare statement from @query;
execute statement;
deallocate prepare statement;

CREATE TABLE IF NOT EXISTS `tblstorageconfigurations` (
 `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
 `name` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
 `handler` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
 `settings` text COLLATE utf8_unicode_ci NOT NULL,
 `is_local` tinyint(1) unsigned NOT NULL,
 `sort_order` int(1) unsigned NOT NULL DEFAULT '0',
 `created_at` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00',
 `updated_at` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00',
 UNIQUE (`name`),
 PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

CREATE TABLE IF NOT EXISTS `tblfileassetsettings` (
 `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
 `asset_type` varchar(64) COLLATE utf8_unicode_ci NOT NULL,
 `storageconfiguration_id` int(10) unsigned NOT NULL,
 `migratetoconfiguration_id` int(10) unsigned DEFAULT NULL,
 `created_at` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00',
 `updated_at` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00',
 UNIQUE (`asset_type`),
 PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

CREATE TABLE IF NOT EXISTS `tblfileassetmigrationprogress` (
 `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
 `asset_type` varchar(64) COLLATE utf8_unicode_ci NOT NULL,
 `migrated_objects` mediumtext COLLATE utf8_unicode_ci NOT NULL,
 `num_objects_migrated` int(10) unsigned DEFAULT '0',
 `num_objects_total` int(10) unsigned DEFAULT '0',
 `active` tinyint(1) unsigned DEFAULT '1',
 `num_failures` int(10) unsigned DEFAULT '0',
 `last_failure_reason` text COLLATE utf8_unicode_ci NOT NULL,
 `created_at` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00',
 `updated_at` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00',
 UNIQUE (`asset_type`),
 PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- Add 'Pending Registration' status to tbldomains
ALTER TABLE `tbldomains` CHANGE `status` `status` ENUM('Pending','Pending Registration','Pending Transfer','Active','Grace','Redemption','Expired','Cancelled','Fraud','Transferred Away') NOT NULL DEFAULT 'Pending';

-- Add the attachment column to tblticketmaillog
set @query = if ((select count(*) from information_schema.columns where table_schema=database() and table_name='tblticketmaillog' and column_name='attachment') = 0, 'alter table `tblticketmaillog` add `attachment` TEXT NOT NULL', 'DO 0');
prepare statement from @query;
execute statement;
deallocate prepare statement;

-- Add the transaction_history_id column to tblgatewaylog
set @query = if ((select count(*) from information_schema.columns where table_schema=database() and table_name='tblgatewaylog' and column_name='transaction_history_id') = 0, 'alter table `tblgatewaylog` add `transaction_history_id` INT(10) unsigned NOT NULL DEFAULT \'0\' AFTER `data`', 'DO 0');
prepare statement from @query;
execute statement;
deallocate prepare statement;

-- permission for Apps and Integrations
INSERT INTO `tbladminperms` (`roleid`, `permid`) VALUES (1, 148);
