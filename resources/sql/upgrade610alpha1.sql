-- Create the product group feature table
CREATE TABLE IF NOT EXISTS `tblproduct_group_features` (
    `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
    `product_group_id` int(10) NOT NULL,
    `feature` text COLLATE utf8_unicode_ci NOT NULL,
    `order` int(11) NOT NULL DEFAULT '0',
    `created_at` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00',
    `updated_at` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00',
    PRIMARY KEY (`id`),
    KEY `tblproduct_group_features_product_group_id_index` (`product_group_id`),
    KEY `tblproduct_group_features_id_product_group_id_index` (`id`,`product_group_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- Add the headline column to tblproductgroups
set @query = if ((select count(*) from information_schema.columns where table_schema=database() and table_name='tblproductgroups' and column_name='headline') = 0, 'alter table tblproductgroups add `headline` text after `name`', 'DO 0');
prepare statement from @query;
execute statement;
deallocate prepare statement;

-- Add the tagline column to tblproductgroups
set @query = if ((select count(*) from information_schema.columns where table_schema=database() and table_name='tblproductgroups' and column_name='tagline') = 0, 'alter table tblproductgroups add `tagline` text after `headline`', 'DO 0');
prepare statement from @query;
execute statement;
deallocate prepare statement;

-- Add the is_featured column to tblproducts
set @query = if ((select count(*) from information_schema.columns where table_schema=database() and table_name='tblproducts' and column_name='is_featured') = 0, 'alter table tblproducts add `is_featured` tinyint(1) not null after `retired`', 'DO 0');
prepare statement from @query;
execute statement;
deallocate prepare statement;

-- Add the is_featured column to tblbundles
set @query = if ((select count(*) from information_schema.columns where table_schema=database() and table_name='tblbundles' and column_name='is_featured') = 0, 'alter table tblbundles add `is_featured` tinyint(1) not null after `sortorder`', 'DO 0');
prepare statement from @query;
execute statement;
deallocate prepare statement;

-- Add the Health and Updates permission to Full Admin role
INSERT INTO tbladminperms (`roleid`, `permid`) VALUES (1, 132);

-- Set order form sidebar toggle setting to on by default
INSERT INTO tblconfiguration (setting, value) VALUES ('OrderFormSidebarToggle', '1');
