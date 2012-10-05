CREATE TABLE IF NOT EXISTS `_index` (
  `class` varchar(128) CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL,
  `table` varchar(64) CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL,
  PRIMARY KEY (`class`),
  UNIQUE KEY `table` (`table`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;