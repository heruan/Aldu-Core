CREATE TRIGGER `%1$s-afterdelete` AFTER DELETE ON `%1$s`
 FOR EACH ROW
  BEGIN
   INSERT INTO `_revhist-%1$s` VALUES (OLD.`%2$s`, NULL, NOW());
  END