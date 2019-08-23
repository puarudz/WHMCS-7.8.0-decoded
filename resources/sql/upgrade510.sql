INSERT INTO tblconfiguration (setting,value) VALUES ('DomainSyncEnabled','on');
INSERT INTO tblconfiguration (setting,value) VALUES ('DomainSyncNextDueDate','');
INSERT INTO tblconfiguration (setting,value) VALUES ('DomainSyncNextDueDateDays','0');
ALTER TABLE `tbldomains` ADD `synced` INT( 1 ) NOT NULL;

ALTER TABLE `tblnotes` ADD `sticky` INT( 1 ) NOT NULL;

ALTER TABLE `tblproducts` CHANGE  `overagesenabled`  `overagesenabled` VARCHAR( 10 ) NOT NULL;

ALTER TABLE `tblproducts` ADD `retired` INT( 1 ) NOT NULL;

INSERT INTO `tblconfiguration` (`setting`, `value`) VALUES ('TicketMask', '%n%n%n%n%n%n');
ALTER TABLE  `tbltickets` CHANGE  `tid`  `tid` VARCHAR( 15 ) NOT NULL;

INSERT INTO `tblemailtemplates` (`id`, `type`, `name`, `subject`, `message`, `attachments`, `fromname`, `fromemail`, `disabled`, `custom`, `language`, `copyto`, `plaintext`) VALUES (NULL, 'admin', 'New Cancellation Request', 'New Cancellation Request', '<p>A new cancellation request has been submitted.</p><p>Client ID: {$client_id}<br>Client Name: {$clientname}<br>Service ID: {$service_id}<br>Product Name: {$product_name}<br>Cancellation Type: {$service_cancellation_type}<br>Cancellation Reason: {$service_cancellation_reason}</p><p>{$whmcs_admin_link}</p>', '', '', '', '', '', '', '', '0');
UPDATE `tblemailtemplates` SET `message` = '<p>Dear {$client_name},</p><p>This email is to confirm that we have received your cancellation request for the service listed below.</p><p>Product/Service: {$service_product_name}<br />Domain: {$service_domain}</p><p>{if $service_cancellation_type=="Immediate"}The service will be terminated within the next 24 hours.{else}The service will be cancelled at the end of your current billing period on {$service_next_due_date}.{/if}</p><p>Thank you for using {$company_name} and we hope to see you again in the future.</p><p>{$signature}</p>' WHERE `name` = 'Cancellation Request Confirmation';

INSERT INTO `tblemailtemplates` (`id`, `type`, `name`, `subject`, `message`, `attachments`, `fromname`, `fromemail`, `disabled`, `custom`, `language`, `copyto`, `plaintext`) VALUES (NULL, 'admin', 'Support Ticket Flagged', 'New Support Ticket Flagged to You', '<p>A new support ticket has been flagged to you.</p><p>Ticket #: {$ticket_tid}<br>Client Name: {$client_name} (ID {$client_id})<br>Department: {$ticket_department}<br>Subject: {$ticket_subject}<br>Priority: {$ticket_priority}</p><p>----------------------<br />{$ticket_message}<br />----------------------</p><p>{$whmcs_admin_link}</p>', '', '', '', '', '', '', '', '0');

ALTER TABLE  `tbladmins` ADD  `authmodule` TEXT NOT NULL AFTER  `password` , ADD  `authdata` TEXT NOT NULL AFTER  `authmodule`;

INSERT INTO `tblemailtemplates` (`id`, `type`, `name`, `subject`, `message`, `attachments`, `fromname`, `fromemail`, `disabled`, `custom`, `language`, `copyto`, `plaintext`) VALUES (NULL, 'domain', 'Domain Transfer Failed', 'Domain Transfer Failed: {$domain_name}', '<p>Dear {$client_name},</p><p>Recently you placed a domain transfer order with us but unfortunately it has failed. The reason for the failure if available is shown below so once this has been resolved, please contact us to re-attempt the transfer.</p><p>Domain: {$domain_name}<br>Failure Reason: {$domain_transfer_failure_reason}</p><p>If you have any questions, please open a support ticket from our client area @ {$whmcs_link}</p><p>{$signature}</p>', '', '', '', '', '', '', '', 0);

ALTER TABLE  `tbldomains` ADD `reminders` TEXT NOT NULL AFTER `donotrenew`;
UPDATE `tblemailtemplates` SET `message` = '<p>Dear {$client_name},</p><p>{if $days_until_expiry}The domain(s) listed below are due to expire within the next {$days_until_expiry} days.{else}The domain(s) listed below are going to expire in {$domain_days_until_expiry} days. Renew now before it\'s too late...{/if}</p><p>{if $expiring_domains}{foreach from=$expiring_domains item=domain}{$domain.name} - {$domain.nextduedate} <strong>({$domain.days} Days)</strong><br />{/foreach}{else}{$domain_name} - {$domain_next_due_date} <strong>({$domain_days_until_nextdue} Days)</strong>{/if}</p><p>To ensure the domain does not expire, you should renew it now. You can do this from the domains management section of our client area here: {$whmcs_link}</p><p>Should you allow the domain to expire, you will be able to renew it for up to 30 days after the renewal date. During this time, the domain will not be accessible so any web site or email services associated with it will stop working.</p><p>{$signature}</p>' WHERE `name` = 'Upcoming Domain Renewal Notice';

CREATE TABLE IF NOT EXISTS `tblorderstatuses` (
  `id` int(10) NOT NULL AUTO_INCREMENT,
  `title` text NOT NULL,
  `color` text NOT NULL,
  `showpending` int(1) NOT NULL,
  `showactive` int(1) NOT NULL,
  `showcancelled` int(1) NOT NULL,
  `sortorder` int(2) NOT NULL,
  PRIMARY KEY (`id`)
)  DEFAULT  CHARSET=utf8 COLLATE=utf8_unicode_ci;
INSERT INTO `tblorderstatuses` (`id`, `title`, `color`, `showpending`, `showactive`, `showcancelled`, `sortorder`) VALUES
(1, 'Pending', '#cc0000', 1, 0, 0, 10),
(2, 'Active', '#779500', 0, 1, 0, 20),
(3, 'Cancelled', '#888888', 0, 0, 1, 30),
(4, 'Fraud', '#000000', 0, 0, 0, 40);
INSERT INTO `tbladminperms` (`roleid`, `permid`) VALUES ('1', '122');

ALTER TABLE  `tblpromotions` ADD `lifetimepromo` INT(1) NOT NULL AFTER `uses`;

ALTER TABLE  `tblquotes` ADD  `datesent` DATE NOT NULL , ADD  `dateaccepted` DATE NOT NULL ;

UPDATE `tblemailtemplates` SET `message` = '<p>Dear {$client_name},</p><p>Here is the quote you requested for {$quote_subject}. The quote is valid until {$quote_valid_until}. You may {if $client_id}view the quote at any time at {$quote_link} and {/if}simply reply to this email with any further questions or requirement.</p><p>{$signature}</p>' WHERE `name` = 'Quote Delivery with PDF';
INSERT INTO `tblemailtemplates` (`id`, `type`, `name`, `subject`, `message`, `attachments`, `fromname`, `fromemail`, `disabled`, `custom`, `language`, `copyto`, `plaintext`) VALUES (NULL, 'general', 'Quote Accepted', 'Quote #{$quote_number} Accepted - {$quote_subject}', '<p>\r\nDear {$client_name}, \r\n</p>\r\n<p>\r\nThis is a confirmation that quote generated on {$quote_date_created} has been accepted by you and invoice # {$invoice_num} is generated.\r\n<p>\r\n{$signature} \r\n</p>', '', '', '', '', '', '', '', '0');
INSERT INTO `tblemailtemplates` (`id`, `type`, `name`, `subject`, `message`, `attachments`, `fromname`, `fromemail`, `disabled`, `custom`, `language`, `copyto`, `plaintext`) VALUES (NULL, 'general', 'Quote Accepted Notification', 'Quote #{$quote_number} Accepted - {$quote_subject}', '<p>A quote has been accepted by the following customer.</p><p><strong>Customer Information</strong></p>\r\n<p>Customer ID: {$client_id}<br />\r\nName: {$clientname}<br />\r\nEmail: {$client_email}<br />\r\nCompany: {$client_company_name}<br />\r\nAddress 1: {$client_address1}<br />\r\nAddress 2: {$client_address2}<br />\r\nCity: {$client_city}<br />\r\nState: {$client_state}<br />\r\nPostcode: {$client_postcode}<br />\r\nCountry: {$client_country}<br />\r\nPhone Number: {$client_phonenumber}</p>\r\n<p><strong>Quote Information</strong></p>\r\n<p>Quote #: {$quote_number}<br />\r\nQuote Subject: {$quote_subject}</p><p><a href="{$whmcs_admin_url}quotes.php?action=manage&id={$quote_number}">{$whmcs_admin_url}quotes.php?action=manage&id={$quote_number}</a></p>', '', '', '', '', '', '', '', 0);
