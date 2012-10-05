<?php
/**
 * Aldu\Core\Model\Datasource\Driver\MySQL
 *
 * AlduPHP(tm) : The Aldu Network PHP Framework (http://aldu.net/php)
 * Copyright 2010-2012, Aldu Network (http://aldu.net)
 *
 * Licensed under Creative Commons Attribution-ShareAlike 3.0 Unported license (CC BY-SA 3.0)
 * Redistributions of files must retain the above copyright notice.
 *
 * @author        Giovanni Lovato <heruan@aldu.net>
 * @copyright     Copyright 2010-2012, Aldu Network (http://aldu.net)
 * @link          http://aldu.net/php AlduPHP(tm) Project
 * @package       Aldu\Core\Model\Datasource\Driver
 * @uses          Aldu\Core\Model\Datasource
 * @since         AlduPHP(tm) v1.0.0
 * @license       Creative Commons Attribution-ShareAlike 3.0 Unported (CC BY-SA 3.0)
 */

namespace Aldu\Core\Model\Datasource\Driver;
use Aldu\Core\Model\Datasource;
use Aldu\Core\Utility\Inflector;
use Aldu\Core\Exception;
use Aldu\Core;
use mysqli;
use DateTime;

class MySQL extends Datasource\Driver implements Datasource\DriverInterface
{
  const DEFAULT_PORT = 3306;
  const DATETIME_FORMAT = 'Y-m-d H:i:s';
  const INDEX_TABLE = '_index';
  protected static $configuration = array(
    'revisions' => true
  );
  protected $database;

  public function __construct($url, $parts)
  {
    parent::__construct($url);
    $conn = array_merge(array(
      'host' => 'localhost',
      'port' => self::DEFAULT_PORT,
      'user' => null,
      'pass' => null,
      'path' => null
    ), $parts);
    $this->database = ltrim($conn['path'], '/');
    if (!$this->link = new mysqli($conn['host'], $conn['user'], $conn['pass'], $this->database,
      $conn['port'])) {
      throw new Exception('Cannot connect MySQL driver to ' . $conn['host']);
    }
    $this->query("SET NAMES 'utf8'");
    while (!$this->link->select_db($this->database)) {
      if (!$this->query("CREATE DATABASE `%s` DEFAULT CHARACTER SET 'utf8' COLLATE 'utf8_unicode_ci'", $this->database)) {
        throw new Exception($this->link->error);
      }
    }
    if (!$this->tableExists('_index')) {
      $query = file_get_contents(__DIR__ . DS . 'MySQL' . DS . 'create-index-table.sql');
      $this->query($query);
    }
    $this->link->autocommit(false);
  }

  public function __destruct()
  {
    if ($this->link) {
      $this->link->close();
    }
  }

  protected function query()
  {
    $args = func_get_args();
    $query = array_shift($args);
    $rows = array();
    if (is_array(current($args))) {
      $args = array_shift($args);
    }
    if (!empty($args)) {
      foreach ($args as &$arg) {
        $arg = $this->link->real_escape_string($arg);
      }
      $query = vsprintf($query, $args);
    }
    echo $query;
    $result = $this->link->query($query);
    if (is_bool($result)) {
      $cache = $result;
    }
    else {
      while ($row = $result->fetch_assoc()) {
        $rows[] = $row;
      }
      $result->free();
      $cache = $rows;
    }
    return $cache;

  }

  protected function type($table, $column)
  {
    $describe = $this->query("DESCRIBE `%s` `%s`", $table, $column);
    return array_shift($describe);
  }

  protected function columns($table)
  {
    $columns = array();
    foreach ($this->query("SHOW COLUMNS FROM `%s`", $table) as $row) {
      $columns[] = array_shift($row);
    }
    return $columns;
  }

  protected function tables($like = '%', $where = array())
  {
    $tables = array();
    foreach ($this->query("SHOW TABLES LIKE '%s'", $like) as $row) {
      $tables[] = array_shift($row);
    }
    return $tables;
  }

  protected function tableExists($table)
  {
    if ($this->link->query("SHOW TABLES LIKE '$table'")->num_rows) {
      return true;
    }
    return false;
  }

  protected function createQuery($class, $attribute, $column = null, $type = null)
  {
    if (!$type && ($type = $class::cfg("attributes.$attribute.type"))
      && is_subclass_of($type, 'Aldu\Core\Model')) {
      return $this->createQuery($type, 'id', $attribute, 'id');
    }
    if (is_array($type) && empty($type)) $type = '';
    list($type, $max, $other) = explode(':', $type) + array_fill(0, 3, null);
    $column = $column ? : $attribute;
    return $this->createType($column, $type, $max, $other);
  }

  protected function createType($column, $type = null, $max = null, $other = null)
  {
    switch ($type) {
    case 'id':
      return "`$column` INT unsigned NOT NULL,\n\t";
    case 'int':
      $max = $max ? : 11;
      return "`$column` INT($max) $other DEFAULT NULL,\n\t";
    case 'float':
      return "`$column` FLOAT DEFAULT NULL,\n\t";
    case 'text':
      return $max ? "`$column` VARCHAR($max) DEFAULT NULL,\n\t" : "`$column` TEXT,\n\t";
    case 'textarea':
      return "`$column` TEXT,\n\t";
    case 'date':
      return "`$column` DATE DEFAULT NULL,\n\t";
    case 'time':
      return "`$column` TIME DEFAULT NULL,\n\t";
    case 'datetime':
      return "`$column` DATETIME DEFAULT NULL,\n\t";
    case 'file':
    case 'data':
    case 'blob':
      return "`$column` MEDIUMBLOB DEFAULT NULL,\n\t";
    case 'bool':
      return "`$column` TINYINT(1) DEFAULT NULL,\n\t";
    }
    return "`$column` VARCHAR(128) DEFAULT NULL,\n\t";
  }

  protected function tablesFor($class)
  {
    if (!$table = $this->tableName($class)) {
      $explode = explode(NS, $class);
      $tableize = array_pop($explode);
      $table = $_table = Inflector::tableize($tableize);
      $i = 1;
      while ($this->tableExists($_table)) {
        $i++;
        $_table = $table . "$i";
      }
      $table = $_table;
      $this->query("INSERT INTO `" . self::INDEX_TABLE . "` VALUES ('%s', '%s')", $class, $table);
    }
    if (!$this->tableExists($table)) {
      $columns = get_public_vars($class);
      // TODO array_diff multiple attributes
      $queries = array();
      $query = "CREATE TABLE IF NOT EXISTS `$table` (\n\t";
      $query .= "`id` INT unsigned NOT NULL AUTO_INCREMENT,\n\t";
      $extensions = $keys = $fkeys = array();
      foreach ($class::cfg('extensions') as $extName => $ext) {
        $extensions[$extName]['ref'] = $this->tableName($ext['ref']);
        $extensions[$extName]['key'] = $ext['key'];
        foreach (array_intersect_key($columns, $ext['attributes']) as $column => $default) {
          $extensions[$extName][] = $this->createQuery($class, $column);
          unset($columns[$column]);
        }
      }
      foreach (array_keys($columns) as $column) {
        if ($column === 'id') {
          continue;
        }
        if (($type = $class::cfg("attributes.$column.type"))
          && is_subclass_of($type, 'Aldu\Core\Model')) {
          $refTable = ($class === $type) ? $table : $this->tableName($type);
          if ($table !== $refTable && !$this->tableExists($refTable)) {
            $this->tablesFor($refClass);
          }
          $keys[] = "KEY `$column` (`$column`)";
          $fkeys[] = "FOREIGN KEY (`$column`) REFERENCES `$refTable` (`id`) ON DELETE CASCADE ON UPDATE CASCADE";
        }
        $query .= $this->createQuery($class, $column);
      }
      $query .= "PRIMARY KEY (`id`)";
      if ($keys) {
        $query .= ",\n\t" . implode(",\n\t", $keys);
      }
      if ($fkeys) {
        $query .= ",\n\t" . implode(",\n\t", $fkeys);
      }
      $query .= "\n) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci";
      $queries[] = $query;
      // TODO multiple attributes? (see old Mysql.php)
      foreach ($extensions as $extension => $_queries) {
        $ref = array_shift($_queries);
        $key = array_shift($_queries);
        $query = "CREATE TABLE IF NOT EXISTS `{$table}-{$extension}` (\n\t";
        $query .= "`id` INT unsigned NOT NULL,\n\t";
        foreach ($_queries as $_query) {
          $query .= $_query;
        }
        $query .= "PRIMARY KEY (`id`,`$key`),\n\t";
        $query .= "KEY `$key` (`$key`),\n\t";
        $query .= "FOREIGN KEY (`id`) REFERENCES `$table` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,\n\t";
        $query .= "FOREIGN KEY (`$key`) REFERENCES `$ref` (`id`) ON DELETE CASCADE ON UPDATE CASCADE\n";
        $query .= ") ENGINE=InnoDB  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci";
        $queries[] = $query;
      }
      foreach ($queries as $query) {
        $this->query($query);
      }
    }
    if (static::cfg('revisions') && !$this->tableExists("_rev-$table")) {
      $this->createRevisionsTables($table);
    }
    return $this->tables("{$table}%");
  }

  protected function tableName($class)
  {
    $class = is_object($class) ? get_class($class) : $class;
    $query = "SELECT * FROM `" . self::INDEX_TABLE . "` WHERE `class` = '%s'";
    $results = $this->query($query, $class);
    $result = array_shift($results);
    if (isset($result['table'])) {
      return $result['table'];
    }
    return null;
  }

  protected function className($table)
  {
    $query = "SELECT * FROM `" . self::INDEX_TABLE . "` WHERE `table` = '%s'";
    $results = $this->query($query, $table);
    $result = array_shift($results);
    if (isset($result['class'])) {
      return $result['class'];
    }
    return null;
  }

  public function nextId($class)
  {
    $table = $this->tableName($class);
    $result = $this->link->query("SHOW TABLE STATUS LIKE '$table'")->fetch_array();
    return isset($result['Auto_increment']) ? $result['Auto_increment'] : null;
  }

  public function exists($model)
  {
    $class = get_class($model);
    if ($table = $this->tableName($class)) {
      if ($this->first($class, array(
        'id' => $model->id
      ))) {
        return true;
      }
    }
    return false;
  }

  public function count($class, $search = array(), $options = array())
  {
    ;
  }

  protected function denormalizeArray(&$array)
  {
    foreach ($array as $attribute => &$value) {
      if ($value instanceof Core\Model) {
        if (!$value->id) {
          $value->save();
        }
        $value = $value->id;
      }
      elseif ($value instanceof DateTime) {
        $value = $value->format(self::DATETIME_FORMAT);
      }
      elseif (is_array($value)) {
        $this->denormalizeArray($value);
      }
    }
  }

  public function save(&$model)
  {
    $fields = array();
    $class = get_class($model);
    $tables = $this->tablesFor($class);
    try {
      if (!$model->id) {
        $model->created = new DateTime();
      }
      else {
        $model->updated = new DateTime();
      }
      $fields = $model->__toArray();
      $this->denormalizeArray($fields);
      foreach ($tables as $table) {
        $values = array_intersect_key($fields, array_flip($this->columns($table)));
        $columns = array_keys($values);
        $placeholders = array();
        $update = array();
        foreach ($values as $column => &$value) {
          if (!trim($value)) {
            $placeholders[] = "NULL";
            unset($values[$column]);
          }
          else {
            if ($value instanceof DateTime) {
              $value = $value->format(self::DATETIME_FORMAT);
            }
            switch ($this->type($table, $column)) {
            case 'int':
              $placeholders[] = "%s";
              break;
            default:
              $placeholders[] = "'%s'";
            }
          }
          $update[] = "`$column` = VALUES(`$column`)";
        }
        $query = "INSERT INTO `$table` (`" . implode('`, `', $columns) . "`) VALUES ";
        $query .= "(" . implode(', ', $placeholders) . ") ON DUPLICATE KEY UPDATE "
          . implode(', ', $update);
        $this->query($query, $values);
        if (!$model->id) {
          $model->id = $fields['id'] = $this->link->insert_id;
        }
      }
    }
    catch (Exception $e) {
      $this->link->rollback();
      Core::log($e->getMessage(), LOG_ERR);
      return false;
    }
    $this->link->commit();
  }

  public function delete($model)
  {
    $class = get_class($model);
    if ($table = $this->tableName($class)) {
      return $this->query("DELETE FROM `%s` WHERE `id` = %s", $table, $model->id);
    }
    return false;
  }

  public function purge($class, $search = array())
  {
    if (empty($search)) {
      if ($table = $this->tableName($class)) {
        return $this->query("TRUNCATE TABLE `$table`");
      }
    }
    foreach ($this->read($class, $search) as $model) {
      $this->delete($model);
    }
  }

  protected function createRevisionsTables($table)
  {
    $this->createRevisionsTable($table);
    foreach ($this->tables("$table-%") as $extTable) {
      $this->createRevisionsTableExt($extTable, $table);
    }
  }
  
  /**
   * Create a revision table based to original table.
   *
   * @param string $table
   * @param array  $info   Table information
   */

  protected function createRevisionsTable($table)
  {
    foreach (explode('##', file_get_contents(__DIR__ . DS . 'MySQL' . DS . 'create-revision-table.sql')) as $sql) {
      $this->query($sql, $table);
    }
    $this->query("INSERT INTO `_rev-$table` SELECT *, NULL, 'INSERT', NOW() FROM `$table`");
  }

  /**
   * Create a revision table based to original table.
   *
   * @param string $table
   * @param array  $info   Table information
   */
  protected function createRevisionsTableExt($table, $parent)
  {
    $sql = file_get_contents(__DIR__ . DS . 'MySQL' . DS . 'create-revision-table-ext.sql');
    $this->query($sql, $table, $key, $parent);
    $this->query("INSERT INTO `_rev-$table` SELECT `t`.*, `p`.`_revision` FROM `$table` AS `t` INNER JOIN `$parent` AS `p` ON `t`.`id`=`p`.`id`");
  }
}
