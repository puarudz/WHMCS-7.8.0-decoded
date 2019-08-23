set @query = if ((select count(*) from information_schema.columns where table_schema=database() and table_name='tbldeviceauth' and column_name='compat_secret') = 0, 'ALTER TABLE `tbldeviceauth` ADD COLUMN `compat_secret` varchar(255) COLLATE utf8_unicode_ci NOT NULL DEFAULT \'\' AFTER `secret`;', 'DO 0;');
prepare statement from @query;
execute statement;
deallocate prepare statement;

set @query = if ((select count(*) from information_schema.columns where table_schema=database() and table_name='tbladdons' and column_name='autolinkby') = 0, 'ALTER TABLE `tbladdons` ADD COLUMN `autolinkby` text COLLATE utf8_unicode_ci NOT NULL DEFAULT \'\' AFTER `weight`;', 'DO 0;');
prepare statement from @query;
execute statement;
deallocate prepare statement;
