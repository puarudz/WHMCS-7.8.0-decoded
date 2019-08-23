CREATE TABLE IF NOT EXISTS `tblapilog` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `action` varchar(255) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  `request` text COLLATE utf8_unicode_ci NOT NULL,
  `response` text COLLATE utf8_unicode_ci NOT NULL,
  `status` int(11) NOT NULL DEFAULT '0',
  `headers` text COLLATE utf8_unicode_ci NOT NULL,
  `level` int(11) NOT NULL DEFAULT '0',
  `created_at` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00',
  `updated_at` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- add What's New permission
INSERT INTO `tbladminperms` (`roleid`, `permid`) VALUES (1, 139);
-- add Manage API Credentials permission
INSERT INTO `tbladminperms` (`roleid`, `permid`) VALUES (1, 142);

CREATE TABLE IF NOT EXISTS `tbldeviceauth` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `identifier` varchar(255) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  `secret` varchar(255) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  `user_id` int(11) NOT NULL DEFAULT '0',
  `is_admin` tinyint(1) NOT NULL DEFAULT '0',
  `description` varchar(255) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  `last_access` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00',
  `created_at` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00',
  `updated_at` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00',
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `tbldeviceauth_identifier_unique` (`identifier`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

CREATE TABLE IF NOT EXISTS `tblmarketconnect_services` (
  `id` int(10) NOT NULL AUTO_INCREMENT,
  `name` varchar(30) NOT NULL  DEFAULT '',
  `status` tinyint(1) NOT NULL DEFAULT '0',
  `product_ids` text NOT NULL,
  `settings` text NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- created_at to tbladdons --
set @query = if ((select count(*) from information_schema.columns where table_schema=database() and table_name='tbladdons' and column_name='created_at') = 0, 'ALTER TABLE `tbladdons` ADD COLUMN `created_at` timestamp NOT NULL DEFAULT \'0000-00-00 00:00:00\';', 'DO 0;');
prepare statement from @query;
execute statement;
deallocate prepare statement;

-- updated_at to tbladdons --
set @query = if ((select count(*) from information_schema.columns where table_schema=database() and table_name='tbladdons' and column_name='updated_at') = 0, 'ALTER TABLE `tbladdons` ADD COLUMN `updated_at` timestamp NOT NULL DEFAULT \'0000-00-00 00:00:00\';', 'DO 0;');
prepare statement from @query;
execute statement;
deallocate prepare statement;

-- type to tbladdons --
set @query = if ((select count(*) from information_schema.columns where table_schema=database() and table_name='tbladdons' and column_name='type') = 0, 'ALTER TABLE tbladdons ADD `type` VARCHAR(16) COLLATE utf8_unicode_ci NOT NULL DEFAULT \'\' AFTER `welcomeemail`;', 'DO 0;');
prepare statement from @query;
execute statement;
deallocate prepare statement;

-- module to tbladdons --
set @query = if ((select count(*) from information_schema.columns where table_schema=database() and table_name='tbladdons' and column_name='module') = 0, 'ALTER TABLE tbladdons ADD `module` VARCHAR(32) COLLATE utf8_unicode_ci NOT NULL DEFAULT \'\' AFTER `type`;', 'DO 0;');
prepare statement from @query;
execute statement;
deallocate prepare statement;

-- server_group_id to tbladdons --
set @query = if ((select count(*) from information_schema.columns where table_schema=database() and table_name='tbladdons' and column_name='server_group_id') = 0, 'ALTER TABLE tbladdons ADD `server_group_id` INTEGER(10) NOT NULL DEFAULT \'0\' AFTER `module`;', 'DO 0;');
prepare statement from @query;
execute statement;
deallocate prepare statement;

-- server to tblhostingaddons --
set @query = if ((select count(*) from information_schema.columns where table_schema=database() and table_name='tblhostingaddons' and column_name='server') = 0, 'ALTER TABLE tblhostingaddons ADD `server` INTEGER(10) NOT NULL DEFAULT \'0\' AFTER `addonid`;', 'DO 0;');
prepare statement from @query;
execute statement;
deallocate prepare statement;

-- userid to tblhostingaddons --
set @query = if ((select count(*) from information_schema.columns where table_schema=database() and table_name='tblhostingaddons' and column_name='userid') = 0, 'ALTER TABLE tblhostingaddons ADD `userid` INTEGER(10) NOT NULL DEFAULT \'0\' AFTER `addonid`;', 'DO 0;');
prepare statement from @query;
execute statement;
deallocate prepare statement;

-- Create tblmodule_configuration
CREATE TABLE IF NOT EXISTS `tblmodule_configuration` (
  `id` INTEGER(10) unsigned NOT NULL AUTO_INCREMENT,
  `entity_type` VARCHAR(8) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  `entity_id` INTEGER(10) unsigned NOT NULL DEFAULT '0',
  `setting_name` VARCHAR(16) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  `friendly_name` VARCHAR(64) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  `value` text COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  `created_at` TIMESTAMP NOT NULL DEFAULT '0000-00-00 00:00:00',
  `updated_at` TIMESTAMP NOT NULL DEFAULT '0000-00-00 00:00:00',
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_constraint` (`entity_type`,`entity_id`,`setting_name`),
  KEY `tblmodule_configuration_entity_type_index` (`entity_type`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- Convert autoactivate on tbladdons --
UPDATE `tbladdons` SET `autoactivate` = 'payment' WHERE `autoactivate` = 'on';

-- id to tblcustomfieldsvalues --
set @query = if ((select count(*) from information_schema.columns where table_schema=database() and table_name='tblcustomfieldsvalues' and column_name='id') = 0, 'ALTER TABLE `tblcustomfieldsvalues` ADD COLUMN `id` INT(10) UNSIGNED PRIMARY KEY AUTO_INCREMENT NOT NULL FIRST;', 'DO 0;');
prepare statement from @query;
execute statement;
deallocate prepare statement;

-- created_at to tblcustomfieldsvalues --
set @query = if ((select count(*) from information_schema.columns where table_schema=database() and table_name='tblcustomfieldsvalues' and column_name='created_at') = 0, 'ALTER TABLE `tblcustomfieldsvalues` ADD COLUMN `created_at` timestamp NOT NULL DEFAULT \'0000-00-00 00:00:00\';', 'DO 0;');
prepare statement from @query;
execute statement;
deallocate prepare statement;

-- updated_at to tblcustomfieldsvalues --
set @query = if ((select count(*) from information_schema.columns where table_schema=database() and table_name='tblcustomfieldsvalues' and column_name='updated_at') = 0, 'ALTER TABLE `tblcustomfieldsvalues` ADD COLUMN `updated_at` timestamp NOT NULL DEFAULT \'0000-00-00 00:00:00\';', 'DO 0;');
prepare statement from @query;
execute statement;
deallocate prepare statement;

-- addon_id to tblsslorders --
set @query = if ((select count(*) from information_schema.columns where table_schema=database() and table_name='tblsslorders' and column_name='addon_id') = 0, 'ALTER TABLE `tblsslorders` ADD COLUMN `addon_id` INT(10) NOT NULL DEFAULT \'0\' AFTER `serviceid`;', 'DO 0;');
prepare statement from @query;
execute statement;
deallocate prepare statement;

-- add default value to completiondate on tblsslorders --
ALTER TABLE `tblsslorders` MODIFY COLUMN `completiondate` DATETIME NOT NULL DEFAULT '0000-00-00 00:00:00';
-- reports to tbladminroles --
set @query = if ((select count(*) from information_schema.columns where table_schema=database() and table_name='tbladminroles' and column_name='reports') = 0, 'ALTER TABLE tbladminroles ADD `reports` text NOT NULL AFTER `widgets`', 'DO 0');
prepare statement from @query;
execute statement;
deallocate prepare statement;
