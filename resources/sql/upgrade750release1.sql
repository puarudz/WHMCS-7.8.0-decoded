-- permission for Client Data Export
INSERT INTO `tbladminperms` (`roleid`, `permid`) VALUES (1, 146);

CREATE TABLE IF NOT EXISTS `tblioncube_file_log` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `filename` text COLLATE utf8_unicode_ci NOT NULL,
  `content_hash` varchar(512) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  `encoder_version` varchar(16) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  `bundled_php_versions` varchar(128) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  `loaded_in_php` varchar(128) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;