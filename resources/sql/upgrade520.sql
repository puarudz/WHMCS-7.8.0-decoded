ALTER TABLE  `tbladmins` ADD  `disabled` INT( 1 ) NOT NULL AFTER  `language`;

INSERT INTO `tblconfiguration` (`setting`, `value`) VALUES ('AutoClientStatusChange', '2');

CREATE TABLE IF NOT EXISTS `tbltickettags` (
  `id` int(10) NOT NULL AUTO_INCREMENT,
  `ticketid` int(10) NOT NULL,
  `tag` text NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

ALTER TABLE  `tbladmins` CHANGE  `ticketnotifications`  `ticketnotifications` TEXT NOT NULL;
UPDATE tbladmins SET ticketnotifications = supportdepts WHERE ticketnotifications != '';

ALTER TABLE `tblclients` ADD `emailoptout` INT( 1 ) NOT NULL;

INSERT INTO `tblconfiguration` (`setting`, `value`) VALUES ('AllowClientsEmailOptOut', '1');

ALTER TABLE `tblemailmarketer` ADD `marketing` INT( 1 ) NOT NULL;

ALTER TABLE `tblclients` ADD `overrideautoclose` INT( 1 ) NOT NULL;

UPDATE `tblemailtemplates` SET `name` = 'Automated Password Reset' WHERE `name` = 'Password Reset Confirmation';

UPDATE `tblemailtemplates` SET `message` = '<p>Dear {$client_name},</p><p>Recently a request was submitted to reset your password for our client area. If you did not request this, please ignore this email. It will expire and become useless in 2 hours time.</p><p>To reset your password, please visit the url below:<br /><a href="{$pw_reset_url}">{$pw_reset_url}</a></p><p>When you visit the link above, your password will be reset, and the new password will be emailed to you.</p><p>{$signature}</p>' WHERE `name` = 'Password Reset Validation';

INSERT INTO `tblemailtemplates` (`id`, `type`, `name`, `subject`, `message`, `fromname`, `fromemail`, `disabled`, `custom`, `language`, `copyto`, `plaintext`) VALUES (NULL, 'general', 'Password Reset Confirmation', 'Your password has been reset for {$company_name}', '<p>Dear {$client_name},</p><p>As you requested, your password for our client area has now been reset. </p><p>If it was not at your request, then please contact support immediately.</p><p>{$signature}</p>', '', '', '', '', '', '', '0');

ALTER TABLE `tblproductgroups` ADD `orderfrmtpl` TEXT NOT NULL AFTER `name`;

INSERT INTO `tblconfiguration` (`setting`, `value`) VALUES ('BannedSubdomainPrefixes', 'mail,mx,gapps,gmail,webmail,cpanel,whm,ftp,clients,billing,members,login,accounts,access');

ALTER TABLE `tblcontacts` ADD `affiliateemails` INT( 1 ) NOT NULL AFTER `supportemails`;

INSERT INTO `tbladminperms` (`roleid`, `permid`) VALUES ('1', '125'), ('1', '126'), ('2', '125'), ('2', '126'), ('3', '125'), ('3', '126');

INSERT INTO `tblconfiguration` (`setting`, `value`) VALUES ('FreeDomainAutoRenewRequiresProduct', 'on');
INSERT INTO `tblconfiguration` (`setting`, `value`) VALUES ('DomainToDoListEntries', 'on');

UPDATE tblemailtemplates SET message='<p>The escalation rule {$rule_name} has just been applied to this ticket.</p><p>Client: {$client_name}{if $client_id} #{$client_id}{/if} <br />Department: {$ticket_department} <br />Subject: {$ticket_subject} <br />Priority: {$ticket_priority}</p><p>---<br />{$ticket_message}<br />---</p><p>You can respond to this ticket by simply replying to this email or through the admin area at the url below.</p><p><a href="{$whmcs_admin_url}supporttickets.php?action=viewticket&id={$ticket_id}">{$whmcs_admin_url}supporttickets.php?action=viewticket&id={$ticket_id}</a></p>' WHERE name='Escalation Rule Notification';

INSERT INTO `tblemailtemplates` (`type`, `name`, `subject`, `message`) VALUES ('support', 'Support Ticket Feedback Request', 'Your Feedback is Requested for Ticket #{$ticket_id}', '<p>This support request has been marked as completed.</p><p>We would really appreciate it if you would just take a moment to let us know about the quality of your experience.</p><p><a href="{$ticket_url}&feedback=1">{$ticket_url}&feedback=1</a></p><p>Your feedback is very important to us.</p><p>Thank you for your business.</p><p>{$signature}</p>');
CREATE TABLE IF NOT EXISTS `tblticketfeedback` (
  `id` int(10) NOT NULL AUTO_INCREMENT,
  `ticketid` int(10) NOT NULL,
  `adminid` int(10) NOT NULL,
  `rating` int(2) NOT NULL,
  `comments` text NOT NULL,
  `datetime` DATETIME NOT NULL,
  `ip` TEXT NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
