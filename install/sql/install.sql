
-- Table structure for table `tblaccounts`
-- 

CREATE TABLE IF NOT EXISTS `tblaccounts` (
  `id` int(1) unsigned zerofill NOT NULL auto_increment,
  `userid` int(10) unsigned zerofill NOT NULL default '0000000000',
  `gateway` text COLLATE utf8_unicode_ci NOT NULL,
  `date` datetime default NULL,
  `description` text COLLATE utf8_unicode_ci NOT NULL,
  `amountin` decimal(10,2) NOT NULL default '0.00',
  `fees` decimal(10,2) NOT NULL default '0.00',
  `amountout` decimal(10,2) NOT NULL default '0.00',
  `transid` text COLLATE utf8_unicode_ci NOT NULL,
  `invoiceid` int(1) NOT NULL,
  PRIMARY KEY  (`id`)
) DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- --------------------------------------------------------

-- 
-- Table structure for table `tblactivitylog`
--

CREATE TABLE IF NOT EXISTS `tblactivitylog` (
  `id` int(1) unsigned zerofill NOT NULL auto_increment,
  `date` datetime NOT NULL default '0000-00-00 00:00:00',
  `description` text COLLATE utf8_unicode_ci NOT NULL,
  `user` text COLLATE utf8_unicode_ci NOT NULL,
  PRIMARY KEY  (`id`)
) DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- --------------------------------------------------------

-- 
-- Table structure for table `tbladdons`
--

CREATE TABLE IF NOT EXISTS `tbladdons` (
  `id` int(1) unsigned zerofill NOT NULL auto_increment,
  `packages` text COLLATE utf8_unicode_ci NOT NULL,
  `name` text COLLATE utf8_unicode_ci NOT NULL,
  `description` text COLLATE utf8_unicode_ci NOT NULL,
  `recurring` decimal(10,2) NOT NULL default '0.00',
  `setupfee` decimal(10,2) NOT NULL default '0.00',
  `billingcycle` text COLLATE utf8_unicode_ci NOT NULL,
  `showorder` text COLLATE utf8_unicode_ci NOT NULL,
  PRIMARY KEY  (`id`)
) DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- --------------------------------------------------------

-- 
-- Table structure for table `tbladminlog`
-- 

CREATE TABLE IF NOT EXISTS `tbladminlog` (
  `id` int(10) unsigned zerofill NOT NULL auto_increment,
  `adminusername` text COLLATE utf8_unicode_ci NOT NULL,
  `logintime` datetime NOT NULL default '0000-00-00 00:00:00',
  `logouttime` datetime NOT NULL default '0000-00-00 00:00:00',
  `ipaddress` text COLLATE utf8_unicode_ci NOT NULL,
  `sessionid` text COLLATE utf8_unicode_ci NOT NULL,
  `lastvisit` datetime NOT NULL default '0000-00-00 00:00:00',
  PRIMARY KEY  (`id`)
) DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- --------------------------------------------------------

-- 
-- Table structure for table `tbladmins`
-- 

CREATE TABLE IF NOT EXISTS `tbladmins` (
  `id` int(1) unsigned zerofill NOT NULL auto_increment,
  `username` text COLLATE utf8_unicode_ci NOT NULL,
  `password` varchar(255) COLLATE utf8_unicode_ci NOT NULL default '',
  `firstname` text COLLATE utf8_unicode_ci NOT NULL,
  `lastname` text COLLATE utf8_unicode_ci NOT NULL,
  `email` text COLLATE utf8_unicode_ci NOT NULL,
  `userlevel` text COLLATE utf8_unicode_ci NOT NULL,
  `signature` text COLLATE utf8_unicode_ci NOT NULL,
  `notes` text COLLATE utf8_unicode_ci NOT NULL,
  `loginattempts` int(1) NOT NULL,
  `supportdepts` text COLLATE utf8_unicode_ci NOT NULL,
  `ticketnotifications` varchar(2) COLLATE utf8_unicode_ci NOT NULL,
  `ordernotifications` varchar(2) COLLATE utf8_unicode_ci NOT NULL,
  PRIMARY KEY  (`id`)
) DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- --------------------------------------------------------

-- 
-- Table structure for table `tblaffiliates`
-- 

CREATE TABLE IF NOT EXISTS `tblaffiliates` (
  `id` int(3) unsigned zerofill NOT NULL auto_increment,
  `date` date default NULL,
  `clientid` int(10) unsigned zerofill NOT NULL default '0000000000',
  `visitors` int(1) NOT NULL,
  `paytype` text COLLATE utf8_unicode_ci NOT NULL,
  `payamount` decimal(10,2) NOT NULL,
  `balance` decimal(10,2) NOT NULL default '0.00',
  `withdrawn` decimal(10,2) NOT NULL default '0.00',
  PRIMARY KEY  (`id`)
) DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- --------------------------------------------------------

-- 
-- Table structure for table `tblaffiliatesaccounts`
-- 

CREATE TABLE IF NOT EXISTS `tblaffiliatesaccounts` (
  `id` int(1) unsigned zerofill NOT NULL auto_increment,
  `affiliateid` text COLLATE utf8_unicode_ci NOT NULL,
  `domain` text COLLATE utf8_unicode_ci NOT NULL,
  `package` text COLLATE utf8_unicode_ci NOT NULL,
  `billingcycle` text COLLATE utf8_unicode_ci NOT NULL,
  `regdate` date default NULL,
  `amount` decimal(10,2) NOT NULL default '0.00',
  `commission` decimal(10,2) NOT NULL,
  `lastpaid` date NOT NULL default '0000-00-00',
  `relid` int(10) unsigned zerofill NOT NULL default '0000000000',
  PRIMARY KEY  (`id`)
) DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- --------------------------------------------------------

-- 
-- Table structure for table `tblaffiliateshistory`
-- 

CREATE TABLE IF NOT EXISTS `tblaffiliateshistory` (
  `id` int(1) unsigned zerofill NOT NULL auto_increment,
  `affiliateid` int(3) unsigned zerofill NOT NULL,
  `date` date NOT NULL,
  `affaccid` int(1) NOT NULL,
  `amount` decimal(10,2) NOT NULL,
  PRIMARY KEY  (`id`)
) DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- --------------------------------------------------------

-- 
-- Table structure for table `tblannouncements`
-- 

CREATE TABLE IF NOT EXISTS `tblannouncements` (
  `id` int(1) unsigned zerofill NOT NULL auto_increment,
  `date` date default NULL,
  `title` text COLLATE utf8_unicode_ci NOT NULL,
  `announcement` text COLLATE utf8_unicode_ci NOT NULL,
  `published` text COLLATE utf8_unicode_ci NOT NULL,
  PRIMARY KEY  (`id`)
) DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- --------------------------------------------------------

-- 
-- Table structure for table `tblbannedemails`
-- 

CREATE TABLE IF NOT EXISTS `tblbannedemails` (
  `id` int(1) NOT NULL auto_increment,
  `domain` text COLLATE utf8_unicode_ci NOT NULL,
  `count` int(1) NOT NULL,
  PRIMARY KEY  (`id`)
) DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- --------------------------------------------------------

-- 
-- Table structure for table `tblbannedips`
-- 

CREATE TABLE IF NOT EXISTS `tblbannedips` (
  `id` int(1) unsigned zerofill NOT NULL auto_increment,
  `ip` text COLLATE utf8_unicode_ci NOT NULL,
  `reason` text COLLATE utf8_unicode_ci NOT NULL,
  `expires` datetime NOT NULL,
  PRIMARY KEY  (`id`)
) DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- --------------------------------------------------------

-- 
-- Table structure for table `tblbrowserlinks`
-- 

CREATE TABLE IF NOT EXISTS `tblbrowserlinks` (
  `id` int(1) unsigned zerofill NOT NULL auto_increment,
  `name` text COLLATE utf8_unicode_ci NOT NULL,
  `url` text COLLATE utf8_unicode_ci NOT NULL,
  PRIMARY KEY  (`id`)
) DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- --------------------------------------------------------

-- 
-- Table structure for table `tblcalendar`
-- 

CREATE TABLE IF NOT EXISTS `tblcalendar` (
  `id` int(1) unsigned zerofill NOT NULL auto_increment,
  `title` text COLLATE utf8_unicode_ci NOT NULL,
  `desc` text COLLATE utf8_unicode_ci NOT NULL,
  `day` text COLLATE utf8_unicode_ci NOT NULL,
  `month` text COLLATE utf8_unicode_ci NOT NULL,
  `year` text COLLATE utf8_unicode_ci NOT NULL,
  `startt1` text COLLATE utf8_unicode_ci NOT NULL,
  `startt2` text COLLATE utf8_unicode_ci NOT NULL,
  `endt1` text COLLATE utf8_unicode_ci NOT NULL,
  `endt2` text COLLATE utf8_unicode_ci NOT NULL,
  `adminid` int(1) NOT NULL,
  PRIMARY KEY  (`id`)
) DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- --------------------------------------------------------

-- 
-- Table structure for table `tblcancelrequests`
-- 

CREATE TABLE IF NOT EXISTS `tblcancelrequests` (
  `id` int(10) unsigned zerofill NOT NULL auto_increment,
  `date` datetime NOT NULL default '0000-00-00 00:00:00',
  `relid` int(10) unsigned zerofill NOT NULL default '0000000000',
  `reason` text COLLATE utf8_unicode_ci NOT NULL,
  `type` text COLLATE utf8_unicode_ci NOT NULL,
  PRIMARY KEY  (`id`)
) DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- --------------------------------------------------------

-- 
-- Table structure for table `tblclients`
-- 

CREATE TABLE IF NOT EXISTS `tblclients` (
  `id` int(10) unsigned zerofill NOT NULL auto_increment,
  `firstname` text COLLATE utf8_unicode_ci NOT NULL,
  `lastname` text COLLATE utf8_unicode_ci NOT NULL,
  `companyname` text COLLATE utf8_unicode_ci NOT NULL,
  `email` text COLLATE utf8_unicode_ci NOT NULL,
  `address1` text COLLATE utf8_unicode_ci NOT NULL,
  `address2` text COLLATE utf8_unicode_ci NOT NULL,
  `city` text COLLATE utf8_unicode_ci NOT NULL,
  `state` text COLLATE utf8_unicode_ci NOT NULL,
  `postcode` text COLLATE utf8_unicode_ci NOT NULL,
  `country` text COLLATE utf8_unicode_ci NOT NULL,
  `phonenumber` text COLLATE utf8_unicode_ci NOT NULL,
  `password` text COLLATE utf8_unicode_ci NOT NULL,
  `credit` decimal(10,2) NOT NULL,
  `taxexempt` text COLLATE utf8_unicode_ci NOT NULL,
  `datecreated` date NOT NULL,
  `notes` text COLLATE utf8_unicode_ci NOT NULL,
  `cardtype` varchar(255) COLLATE utf8_unicode_ci NOT NULL default '',
  `cardnum` blob NOT NULL,
  `startdate` blob NOT NULL,
  `expdate` blob NOT NULL,
  `issuenumber` blob NOT NULL,
  `lastlogin` datetime default NULL,
  `ip` text COLLATE utf8_unicode_ci NOT NULL,
  `host` text COLLATE utf8_unicode_ci NOT NULL,
  `status` text COLLATE utf8_unicode_ci NOT NULL,
  `language` text COLLATE utf8_unicode_ci NOT NULL,
  PRIMARY KEY  (`id`)
) DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- --------------------------------------------------------

-- 
-- Table structure for table `tblconfiguration`
-- 

CREATE TABLE IF NOT EXISTS `tblconfiguration` (
  `setting` text COLLATE utf8_unicode_ci NOT NULL,
  `value` text COLLATE utf8_unicode_ci NOT NULL
) DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- --------------------------------------------------------

-- 
-- Table structure for table `tblcredit`
-- 

CREATE TABLE IF NOT EXISTS `tblcredit` (
  `id` int(10) unsigned zerofill NOT NULL auto_increment,
  `clientid` int(10) unsigned zerofill NOT NULL,
  `date` date NOT NULL,
  `description` text COLLATE utf8_unicode_ci NOT NULL,
  `amount` decimal(10,2) NOT NULL,
  PRIMARY KEY  (`id`)
) DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- --------------------------------------------------------

-- 
-- Table structure for table `tblcustomfields`
-- 

CREATE TABLE IF NOT EXISTS `tblcustomfields` (
  `id` int(1) unsigned zerofill NOT NULL auto_increment,
  `type` text COLLATE utf8_unicode_ci NOT NULL,
  `relid` int(10) unsigned zerofill NOT NULL default '0000000000',
  `num` text COLLATE utf8_unicode_ci NOT NULL,
  `fieldname` text COLLATE utf8_unicode_ci NOT NULL,
  `fieldtype` text COLLATE utf8_unicode_ci NOT NULL,
  `fieldoptions` text COLLATE utf8_unicode_ci NOT NULL,
  `adminonly` text COLLATE utf8_unicode_ci NOT NULL,
  `required` text COLLATE utf8_unicode_ci NOT NULL,
  `showorder` text COLLATE utf8_unicode_ci NOT NULL,
  PRIMARY KEY  (`id`)
) DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- --------------------------------------------------------

-- 
-- Table structure for table `tblcustomfieldsvalues`
-- 

CREATE TABLE IF NOT EXISTS `tblcustomfieldsvalues` (
  `fieldid` int(1) NOT NULL,
  `relid` int(10) unsigned zerofill NOT NULL,
  `value` text COLLATE utf8_unicode_ci NOT NULL
) DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- --------------------------------------------------------

-- 
-- Table structure for table `tbldomainpricing`
-- 

CREATE TABLE IF NOT EXISTS `tbldomainpricing` (
  `id` int(1) unsigned zerofill NOT NULL auto_increment,
  `extension` text COLLATE utf8_unicode_ci NOT NULL,
  `registrationperiod` int(1) NOT NULL default '0',
  `register` decimal(10,2) NOT NULL default '0.00',
  `transfer` decimal(10,2) NOT NULL,
  `renew` decimal(10,2) NOT NULL,
  `autoreg` text COLLATE utf8_unicode_ci NOT NULL,
  `order` int(1) NOT NULL default '0',
  PRIMARY KEY  (`id`)
) DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- --------------------------------------------------------

-- 
-- Table structure for table `tbldomains`
-- 

CREATE TABLE IF NOT EXISTS `tbldomains` (
  `id` int(10) unsigned zerofill NOT NULL auto_increment,
  `userid` int(10) unsigned zerofill NOT NULL default '0000000000',
  `orderid` int(1) NOT NULL,
  `registrationdate` date NOT NULL,
  `domain` text COLLATE utf8_unicode_ci NOT NULL,
  `firstpaymentamount` decimal(10,2) NOT NULL default '0.00',
  `recurringamount` decimal(10,2) NOT NULL,
  `registrar` text COLLATE utf8_unicode_ci NOT NULL,
  `registrationperiod` int(1) NOT NULL default '1',
  `expirydate` date default NULL,
  `subscriptionid` text COLLATE utf8_unicode_ci NOT NULL,
  `status` text COLLATE utf8_unicode_ci NOT NULL,
  `nextduedate` date NOT NULL default '0000-00-00',
  `nextinvoicedate` date NOT NULL,
  `additionalnotes` text COLLATE utf8_unicode_ci NOT NULL,
  `paymentmethod` text COLLATE utf8_unicode_ci NOT NULL,
  `urlforwarding` text COLLATE utf8_unicode_ci NOT NULL,
  `emailforwarding` text COLLATE utf8_unicode_ci NOT NULL,
  PRIMARY KEY  (`id`)
) DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- --------------------------------------------------------

-- 
-- Table structure for table `tbldomainsadditionalfields`
-- 

CREATE TABLE IF NOT EXISTS `tbldomainsadditionalfields` (
  `id` int(1) NOT NULL auto_increment,
  `domainid` int(10) unsigned zerofill NOT NULL,
  `name` text COLLATE utf8_unicode_ci NOT NULL,
  `value` text COLLATE utf8_unicode_ci NOT NULL,
  PRIMARY KEY  (`id`)
) DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- --------------------------------------------------------

-- 
-- Table structure for table `tbldownloadcats`
-- 

CREATE TABLE IF NOT EXISTS `tbldownloadcats` (
  `id` int(1) unsigned zerofill NOT NULL auto_increment,
  `parentid` int(1) NOT NULL default '0',
  `name` text COLLATE utf8_unicode_ci NOT NULL,
  `description` text COLLATE utf8_unicode_ci NOT NULL,
  `hidden` text COLLATE utf8_unicode_ci NOT NULL,
  PRIMARY KEY  (`id`)
) DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- --------------------------------------------------------

-- 
-- Table structure for table `tbldownloads`
-- 

CREATE TABLE IF NOT EXISTS `tbldownloads` (
  `id` int(1) unsigned zerofill NOT NULL auto_increment,
  `type` text COLLATE utf8_unicode_ci NOT NULL,
  `category` text COLLATE utf8_unicode_ci NOT NULL,
  `title` text COLLATE utf8_unicode_ci NOT NULL,
  `description` text COLLATE utf8_unicode_ci NOT NULL,
  `downloads` int(1) NOT NULL default '0',
  `location` text COLLATE utf8_unicode_ci NOT NULL,
  `clientsonly` text COLLATE utf8_unicode_ci NOT NULL,
  PRIMARY KEY  (`id`)
) DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- --------------------------------------------------------

-- 
-- Table structure for table `tblemails`
-- 

CREATE TABLE IF NOT EXISTS `tblemails` (
  `id` int(1) unsigned zerofill NOT NULL auto_increment,
  `userid` int(10) unsigned zerofill NOT NULL default '0000000000',
  `subject` text COLLATE utf8_unicode_ci NOT NULL,
  `message` text COLLATE utf8_unicode_ci NOT NULL,
  `date` datetime default NULL,
  PRIMARY KEY  (`id`)
) DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- --------------------------------------------------------

-- 
-- Table structure for table `tblemailtemplates`
-- 

CREATE TABLE IF NOT EXISTS `tblemailtemplates` (
  `id` int(1) unsigned zerofill NOT NULL auto_increment,
  `type` text COLLATE utf8_unicode_ci NOT NULL,
  `name` text COLLATE utf8_unicode_ci NOT NULL,
  `subject` text COLLATE utf8_unicode_ci NOT NULL,
  `message` text COLLATE utf8_unicode_ci NOT NULL,
  `fromname` text COLLATE utf8_unicode_ci NOT NULL,
  `fromemail` text COLLATE utf8_unicode_ci NOT NULL,
  `disabled` text COLLATE utf8_unicode_ci NOT NULL,
  `custom` text COLLATE utf8_unicode_ci NOT NULL,
  `language` text COLLATE utf8_unicode_ci NOT NULL,
  PRIMARY KEY  (`id`)
) DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- --------------------------------------------------------

-- 
-- Table structure for table `tblfraud`
-- 

CREATE TABLE IF NOT EXISTS `tblfraud` (
  `id` int(1) unsigned zerofill NOT NULL auto_increment,
  `fraud` text COLLATE utf8_unicode_ci NOT NULL,
  `setting` text COLLATE utf8_unicode_ci NOT NULL,
  `value` text COLLATE utf8_unicode_ci NOT NULL,
  PRIMARY KEY  (`id`)
) DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- --------------------------------------------------------

-- 
-- Table structure for table `tblgatewaylog`
-- 

CREATE TABLE IF NOT EXISTS `tblgatewaylog` (
  `id` int(10) unsigned zerofill NOT NULL auto_increment,
  `date` datetime NOT NULL default '0000-00-00 00:00:00',
  `gateway` text COLLATE utf8_unicode_ci NOT NULL,
  `data` text COLLATE utf8_unicode_ci NOT NULL,
  `result` text COLLATE utf8_unicode_ci NOT NULL,
  PRIMARY KEY  (`id`)
) DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- --------------------------------------------------------

-- 
-- Table structure for table `tblhosting`
-- 

CREATE TABLE IF NOT EXISTS `tblhosting` (
  `id` int(10) unsigned zerofill NOT NULL auto_increment,
  `userid` int(10) unsigned zerofill NOT NULL default '0000000000',
  `orderid` int(1) NOT NULL,
  `regdate` date NOT NULL,
  `domain` text COLLATE utf8_unicode_ci NOT NULL,
  `server` text COLLATE utf8_unicode_ci NOT NULL,
  `paymentmethod` text COLLATE utf8_unicode_ci NOT NULL,
  `firstpaymentamount` decimal(10,2) NOT NULL default '0.00',
  `amount` decimal(10,2) NOT NULL default '0.00',
  `billingcycle` text COLLATE utf8_unicode_ci NOT NULL,
  `nextduedate` date default NULL,
  `nextinvoicedate` date NOT NULL,
  `domainstatus` text COLLATE utf8_unicode_ci NOT NULL,
  `username` text COLLATE utf8_unicode_ci NOT NULL,
  `password` text COLLATE utf8_unicode_ci NOT NULL,
  `notes` text COLLATE utf8_unicode_ci NOT NULL,
  `subscriptionid` text COLLATE utf8_unicode_ci NOT NULL,
  `packageid` int(10) unsigned zerofill NOT NULL default '0000000000',
  `overideautosuspend` text COLLATE utf8_unicode_ci NOT NULL,
  `dedicatedip` text COLLATE utf8_unicode_ci NOT NULL,
  `assignedips` text COLLATE utf8_unicode_ci NOT NULL,
  `rootpassword` text COLLATE utf8_unicode_ci NOT NULL,
  `ns1` text COLLATE utf8_unicode_ci NOT NULL,
  `ns2` text COLLATE utf8_unicode_ci NOT NULL,
  `diskusage` int(10) NOT NULL default '0',
  `disklimit` int(10) NOT NULL default '0',
  `bwusage` int(10) NOT NULL default '0',
  `bwlimit` int(10) NOT NULL default '0',
  `lastupdate` datetime NOT NULL default '0000-00-00 00:00:00',
  PRIMARY KEY  (`id`)
) DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- --------------------------------------------------------

-- 
-- Table structure for table `tblhostingaddons`
-- 

CREATE TABLE IF NOT EXISTS `tblhostingaddons` (
  `id` int(10) unsigned zerofill NOT NULL auto_increment,
  `orderid` int(1) NOT NULL,
  `hostingid` int(10) unsigned zerofill NOT NULL default '0000000000',
  `name` text COLLATE utf8_unicode_ci NOT NULL,
  `setupfee` decimal(10,2) NOT NULL default '0.00',
  `recurring` decimal(10,2) NOT NULL default '0.00',
  `billingcycle` text COLLATE utf8_unicode_ci NOT NULL,
  `status` text COLLATE utf8_unicode_ci NOT NULL,
  `regdate` date NOT NULL default '0000-00-00',
  `nextduedate` date default NULL,
  `nextinvoicedate` date NOT NULL,
  `paymentmethod` text COLLATE utf8_unicode_ci NOT NULL,
  `subscriptionid` text COLLATE utf8_unicode_ci NOT NULL,
  PRIMARY KEY  (`id`)
) DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- --------------------------------------------------------

-- 
-- Table structure for table `tblhostingconfigoptions`
-- 

CREATE TABLE IF NOT EXISTS `tblhostingconfigoptions` (
  `id` int(10) unsigned zerofill NOT NULL auto_increment,
  `relid` int(10) unsigned zerofill NOT NULL default '0000000000',
  `configid` int(10) unsigned zerofill NOT NULL default '0000000000',
  `optionid` int(10) unsigned zerofill NOT NULL default '0000000000',
  PRIMARY KEY  (`id`)
) DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- --------------------------------------------------------

-- 
-- Table structure for table `tblinvoiceitems`
-- 

CREATE TABLE IF NOT EXISTS `tblinvoiceitems` (
  `id` int(1) unsigned zerofill NOT NULL auto_increment,
  `invoiceid` text COLLATE utf8_unicode_ci NOT NULL,
  `userid` int(10) unsigned zerofill NOT NULL default '0000000000',
  `type` text COLLATE utf8_unicode_ci NOT NULL,
  `relid` int(10) unsigned zerofill NOT NULL default '0000000000',
  `description` text COLLATE utf8_unicode_ci NOT NULL,
  `amount` decimal(10,2) NOT NULL default '0.00',
  `taxed` int(1) NOT NULL,
  `duedate` date default NULL,
  `paymentmethod` text COLLATE utf8_unicode_ci NOT NULL,
  `notes` text COLLATE utf8_unicode_ci NOT NULL,
  PRIMARY KEY  (`id`)
) DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `tblinvoices`
-- 

CREATE TABLE IF NOT EXISTS `tblinvoices` (
  `id` int(1) unsigned zerofill NOT NULL auto_increment,
  `date` date default NULL,
  `duedate` date default NULL,
  `datepaid` datetime NOT NULL default '0000-00-00 00:00:00',
  `userid` int(10) unsigned zerofill NOT NULL default '0000000000',
  `subtotal` decimal(10,2) NOT NULL,
  `credit` decimal(10,2) NOT NULL,
  `tax` decimal(10,2) NOT NULL,
  `total` decimal(10,2) NOT NULL default '0.00',
  `taxrate` decimal(10,2) NOT NULL,
  `status` text COLLATE utf8_unicode_ci NOT NULL,
  `randomstring` text COLLATE utf8_unicode_ci NOT NULL,
  `paymentmethod` text COLLATE utf8_unicode_ci NOT NULL,
  `notes` text COLLATE utf8_unicode_ci NOT NULL,
  PRIMARY KEY  (`id`)
) DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- --------------------------------------------------------

-- 
-- Table structure for table `tblknowledgebase`
-- 

CREATE TABLE IF NOT EXISTS `tblknowledgebase` (
  `id` int(1) unsigned zerofill NOT NULL auto_increment,
  `category` text COLLATE utf8_unicode_ci NOT NULL,
  `title` text COLLATE utf8_unicode_ci NOT NULL,
  `article` text COLLATE utf8_unicode_ci NOT NULL,
  `views` int(1) NOT NULL default '0',
  `useful` int(1) NOT NULL default '0',
  `votes` int(1) NOT NULL default '0',
  `private` text COLLATE utf8_unicode_ci NOT NULL,
  PRIMARY KEY  (`id`)
) DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- --------------------------------------------------------

-- 
-- Table structure for table `tblknowledgebasecats`
-- 

CREATE TABLE IF NOT EXISTS `tblknowledgebasecats` (
  `id` int(1) unsigned zerofill NOT NULL auto_increment,
  `parentid` int(1) NOT NULL default '0',
  `name` text COLLATE utf8_unicode_ci NOT NULL,
  `description` text COLLATE utf8_unicode_ci NOT NULL,
  `hidden` text COLLATE utf8_unicode_ci NOT NULL,
  PRIMARY KEY  (`id`)
) DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- --------------------------------------------------------

-- 
-- Table structure for table `tblorders`
-- 

CREATE TABLE IF NOT EXISTS `tblorders` (
  `id` int(1) unsigned zerofill NOT NULL auto_increment,
  `ordernum` bigint(10) NOT NULL,
  `userid` int(10) unsigned zerofill NOT NULL,
  `date` datetime NOT NULL,
  `hostingid` int(1) NOT NULL,
  `domainids` text COLLATE utf8_unicode_ci NOT NULL,
  `addonids` text COLLATE utf8_unicode_ci NOT NULL,
  `upgradeids` text COLLATE utf8_unicode_ci NOT NULL,
  `domaintype` text COLLATE utf8_unicode_ci NOT NULL,
  `nameservers` text COLLATE utf8_unicode_ci NOT NULL,
  `transfersecret` text COLLATE utf8_unicode_ci NOT NULL,
  `promocode` text COLLATE utf8_unicode_ci NOT NULL,
  `promotype` text COLLATE utf8_unicode_ci NOT NULL,
  `promovalue` text COLLATE utf8_unicode_ci NOT NULL,
  `amount` decimal(10,2) NOT NULL,
  `paymentmethod` text COLLATE utf8_unicode_ci NOT NULL,
  `invoiceid` int(1) NOT NULL,
  `status` text COLLATE utf8_unicode_ci NOT NULL,
  `ipaddress` text COLLATE utf8_unicode_ci NOT NULL,
  `fraudmodule` text COLLATE utf8_unicode_ci NOT NULL,
  `fraudoutput` text COLLATE utf8_unicode_ci NOT NULL,
  PRIMARY KEY  (`id`)
) DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- --------------------------------------------------------

-- 
-- Table structure for table `tblpaymentgateways`
-- 

CREATE TABLE IF NOT EXISTS `tblpaymentgateways` (
  `id` int(1) unsigned zerofill NOT NULL auto_increment,
  `gateway` text COLLATE utf8_unicode_ci NOT NULL,
  `type` text COLLATE utf8_unicode_ci NOT NULL,
  `setting` text COLLATE utf8_unicode_ci NOT NULL,
  `value` text COLLATE utf8_unicode_ci NOT NULL,
  `name` text COLLATE utf8_unicode_ci NOT NULL,
  `size` int(1) NOT NULL default '0',
  `notes` text COLLATE utf8_unicode_ci NOT NULL,
  `description` text COLLATE utf8_unicode_ci NOT NULL,
  `order` int(1) NOT NULL,
  PRIMARY KEY  (`id`)
) DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- --------------------------------------------------------

-- 
-- Table structure for table `tblproductconfigoptions`
-- 

CREATE TABLE IF NOT EXISTS `tblproductconfigoptions` (
  `id` int(10) unsigned zerofill NOT NULL auto_increment,
  `productid` int(10) unsigned zerofill NOT NULL default '0000000000',
  `optionname` text COLLATE utf8_unicode_ci NOT NULL,
  `optiontype` text COLLATE utf8_unicode_ci NOT NULL,
  `order` int(1) NOT NULL default '0',
  PRIMARY KEY  (`id`)
) DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- --------------------------------------------------------

-- 
-- Table structure for table `tblproductconfigoptionssub`
-- 

CREATE TABLE IF NOT EXISTS `tblproductconfigoptionssub` (
  `id` int(10) unsigned zerofill NOT NULL auto_increment,
  `configid` int(10) unsigned zerofill NOT NULL default '0000000000',
  `optionname` text COLLATE utf8_unicode_ci NOT NULL,
  `price` decimal(10,2) NOT NULL default '0.00',
  PRIMARY KEY  (`id`)
) DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- --------------------------------------------------------

-- 
-- Table structure for table `tblproductgroups`
-- 

CREATE TABLE IF NOT EXISTS `tblproductgroups` (
  `id` int(10) unsigned zerofill NOT NULL auto_increment,
  `name` text COLLATE utf8_unicode_ci NOT NULL,
  `disabledgateways` text COLLATE utf8_unicode_ci NOT NULL,
  `hidden` text COLLATE utf8_unicode_ci NOT NULL,
  `order` int(1) NOT NULL default '0',
  PRIMARY KEY  (`id`)
) DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- --------------------------------------------------------

-- 
-- Table structure for table `tblproducts`
-- 

CREATE TABLE IF NOT EXISTS `tblproducts` (
  `id` int(10) unsigned zerofill NOT NULL auto_increment,
  `type` text COLLATE utf8_unicode_ci NOT NULL,
  `gid` int(10) NOT NULL default '0',
  `name` text COLLATE utf8_unicode_ci NOT NULL,
  `description` text COLLATE utf8_unicode_ci NOT NULL,
  `hidden` text COLLATE utf8_unicode_ci NOT NULL,
  `showdomainoptions` text COLLATE utf8_unicode_ci NOT NULL,
  `welcomeemail` int(1) NOT NULL default '0',
  `stockcontrol` text COLLATE utf8_unicode_ci NOT NULL,
  `qty` int(1) NOT NULL,
  `proratabilling` text COLLATE utf8_unicode_ci NOT NULL,
  `proratadate` int(2) NOT NULL,
  `proratachargenextmonth` int(2) NOT NULL,
  `paytype` text COLLATE utf8_unicode_ci NOT NULL,
  `msetupfee` decimal(10,2) NOT NULL default '0.00',
  `qsetupfee` decimal(10,2) NOT NULL default '0.00',
  `ssetupfee` decimal(10,2) NOT NULL default '0.00',
  `asetupfee` decimal(10,2) NOT NULL default '0.00',
  `bsetupfee` decimal(10,2) NOT NULL,
  `monthly` decimal(10,2) NOT NULL default '0.00',
  `quarterly` decimal(10,2) NOT NULL default '0.00',
  `semiannual` decimal(10,2) NOT NULL default '0.00',
  `annual` decimal(10,2) NOT NULL default '0.00',
  `biennial` decimal(10,2) NOT NULL,
  `subdomain` text COLLATE utf8_unicode_ci NOT NULL,
  `autosetup` text COLLATE utf8_unicode_ci NOT NULL,
  `servertype` text COLLATE utf8_unicode_ci NOT NULL,
  `defaultserver` int(1) NOT NULL default '0',
  `configoption1` text COLLATE utf8_unicode_ci NOT NULL,
  `configoption2` text COLLATE utf8_unicode_ci NOT NULL,
  `configoption3` text COLLATE utf8_unicode_ci NOT NULL,
  `configoption4` text COLLATE utf8_unicode_ci NOT NULL,
  `configoption5` text COLLATE utf8_unicode_ci NOT NULL,
  `configoption6` text COLLATE utf8_unicode_ci NOT NULL,
  `configoption7` text COLLATE utf8_unicode_ci NOT NULL,
  `configoption8` text COLLATE utf8_unicode_ci NOT NULL,
  `configoption9` text COLLATE utf8_unicode_ci NOT NULL,
  `configoption10` text COLLATE utf8_unicode_ci NOT NULL,
  `configoption11` text COLLATE utf8_unicode_ci NOT NULL,
  `configoption12` text COLLATE utf8_unicode_ci NOT NULL,
  `configoption13` text COLLATE utf8_unicode_ci NOT NULL,
  `configoption14` text COLLATE utf8_unicode_ci NOT NULL,
  `configoption15` text COLLATE utf8_unicode_ci NOT NULL,
  `configoption16` text COLLATE utf8_unicode_ci NOT NULL,
  `configoption17` text COLLATE utf8_unicode_ci NOT NULL,
  `configoption18` text COLLATE utf8_unicode_ci NOT NULL,
  `configoption19` text COLLATE utf8_unicode_ci NOT NULL,
  `configoption20` text COLLATE utf8_unicode_ci NOT NULL,
  `configoption21` text COLLATE utf8_unicode_ci NOT NULL,
  `configoption22` text COLLATE utf8_unicode_ci NOT NULL,
  `configoption23` text COLLATE utf8_unicode_ci NOT NULL,
  `configoption24` text COLLATE utf8_unicode_ci NOT NULL,
  `freedomain` text COLLATE utf8_unicode_ci NOT NULL,
  `freedomainpaymentterms` text COLLATE utf8_unicode_ci NOT NULL,
  `freedomaintlds` text COLLATE utf8_unicode_ci NOT NULL,
  `upgradepackages` text COLLATE utf8_unicode_ci NOT NULL,
  `configoptionsupgrade` text COLLATE utf8_unicode_ci NOT NULL,
  `billingcycleupgrade` text COLLATE utf8_unicode_ci NOT NULL,
  `tax` int(1) NOT NULL,
  `affiliateonetime` text COLLATE utf8_unicode_ci NOT NULL,
  `affiliatepaytype` text COLLATE utf8_unicode_ci NOT NULL,
  `affiliatepayamount` decimal(10,2) NOT NULL,
  PRIMARY KEY  (`id`)
) DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- --------------------------------------------------------

-- 
-- Table structure for table `tblpromotions`
-- 

CREATE TABLE IF NOT EXISTS `tblpromotions` (
  `id` int(1) unsigned zerofill NOT NULL auto_increment,
  `item` text COLLATE utf8_unicode_ci NOT NULL,
  `type` text COLLATE utf8_unicode_ci NOT NULL,
  `code` text COLLATE utf8_unicode_ci NOT NULL,
  `discount` text COLLATE utf8_unicode_ci NOT NULL,
  `value` decimal(10,2) NOT NULL default '0.00',
  `expirationdate` date default NULL,
  `packages` text COLLATE utf8_unicode_ci NOT NULL,
  `addons` text COLLATE utf8_unicode_ci NOT NULL,
  `maxuses` int(1) NOT NULL default '0',
  `uses` int(1) NOT NULL default '0',
  `appliesto` text COLLATE utf8_unicode_ci NOT NULL,
  PRIMARY KEY  (`id`)
) DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- --------------------------------------------------------

-- 
-- Table structure for table `tblregistrars`
-- 

CREATE TABLE IF NOT EXISTS `tblregistrars` (
  `id` int(1) unsigned zerofill NOT NULL auto_increment,
  `registrar` text COLLATE utf8_unicode_ci NOT NULL,
  `setting` text COLLATE utf8_unicode_ci NOT NULL,
  `value` text COLLATE utf8_unicode_ci NOT NULL,
  PRIMARY KEY  (`id`)
) DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- --------------------------------------------------------

-- 
-- Table structure for table `tblreselleraccountsetup`
-- 

CREATE TABLE IF NOT EXISTS `tblreselleraccountsetup` (
  `id` int(1) unsigned zerofill NOT NULL auto_increment,
  `packageid` text COLLATE utf8_unicode_ci NOT NULL,
  `resnumlimit` text COLLATE utf8_unicode_ci NOT NULL,
  `resnumlimitamt` text COLLATE utf8_unicode_ci NOT NULL,
  `rsnumlimitenabled` text COLLATE utf8_unicode_ci NOT NULL,
  `reslimit` text COLLATE utf8_unicode_ci NOT NULL,
  `resreslimit` text COLLATE utf8_unicode_ci NOT NULL,
  `rslimit-disk` text COLLATE utf8_unicode_ci NOT NULL,
  `rsolimit-disk` text COLLATE utf8_unicode_ci NOT NULL,
  `rslimit-bw` text COLLATE utf8_unicode_ci NOT NULL,
  `rsolimit-bw` text COLLATE utf8_unicode_ci NOT NULL,
  `acl-list-accts` text COLLATE utf8_unicode_ci NOT NULL,
  `acl-show-bandwidth` text COLLATE utf8_unicode_ci NOT NULL,
  `acl-create-acct` text COLLATE utf8_unicode_ci NOT NULL,
  `acl-edit-account` text COLLATE utf8_unicode_ci NOT NULL,
  `acl-suspend-acct` text COLLATE utf8_unicode_ci NOT NULL,
  `acl-kill-acct` text COLLATE utf8_unicode_ci NOT NULL,
  `acl-upgrade-account` text COLLATE utf8_unicode_ci NOT NULL,
  `acl-limit-bandwidth` text COLLATE utf8_unicode_ci NOT NULL,
  `acl-edit-mx` text COLLATE utf8_unicode_ci NOT NULL,
  `acl-frontpage` text COLLATE utf8_unicode_ci NOT NULL,
  `acl-mod-subdomains` text COLLATE utf8_unicode_ci NOT NULL,
  `acl-passwd` text COLLATE utf8_unicode_ci NOT NULL,
  `acl-quota` text COLLATE utf8_unicode_ci NOT NULL,
  `acl-res-cart` text COLLATE utf8_unicode_ci NOT NULL,
  `acl-ssl-gencrt` text COLLATE utf8_unicode_ci NOT NULL,
  `acl-ssl` text COLLATE utf8_unicode_ci NOT NULL,
  `acl-demo-setup` text COLLATE utf8_unicode_ci NOT NULL,
  `acl-rearrange-accts` text COLLATE utf8_unicode_ci NOT NULL,
  `acl-clustering` text COLLATE utf8_unicode_ci NOT NULL,
  `acl-create-dns` text COLLATE utf8_unicode_ci NOT NULL,
  `acl-edit-dns` text COLLATE utf8_unicode_ci NOT NULL,
  `acl-park-dns` text COLLATE utf8_unicode_ci NOT NULL,
  `acl-kill-dns` text COLLATE utf8_unicode_ci NOT NULL,
  `acl-add-pkg` text COLLATE utf8_unicode_ci NOT NULL,
  `acl-edit-pkg` text COLLATE utf8_unicode_ci NOT NULL,
  `acl-add-pkg-shell` text COLLATE utf8_unicode_ci NOT NULL,
  `acl-allow-unlimited-disk-pkgs` text COLLATE utf8_unicode_ci NOT NULL,
  `acl-allow-unlimited-pkgs` text COLLATE utf8_unicode_ci NOT NULL,
  `acl-add-pkg-ip` text COLLATE utf8_unicode_ci NOT NULL,
  `acl-allow-addoncreate` text COLLATE utf8_unicode_ci NOT NULL,
  `acl-allow-parkedcreate` text COLLATE utf8_unicode_ci NOT NULL,
  `acl-onlyselfandglobalpkgs` text COLLATE utf8_unicode_ci NOT NULL,
  `acl-disallow-shell` text COLLATE utf8_unicode_ci NOT NULL,
  `acl-all` text COLLATE utf8_unicode_ci NOT NULL,
  `acl-stats` text COLLATE utf8_unicode_ci NOT NULL,
  `acl-status` text COLLATE utf8_unicode_ci NOT NULL,
  `acl-restart` text COLLATE utf8_unicode_ci NOT NULL,
  `acl-mailcheck` text COLLATE utf8_unicode_ci NOT NULL,
  `acl-resftp` text COLLATE utf8_unicode_ci NOT NULL,
  `acl-news` text COLLATE utf8_unicode_ci NOT NULL,
  PRIMARY KEY  (`id`)
) DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- --------------------------------------------------------

-- 
-- Table structure for table `tblservers`
-- 

CREATE TABLE IF NOT EXISTS `tblservers` (
  `id` int(1) unsigned zerofill NOT NULL auto_increment,
  `name` text COLLATE utf8_unicode_ci NOT NULL,
  `ipaddress` text COLLATE utf8_unicode_ci NOT NULL,
  `monthlycost` decimal(10,2) NOT NULL default '0.00',
  `noc` text COLLATE utf8_unicode_ci NOT NULL,
  `statusaddress` text COLLATE utf8_unicode_ci NOT NULL,
  `primarynameserver` text COLLATE utf8_unicode_ci NOT NULL,
  `primarynameserverip` text COLLATE utf8_unicode_ci NOT NULL,
  `secondarynameserver` text COLLATE utf8_unicode_ci NOT NULL,
  `secondarynameserverip` text COLLATE utf8_unicode_ci NOT NULL,
  `maxaccounts` int(1) NOT NULL default '0',
  `type` text COLLATE utf8_unicode_ci NOT NULL,
  `username` text COLLATE utf8_unicode_ci NOT NULL,
  `password` text COLLATE utf8_unicode_ci NOT NULL,
  `secure` text COLLATE utf8_unicode_ci NOT NULL,
  `active` text COLLATE utf8_unicode_ci NOT NULL,
  PRIMARY KEY  (`id`)
) DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- --------------------------------------------------------

-- 
-- Table structure for table `tbltax`
-- 

CREATE TABLE IF NOT EXISTS `tbltax` (
  `id` int(1) unsigned zerofill NOT NULL auto_increment,
  `name` text COLLATE utf8_unicode_ci NOT NULL,
  `state` text COLLATE utf8_unicode_ci NOT NULL,
  `country` text COLLATE utf8_unicode_ci NOT NULL,
  `taxrate` decimal(10,2) NOT NULL,
  PRIMARY KEY  (`id`)
) DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- --------------------------------------------------------

-- 
-- Table structure for table `tblticketbreaklines`
-- 

CREATE TABLE IF NOT EXISTS `tblticketbreaklines` (
  `id` int(1) unsigned zerofill NOT NULL auto_increment,
  `breakline` text COLLATE utf8_unicode_ci NOT NULL,
  PRIMARY KEY  (`id`)
) DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- --------------------------------------------------------

-- 
-- Table structure for table `tblticketdepartments`
-- 

CREATE TABLE IF NOT EXISTS `tblticketdepartments` (
  `id` int(3) unsigned zerofill NOT NULL auto_increment,
  `name` text COLLATE utf8_unicode_ci NOT NULL,
  `description` text COLLATE utf8_unicode_ci NOT NULL,
  `email` text COLLATE utf8_unicode_ci NOT NULL,
  `hidden` text COLLATE utf8_unicode_ci NOT NULL,
  `order` int(1) NOT NULL,
  `host` text COLLATE utf8_unicode_ci NOT NULL,
  `port` text COLLATE utf8_unicode_ci NOT NULL,
  `login` text COLLATE utf8_unicode_ci NOT NULL,
  `password` text COLLATE utf8_unicode_ci NOT NULL,
  PRIMARY KEY  (`id`)
) DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- --------------------------------------------------------

-- 
-- Table structure for table `tblticketlog`
-- 

CREATE TABLE IF NOT EXISTS `tblticketlog` (
  `id` int(10) unsigned zerofill NOT NULL auto_increment,
  `date` datetime NOT NULL,
  `tid` int(10) unsigned zerofill NOT NULL,
  `action` text COLLATE utf8_unicode_ci NOT NULL,
  PRIMARY KEY  (`id`)
) DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- --------------------------------------------------------

-- 
-- Table structure for table `tblticketmaillog`
-- 

CREATE TABLE IF NOT EXISTS `tblticketmaillog` (
  `id` int(1) NOT NULL auto_increment,
  `date` datetime NOT NULL,
  `to` text COLLATE utf8_unicode_ci NOT NULL,
  `name` text COLLATE utf8_unicode_ci NOT NULL,
  `email` text COLLATE utf8_unicode_ci NOT NULL,
  `subject` text COLLATE utf8_unicode_ci NOT NULL,
  `message` text COLLATE utf8_unicode_ci NOT NULL,
  `status` text COLLATE utf8_unicode_ci NOT NULL,
  PRIMARY KEY  (`id`)
) DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- --------------------------------------------------------

-- 
-- Table structure for table `tblticketnotes`
-- 

CREATE TABLE IF NOT EXISTS `tblticketnotes` (
  `id` int(10) unsigned zerofill NOT NULL auto_increment,
  `admin` text COLLATE utf8_unicode_ci NOT NULL,
  `date` datetime NOT NULL default '0000-00-00 00:00:00',
  `message` text COLLATE utf8_unicode_ci NOT NULL,
  `ticketid` int(10) unsigned zerofill NOT NULL default '0000000000',
  PRIMARY KEY  (`id`)
) DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- --------------------------------------------------------

-- 
-- Table structure for table `tblticketpredefinedcats`
-- 

CREATE TABLE IF NOT EXISTS `tblticketpredefinedcats` (
  `id` int(1) unsigned zerofill NOT NULL auto_increment,
  `parentid` int(1) NOT NULL,
  `name` text COLLATE utf8_unicode_ci NOT NULL,
  PRIMARY KEY  (`id`)
) DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- --------------------------------------------------------

-- 
-- Table structure for table `tblticketpredefinedreplies`
-- 

CREATE TABLE IF NOT EXISTS `tblticketpredefinedreplies` (
  `id` int(1) unsigned zerofill NOT NULL auto_increment,
  `catid` text COLLATE utf8_unicode_ci NOT NULL,
  `name` text COLLATE utf8_unicode_ci NOT NULL,
  `reply` text COLLATE utf8_unicode_ci NOT NULL,
  PRIMARY KEY  (`id`)
) DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- --------------------------------------------------------

-- 
-- Table structure for table `tblticketreplies`
-- 

CREATE TABLE IF NOT EXISTS `tblticketreplies` (
  `id` int(10) unsigned zerofill NOT NULL auto_increment,
  `tid` int(10) unsigned zerofill NOT NULL,
  `userid` int(10) unsigned zerofill NOT NULL,
  `name` text COLLATE utf8_unicode_ci NOT NULL,
  `email` text COLLATE utf8_unicode_ci NOT NULL,
  `date` datetime NOT NULL,
  `message` text COLLATE utf8_unicode_ci NOT NULL,
  `admin` text COLLATE utf8_unicode_ci NOT NULL,
  `attachment` text COLLATE utf8_unicode_ci NOT NULL,
  PRIMARY KEY  (`id`)
) DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- --------------------------------------------------------

-- 
-- Table structure for table `tbltickets`
-- 

CREATE TABLE IF NOT EXISTS `tbltickets` (
  `id` int(10) unsigned zerofill NOT NULL auto_increment,
  `tid` int(6) NOT NULL default '0',
  `did` int(3) unsigned zerofill NOT NULL default '000',
  `userid` int(10) unsigned zerofill NOT NULL default '0000000000',
  `name` text COLLATE utf8_unicode_ci NOT NULL,
  `email` text COLLATE utf8_unicode_ci NOT NULL,
  `c` text COLLATE utf8_unicode_ci NOT NULL,
  `date` datetime NOT NULL default '0000-00-00 00:00:00',
  `title` text COLLATE utf8_unicode_ci NOT NULL,
  `message` text COLLATE utf8_unicode_ci NOT NULL,
  `status` text COLLATE utf8_unicode_ci NOT NULL,
  `urgency` text COLLATE utf8_unicode_ci NOT NULL,
  `admin` text COLLATE utf8_unicode_ci NOT NULL,
  `attachment` text COLLATE utf8_unicode_ci NOT NULL,
  `lastreply` datetime NOT NULL,
  `flag` int(1) NOT NULL,
  `clientunread` int(1) NOT NULL,
  `adminunread` int(1) NOT NULL,
  PRIMARY KEY  (`id`)
) DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- --------------------------------------------------------

-- 
-- Table structure for table `tblticketspamfilters`
-- 

CREATE TABLE IF NOT EXISTS `tblticketspamfilters` (
  `id` int(1) NOT NULL auto_increment,
  `type` enum('sender','subject','phrase') NOT NULL,
  `content` text COLLATE utf8_unicode_ci NOT NULL,
  PRIMARY KEY  (`id`)
) DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- --------------------------------------------------------

-- 
-- Table structure for table `tbltodolist`
-- 

CREATE TABLE IF NOT EXISTS `tbltodolist` (
  `id` int(1) NOT NULL auto_increment,
  `date` date NOT NULL,
  `title` text COLLATE utf8_unicode_ci NOT NULL,
  `description` text COLLATE utf8_unicode_ci NOT NULL,
  `admin` int(1) NOT NULL,
  `status` text COLLATE utf8_unicode_ci NOT NULL,
  `duedate` date NOT NULL,
  PRIMARY KEY  (`id`)
) DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- --------------------------------------------------------

-- 
-- Table structure for table `tblupgrades`
-- 

CREATE TABLE IF NOT EXISTS `tblupgrades` (
  `id` int(1) NOT NULL auto_increment,
  `type` text COLLATE utf8_unicode_ci NOT NULL,
  `date` date NOT NULL,
  `relid` int(10) NOT NULL,
  `originalvalue` text COLLATE utf8_unicode_ci NOT NULL,
  `newvalue` text COLLATE utf8_unicode_ci NOT NULL,
  `amount` decimal(10,2) NOT NULL,
  `recurringchange` decimal(10,2) NOT NULL,
  `status` enum('Pending','Completed') NOT NULL default 'Pending',
  `paid` enum('Y','N') NOT NULL default 'N',
  PRIMARY KEY  (`id`)
) DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- --------------------------------------------------------

-- 
-- Table structure for table `tblwhoislog`
-- 

CREATE TABLE IF NOT EXISTS `tblwhoislog` (
  `id` int(10) unsigned zerofill NOT NULL auto_increment,
  `date` datetime NOT NULL,
  `domain` text COLLATE utf8_unicode_ci NOT NULL,
  `ip` text COLLATE utf8_unicode_ci NOT NULL,
  PRIMARY KEY  (`id`)
) DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- --------------------------------------------------------

INSERT INTO `tblconfiguration` VALUES ('Language', 'English');
INSERT INTO `tblconfiguration` VALUES ('CompanyName', 'Company Name');
INSERT INTO `tblconfiguration` VALUES ('Email', 'changeme@changeme.com');
INSERT INTO `tblconfiguration` VALUES ('Domain', 'http://www.yourdomain.com/');
INSERT INTO `tblconfiguration` VALUES ('LogoURL', '');
INSERT INTO `tblconfiguration` VALUES ('SystemURL', 'http://www.yourdomain.com/whmcs/');
INSERT INTO `tblconfiguration` VALUES ('SystemSSLURL', '');
INSERT INTO `tblconfiguration` VALUES ('Currency', 'USD');
INSERT INTO `tblconfiguration` VALUES ('CurrencySymbol', '$');
INSERT INTO `tblconfiguration` VALUES ('AutoSuspension', 'on');
INSERT INTO `tblconfiguration` VALUES ('AutoSuspensionDays', '5');
INSERT INTO `tblconfiguration` VALUES ('CreateInvoiceDaysBefore', '14');
INSERT INTO `tblconfiguration` VALUES ('AffiliateEnabled', '');
INSERT INTO `tblconfiguration` VALUES ('AffiliateEarningPercent', '0');
INSERT INTO `tblconfiguration` VALUES ('AffiliateBonusDeposit', '0.00');
INSERT INTO `tblconfiguration` VALUES ('AffiliatePayout', '0.00');
INSERT INTO `tblconfiguration` VALUES ('AffiliateLinks', '');
INSERT INTO `tblconfiguration` VALUES ('ActivityLimit', '10000');
INSERT INTO `tblconfiguration` VALUES ('DateFormat', 'DD/MM/YYYY');
INSERT INTO `tblconfiguration` VALUES ('PreSalesQuestions', 'on');
INSERT INTO `tblconfiguration` VALUES ('Template', 'default');
INSERT INTO `tblconfiguration` VALUES ('AllowRegister', 'on');
INSERT INTO `tblconfiguration` VALUES ('AllowTransfer', 'on');
INSERT INTO `tblconfiguration` VALUES ('AllowOwnDomain', 'on');
INSERT INTO `tblconfiguration` VALUES ('EnableTOSAccept', '');
INSERT INTO `tblconfiguration` VALUES ('TermsOfService', '');
INSERT INTO `tblconfiguration` VALUES ('AllowLanguageChange', 'on');
INSERT INTO `tblconfiguration` VALUES ('CutUtf8Mb4', 'on');
INSERT INTO `tblconfiguration` VALUES ('Version', '');
INSERT INTO `tblconfiguration` VALUES ('AllowCustomerChangeInvoiceGateway', 'on');
INSERT INTO `tblconfiguration` VALUES ('DefaultNameserver1', 'ns1.yourdomain.com');
INSERT INTO `tblconfiguration` VALUES ('DefaultNameserver2', 'ns2.yourdomain.com');
INSERT INTO `tblconfiguration` VALUES ('SendInvoiceReminderDays', '7');
INSERT INTO `tblconfiguration` VALUES ('SendReminder', 'on');
INSERT INTO `tblconfiguration` VALUES ('NumRecordstoDisplay', '50');
INSERT INTO `tblconfiguration` VALUES ('BCCMessages', '');
INSERT INTO `tblconfiguration` VALUES ('MailType', 'mail');
INSERT INTO `tblconfiguration` VALUES ('SMTPHost', '');
INSERT INTO `tblconfiguration` VALUES ('SMTPUsername', '');
INSERT INTO `tblconfiguration` VALUES ('SMTPPassword', '');
INSERT INTO `tblconfiguration` VALUES ('SMTPPort', '25');
INSERT INTO `tblconfiguration` VALUES ('ShowCancellationButton', 'on');
INSERT INTO `tblconfiguration` VALUES ('UpdateStatsAuto', 'on');
INSERT INTO `tblconfiguration` VALUES ('InvoicePayTo', 'Address goes here...');
INSERT INTO `tblconfiguration` VALUES ('SendAffiliateReportMonthly', 'on');
INSERT INTO `tblconfiguration` VALUES ('InvalidLoginBanLength', '15');
INSERT INTO `tblconfiguration` VALUES ('Signature', 'Signature goes here...');
INSERT INTO `tblconfiguration` VALUES ('DomainOnlyOrderEnabled', 'on');
INSERT INTO `tblconfiguration` VALUES ('TicketBannedAddresses', '');
INSERT INTO `tblconfiguration` VALUES ('SendEmailNotificationonUserDetailsChange', 'on');
INSERT INTO `tblconfiguration` VALUES ('TicketAllowedFileTypes', '.jpg,.gif,.jpeg,.png');
INSERT INTO `tblconfiguration` VALUES ('CloseInactiveTickets', '0');
INSERT INTO `tblconfiguration` VALUES ('InvoiceLateFeeAmount', '10.00');
INSERT INTO `tblconfiguration` VALUES ('AutoTermination', '');
INSERT INTO `tblconfiguration` VALUES ('AutoTerminationDays', '30');
INSERT INTO `tblconfiguration` VALUES ('RegistrarAdminFirstName', '');
INSERT INTO `tblconfiguration` VALUES ('RegistrarAdminLastName', '');
INSERT INTO `tblconfiguration` VALUES ('RegistrarAdminCompanyName', '');
INSERT INTO `tblconfiguration` VALUES ('RegistrarAdminAddress1', '');
INSERT INTO `tblconfiguration` VALUES ('RegistrarAdminAddress2', '');
INSERT INTO `tblconfiguration` VALUES ('RegistrarAdminCity', '');
INSERT INTO `tblconfiguration` VALUES ('RegistrarAdminStateProvince', '');
INSERT INTO `tblconfiguration` VALUES ('RegistrarAdminCountry', 'US');
INSERT INTO `tblconfiguration` VALUES ('RegistrarAdminPostalCode', '');
INSERT INTO `tblconfiguration` VALUES ('RegistrarAdminPhone', '');
INSERT INTO `tblconfiguration` VALUES ('RegistrarAdminFax', '');
INSERT INTO `tblconfiguration` VALUES ('RegistrarAdminEmailAddress', '');
INSERT INTO `tblconfiguration` VALUES ('RegistrarAdminUseClientDetails', 'on');
INSERT INTO `tblconfiguration` VALUES ('Charset', 'utf-8');
INSERT INTO `tblconfiguration` VALUES ('AutoUnsuspend', 'on');
INSERT INTO `tblconfiguration` VALUES ('RunScriptonCheckOut', '');
INSERT INTO `tblconfiguration` VALUES ('License', '');
INSERT INTO `tblconfiguration` VALUES ('OrderFormTemplate', 'standard_cart');
INSERT INTO `tblconfiguration` VALUES ('AllowDomainsTwice', 'on');
INSERT INTO `tblconfiguration` VALUES ('AddLateFeeDays', '5');
INSERT INTO `tblconfiguration` VALUES ('TaxEnabled', '');
INSERT INTO `tblconfiguration` VALUES ('DefaultCountry', 'US');
INSERT INTO `tblconfiguration` VALUES ('AllowTicketsRegisteredClientsOnly', '');
INSERT INTO `tblconfiguration` VALUES ('AutoRedirectoInvoice', 'gateway');
INSERT INTO `tblconfiguration` VALUES ('EnablePDFInvoices', 'on');
INSERT INTO `tblconfiguration` VALUES ('DisableCapatcha', 'offloggedin');
INSERT INTO `tblconfiguration` VALUES ('SupportTicketOrder', 'ASC');
INSERT INTO `tblconfiguration` VALUES ('SendOverdueInvoiceReminders', '1');
INSERT INTO `tblconfiguration` VALUES ('TaxType', 'Exclusive');
INSERT INTO `tblconfiguration` VALUES ('InvoiceSubscriptionPayments', 'on');
INSERT INTO `tblconfiguration` VALUES ('DomainURLForwarding', '5.00');
INSERT INTO `tblconfiguration` VALUES ('DomainEmailForwarding', '5.00');
INSERT INTO `tblconfiguration` VALUES ('InvoiceIncrement', '1');
INSERT INTO `tblconfiguration` VALUES ('ContinuousInvoiceGeneration', '');
INSERT INTO `tblconfiguration` VALUES ('AutoCancellationRequests', 'on');
INSERT INTO `tblconfiguration` VALUES ('SystemEmailsFromName', 'WHMCompleteSolution');
INSERT INTO `tblconfiguration` VALUES ('SystemEmailsFromEmail', 'noreply@yourdomain.com');
INSERT INTO `tblconfiguration` VALUES ('AllowClientRegister', 'on');
INSERT INTO `tblconfiguration` VALUES ('BulkCheckTLDs', '.com,.net');
INSERT INTO `tblconfiguration` VALUES ('OrderDaysGrace', '0');
INSERT INTO `tblconfiguration` VALUES ('CreditOnDowngrade', 'on');
INSERT INTO `tblconfiguration` VALUES ('TaxDomains', 'on');
INSERT INTO `tblconfiguration` VALUES ('TaxLateFee', 'on');
INSERT INTO `tblconfiguration` VALUES ('AdminForceSSL', 'on');
INSERT INTO `tblconfiguration` VALUES ('MarketingEmailConvert', 'on');

INSERT INTO `tblticketbreaklines` VALUES (1, '> -----Original Message-----');
INSERT INTO `tblticketbreaklines` VALUES (2, '----- Original Message -----');
INSERT INTO `tblticketbreaklines` VALUES (3, '-----Original Message-----');
INSERT INTO `tblticketbreaklines` VALUES (4, '<!-- Break Line -->');
INSERT INTO `tblticketbreaklines` VALUES (5, '====== Please reply above this line ======');
INSERT INTO `tblticketbreaklines` VALUES (6, '_____');
UPDATE `tblconfiguration` SET `value` = '3.2.0' WHERE `setting`= 'Version';
