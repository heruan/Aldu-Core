CREATE TRIGGER `%1$s-after%2$s` AFTER %2$s ON `%1$s`
 FOR EACH ROW
  BEGIN
   UPDATE `_rev-%1$s` SET %3$s, `_revision_action`='%2$s' WHERE `_revision`=NEW.`_revision` AND `_revision_action` IS NULL;
   INSERT INTO `_revhist-%1$s` VALUES (NEW.`%4$s`, NEW.`_revision`, NOW());
  END