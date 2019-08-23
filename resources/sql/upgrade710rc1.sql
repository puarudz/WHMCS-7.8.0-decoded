-- last_capture_attempt to tblinvoices --
set @query = if ((select count(*) from information_schema.columns where table_schema=database() and table_name='tblinvoices' and column_name='last_capture_attempt') = 0, 'ALTER TABLE tblinvoices ADD `last_capture_attempt` timestamp NOT NULL DEFAULT \'0000-00-00 00:00:00\' AFTER `datepaid`', 'DO 0');
prepare statement from @query;
execute statement;
deallocate prepare statement;
