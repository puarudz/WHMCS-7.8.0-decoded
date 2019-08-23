-- Create the updater history table.
CREATE TABLE `tblupdatehistory` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `admin_id` int(11) NOT NULL,
  `original_version` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `new_version` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `success` tinyint(1) NOT NULL,
  `message` text COLLATE utf8_unicode_ci,
  `created_at` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00',
  `updated_at` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- Add timestamps to mail templates.
-- Set utf8_unicode_ci collation to existing mail templates.
ALTER TABLE `tblemailtemplates`
  ADD `created_at` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00' AFTER `plaintext`,
  ADD `updated_at` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00' AFTER `created_at`;

-- Add timestamps to configuration entries.
-- Set utf8_unicode_ci collation to existing settings.
ALTER TABLE `tblconfiguration`
  CHANGE COLUMN `value` `value` TEXT COLLATE utf8_unicode_ci NOT NULL,
  ADD `created_at` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00' AFTER `value`,
  ADD `updated_at` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00' AFTER `created_at`;

ALTER TABLE `tblconfiguration` DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- Add timestamps to clients.
-- Set utf8_unicode_ci collation to existing clients.
ALTER TABLE `tblclients`
  ADD `created_at` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00' AFTER `overrideautoclose`,
  ADD `updated_at` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00' AFTER `created_at`;

-- Add timestamps to network issues.
ALTER TABLE `tblnetworkissues` ADD COLUMN `created_at` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00';
ALTER TABLE `tblnetworkissues` ADD COLUMN `updated_at` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00';

-- Add port field to servers table.
ALTER TABLE  `tblservers` ADD  `port` INT( 8 ) NULL DEFAULT NULL AFTER  `secure` ;

-- Set default price of a domain registration to -1 where price is currently 0 --
UPDATE tblpricing SET `msetupfee` = '-1.00' WHERE `msetupfee` = '0.00' AND `type` = 'domainregister';
UPDATE tblpricing SET `qsetupfee` = '-1.00' WHERE `qsetupfee` = '0.00' AND `type` = 'domainregister';
UPDATE tblpricing SET `ssetupfee` = '-1.00' WHERE `ssetupfee` = '0.00' AND `type` = 'domainregister';
UPDATE tblpricing SET `asetupfee` = '-1.00' WHERE `asetupfee` = '0.00' AND `type` = 'domainregister';
UPDATE tblpricing SET `bsetupfee` = '-1.00' WHERE `bsetupfee` = '0.00' AND `type` = 'domainregister';
UPDATE tblpricing SET `monthly` = '-1.00' WHERE `monthly` = '0.00' AND `type` = 'domainregister';
UPDATE tblpricing SET `quarterly` = '-1.00' WHERE `quarterly` = '0.00' AND `type` = 'domainregister';
UPDATE tblpricing SET `semiannually` = '-1.00' WHERE `semiannually` = '0.00' AND `type` = 'domainregister';
UPDATE tblpricing SET `annually` = '-1.00' WHERE `annually` = '0.00' AND `type` = 'domainregister';
UPDATE tblpricing SET `biennially` = '-1.00' WHERE `biennially` = '0.00' AND `type` = 'domainregister';

-- Increase affiliate maximum limit.
ALTER TABLE `tblaffiliates` MODIFY `id` int(10) NOT NULL AUTO_INCREMENT;

-- Add sever sso restriction field and permissions tables.
CREATE TABLE IF NOT EXISTS `tblserversssoperms` (
  `id` int(10) NOT NULL AUTO_INCREMENT,
  `server_id` int(10) NOT NULL,
  `role_id` int(10) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- Create knowledgebase tags data table.
CREATE TABLE IF NOT EXISTS `tblknowledgebasetags` (
  `id` int(10) NOT NULL AUTO_INCREMENT,
  `articleid` int(10) NOT NULL,
  `tag` varchar(64) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- Add timestamps to products.
ALTER TABLE `tblproducts` ADD COLUMN `created_at` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00';
ALTER TABLE `tblproducts` ADD COLUMN `updated_at` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00';

-- Allow larger product welcome email templates ids and display orders.
ALTER TABLE `tblproducts` CHANGE COLUMN `welcomeemail` `welcomeemail` INT(10) NOT NULL DEFAULT '0';
ALTER TABLE `tblproducts` CHANGE COLUMN `order` `order` INT(10) NOT NULL DEFAULT '0';

-- Allow larger quantities of products in stock.
ALTER TABLE `tblproducts` CHANGE COLUMN `qty` `qty` INT(10) NOT NULL DEFAULT '0';

-- Use integers to store product mail template ids.
ALTER TABLE `tblproducts` CHANGE COLUMN `autoterminateemail` `autoterminateemail` INT(10) NOT NULL DEFAULT '0';
ALTER TABLE `tblproducts` CHANGE COLUMN `upgradeemail` `upgradeemail` INT(10) NOT NULL DEFAULT '0';

-- Add timestamps to product groups.
ALTER TABLE `tblproductgroups` ADD COLUMN `created_at` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00';
ALTER TABLE `tblproductgroups` ADD COLUMN `updated_at` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00';

-- Add timestamps to downloads.
ALTER TABLE `tbldownloads` ADD COLUMN `created_at` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00';
ALTER TABLE `tbldownloads` ADD COLUMN `updated_at` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00';

-- Add timestamps to download categoriess.
ALTER TABLE `tbldownloadcats` ADD COLUMN `created_at` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00';
ALTER TABLE `tbldownloadcats` ADD COLUMN `updated_at` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00';

-- Link products to downloads via table.
CREATE TABLE `tblproduct_downloads` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `product_id` int(10) NOT NULL,
  `download_id` int(10) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00',
  `updated_at` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00',
  PRIMARY KEY (`id`),
  KEY `tblproduct_downloads_product_id_index` (`product_id`),
  KEY `tblproduct_downloads_download_id_index` (`download_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- Link products to their upgrade products via table.
CREATE TABLE `tblproduct_upgrade_products` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `product_id` int(10) NOT NULL,
  `upgrade_product_id` int(10) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00',
  `updated_at` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00',
  PRIMARY KEY (`id`),
  KEY `tblproduct_upgrade_products_product_id_index` (`product_id`),
  KEY `tblproduct_upgrade_products_upgrade_product_id_index` (`upgrade_product_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- Index various columns
ALTER TABLE `tblinvoiceitems` CHANGE `type` `type` VARCHAR(30) NOT NULL;
ALTER TABLE `tblinvoiceitems` ADD INDEX (`userid`, `type`, `relid`);
ALTER TABLE `tblactivitylog` ADD INDEX (`userid`);
ALTER TABLE `tbltickets` ADD INDEX (`did`);
ALTER TABLE `tbltickets` CHANGE `status` `status` VARCHAR(64) NOT NULL;
ALTER TABLE `tblticketstatuses` CHANGE `title` `title` VARCHAR(64) NOT NULL;

-- Add timestamps to domains
ALTER TABLE `tbldomains` ADD COLUMN `created_at` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00';
ALTER TABLE `tbldomains` ADD COLUMN `updated_at` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00';
ALTER TABLE `tbldomainsadditionalfields` ADD COLUMN `created_at` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00';
ALTER TABLE `tbldomainsadditionalfields` ADD COLUMN `updated_at` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00';

-- Add timestamps to services
ALTER TABLE `tblhosting` ADD COLUMN `created_at` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00';
ALTER TABLE `tblhosting` ADD COLUMN `updated_at` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00';

-- Add timestamps to announcements
ALTER TABLE `tblannouncements` ADD COLUMN `created_at` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00';
ALTER TABLE `tblannouncements` ADD COLUMN `updated_at` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00';

-- Adjust tid length in tbltickets
ALTER TABLE `tbltickets` MODIFY `tid` VARCHAR(128);

-- Add new quotes permission to the subaccounts permission where the existing invoices permission is present.
UPDATE `tblcontacts` SET `permissions` = CONCAT(`permissions`, ',quotes') WHERE `subaccount` = 1 AND `permissions` LIKE '%invoices%';

-- Add the TLD table.
CREATE TABLE `tbltlds` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `tld` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00',
  `updated_at` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- Add the TLD category table.
CREATE TABLE `tbltld_categories` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `category` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `is_primary` tinyint(1) NOT NULL DEFAULT 0,
  `display_order` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00',
  `updated_at` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- Add the linker between TLD and category.
CREATE TABLE `tbltld_category_pivot` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `tld_id` int(11) NOT NULL,
  `category_id` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00',
  `updated_at` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00',
  PRIMARY KEY (`id`),
  KEY `tbltld_category_pivot_tld_id_index` (`tld_id`),
  KEY `tbltld_category_pivot_category_id_index` (`category_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- Add new permission for WHMCS Connect:
INSERT INTO `tbladminperms` (`roleid`, `permid`) VALUES ('1', '131');

-- Add timestamps to service addons
ALTER TABLE `tblhostingaddons` ADD COLUMN `created_at` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00';
ALTER TABLE `tblhostingaddons` ADD COLUMN `updated_at` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00';

-- Add timestamps to client contacts
ALTER TABLE `tblcontacts` ADD COLUMN `created_at` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00';
ALTER TABLE `tblcontacts` ADD COLUMN `updated_at` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00';

-- Add timestamps to cancellation requests
ALTER TABLE `tblcancelrequests` ADD COLUMN `created_at` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00';
ALTER TABLE `tblcancelrequests` ADD COLUMN `updated_at` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00';

-- Add timestamps to quote items
ALTER TABLE `tblquoteitems` ADD COLUMN `created_at` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00';
ALTER TABLE `tblquoteitems` ADD COLUMN `updated_at` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00';

-- Add timestamps to affiliates
ALTER TABLE `tblaffiliates` ADD COLUMN `created_at` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00';
ALTER TABLE `tblaffiliates` ADD COLUMN `updated_at` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00';

-- Add timestamps to security questions
ALTER TABLE `tbladminsecurityquestions` ADD COLUMN `created_at` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00';
ALTER TABLE `tbladminsecurityquestions` ADD COLUMN `updated_at` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00';

-- Add unique identifier column for clients and admins
ALTER TABLE `tblclients` ADD COLUMN `uuid` VARCHAR(255) NOT NULL DEFAULT '' AFTER `id`;
ALTER TABLE `tbladmins` ADD COLUMN `uuid` VARCHAR(255) NOT NULL DEFAULT '' AFTER `id`;
