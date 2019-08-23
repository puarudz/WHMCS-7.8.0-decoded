-- Add the Update WHMCS permissions to Full Admin role
INSERT INTO `tbladminperms` (`roleid`, `permid`) VALUES (1, 135), (1, 136);

DROP TABLE IF EXISTS `tblupdatelog`;
CREATE TABLE `tblupdatelog` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `instance_id` varchar(255) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  `level` int(10) unsigned NOT NULL,
  `message` text COLLATE utf8_unicode_ci NOT NULL,
  `extra` text COLLATE utf8_unicode_ci NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00',
  `updated_at` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
