ALTER TABLE `_revision_%s`
  ADD `_revision` bigint unsigned,
  ADD INDEX `_revision` (`_revision`),
  %s
  COMMENT = 'Child of `_revision_%s`'
;