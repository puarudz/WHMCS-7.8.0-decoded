-- Remove table structure for table `tbltask_schedule`
DROP TABLE IF EXISTS `tbltask_schedule`;

-- Remove table structure for table `tbltask_mutex`
DROP TABLE IF EXISTS `tbltask_mutex`;

-- Remove table structure for table `tbllog_task`
DROP TABLE IF EXISTS `tbllog_task`;


-- Table structure for table `tbltask`

DROP TABLE IF EXISTS `tbltask`;
CREATE TABLE `tbltask` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `priority` int(11) NOT NULL DEFAULT '0',
  `class_name` varchar(255) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  `is_enabled` tinyint(4) NOT NULL DEFAULT '1',
  `is_periodic` tinyint(4) NOT NULL DEFAULT '1',
  `frequency` int(10) unsigned NOT NULL DEFAULT '0',
  `name` varchar(255) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  `description` text COLLATE utf8_unicode_ci NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00',
  `updated_at` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=23 DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

LOCK TABLES `tbltask` WRITE;
INSERT INTO `tbltask` (`id`,`priority`,`class_name`,`is_enabled`,`is_periodic`,`frequency`,`name`,`description`,`created_at`,`updated_at`) VALUES (1,1530,'WHMCS\\Cron\\Task\\AddLateFees',1,1,1440,'Late Fees','Apply Late Fees','2016-11-02 16:59:20','2016-11-02 16:59:20'),(2,1620,'WHMCS\\Cron\\Task\\AffiliateCommissions',1,1,1440,'Delayed Affiliate Commissions','Process Delayed Affiliate Commissions','2016-11-02 16:59:20','2016-11-02 16:59:20'),(3,1630,'WHMCS\\Cron\\Task\\AffiliateReports',1,1,43200,'Affiliate Reports','Send Monthly Affiliate Reports','2016-11-02 16:59:20','2016-11-02 16:59:20'),(4,1680,'WHMCS\\Cron\\Task\\AutoClientStatusSync',1,1,1440,'Client Status Update','Synchronise Client Status','2016-11-02 16:59:20','2016-11-02 16:59:20'),(5,1590,'WHMCS\\Cron\\Task\\AutoTerminations',1,1,1440,'Overdue Terminations','Process Overdue Terminations','2016-11-02 16:59:20','2016-11-02 16:59:20'),(6,1570,'WHMCS\\Cron\\Task\\CancellationRequests',1,1,1440,'Cancellation Requests','Process Cancellation Requests','2016-11-02 16:59:20','2016-11-02 16:59:20'),(7,2000,'WHMCS\\Cron\\Task\\CheckForWhmcsUpdate',1,1,480,'WHMCS Updates','Check for WHMCS Software Updates','2016-11-02 16:59:20','2016-11-02 16:59:20'),(8,1610,'WHMCS\\Cron\\Task\\CloseInactiveTickets',1,1,1440,'Inactive Tickets','Auto Close Inactive Tickets','2016-11-02 16:59:20','2016-11-02 16:59:20'),(9,1520,'WHMCS\\Cron\\Task\\CreateInvoices',1,1,1440,'Invoices','Generate Invoices','2016-11-02 16:59:20','2016-11-02 16:59:20'),(10,1650,'WHMCS\\Cron\\Task\\CreditCardExpiryNotices',1,1,43200,'Credit Card Expiry Notices','Sending Credit Card Expiry Reminders','2016-11-02 16:59:20','2016-11-02 16:59:20'),(11,1500,'WHMCS\\Cron\\Task\\CurrencyUpdateExchangeRates',1,1,1440,'Currency Exchange Rates','Update Currency Exchange Rates','2016-11-02 16:59:20','2016-11-02 16:59:20'),(12,1510,'WHMCS\\Cron\\Task\\CurrencyUpdateProductPricing',1,1,1440,'Product Pricing Updates','Update Product Prices for Current Rates','2016-11-02 16:59:20','2016-11-02 16:59:20'),(13,1560,'WHMCS\\Cron\\Task\\DomainRenewalNotices',1,1,1440,'Domain Renewal Notices','Processing Domain Renewal Notices','2016-11-02 16:59:20','2016-11-02 16:59:20'),(14,1640,'WHMCS\\Cron\\Task\\EmailMarketer',1,1,1440,'Email Marketer Rules','Process Email Marketer Rules','2016-11-02 16:59:20','2016-11-02 16:59:20'),(15,1600,'WHMCS\\Cron\\Task\\FixedTermTerminations',1,1,1440,'Fixed Term Terminations','Process Fixed Term Terminations','2016-11-02 16:59:20','2016-11-02 16:59:20'),(16,1550,'WHMCS\\Cron\\Task\\InvoiceReminders',1,1,1440,'Invoice & Overdue Reminders','Generate daily reminders for unpaid and overdue invoice','2016-11-02 16:59:20','2016-11-02 16:59:20'),(17,1670,'WHMCS\\Cron\\Task\\OverageBilling',1,1,43200,'Overage Billing Charges','Process Overage Billing Charges','2016-11-02 16:59:20','2016-11-02 16:59:20'),(18,1540,'WHMCS\\Cron\\Task\\ProcessCreditCardPayments',1,1,1440,'Credit Card Charges','Process Credit Card Charges','2016-11-02 16:59:20','2016-11-02 16:59:20'),(19,1580,'WHMCS\\Cron\\Task\\AutoSuspensions',1,1,1440,'Overdue Suspensions','Process Overdue Suspensions','2016-11-02 16:59:21','2016-11-02 16:59:21'),(20,1700,'WHMCS\\Cron\\Task\\TicketEscalations',1,1,3,'Ticket Escalation Rules','Process and escalate tickets per any Escalation Rules','2016-11-02 16:59:21','2016-11-02 16:59:21'),(21,1690,'WHMCS\\Cron\\Task\\UpdateDomainExpiryStatus',1,1,1440,'Domain Expiry','Update Domain Expiry Status','2016-11-02 16:59:21','2016-11-02 16:59:21'),(22,1660,'WHMCS\\Cron\\Task\\UpdateServerUsage',1,1,1440,'Server Usage Stats','Updating Disk & Bandwidth Usage Stats','2016-11-02 16:59:21','2016-11-02 16:59:21');
UNLOCK TABLES;

-- Table structure for table `tbltask_status`

DROP TABLE IF EXISTS `tbltask_status`;
CREATE TABLE `tbltask_status` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `task_id` int(10) unsigned NOT NULL,
  `in_progress` tinyint(4) NOT NULL DEFAULT '0',
  `last_run` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00',
  `next_due` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00',
  `created_at` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00',
  `updated_at` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=23 DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

LOCK TABLES `tbltask_status` WRITE;
INSERT INTO `tbltask_status` (`id`,`task_id`,`in_progress`,`last_run`,`next_due`,`created_at`,`updated_at`) VALUES (1,1,0,'0000-00-00 00:00:00','2016-11-02 16:59:23','2016-11-02 16:59:20','2016-11-02 16:59:23'),(2,2,0,'0000-00-00 00:00:00','2016-11-02 16:59:23','2016-11-02 16:59:20','2016-11-02 16:59:23'),(3,3,0,'0000-00-00 00:00:00','2016-11-02 16:59:23','2016-11-02 16:59:20','2016-11-02 16:59:23'),(4,4,0,'0000-00-00 00:00:00','2016-11-02 16:59:23','2016-11-02 16:59:20','2016-11-02 16:59:23'),(5,5,0,'0000-00-00 00:00:00','2016-11-02 16:59:23','2016-11-02 16:59:20','2016-11-02 16:59:23'),(6,6,0,'0000-00-00 00:00:00','2016-11-02 16:59:23','2016-11-02 16:59:20','2016-11-02 16:59:23'),(7,7,0,'0000-00-00 00:00:00','2016-11-02 16:59:23','2016-11-02 16:59:20','2016-11-02 16:59:23'),(8,8,0,'0000-00-00 00:00:00','2016-11-02 16:59:23','2016-11-02 16:59:20','2016-11-02 16:59:23'),(9,9,0,'0000-00-00 00:00:00','2016-11-02 16:59:23','2016-11-02 16:59:20','2016-11-02 16:59:23'),(10,10,0,'0000-00-00 00:00:00','2016-11-02 16:59:23','2016-11-02 16:59:20','2016-11-02 16:59:23'),(11,11,0,'0000-00-00 00:00:00','2016-11-02 16:59:23','2016-11-02 16:59:20','2016-11-02 16:59:23'),(12,12,0,'0000-00-00 00:00:00','2016-11-02 16:59:23','2016-11-02 16:59:20','2016-11-02 16:59:23'),(13,13,0,'0000-00-00 00:00:00','2016-11-02 16:59:23','2016-11-02 16:59:20','2016-11-02 16:59:23'),(14,14,0,'0000-00-00 00:00:00','2016-11-02 16:59:23','2016-11-02 16:59:20','2016-11-02 16:59:23'),(15,15,0,'0000-00-00 00:00:00','2016-11-02 16:59:23','2016-11-02 16:59:20','2016-11-02 16:59:23'),(16,16,0,'0000-00-00 00:00:00','2016-11-02 16:59:23','2016-11-02 16:59:20','2016-11-02 16:59:23'),(17,17,0,'0000-00-00 00:00:00','2016-11-02 16:59:23','2016-11-02 16:59:20','2016-11-02 16:59:23'),(18,18,0,'0000-00-00 00:00:00','2016-11-02 16:59:23','2016-11-02 16:59:20','2016-11-02 16:59:23'),(19,19,0,'0000-00-00 00:00:00','2016-11-02 16:59:23','2016-11-02 16:59:21','2016-11-02 16:59:23'),(20,20,0,'0000-00-00 00:00:00','2016-11-02 16:59:23','2016-11-02 16:59:21','2016-11-02 16:59:23'),(21,21,0,'0000-00-00 00:00:00','2016-11-02 16:59:23','2016-11-02 16:59:21','2016-11-02 16:59:23'),(22,22,0,'0000-00-00 00:00:00','2016-11-02 16:59:23','2016-11-02 16:59:21','2016-11-02 16:59:23');
UNLOCK TABLES;

-- Table structure for table `tbllog_register`

DROP TABLE IF EXISTS `tbllog_register`;
CREATE TABLE `tbllog_register` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  `namespace_id` int(10) unsigned,
  `namespace` varchar(255) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  `namespace_value` text COLLATE utf8_unicode_ci NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00',
  `updated_at` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- Insert Automation Status Permission to admin role 1
INSERT INTO `tbladminperms` (`roleid`, `permid`) VALUES (1, 138);
