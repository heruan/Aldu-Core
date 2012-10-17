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
  protected static $configuration = array(__CLASS__ => array(
    'revisions' => false,
    'type' => 'db2',
    'db2' => array(
      'describe' => array(
        'table' => 'QSYS2.SYSCOLUMNS',
        'schema' => 'TABLE_SCHEMA',
        'name' => 'TABLE_NAME',
        'field' => 'COLUMN_NAME',
        'type' => 'DATA_TYPE',
        'length' => 'LENGTH',
        'null' => 'IS_NULLABLE'
      )
    )
  ));

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

  public function query()
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


  protected function describe($table)
  {
    $type = static::cfg('type');
    $select = array(
      static::cfg("$type.describe.field"),
      static::cfg("$type.describe.type"),
      static::cfg("$type.describe.length"),
      static::cfg("$type.describe.null")
    );
    $explode = explode('.', $table);
    $where = array(
      static::cfg("$type.describe.schema") . " = '{$explode[0]}'",
      static::cfg("$type.describe.name") . " = '{$explode[1]}'" 
    );
    $query = "SELECT " . implode(', ', $select) . " FROM " . static::cfg("$type.describe.table") . " WHERE " . implode(' AND ', $where);
    $describe = $this->query($query);
    $fields = array();
    foreach ($describe as $field) {
      $fields[$field[$select[0]]] = array(
        'type' => trim($field[$select[1]]),
        'length' => trim($field[$select[2]]),
        'null' => trim($field[$select[3]]) === 'Y' ? true : false
      );
    }
    return array(
      'fields' => $fields
    );
  }

  protected function type($table, $column)
  {
    $describe = $this->describe($table);
    return $describe['fields'][$column]['type'];
  }
  
  protected function tableName($class)
  {
    $class = is_object($class) ? get_class($class) : $class;
    return $class::cfg('datasource.odbc.table');
  }

  protected function className($table)
  {
  }

  protected function search($class, $search = array(), $logic = '$and',
    $op = '=')
  {
    $where = array();
    foreach ($search as $conditions) {
      foreach ($conditions as $attribute => $condition) {
        switch ($attribute) {
        case '$has':
          if (!$class) {
            $where[] = '0 = 1';
            break;
          }
          $tags = array_shift($condition);
          if (!is_array($tags)) {
            $tags = array(
              $tags
            );
          }
          $relation = array_shift($condition);
          $intersect = array();
          foreach ($tags as $tag) {
            if (!$tag) {
              $where[] = '0 = 1';
              break;
            }
            $hasTable = $this->hasTable($class, $tag);
            if (!$tag->id || !$this->tableExists($hasTable)) {
              $where[] = '0 = 1';
              break;
            }
            $relation['tag'] = $tag;
            $hasWhere = $this->conditions(null, $relation);
            $hasQuery = "SELECT model AS id FROM $hasTable WHERE $hasWhere";
            $intersect[] = "($hasQuery)";
          }
          if ($intersect) {
            $where[] = "id IN " . implode(" AND id IN ", $intersect);
          }
          continue 2;
        case '$belongs':
          if (!$class) {
            $where[] = '0 = 1';
            break;
          }
          $models = array_shift($condition);
          if (!is_array($models)) {
            $models = array(
              $models
            );
          }
          $relation = array_shift($condition);
          $intersect = array();
          foreach ($models as $model) {
            if (!$model) {
              $where[] = '0 = 1';
              break;
            }
            $hasTable = $this->hasTable($model, $class);
            if (!$model->id || !$this->tableExists($hasTable)) {
              $where[] = '0 = 1';
              break;
            }
            $relation['model'] = $model;
            $hasWhere = $this->conditions(null, $relation);
            $hasQuery = "SELECT tag AS id FROM $hasTable WHERE $hasWhere";
            $intersect[] = "($hasQuery)";
          }
          if ($intersect) {
            $where[] = "id IN " . implode(" AND id IN ", $intersect);
          }
          continue 2;
        case '$and':
        case '$or':
          $where[] = $this->search($class, $condition, $attribute);
          continue 2;
        default:
          if (!is_array($condition)) {
            $condition = array(
              '=' => $condition
            );
          }
          elseif (is_numeric(key($condition))) {
            $or = array();
            foreach ($condition as $v) {
              $or[] = array(
                $attribute => array(
                  '=' => $v
                )
              );
            }
            $where[] = $this->search($class, $or, '$or');
            continue 2;
          }
          switch (key($condition)) {
          case '=':
            $op = '=';
            $v = array_shift($condition);
            break;
          case '$lt':
          case '<':
            $op = '<';
            $v = array_shift($condition);
            break;
          case '$lte':
          case '<=':
            $op = '<=';
            $v = array_shift($condition);
            break;
          case '$gt':
          case '>':
            $op = '>';
            $v = array_shift($condition);
            break;
          case '$gte':
          case '>=':
            $op = '>=';
            $v = array_shift($condition);
            break;
          case '$in':
            $in = array();
            foreach (array_shift($condition) as $v) {
              $in[] = array(
                $attribute => array(
                  '=' => $v
                )
              );
            }
            $where[] = $this->search($class, $in, '$or');
            continue 2;
          case '$nin':
            $nin = array();
            foreach (array_shift($condition) as $v) {
              $nin[] = array(
                $attribute => array(
                  '!=' => $v
                )
              );
            }
            $where[] = $this->search($class, $nin);
            continue 2;
          case '$all':
            $all = array();
            foreach (array_shift($condition) as $v) {
              $all[] = array(
                $attribute => array(
                  '=' => $v
                )
              );
            }
            $where[] = $this->search($class, $all);
            continue 2;
          case '$mod':
            $v = array_shift($condition);
            $op = "% {$v[0]} = ";
            $v = $v[1];
            break;
          case '$ne':
          case '<>':
          case '!=':
            $op = '!=';
            $v = array_shift($condition);
            break;
          case '$regex':
            $op = 'REGEXP';
            $v = array_shift($condition);
            break;
          default:
            $v = array_shift($condition);
          }
          $k = array_search($attribute,
                  array_flip(
                    $class::cfg('datasource.odbc.mappings'))) ? : $attribute;
          if ($v instanceof Core\Model) {
            if (!$v->id) {
              $v->save();
            }
            $v = $v->id;
          }
          elseif (is_array($v)) {
            foreach ($v as $_v) {
              $_v = addslashes($_v);
              $where[] = "FIND_IN_SET('$_v', $k)";
            }
            continue 2;
          }
          elseif ($v instanceof DateTime) {
            $v = $v->format(self::DATETIME_FORMAT);
          }
          elseif ($this->isRegex($v)) {
            $op = 'REGEXP';
            $v = trim($v, $v[0]);
          }
          $op = is_null($v) ? ($op === '=' ? 'IS' : 'IS NOT') : $op;
          if (is_null($v)) {
            $v = 'NULL';
          }
          else {
            $type = $this->type($this->tableName($class), $k);
            switch ($type) {
            case 'NUMERIC':
              $v = addslashes($v);
              break;
            default:
              $v = "'" . addslashes($v) . "'";
            }
          }
          $where[] = "$k $op $v";
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

  protected function conditions($class, $search = array(), $logic = '$and',
    $op = '=')
  {
    $and = array();
    $where = array();
    foreach ($search as $attribute => $value) {
      switch ($attribute) {
      case is_numeric($attribute):
        $and[] = $value;
        break;
      case '$and':
      case '$or':
        $where[] = $this->search($class, $value, $attribute);
        break;
      default:
        $and[] = array(
          $attribute => $value
        );
      }
    }
    if ($and) {
      $where[] = $this->search($class, $and);
    }
    switch ($logic) {
    case '$and':
      $logic = 'AND';
      break;
    case '$or':
      $logic = 'OR';
      break;
    }
    $where = implode(" $logic ", $where);
    return $where ? $where : '1 = 1';
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
          $sort[] = "$k $d";
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
      $value = trim($value);
      $normalize[$field] = $value ? : null;
    }
    return $normalize;
  }
  
  protected function normalizeModel(&$model)
  {
    $class = get_class($model);
    foreach (get_object_vars($model) as $attribute => $value) {
      if (($type = $class::cfg("attributes.$attribute.type")) && is_subclass_of($type, 'Aldu\Core\Model')) {
        $model->$attribute = $type::instance($value);
      }
    }
  }

  protected function select($class, $search = array(), $options = array())
  {
    $table = $this->tableName($class);
    $where = $this->conditions($class, $search);
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
      $this->normalizeModel($model);
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
    return array();
  }

  public function has($model, $tag = null, $relation = array(),
    $search = array(), $options = array())
  {
    return array();
  }
}
