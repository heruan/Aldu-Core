CREATE TABLE `_rev-%1$s` LIKE `%1$s`;
##
ALTER TABLE `_rev-%1$s`
  CHANGE `id` `id` int(10) unsigned,
  DROP PRIMARY KEY,
  ADD `_revision` bigint unsigned AUTO_INCREMENT,
  ADD `_revision_previous` bigint unsigned NULL,
  ADD `_revision_action` enum('INSERT','UPDATE') default NULL,
  ADD `_revision_timestamp` datetime NULL default NULL,
  ADD PRIMARY KEY (`_revision`),
  ADD INDEX (`_revision_previous`),
  ADD INDEX `id` (`id`)
;
##
ALTER TABLE `_rev-%1$s` ADD FOREIGN KEY (`_revision_previous`) REFERENCES `_rev-%1$s` (`_revision`) ON DELETE SET NULL ON UPDATE CASCADE;
##
CREATE TABLE `_revhist-%1$s` (
  `id` int(10) unsigned,
  `_revision` bigint unsigned NULL,
  `_revhistory_timestamp` timestamp NOT NULL default CURRENT_TIMESTAMP,
  INDEX (`id`),
  INDEX (`_revision`),
  INDEX (`_revhistory_timestamp`)
) ENGINE=InnoDB;
##
ALTER TABLE `_revhist-%1$s` ADD FOREIGN KEY (`_revision`) REFERENCES `_rev-%1$s` (`_revision`) ON DELETE SET NULL ON UPDATE CASCADE;
##
ALTER TABLE `%1$s`
  ADD `_revision` bigint unsigned NULL,
  ADD UNIQUE INDEX (`_revision`),
  ADD FOREIGN KEY (`_revision`) REFERENCES `_rev-%1$s` (`_revision`) ON DELETE SET NULL ON UPDATE CASCADE
;
