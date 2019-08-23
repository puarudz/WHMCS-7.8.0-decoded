-- Add the created_at column to tblcustomfields
set @query = if ((select count(*) from information_schema.columns where table_schema=database() and table_name='tblcustomfields' and column_name='created_at') = 0, 'alter table tblcustomfields add `created_at` timestamp NOT NULL DEFAULT \'0000-00-00 00:00:00\' AFTER `sortorder`', 'DO 0');
prepare statement from @query;
execute statement;
deallocate prepare statement;

-- Add the updated_at column to tblcustomfields
set @query = if ((select count(*) from information_schema.columns where table_schema=database() and table_name='tblcustomfields' and column_name='updated_at') = 0, 'alter table tblcustomfields add `updated_at` timestamp NOT NULL DEFAULT \'0000-00-00 00:00:00\' AFTER `created_at`', 'DO 0');
prepare statement from @query;
execute statement;
deallocate prepare statement;

-- attachments to ticket notes
set @query = if ((select count(*) from information_schema.columns where table_schema=database() and table_name='tblticketnotes' and column_name='attachments') = 0, 'ALTER TABLE tblticketnotes ADD `attachments` TEXT NOT NULL AFTER `message`', 'DO 0');
prepare statement from @query;
execute statement;
deallocate prepare statement;

-- merged_ticket_id to tbltickets
set @query = if ((select count(*) from information_schema.columns where table_schema=database() and table_name='tbltickets' and column_name='merged_ticket_id') = 0, 'ALTER TABLE tbltickets ADD `merged_ticket_id` integer NOT NULL DEFAULT 0', 'DO 0');
prepare statement from @query;
execute statement;
deallocate prepare statement;

set @query = if ((select count(*) from information_schema.STATISTICS where table_schema=database() and table_name='tbltickets' and index_name='merged_ticket_id') = 0, 'CREATE INDEX `merged_ticket_id` ON `tbltickets` (`merged_ticket_id`,`id`)', 'DO 0');
prepare statement from @query;
execute statement;
deallocate prepare statement;

-- Add email_verified to tblclients.
set @query = if (
  (select count(*) from information_schema.columns where table_schema=database() and table_name='tblclients' and column_name='email_verified') = 0,
    'ALTER TABLE `tblclients` add `email_verified` tinyint(1) NOT NULL DEFAULT 0 AFTER `allow_sso`',
    'DO 0'
);
prepare statement from @query;
execute statement;
deallocate prepare statement;

-- Insert Email Verification email template.
set @query = if (
  (select count(*) from tblemailtemplates where name = 'Client Email Address Verification') = 0,
    'INSERT INTO `tblemailtemplates` (`type`, `name`, `subject`, `message`) VALUES (\'general\', \'Client Email Address Verification\', \'Confirm Your Registration\', \'<p>Dear {$client_name},</p><p>Thank you for creating a {$companyname} account.</p><p>Please visit the link below and sign into your account to verify your email address and complete your registration.</p><p>{$client_email_verification_link}</p><p>You are receiving this email because you recently created an account or changed your email address. If you did not do this, please contact us.</p><p>{$signature}</p>\')',
    'DO 0'
);
prepare statement from @query;
execute statement;
deallocate prepare statement;

-- Create translations table
CREATE TABLE IF NOT EXISTS `tbldynamic_translations` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `related_type` enum('configurable_option.{id}.name','configurable_option_option.{id}.name','custom_field.{id}.description','custom_field.{id}.name','download.{id}.description','download.{id}.title','product.{id}.description','product.{id}.name','product_addon.{id}.description','product_addon.{id}.name','product_bundle.{id}.description','product_bundle.{id}.name','product_group.{id}.headline','product_group.{id}.name','product_group.{id}.tagline','product_group_features.{id}.feature','ticket_department.{id}.description','ticket_department.{id}.name') NOT NULL,
  `related_id` int(10) unsigned NOT NULL DEFAULT 0,
  `language` varchar(16) NOT NULL DEFAULT '',
  `translation` text NOT NULL,
  `input_type` enum('text','textarea') NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00',
  `updated_at` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00',
  PRIMARY KEY (`id`),
  KEY `tbldynamic_translations_id` (`related_id`),
  KEY `tbldynamic_translations_type` (`related_type`),
  KEY `tbldynamic_translations_id_type` (`related_id`, `related_type`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

CREATE TABLE IF NOT EXISTS `tblticket_watchers` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `ticket_id` int(10) unsigned NOT NULL DEFAULT 0,
  `admin_id` int(10) unsigned NOT NULL DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00',
  `updated_at` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00',
  PRIMARY KEY (`id`),
  UNIQUE KEY `admin_ticket_unique` (`ticket_id`,`admin_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- Remove partial index and replace with full column index so its used more often CORE-6368
DROP INDEX  `status` on `tbltickets`;
CREATE INDEX `status` ON `tbltickets` (status);
