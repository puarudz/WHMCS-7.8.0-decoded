-- editor to tbltickets --
set @query = if ((select count(*) from information_schema.columns where table_schema=database() and table_name='tbltickets' and column_name='editor') = 0, 'ALTER TABLE tbltickets ADD `editor` enum(\'plain\',\'markdown\') COLLATE utf8_unicode_ci NOT NULL DEFAULT \'plain\'', 'DO 0');
prepare statement from @query;
execute statement;
deallocate prepare statement;

-- editor to tblticketreplies --
set @query = if ((select count(*) from information_schema.columns where table_schema=database() and table_name='tblticketreplies' and column_name='editor') = 0, 'ALTER TABLE tblticketreplies ADD `editor` enum(\'plain\',\'markdown\') COLLATE utf8_unicode_ci NOT NULL DEFAULT \'plain\'', 'DO 0');
prepare statement from @query;
execute statement;
deallocate prepare statement;

-- editor to tblticketnotes --
set @query = if ((select count(*) from information_schema.columns where table_schema=database() and table_name='tblticketnotes' and column_name='editor') = 0, 'ALTER TABLE tblticketnotes ADD `editor` enum(\'plain\',\'markdown\') COLLATE utf8_unicode_ci NOT NULL DEFAULT \'plain\'', 'DO 0');
prepare statement from @query;
execute statement;
deallocate prepare statement;
