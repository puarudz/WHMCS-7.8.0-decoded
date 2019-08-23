ALTER TABLE `tbladmins` ADD `homewidgets` TEXT NOT NULL ;
ALTER TABLE `tbladminroles` ADD `widgets` TEXT NOT NULL AFTER `name` ;
UPDATE `tbladminroles` SET `widgets`='activity_log,getting_started,income_forecast,income_overview,my_notes,network_status,open_invoices,orders_overview,paypal_addon,admin_activity,client_activity,system_overview,todo_list,whmcs_news' WHERE `id`=1;
UPDATE `tbladminroles` SET `widgets`='activity_log,getting_started,income_forecast,income_overview,my_notes,network_status,open_invoices,orders_overview,paypal_addon,client_activity,todo_list,whmcs_news' WHERE `id`=2;
UPDATE `tbladminroles` SET `widgets`='activity_log,getting_started,my_notes,todo_list,whmcs_news' WHERE `id`=3;

INSERT INTO `tblemailtemplates` (`type`, `name`, `subject`, `message`, `plaintext`) VALUES ('admin', 'Support Ticket Department Reassigned', '[Ticket ID: {$ticket_tid}] Support Ticket Department Reassigned', '<p>The department this ticket is assigned to has been changed to a department you are a member of.</p><p>Client: {$client_name}{if $client_id} #{$client_id}{/if}<br />Department: {$ticket_department}<br />Subject: {$ticket_subject}<br />Priority: {$ticket_priority}</p><p>---<br />{$ticket_message}<br />---</p><p>You can respond to this ticket by simply replying to this email or through the admin area at the url below.</p><p><a href="{$whmcs_admin_url}supporttickets.php?action=viewticket&id={$ticket_id}">{$whmcs_admin_url}supporttickets.php?action=viewticket&id={$ticket_id}</a></p>', '0');

INSERT INTO `tblconfiguration` ( `setting` , `value` ) VALUES ( 'AttachmentThumbnails','on' );

ALTER TABLE `tblaffiliates` ADD `onetime` INT(1) NOT NULL AFTER `payamount` ;
ALTER TABLE `tblaffiliateshistory` ADD `description` TEXT NOT NULL AFTER `affaccid` ;

ALTER TABLE `tblproducts` ADD `recurringcycles` INT(2) NOT NULL AFTER `freedomaintlds` ;
ALTER TABLE `tblproducts` ADD `allowqty` INT(1) NOT NULL AFTER `paytype`;

INSERT INTO `tblemailtemplates` (`type`, `name`, `subject`, `message`, `plaintext`) VALUES('invoice', 'Invoice Refund Confirmation', 'Invoice Refund Confirmation', '<p>Dear {$client_name},</p>\r\n<p>This is confirmation that a {if $invoice_status eq "Refunded"}full{else}partial{/if} refund has been processed for Invoice #{$invoice_num}</p>\r\n<p>The refund has been {if $invoice_refund_type eq "credit"}credited to your account balance with us{else}returned via the payment method you originally paid with{/if}.</p>\r\n<p>{$invoice_html_contents}</p>\r\n<p>Amount Refunded: {$invoice_last_payment_amount}{if $invoice_last_payment_transid}<br />Transaction #: {$invoice_last_payment_transid}{/if}</p>\r\n<p>You may review your invoice history at any time by logging in to your client area.</p>\r\n<p>{$signature}</p>', 0);

ALTER TABLE `tblservers` ADD `nameserver5` TEXT NOT NULL AFTER `nameserver4ip` ,
 ADD `nameserver5ip` TEXT NOT NULL AFTER `nameserver5`;

CREATE TABLE IF NOT EXISTS `tblemailmarketer` (
  `id` int(10) NOT NULL AUTO_INCREMENT,
  `name` text COLLATE utf8_unicode_ci NOT NULL,
  `type` text COLLATE utf8_unicode_ci NOT NULL,
  `settings` text COLLATE utf8_unicode_ci NOT NULL,
  `disable` int(1) NOT NULL,
  PRIMARY KEY (`id`)
) DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

ALTER TABLE `tblknowledgebase` ADD `order` INT(3) NOT NULL AFTER `private` ;

CREATE TABLE IF NOT EXISTS `tblbundles` (
  `id` int(10) NOT NULL AUTO_INCREMENT,
  `name` text COLLATE utf8_unicode_ci NOT NULL,
  `validfrom` date NOT NULL,
  `validuntil` date NOT NULL,
  `uses` int(4) NOT NULL,
  `maxuses` int(4) NOT NULL,
  `itemdata` text COLLATE utf8_unicode_ci NOT NULL,
  `allowpromo` int(1) NOT NULL,
  PRIMARY KEY (`id`)
) DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

CREATE TABLE IF NOT EXISTS `tblmodulelog` (
  `id` int(10) NOT NULL AUTO_INCREMENT,
  `date` DATETIME NOT NULL,
  `module` text COLLATE utf8_unicode_ci NOT NULL,
  `action` text COLLATE utf8_unicode_ci NOT NULL,
  `request` text COLLATE utf8_unicode_ci NOT NULL,
  `response` text COLLATE utf8_unicode_ci NOT NULL,
  `arrdata` text COLLATE utf8_unicode_ci NOT NULL,
  PRIMARY KEY (`id`)
) DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

INSERT INTO `tbladminperms` (`roleid`, `permid`) VALUES ('1', '101'), ('2', '101'), ('1', '102'), ('1', '103');

INSERT INTO `tblconfiguration` (`setting`, `value`) VALUES('EmailGlobalHeader', '&lt;p&gt;&lt;a href=&quot;{$company_domain}&quot; target=&quot;_blank&quot;&gt;&lt;img src=&quot;{$company_logo_url}&quot; alt=&quot;{$company_name}&quot; border=&quot;0&quot; /&gt;&lt;/a&gt;&lt;/p&gt;');
INSERT INTO `tblconfiguration` (`setting`, `value`) VALUES('EmailGlobalFooter', '');
