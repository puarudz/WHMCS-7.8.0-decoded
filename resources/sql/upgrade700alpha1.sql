-- remove browser.php
DROP TABLE IF EXISTS `tblbrowserlinks`;
DELETE FROM `tbladminperms` WHERE `permid` = 48;

-- remove ClientDropdownFormat
DELETE FROM `tblconfiguration` WHERE setting = 'ClientDropdownFormat';

-- feedback_request to tblticketdepartments --
set @query = if ((select count(*) from information_schema.columns where table_schema=database() and table_name='tblticketdepartments' and column_name='feedback_request') = 0, 'ALTER TABLE tblticketdepartments ADD `feedback_request` tinyint(1) NOT NULL DEFAULT \'0\'', 'DO 0');
prepare statement from @query;
execute statement;
deallocate prepare statement;

-- set feedback request for each department based on current setting --
UPDATE `tblticketdepartments` SET `feedback_request` = CASE WHEN (SELECT value from `tblconfiguration` WHERE setting = 'TicketFeedback' LIMIT 1) = 'on' THEN 1 ELSE 0 END;

-- remove TicketFeedback from tblconfiguration --
DELETE FROM `tblconfiguration` WHERE setting = 'TicketFeedback';

-- new field for tbldomainpricing
set @query = if ((select count(*) from information_schema.columns where table_schema=database() and table_name='tbldomainpricing' and column_name='group') = 0, 'ALTER TABLE tbldomainpricing ADD `group` VARCHAR(5) COLLATE utf8_unicode_ci NOT NULL DEFAULT \'none\'', 'DO 0');
prepare statement from @query;
execute statement;
deallocate prepare statement;
