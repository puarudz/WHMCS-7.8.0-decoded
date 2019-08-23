-- editor to tblticketescalations --
set @query = if ((select count(*) from information_schema.columns where table_schema=database() and table_name='tblticketescalations' and column_name='editor') = 0, 'ALTER TABLE tblticketescalations ADD `editor` enum(\'plain\',\'markdown\') COLLATE utf8_unicode_ci NOT NULL DEFAULT \'plain\'', 'DO 0');
prepare statement from @query;
execute statement;
deallocate prepare statement;
