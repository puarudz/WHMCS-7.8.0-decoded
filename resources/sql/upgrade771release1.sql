-- Add the last_error column to tblstorageconfigurations
set @query = if ((select count(*) from information_schema.columns where table_schema=database() and table_name='tblstorageconfigurations' and column_name='last_error') = 0, 'alter table `tblstorageconfigurations` add `last_error` TEXT COLLATE utf8_unicode_ci AFTER `settings`', 'DO 0');
prepare statement from @query;
execute statement;
deallocate prepare statement;
