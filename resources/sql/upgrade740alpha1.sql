set @query = if ((select count(*) from information_schema.columns where table_schema=database() and table_name='tbldeviceauth' and column_name='role_ids') = 0, 'ALTER TABLE  `tbldeviceauth` ADD `role_ids` TEXT COLLATE utf8_unicode_ci NOT NULL AFTER `is_admin`', 'DO 0;');
prepare statement from @query;
execute statement;
deallocate prepare statement;

CREATE TABLE IF NOT EXISTS `tblapi_roles` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `role` varchar(255) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  `description` varchar(255) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  `permissions` text COLLATE utf8_unicode_ci NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00',
  `updated_at` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
