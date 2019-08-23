set @query = if ((select count(*) from information_schema.columns where table_schema=database() and table_name='tblupgrades' and column_name='userid') = 0, 'ALTER TABLE  `tblupgrades` ADD  `userid` INT( 10 ) NOT NULL AFTER  `id`', 'DO 0;');
prepare statement from @query;
execute statement;
deallocate prepare statement;

UPDATE  `tblupgrades` SET type = 'package' WHERE type != 'package' AND type != 'configoptions';

ALTER TABLE  `tblupgrades` CHANGE  `type`  `type` ENUM(  'service', 'addon', 'package',  'configoptions') CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL ;

set @query = if ((select count(*) from information_schema.columns where table_schema=database() and table_name='tblupgrades' and column_name='new_cycle') = 0, 'ALTER TABLE  `tblupgrades` ADD  `new_cycle` VARCHAR( 30 ) NOT NULL AFTER  `newvalue`', 'DO 0;');
prepare statement from @query;
execute statement;
deallocate prepare statement;

set @query = if ((select count(*) from information_schema.columns where table_schema=database() and table_name='tblupgrades' and column_name='new_recurring_amount') = 0, 'ALTER TABLE  `tblupgrades` ADD  `new_recurring_amount` DECIMAL( 10, 2 ) NOT NULL AFTER  `amount`', 'DO 0;');
prepare statement from @query;
execute statement;
deallocate prepare statement;

set @query = if ((select count(*) from information_schema.columns where table_schema=database() and table_name='tblupgrades' and column_name='credit_amount') = 0, 'ALTER TABLE  `tblupgrades` ADD  `credit_amount` DECIMAL( 10, 2 ) NOT NULL AFTER  `amount`', 'DO 0;');
prepare statement from @query;
execute statement;
deallocate prepare statement;

set @query = if ((select count(*) from information_schema.columns where table_schema=database() and table_name='tblupgrades' and column_name='days_remaining') = 0, 'ALTER TABLE  `tblupgrades` ADD  `days_remaining` INT( 4 ) NOT NULL AFTER  `credit_amount`', 'DO 0;');
prepare statement from @query;
execute statement;
deallocate prepare statement;

set @query = if ((select count(*) from information_schema.columns where table_schema=database() and table_name='tblupgrades' and column_name='total_days_in_cycle') = 0, 'ALTER TABLE  `tblupgrades` ADD  `total_days_in_cycle` INT( 4 ) NOT NULL AFTER  `days_remaining`', 'DO 0;');
prepare statement from @query;
execute statement;
deallocate prepare statement;

UPDATE tblupgrades SET userid = (SELECT userid FROM tblhosting WHERE tblhosting.id = tblupgrades.relid) WHERE type='package';

-- update custom field 'bool' values to be on or empty
UPDATE `tblcustomfields` SET `adminonly` = 'on' where `adminonly` = '1';
UPDATE `tblcustomfields` SET `adminonly` = '' where `adminonly` = '0';
UPDATE `tblcustomfields` SET `required` = 'on' where `required` = '1';
UPDATE `tblcustomfields` SET `required` = '' where `required` = '0';
UPDATE `tblcustomfields` SET `showorder` = 'on' where `showorder` = '1';
UPDATE `tblcustomfields` SET `showorder` = '' where `showorder` = '0';
UPDATE `tblcustomfields` SET `showinvoice` = 'on' where `showinvoice` = '1';
UPDATE `tblcustomfields` SET `showinvoice` = '' where `showinvoice` = '0';
