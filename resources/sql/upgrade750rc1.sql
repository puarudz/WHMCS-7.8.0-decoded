-- Add the marketing_emails_opt_in column to tblclient
set @query = if ((select count(*) from information_schema.columns where table_schema=database() and table_name='tblclients' and column_name='marketing_emails_opt_in') = 0, 'alter table `tblclients` add `marketing_emails_opt_in` TINYINT(1) unsigned NOT NULL DEFAULT \'0\' AFTER `emailoptout`', 'DO 0');
prepare statement from @query;
execute statement;
deallocate prepare statement;
-- Add new TaxPerLineItem setting to tblconfiguration
set @query = if ((select count(*) from `tblconfiguration` where `setting` = 'TaxPerLineItem') = 0, "INSERT INTO `tblconfiguration` (`setting`, `value`, `created_at`, `updated_at`) VALUES ('TaxPerLineItem', '1', now(), now());",'DO 0;');
prepare statement from @query;
execute statement;
deallocate prepare statement;
