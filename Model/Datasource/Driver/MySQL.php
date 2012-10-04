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
use Aldu\Core\Exception;
use Aldu\Core;
use DateTime;

class MySQL extends Datasource\Driver implements Datasource\DriverInterface
{
  const DEFAULT_PORT = 3306;
  const DATETIME_FORMAT = 'Y-m-d H:i:s';
  const INDEX_TABLE = '_index';
  protected $database;

  public function __construct($url, $parts)
  {
    parent::__construct($url);
    $conn = array_merge(
      array(
        'host' => 'localhost', 'port' => self::DEFAULT_PORT, 'user' => null,
        'pass' => null, 'path' => null
      ), $parts);
    $this->database = ltrim($conn['path'], '/');
    if (!$this->link = new mysqli($conn['host'], $conn['user'], $conn['pass'],
      $this->database, $conn['port'])) {
      throw new Exception('Cannot connect MySQL driver to ' . $conn['host']);
    }
    $this->query("SET NAMES 'utf8'");
    while (!$this->link->select_db($this->database)) {
      if (!$this
        ->query(
          "CREATE DATABASE `%s` DEFAULT CHARACTER SET 'utf8' COLLATE 'utf8_unicode_ci'",
          $this->database)) {
        throw new Exception($this->link->error);
      }
    }
    if (!$this->tableExists('_index')) {
      $query = file_get_contents(
        __DIR__ . DS . 'Mysql' . DS . 'create-index-table.sql');
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

  protected function createQuery($column, $class, $name = null, $type = null,
    $max = null, $other = null)
  {
    $classes = class_parents($class);
    array_unshift($classes, $class);
    foreach ($classes as $_class) {
      if (property_exists($_class, 'references')
        && isset($_class::$references[$column])) {
        $reference = $_class::$references[$column];
        list($refClass, $alias, $key) = explode('::', $reference)
          + array(
            null, 'name', 'id'
          );
        return $this->createQuery($key, $refClass, $column);
      }
      if (property_exists($_class, 'types') && isset($_class::$types[$column])) {
        list($type, $max, $other) = explode(':', $_class::$types[$column])
          + array(
            null, null, null
          );
        if ($type) {
          break;
        }
      }
    }
    if ($name) {
      $column = $name;
    }
    return $this
      ->createType($column, array(
        $type, $max, $other
      ));
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
      $this
        ->query("INSERT INTO `" . self::INDEX_TABLE . "` VALUES ('%s', '%s')",
          $class, $table);
    }
    $columns = get_public_vars($class);
    // TODO array_diff multiple attributes
    $queries = array();
    $query = "CREATE TABLE IF NOT EXISTS `$table` (\n\t";
    $query .= "`id` int unsigned NOT NULL AUTO_INCREMENT,\n\t";
    $extensions = $keys = $fkeys = array();
    foreach ($columns as $column) {
      foreach ($class::cfg('extensions') as $extName => $ext) {
        $extensions[$extName]['ref'] = $this->tableName($ext['ref']);
        $extensions[$extName]['key'] = $ext['key'];
        if (array_key_exists($column, $ext['attributes'])) {
          $extensions[$extName][] = $this->createQuery($column, $class);
          continue 2;
        }
      }
      if (($type = $class::cfg("attributes.$column.type"))
        && is_subclass_of($type, 'Aldu\Core\Model')) {
        $refTable = ($class === $type) ? $table : $this->tableName($type);
        if ($table !== $refTable && !$this->tableExists($refTable)) {
          $this->tablesFor($refClass);
        }
        $keys[] = "KEY `$column` (`$column`)";
        $fkeys[] = "FOREIGN KEY (`$attribute`) REFERENCES `$refTable` (`id`) ON DELETE CASCADE ON UPDATE CASCADE";
      }
      $query .= $this->createQuery($column, $class);
    }
    $query .= "PRIMARY KEY (`id`)";
    $query .= ',\n\t' . implode(',\n\t', $keys) . '\n\t'
      . implode(',\n\t', $fkeys);
    $query .= "\n) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci";
    $queries[] = $query;
    // TODO multiple attributes? (see old Mysql.php)
    foreach ($extensions as $extension => $_queries) {
      $ref = array_shift($_queries);
      $key = array_shift($_queries);
      $query = "CREATE TABLE IF NOT EXISTS `{$class}-{$extension}` (\n\t";
      $query .= "`id` int unsigned NOT NULL,\n\t";
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
    $result = $this->link->query("SHOW TABLE STATUS LIKE '$table'")
      ->fetch_array();
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

  public function save(&$model)
  {
    $fields = array();
    $class = get_class($model);
    $tables = $this->tablesFor($class);
    foreach ($model->__toArray() as $attribute => $value) {
      if (is_object($value)) {
        if (!$value->id) {
          $value->save();
        }
        $fields[$attribute] = $value->id;
      }
      elseif (is_array($value)) {
      }
      else {
        $fields[$attribute] = $value;
      }
    }
    try {
      if (!$model->id) {
        $model->created = new DateTime();
      }
      else {
        $model->updated = new DateTime();
      }
      foreach ($tables as $table) {
        $values = array_intersect_key($fields, $this->fields($table));
        $columns = array_keys($values);
        $placeholders = array();
        foreach ($values as $column => &$value) {
          if (!trim($value)) {
            $placeholders[] = "NULL";
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
        $query = "INSERT INTO `$table` (`" . implode('`, `', $columns)
          . "`) VALUES ";
        $query .= "(" . implode(', ', $placeholders)
          . ") ON DUPLICATE KEY UPDATE " . implode(', ', $update);
        $this->query($query, $values);
        if (!$model->id) {
          $model->id = $this->link_insert_id;
        }
      }
    } catch (Exception $e) {
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
      return $this
        ->query("DELETE FROM `%s` WHERE `id` = %s", $table, $model->id);
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
}
