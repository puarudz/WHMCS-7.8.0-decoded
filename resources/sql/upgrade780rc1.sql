-- Add the cc column to tblticketmaillog
set @query = if ((select count(*) from information_schema.columns where table_schema=database() and table_name='tblticketmaillog' and column_name='cc') = 0, 'alter table `tblticketmaillog` add `cc` TEXT COLLATE utf8_unicode_ci NOT NULL AFTER `to`', 'DO 0');
prepare statement from @query;
execute statement;
deallocate prepare statement;

-- Add new TicketAddCarbonCopyRecipients setting to tblconfiguration
set @query = if ((select count(*) from `tblconfiguration` where `setting` = 'TicketAddCarbonCopyRecipients') = 0, "INSERT INTO `tblconfiguration` (`setting`, `value`, `created_at`, `updated_at`) VALUES ('TicketAddCarbonCopyRecipients', '1', now(), now());",'DO 0;');
prepare statement from @query;
execute statement;
deallocate prepare statement;
