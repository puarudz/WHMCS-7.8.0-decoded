-- Add the Configure Application Links permission to Full Admin role
INSERT INTO `tbladminperms` (`roleid`, `permid`) VALUES (1, 133);

-- Add the Configure OpenID Connect permission to Full Admin role
INSERT INTO `tbladminperms` (`roleid`, `permid`) VALUES (1, 134);

-- Remove the Disable Client Dropdown setting
DELETE FROM `tblconfiguration` WHERE `setting` = 'DisableClientDropdown';

-- Add termination_date to tblhosting
ALTER TABLE `tblhosting` ADD `termination_date` DATE NOT NULL DEFAULT '0000-00-00' AFTER `nextinvoicedate`;

-- Add termination_date to tblhostingaddons
ALTER TABLE `tblhostingaddons` ADD `termination_date` DATE NOT NULL DEFAULT '0000-00-00' AFTER `nextinvoicedate`;

-- Populate the termination date for tblhosting and tblhostingaddons for terminated products
UPDATE `tblhosting` SET `termination_date` = `nextduedate` WHERE `domainstatus` = 'Terminated' OR `domainstatus` = 'Cancelled';
UPDATE `tblhostingaddons` SET `termination_date` = `nextduedate` WHERE `status` = 'Terminated' OR `status` = 'Cancelled';

-- Add single sign-on client toggle setting
ALTER TABLE `tblclients` ADD `allow_sso` TINYINT( 1 ) NOT NULL DEFAULT '1' AFTER `overrideautoclose` ;

-- \WHMCS\ApplicationLink\AccessToken
CREATE TABLE IF NOT EXISTS `tbloauthserver_access_tokens` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `access_token` varchar(80) COLLATE utf8_unicode_ci NOT NULL,
  `client_id` varchar(80) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  `user_id` varchar(255) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  `redirect_uri` varchar(2000) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  `expires` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00',
  `created_at` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00',
  `updated_at` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00',
  PRIMARY KEY (`id`),
  UNIQUE KEY `tbloauthserver_access_tokens_access_token_unique` (`access_token`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

CREATE TABLE IF NOT EXISTS `tbloauthserver_access_token_scopes` (
  `access_token_id` int(10) unsigned NOT NULL DEFAULT '0',
  `scope_id` int(10) unsigned NOT NULL DEFAULT '0',
  KEY `tbloauthserver_access_token_scopes_scope_id_index` (`access_token_id`,`scope_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- \WHMCS\ApplicationLink\ApplicationLink
CREATE TABLE IF NOT EXISTS `tblapplinks` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `module_type` varchar(20) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  `module_name` varchar(50) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  `is_enabled` tinyint(4) NOT NULL DEFAULT '0',
  `created_at` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00',
  `updated_at` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- \WHMCS\ApplicationLink\AuthorizationCode
CREATE TABLE IF NOT EXISTS `tbloauthserver_auth_codes` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `authorization_code` varchar(80) COLLATE utf8_unicode_ci NOT NULL,
  `client_id` varchar(80) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  `user_id` varchar(255) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  `redirect_uri` varchar(2000) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  `id_token` varchar(2000) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  `expires` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00',
  `created_at` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00',
  `updated_at` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00',
  PRIMARY KEY (`id`),
  UNIQUE KEY `tbloauthserver_auth_codes_authorization_code_unique` (`authorization_code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

CREATE TABLE IF NOT EXISTS `tbloauthserver_authcode_scopes` (
  `authorization_code_id` int(10) unsigned NOT NULL DEFAULT '0',
  `scope_id` int(10) unsigned NOT NULL DEFAULT '0',
  KEY `tbloauthserver_authcode_scopes_scope_id_index` (`authorization_code_id`,`scope_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- \WHMCS\ApplicationLink\Client
CREATE TABLE IF NOT EXISTS `tbloauthserver_clients` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `identifier` varchar(80) COLLATE utf8_unicode_ci NOT NULL,
  `secret` varchar(255) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  `redirect_uri` varchar(2000) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  `grant_types` varchar(80) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  `user_id` varchar(255) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  `service_id` int(11) NOT NULL DEFAULT '0',
  `name` varchar(255) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  `description` varchar(255) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  `logo_uri` varchar(255) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  `rsa_key_pair_id` int(11) NOT NULL DEFAULT '0',
  `created_at` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00',
  `updated_at` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00',
  PRIMARY KEY (`id`),
  UNIQUE KEY `tbloauthserver_clients_identifier_unique` (`identifier`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

CREATE TABLE IF NOT EXISTS `tbloauthserver_client_scopes` (
  `client_id` int(10) unsigned NOT NULL DEFAULT '0',
  `scope_id` int(10) unsigned NOT NULL DEFAULT '0',
  KEY `tbloauthserver_client_scopes_scope_id_index` (`client_id`,`scope_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- \WHMCS\ApplicationLink\Links
CREATE TABLE IF NOT EXISTS `tblapplinks_links` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `applink_id` int(10) unsigned NOT NULL DEFAULT '0',
  `scope` varchar(80) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  `display_label` varchar(256) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  `is_enabled` tinyint(4) NOT NULL DEFAULT '0',
  `order` tinyint(4) NOT NULL DEFAULT '0',
  `created_at` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00',
  `updated_at` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- \WHMCS\ApplicationLink\Log
CREATE TABLE IF NOT EXISTS `tblapplinks_log` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `applink_id` int(10) unsigned NOT NULL DEFAULT '0',
  `message` varchar(2000) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  `level` int(11) NOT NULL DEFAULT '0',
  `created_at` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00',
  `updated_at` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- \WHMCS\ApplicationLink\Scope
CREATE TABLE IF NOT EXISTS `tbloauthserver_scopes` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `scope` varchar(80) COLLATE utf8_unicode_ci NOT NULL,
  `description` varchar(255) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  `is_default` tinyint(4) NOT NULL DEFAULT '0',
  `created_at` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00',
  `updated_at` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00',
  PRIMARY KEY (`id`),
  UNIQUE KEY `tbloauthserver_scopes_scope_unique` (`scope`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

INSERT INTO `tbloauthserver_scopes` (`id`,`scope`,`description`,`is_default`,`created_at`,`updated_at`) VALUES
  (1, 'clientarea:sso', 'Single Sign-on for Client Area', 1, '0000-00-00 00:00:00','0000-00-00 00:00:00'),
  (2, 'clientarea:profile', 'Account Profile', 0, '0000-00-00 00:00:00','0000-00-00 00:00:00'),
  (3, 'clientarea:billing_info', 'Manage Billing Information', 0, '0000-00-00 00:00:00','0000-00-00 00:00:00'),
  (4, 'clientarea:emails', 'Email History', 0, '0000-00-00 00:00:00','0000-00-00 00:00:00'),
  (5, 'clientarea:announcements', 'Announcements', 0, '0000-00-00 00:00:00','0000-00-00 00:00:00'),
  (6, 'clientarea:downloads', 'Downloads', 0, '0000-00-00 00:00:00','0000-00-00 00:00:00'),
  (7, 'clientarea:knowledgebase', 'Knowledgebase', 0, '0000-00-00 00:00:00','0000-00-00 00:00:00'),
  (8, 'clientarea:network_status', 'Network Status', 0, '0000-00-00 00:00:00','0000-00-00 00:00:00'),
  (9, 'clientarea:services', 'Products/Services', 0, '0000-00-00 00:00:00','0000-00-00 00:00:00'),
  (10, 'clientarea:product_details', 'Product Info/Details (requires associated serviceId)', 0, '0000-00-00 00:00:00','0000-00-00 00:00:00'),
  (11, 'clientarea:domains', 'Domains', 0, '0000-00-00 00:00:00','0000-00-00 00:00:00'),
  (12, 'clientarea:domain_details', 'Domain Info/Details (requires associated domainId)', 0, '0000-00-00 00:00:00','0000-00-00 00:00:00'),
  (13, 'clientarea:invoices', 'Invoices', 0, '0000-00-00 00:00:00','0000-00-00 00:00:00'),
  (14, 'clientarea:tickets', 'Support Tickets', 0, '0000-00-00 00:00:00','0000-00-00 00:00:00'),
  (15, 'clientarea:submit_ticket', 'Submit New Ticket', 0, '0000-00-00 00:00:00','0000-00-00 00:00:00'),
  (16, 'clientarea:shopping_cart', 'Shopping Cart Default Product Group', 0, '0000-00-00 00:00:00','0000-00-00 00:00:00'),
  (17, 'clientarea:shopping_cart_addons', 'Shopping Cart Product Addons', 0, '0000-00-00 00:00:00','0000-00-00 00:00:00'),
  (18, 'clientarea:shopping_cart_domain_register', 'Shopping Cart Register New Domain', 0, '0000-00-00 00:00:00','0000-00-00 00:00:00'),
  (19, 'clientarea:shopping_cart_domain_transfer', 'Shopping Cart Transfer Existing Domain', 0, '0000-00-00 00:00:00','0000-00-00 00:00:00'),
  (20, 'openid', 'Scope required for OpenID Connect ID tokens', 0, '0000-00-00 00:00:00','0000-00-00 00:00:00'),
  (21, 'email', 'Scope used for Email Claim', 0, '0000-00-00 00:00:00','0000-00-00 00:00:00'),
  (22, 'profile', 'Scope used for Profile Claim', 0, '0000-00-00 00:00:00','0000-00-00 00:00:00');

-- \WHMCS\ApplicationLink\Scope\UserAuthorization
CREATE TABLE IF NOT EXISTS `tbloauthserver_user_authz` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` varchar(255) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  `client_id` int(10) unsigned NOT NULL DEFAULT '0',
  `expires` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00',
  `created_at` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00',
  `updated_at` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

CREATE TABLE IF NOT EXISTS `tbloauthserver_user_authz_scopes` (
  `user_authz_id` int(10) unsigned NOT NULL DEFAULT '0',
  `scope_id` int(10) unsigned NOT NULL DEFAULT '0',
  KEY `tbloauthserver_user_authz_scopes_scope_id_index` (`user_authz_id`,`scope_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- \WHMCS\Security\Encryption\RsaKeyPair
CREATE TABLE IF NOT EXISTS `tblrsakeypairs` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `identifier` varchar(96) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  `private_key` text COLLATE utf8_unicode_ci NOT NULL,
  `public_key` text COLLATE utf8_unicode_ci NOT NULL,
  `algorithm` varchar(16) COLLATE utf8_unicode_ci NOT NULL DEFAULT 'RS256',
  `name` varchar(255) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  `description` varchar(255) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  `created_at` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00',
  `updated_at` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
