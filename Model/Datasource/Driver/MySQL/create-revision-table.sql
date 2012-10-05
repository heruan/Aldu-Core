CREATE TABLE `_revision_%1$s` LIKE `%1$s`;
ALTER TABLE `_revision_%1$s`
  %2$s
  DROP PRIMARY KEY,
  ADD `_revision` bigint unsigned AUTO_INCREMENT,
  ADD `_revision_previous` bigint unsigned NULL,
  ADD `_revision_action` enum('INSERT','UPDATE') default NULL,
  ADD `_revision_user_id` int(10) unsigned NULL,
  ADD `_revision_timestamp` datetime NULL default NULL,
  ADD `_revision_comment` text NULL,
  ADD PRIMARY KEY (`_revision`),
  ADD INDEX (`_revision_previous`),
  ADD INDEX `org_primary` ($pk)
  %3$s
;