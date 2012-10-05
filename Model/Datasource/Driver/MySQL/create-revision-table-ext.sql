CREATE TABLE `_rev-%1$s` LIKE `%1$s`;
ALTER TABLE `_rev-%1$s`
  DROP PRIMARY KEY,
  ADD `_revision` bigint unsigned,
  ADD PRIMARY KEY (`_revision`, %2$s),
  ADD INDEX `original` (%2$s),
  ADD FOREIGN KEY (`_revision`) REFERENCES `_rev-%3$s` (`_revision`) ON DELETE SET NULL ON UPDATE CASCADE
;
