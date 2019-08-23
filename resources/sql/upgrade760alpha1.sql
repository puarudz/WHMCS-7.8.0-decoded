-- Add the target_php_version column to tblioncube_file_log
set @query = if ((select count(*) from information_schema.columns where table_schema=database() and table_name='tblioncube_file_log' and column_name='target_php_version') = 0, 'alter table `tblioncube_file_log` add `target_php_version` CHAR(16) NOT NULL DEFAULT \'\'', 'DO 0');
prepare statement from @query;
execute statement;
deallocate prepare statement;

-- update MaxMind Configuration
UPDATE `tblfraud` SET `setting` = 'licenseKey' WHERE `setting` = 'MaxMind License Key' AND `fraud` = 'maxmind';
UPDATE `tblfraud` SET `setting` = 'riskScore' WHERE `setting` = 'MaxMind Fraud Risk Score' AND `fraud` = 'maxmind';
UPDATE `tblfraud` SET `setting` = 'ignoreAddressValidation' WHERE `setting` = 'Do Not Validate Address Information' AND `fraud` = 'maxmind';
UPDATE `tblfraud` SET `setting` = 'rejectFreeEmail' WHERE `setting` = 'Reject Free Email Service' AND `fraud` = 'maxmind';
UPDATE `tblfraud` SET `setting` = 'rejectCountryMismatch' WHERE `setting` = 'Reject Country Mismatch' AND `fraud` = 'maxmind';
UPDATE `tblfraud` SET `setting` = 'rejectAnonymousNetwork' WHERE `setting` = 'Reject Anonymous Proxy' AND `fraud` = 'maxmind';
UPDATE `tblfraud` SET `setting` = 'rejectHighRiskCountry' WHERE `setting` = 'Reject High Risk Country' AND `fraud` = 'maxmind';
DELETE FROM `tblfraud` WHERE `setting` IN ('Use New Risk Score', 'Service Type', 'Force Phone Verify Products') AND `fraud` = 'maxmind';

-- Change enum to include bbcode for editor column in tbltickets
ALTER TABLE `tbltickets` CHANGE `editor` `editor` ENUM('plain','markdown','bbcode') CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL DEFAULT 'plain';
