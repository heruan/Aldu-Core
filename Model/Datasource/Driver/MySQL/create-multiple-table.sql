CREATE TABLE IF NOT EXISTS `%s` (
  `id` int(10) unsigned NOT NULL,
  `label` VARCHAR(30) NULL,
  %s
  PRIMARY KEY (`id`, `value`),
  KEY `id` (`id`),
  FOREIGN KEY (`id`) REFERENCES `%s` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;