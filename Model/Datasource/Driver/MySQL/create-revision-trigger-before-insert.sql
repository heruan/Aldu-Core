CREATE TRIGGER `%1$s-beforeinsert` BEFORE INSERT ON `%1$s`
  FOR EACH ROW BEGIN
    %2$s;
    DECLARE `revisionCursor` CURSOR FOR SELECT `%3$s` FROM `_rev-%1$s` WHERE `_revision`=`var-_revision` LIMIT 1;
    
    IF NEW.`_revision` IS NULL THEN
      INSERT INTO `_rev-%1$s` (`_revision_timestamp`) VALUES (NOW());
	  SET NEW.`_revision` = LAST_INSERT_ID(); 
    ELSE
      SET `var-_revision`=NEW.`_revision`;
      OPEN `revisionCursor`;
      FETCH `revisionCursor` INTO `var-%4$s`;
      CLOSE `revisionCursor`;
      
      SET %5$s;
    END IF;
  END