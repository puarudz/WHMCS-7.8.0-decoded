UPDATE `tblemailtemplates` SET name='Hosting Account Welcome Email' WHERE name='Hosting Account Welcome Email (cPanel)';
UPDATE `tblemailtemplates` SET custom='1' WHERE name='Hosting Account Welcome Email (DirectAdmin)';
UPDATE `tblemailtemplates` SET custom='1' WHERE name='Hosting Account Welcome Email (Plesk)';

ALTER TABLE `tblpromotions` ADD `startdate` DATE NOT NULL AFTER `requiresexisting` ;
ALTER TABLE `tblpromotions` ADD `notes` TEXT NOT NULL ;

ALTER TABLE `tbladdons` ADD `suspendproduct` TEXT NOT NULL AFTER `autoactivate` ;

INSERT INTO `tblemailtemplates` (`id`, `type`, `name`, `subject`, `message`, `fromname`, `fromemail`, `disabled`, `custom`, `language`, `copyto`, `plaintext`) VALUES ('', 'admin', 'Escalation Rule Notification', '[Ticket ID: {$tickettid}] Escalation Rule Notification', '<p>The escalation rule {$name} has just been applied to this ticket.</p>\r\n<p>Client: {$clientname}<br />Department: {$deptname}<br />Subject: {$ticketsubject}<br />Priority: {$ticketpriority}<br />Status: {$ticketstatus}</p>\r\n<p>You can respond to this ticket by simply replying to this email or by logging into the administration area.</p>', '', '', '', '', '', '', 0);

ALTER TABLE `tblcustomfields` ADD `showinvoice` TEXT NOT NULL AFTER `showorder` ;

ALTER TABLE `tblproducts` ADD `autoterminatedays` INT( 4) NOT NULL AFTER `freedomaintlds` ,
ADD `autoterminateemail` TEXT NOT NULL AFTER `autoterminatedays` ;

INSERT INTO `tblconfiguration` (`setting`, `value`) VALUES ('GenerateRandomUsername', '');

ALTER TABLE `tblservers` ADD `assignedips` TEXT NOT NULL AFTER `ipaddress` ;

ALTER TABLE `tblquotes` ADD `proposal` TEXT NOT NULL AFTER `total` ;

INSERT INTO `tblconfiguration` (`setting`, `value`) VALUES ('AddFundsRequireOrder', 'on');

INSERT INTO `tblconfiguration` (`setting`, `value`) VALUES ('GroupSimilarLineItems', 'on');

INSERT INTO `tblconfiguration` (`setting`, `value`) VALUES ('ProrataClientsAnniversaryDate', '');

INSERT INTO `tblconfiguration` (`setting`, `value`) VALUES ('TCPDFFont', 'helvetica');

ALTER TABLE `tblservers` CHANGE `active` `active` INT(1) NOT NULL ;
ALTER TABLE `tblservers` ADD `disabled` INT(1) NOT NULL ;

ALTER TABLE `tblemailtemplates` ADD `attachments` TEXT NOT NULL AFTER `message` ;

ALTER TABLE `tblpromotions` ADD `upgrades` INT(1) NOT NULL AFTER `recurfor` ,
ADD `upgradeconfig` TEXT NOT NULL AFTER `upgrades` ;

INSERT INTO `tblconfiguration` (`setting`, `value`) VALUES ('CancelInvoiceOnCancellation', 'on');
