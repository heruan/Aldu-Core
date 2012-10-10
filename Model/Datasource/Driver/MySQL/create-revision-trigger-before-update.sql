CREATE TRIGGER `%1$s-beforeupdate` BEFORE UPDATE ON `%1$s`
  FOR EACH ROW BEGIN
    %2$s;
    DECLARE `var-_revision_action` enum('INSERT','UPDATE','DELETE');
    DECLARE `revisionCursor` CURSOR FOR SELECT `%3$s`, `_revision_action` FROM `_rev-%1$s` WHERE `_revision`=`var-_revision` LIMIT 1;
    
    IF NEW.`_revision` = OLD.`_revision` THEN
      SET NEW.`_revision` = NULL;
      
    ELSEIF NEW.`_revision` IS NOT NULL THEN 
      SET `var-_revision` = NEW.`_revision`;
      
      OPEN `revisionCursor`;
      FETCH `revisionCursor` INTO `var-%4$s`, `var-_revision_action`;
      CLOSE `revisionCursor`;
      
      IF `var-_revision_action` IS NOT NULL THEN
        SET %5$s;
      END IF;
    END IF;

    #IF $pk THEN
    #  $signal;
    #END IF;

    IF NEW.`_revision` IS NULL THEN
      INSERT INTO `_rev-%1$s` (`_revision_previous`, `_revision_timestamp`) VALUES (OLD.`_revision`, NOW());
      SET NEW.`_revision` = LAST_INSERT_ID();
    END IF;
  END