-- Add new EnableSafeInclude setting to tblconfiguration
set @query = if ((select count(*) from `tblconfiguration` where `setting` = 'EnableSafeInclude') = 0, "INSERT INTO `tblconfiguration` (`setting`, `value`, `created_at`, `updated_at`) VALUES ('EnableSafeInclude', '1', now(), now());",'DO 0;');
prepare statement from @query;
execute statement;
deallocate prepare statement;
