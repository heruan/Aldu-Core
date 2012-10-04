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
      $query = file_get_contents(__DIR__ . DS . 'Mysql' . DS . 'create-index-table.sql');
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

  protected function type($table, $column)
  {
    $describe = $this->query("DESCRIBE `%s` `%s`", $table, $column);
    return array_shift($describe);
  }

  protected function tablesFor($model)
  {
    return array();
  }

  public function save(&$model)
  {
    $fields = array();
    $tables = $this->tablesFor($model);
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
        $query = "INSERT INTO `$table` (`" . implode('`, `', $columns) . "`) VALUES ";
        $query .= "(" . implode(', ', $placeholders) . ") ON DUPLICATE KEY UPDATE " . implode(', ', $update);
        $this->query($query, $values);
        if (!$model->id) {
          $model->id = $this->link_insert_id;
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
}
