CREATE TABLE IF NOT EXISTS `%s` (
  `entity` int(10) unsigned NOT NULL,
  `tag` int(10) unsigned NOT NULL,
  `relation` text DEFAULT NULL,
  `weight` int(10) NOT NULL DEFAULT '0',
  PRIMARY KEY (`entity`,`tag`),
  KEY `tag` (`tag`),
  FOREIGN KEY (`entity`) REFERENCES `%s` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  FOREIGN KEY (`tag`) REFERENCES `%s` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;