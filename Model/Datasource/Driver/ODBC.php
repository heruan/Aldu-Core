<?php
/**
 * Aldu\Core\Model\Datasource\Driver\ODBC
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
use DateTime;
use PDO, PDOStatement, PDOException;

/*
use SAIV\ITSM\Models\CustomerCompany as CC;
use SAIV\ITSM\Models\SLA;
use SAIV\ITSM\Models\Plant;
use Aldu\Core\Model\Datasource\Driver\ODBC;

ODBC::cfg('debug.all', true);

$cc = CC::first(['id' => '000033']);

$sla = SLA::first();

//$plants = Plant::read(['customer' => $cc]);
 */

class ODBC extends Datasource\Driver implements Datasource\DriverInterface
{
  const DATETIME_FORMAT = 'YmdHis';
  protected static $configuration = array(
    'revisions' => false
  );

  public function __construct($url, $parts)
  {
    parent::__construct($url);
    $conn = array_merge(
      array(
        'host' => 'localhost', 'port' => null, 'user' => null, 'pass' => null,
        'path' => null
      ), $parts);
    try {
      $this->link = new PDO($conn['scheme'] . ':' . $conn['host'], $conn['user'], $conn['pass']);
    } catch (PDOException $e) {
      var_dump($conn['host']);
      var_dump($e->getMessage());
    }
  }

  public function __destruct()
  {
    if ($this->link) {
    }
  }

  protected function query()
  {
    $debug = static::cfg('debug.all');
    $args = func_get_args();
    $query = array_shift($args);
    $rows = array();
    if (is_array(current($args))) {
      $args = array_shift($args);
    }
    if (!empty($args)) {
      if (is_bool(end($args))) {
        $debug = array_pop($args);
      }
      foreach ($args as &$arg) {
        $arg = addslashes($arg);
      }
      $query = vsprintf($query, $args);
    }
    if ($debug) {
      echo $query . "\n";
    }
    $result = $this->link->query($query);
    if (is_bool($result)) {
      $cache = $result;
    }
    else {
      $rows = $result->fetchAll(PDO::FETCH_ASSOC);
      $cache = $rows;
    }
    return $cache;

  }

  protected function tableName($class)
  {
    $class = is_object($class) ? get_class($class) : $class;
    return $class::cfg('datasource.odbc.table');
  }

  protected function className($table)
  {
  }

  protected function conditions($search = array(), $class = null, $op = '=',
    $logic = '$and')
  {
    $search = $this->normalizeSearch($search);
    $where = array();
    foreach ($search as $attribute => $value) {
      switch ($attribute) {
      case '$has':
        break;
      case '$and':
      case '$or':
        $logic = $attribute;
        foreach ($value as $conditions) {
          foreach ($conditions as $attribute => $condition) {
            if (!is_array($condition)) {
              $condition = array(
                '=' => $condition
              );
            }
            foreach ($condition as $k => $v) {
              switch ((string) $k) {
              case '=':
                $op = '=';
                break;
              case '$lt':
              case '<':
                $op = '<';
                break;
              case '$lte':
              case '<=':
                $op = '<=';
                break;
              case '$gt':
              case '>':
                $op = '>';
                break;
              case '$gte':
              case '>=':
                $op = '>=';
                break;
              case '$in':
                $in = array();
                foreach ($v as $_v) {
                  $in[] = array(
                    $attribute => array(
                      '=' => $_v
                    )
                  );
                }
                $where[] = $this
                  ->conditions(
                    array(
                      '$or' => $in
                    ), $class);
                continue 2;
              case '$nin':
                $nin = array();
                foreach ($v as $_v) {
                  $nin[] = array(
                    $attribute => array(
                      '!=' => $_v
                    )
                  );
                }
                $where[] = $this
                  ->conditions(
                    array(
                      '$and' => $nin
                    ), $class);
                continue 2;
              case '$all':
                $all = array();
                foreach ($v as $_v) {
                  $all[] = array(
                    $attribute => array(
                      '=' => $_v
                    )
                  );
                }
                $where[] = $this
                  ->conditions(
                    array(
                      '$and' => $all
                    ), $class);
                continue 2;
              case '$mod':
                $op = "% {$v[0]} = ";
                $v = $v[1];
                break;
              case '$ne':
              case '<>':
              case '!=':
                $op = '!=';
                break;
              case '$regex':
                $op = 'REGEXP';
                break;
              }
              if ($v instanceof Core\Model) {
                if (!$v->id) {
                  $v->save();
                }
                $v = $v->id;
              }
              elseif ($v instanceof DateTime) {
                $v = $v->format(self::DATETIME_FORMAT);
              }
              elseif ($this->isRegex($v)) {
                $op = 'REGEXP';
                $v = trim($v, $v[0]);
              }
              if (is_string($attribute)) {
                $k = array_search($attribute,
                  array_flip(
                    $class::cfg('datasource.odbc.mappings'))) ? : $attribute;
              }
              $op = is_null($v) ? 'IS' : $op;
              $v = is_null($v) ? 'NULL' : "'" . addslashes($v) . "'";
              $where[] = "$k $op $v";
            }
          }
        }
      }
    }
    switch ($logic) {
    case '$and':
      $logic = 'AND';
      break;
    case '$or':
      $logic = 'OR';
      break;
    }
    $where = implode(") $logic (", $where);
    return $where ? "($where)" : '1 = 1';
  }

  protected function options($options = array())
  {
    $return = array();
    foreach ($options as $option => $value) {
      switch ($option) {
      case 'group':
        break;
        $return[0] = "GROUP BY " . addslashes($value);
        break;
      case 'order':
      case 'sort':
        break;
        $sort = array();
        if (!is_array($value)) {
          $value = array(
            $value => 1
          );
        }
        foreach ($value as $k => $s) {
          if (is_numeric($k)) {
            $k = $s;
            $s = 1;
          }
          $k = addslashes($k);
          $d = $s > 0 ? 'ASC' : 'DESC';
          $sort[] = "`$k` $d";
        }
        $return[1] = $sort ? "ORDER BY " . implode(', ', $sort) : '';
        break;
      case 'limit':
        $value = addslashes($value);
        $return[2] = "FETCH FIRST $value ROWS ONLY";
        break;
      case 'offset':
      case 'skip':
        break;
      }
    }
    ksort($return);
    return implode(' ', $return);
  }

  protected function normalizeRow($class, $row)
  {
    $normalize = array();
    foreach ($row as $field => $value) {
      $field = array_search($field, $class::cfg("datasource.odbc.mappings")) ? 
        : $field;
      if (($type = $class::cfg("attributes.$field.type"))
        && is_subclass_of($type, 'Aldu\Core\Model')) {
        $value = $type::first(array(
          'id' => $value
        ));
      }
      else {
        $value = trim($value);
      }
      $normalize[$field] = $value ? : null;
    }
    return $normalize;
  }

  protected function select($class, $search = array(), $options = array())
  {
    $table = $this->tableName($class);
    $where = $this->conditions($search, $class);
    $options = $this->options($options);
    $query = "SELECT * FROM $table WHERE $where $options";
    if (static::cfg('debug.read')) {
      static::cfg('debug.all', true);
    }
    $select = $this->query($query);
    if (static::cfg('debug.read')) {
      static::cfg('debug.all', false);
    }
    return $select;
  }

  public function read($class, $search = array(), $options = array())
  {
    $models = array();
    if (!$select = $this->select($class, $search, $options)) {
      return $models;
    }
    foreach ($select as $row) {
      $row = $this->normalizeRow($class, $row);
      $this->normalizeAttributes($class, $row);
      $model = new $class($row);
      $models[] = $model;
    }
    return $models;
  }

  public function first($class, $search = array(), $options = array())
  {
    $options['limit'] = 1;
    $read = $this->read($class, $search, $options);
    return array_shift($read);
  }

  public function count($class, $search = array(), $options = array())
  {
    $select = $this->select($class, $search, $options);
    return count($select);
  }

  public function save(&$model)
  {
    return false;
  }

  public function delete($model)
  {
    return false;
  }

  public function purge($class, $search = array())
  {
    return false;
  }

  public function tag($model, $tags, $relation = array())
  {
  }

  public function untag($model, $tags = array())
  {
  }

  public function belongs($tag, $model, $relation = array(), $search = array(),
    $options = array())
  {
  }

  public function has($model, $tag = null, $relation = array(),
    $search = array(), $options = array())
  {
  }
}
