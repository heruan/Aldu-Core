ALTER TABLE `_revision_%1$s`
  %2$s
  DROP PRIMARY KEY,
  ADD `_revision` bigint unsigned,
  ADD PRIMARY KEY (`_revision`, %3$s),
  ADD INDEX `org_primary` (%3$s),
  %4$s
  COMMENT = 'Child of `_revision_%5$s`'
;