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
    'revisions' => false
  );
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
        __DIR__ . DS . 'MySQL' . DS . 'create-index-table.sql');
      $this->query($query);
    }
  }

  public function __destruct()
  {
    if ($this->link) {
      $this->link->close();
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
        $arg = $this->link->real_escape_string($arg);
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
      while ($row = $result->fetch_assoc()) {
        $rows[] = $row;
      }
      $result->free();
      $cache = $rows;
    }
    return $cache;

  }

  protected function describe($table)
  {
    $describe = $this->query("DESCRIBE `%s`", $table);
    $keys = array(
      'primary' => array(), 'unique' => array(), 'indexes' => array()
    );
    $fields = array();
    foreach ($describe as $field) {
      $fields[$field['Field']] = array(
        'type' => $field['Type'],
        'null' => $field['Null'] === 'YES' ? true : false,
        'default' => $field['Default'], 'extra' => $field['Extra']
      );
      switch ($field['Key']) {
      case 'PRI':
        $keys['primary'][] = $field['Field'];
        break;
      case 'UNI':
        $keys['unique'][] = $field['Field'];
        break;
      case 'IND':
        $keys['indexes'][] = $field['Field'];
        break;
      }
    }
    return array(
      'fields' => $fields, 'keys' => $keys
    );
  }

  protected function type($table, $column)
  {
    $describe = $this->describe($table);
    return $describe['fields'][$column]['type'];
  }

  protected function keys($table, $primaryOnly = true)
  {
    $describe = $this->describe($table);
    if ($primaryOnly) {
      return $describe['keys']['primary'];
    }
    return $describe;
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
    if ($table && $this->link->query("SHOW TABLES LIKE '$table'")->num_rows) {
      return $table;
    }
    return false;
  }

  protected function createQuery($class, $attribute, $column = null)
  {
    $options = array_merge(
      array(
        'type' => 'text', 'other' => null, 'null' => true, 'default' => null
      ), $class::cfg("attributes.$attribute"));
    extract($options);
    if (is_subclass_of($type, 'Aldu\Core\Model')) {
      return $this->createQuery($type, 'id', $attribute);
    }
    $column = $column ? : $attribute;
    return $this->createType($column, $options);
  }

  protected function createType($column, $options = array())
  {
    extract(
      array_merge(
        array(
          'type' => null, 'other' => null, 'null' => true, 'default' => null
        ), $options));
    if (is_array($default)) {
      $default = implode(",",
        array_map('var_export', $default, array_fill(0, count($default), true)));
    }
    elseif ($default) {
      $default = var_export($this->denormalizeValue($default), true);
    }
    $default = is_null($default) ? ($null ? "DEFAULT NULL" : '')
      : "DEFAULT $default";
    $null = $null ? "" : "NOT NULL";
    if (is_array($type)) {
      return "`$column` SET("
        . implode(",",
          array_map('var_export', $type, array_fill(0, count($type), true)))
        . ") $null $default,\n\t";
    }
    switch ($type) {
    case 'int':
      return "`$column` INT $other $null $default,\n\t";
    case 'float':
      return "`$column` FLOAT $other $null $default,\n\t";
    case 'text':
      return "`$column` VARCHAR(128) $other $null $default,\n\t";
    case 'textarea':
      return "`$column` TEXT,\n\t";
    case 'date':
      return "`$column` DATE $other $null $default,\n\t";
    case 'time':
      return "`$column` TIME $other $null $default,\n\t";
    case 'datetime':
      return "`$column` DATETIME $other $null $default,\n\t";
    case 'file':
    case 'data':
    case 'blob':
      return "`$column` MEDIUMBLOB $other $null $default,\n\t";
    case 'boolean':
    case 'bool':
      return "`$column` TINYINT(1) $other $null $default,\n\t";
    }
    return "`$column` VARCHAR(128) $other $null $default,\n\t";
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

  protected function createHasTableFor($model, $tag)
  {
    $modelClass = is_object($model) ? get_class($model) : $model;
    $modelTable = $this->tableName($model);
    $tagClass = is_object($tag) ? get_class($tag) : $tag;
    $tagTable = $this->tableName($tag);
    $hasTable = $this->hasTable($model, $tag);
    $query = "CREATE TABLE IF NOT EXISTS `$hasTable` (\n\t";
    $query .= $this->createQuery($modelClass, 'id', 'model');
    $query .= $this->createQuery($tagClass, 'id', 'tag');
    $fields = array();
    foreach ($modelClass::cfg('relations.has') as $class => $relation) {
      if (is_a($tagClass, $class, true)) {
        if (is_array($relation)) {
          $fields = array_replace_recursive($fields, $relation);
        }
      }
    }
    foreach ($tagClass::cfg('relations.belongs') as $class => $relation) {
      if (is_a($modelClass, $class, true)) {
        if (is_array($relation)) {
          $fields = array_replace_recursive($fields, $relation);
        }
      }
    }
    foreach ($fields as $field => $options) {
      $query .= $this->createType($field, $options);
    }
    $query .= "PRIMARY KEY (`model`, `tag`),\n\t";
    $query .= "KEY `tag` (`tag`),\n\t";
    $query .= "FOREIGN KEY (`model`) REFERENCES `$modelTable` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,\n\t";
    $query .= "FOREIGN KEY (`tag`) REFERENCES `$tagTable` (`id`) ON DELETE CASCADE ON UPDATE CASCADE\n";
    $query .= ") ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci";
    return $this->query($query);
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

  protected function denormalizeValue(&$value)
  {
    if ($value instanceof Core\Model) {
      if (!$value->id) {
        $value->save();
      }
      $value = (int) $value->id;
    }
    elseif ($value instanceof DateTime) {
      $value = $value->format(self::DATETIME_FORMAT);
    }
    elseif (is_bool($value)) {
      $value = (int) $value;
    }
    elseif (is_array($value)) {
      $this->denormalizeArray($value);
    }
    return $value;
  }

  protected function denormalizeArray(&$array)
  {
    foreach ($array as $attribute => &$value) {
      $this->denormalizeValue($value);
    }
  }

  protected function normalizeRow($class, &$row)
  {
    $table = $this->tableName($class);
    foreach ($row as $field => &$value) {
      if (($type = $class::cfg("attributes.$field.type"))
        && is_subclass_of($type, 'Aldu\Core\Model')) {
        $value = $type::first(array(
          'id' => $value
        ));
      }
    }
  }

  protected function hasTable($class, $tag = null)
  {
    $table = $this->tableName($class);
    $hasTable = $tag ? $this->tableName($tag) : '%';
    return "_tags-$table-$hasTable";
  }

  protected function conditions($search = array(), $op = '=', $logic = '$and')
  {
    $where = array();
    $and = array();
    foreach ($search as $attribute => $value) {
      switch ($attribute) {
      case '$has':
        if (!$class) {
          return 0;
        }
        $tags = array_shift($value);
        $intersect = array();
        foreach ($tags as $tag) {
          $hasTable = $this->hasTable($class, $tag);
          if (!$tag->id || !$this->tableExists($hasTable)) {
            return '0';
          }
          $hasQuery = "SELECT `model` AS `id` FROM `$hasTable` WHERE `tag` = {$tag
            ->id}";
          $intersect[] = "($hasQuery)";
        }
        if ($intersect) {
          $where[] = "`id` IN " . implode(" AND `id` IN ", $intersect);
        }
        continue 2;
      case '$and':
      case '$or':
        $logic = $attribute;
        foreach ($value as $conditions) {
          foreach ($conditions as $attribute => $condition) {
            if (!is_array($condition)) {
              $condition = array('=' => $condition);
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
                    $attribute => array('=' => $_v)
                  );
                }
                $where[] = $this
                  ->conditions(
                    array(
                      '$or' => $in
                    ));
                continue 2;
              case '$nin':
                $nin = array();
                foreach ($v as $_v) {
                  $nin[] = array(
                    $attribute => array('!=' => $_v)
                  );
                }
                $where[] = $this
                  ->conditions(
                    array(
                      '$and' => $nin
                    ));
                continue 2;
              case '$all':
                $all = array();
                foreach ($v as $_v) {
                  $all[] = array(
                    $attribute => array('=' => $_v)
                  );
                }
                $where[] = $this
                  ->conditions(
                    array(
                      '$and' => $all
                    ));
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
              $k = $this->link->real_escape_string($attribute);
              $op = is_null($v) ? 'IS' : $op;
              $v = is_null($v) ? 'NULL'
                : "'{$this->link->real_escape_string($v)}'";
              $where[] = "`$k` $op $v";
            }
          }
        }
        continue 2;
      default:
        if (is_array($value)) {
          $or = array();
          foreach ($value as $k => $v) {
            switch ((string) $k) {
            case '=':
            case '$lt':
            case '<':
            case '$lte':
            case '<=':
            case '$gt':
            case '>':
            case '$gte':
            case '>=':
            case '$in':
            case '$nin':
            case '$all':
            case '$mod':
            case '$ne':
            case '<>':
            case '!=':
            case '$regex':
              $and[] = array(
                $attribute => $value
              );
              break 3;
            }
            $or[] = array(
              $attribute => array('=' => $v)
            );
          }
          $where[] = $this->conditions(array(
              '$or' => $or
            ));
        }
        else {
          $and[] = array(
            $attribute => array('=' => $value)
          );
        }
      }
    }
    if ($and) {
      $where[] = $this->conditions(array(
          '$and' => $and
        ));
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
    return $where ? "($where)" : '1';
  }

  protected function options($options = array())
  {
    $return = array();
    foreach ($options as $option => $value) {
      switch ($option) {
      case 'group':
        $return[0] = "GROUP BY " . $this->link->real_escape_string($value);
        break;
      case 'order':
      case 'sort':
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
          $k = $this->link->real_escape_string($k);
          if (is_array($s)) {
            $sort[] = "FIELD(`$k`, " . implode(', ', $s) . ")";
            continue;
          }
          $d = $s > 0 ? 'ASC' : 'DESC';
          $sort[] = "`$k` $d";
        }
        $return[1] = $sort ? "ORDER BY " . implode(', ', $sort) : '';
        break;
      case 'limit':
        if ($value < 0) {
          $value = '18446744073709551615';
        }
        $return[2] = "LIMIT " . $this->link->real_escape_string($value);
        break;
      case 'offset':
      case 'skip':
        $limit = isset($options['limit']) ? '' : "LIMIT 18446744073709551615";
        $return[3] = "$limit OFFSET " . $this->link->real_escape_string($value);
        break;
      }
    }
    ksort($return);
    return implode(' ', $return);
  }

  protected function select($class, $search = array(), $options = array())
  {
    $table = $this->tableName($class);
    if (!$this->tableExists($table)) {
      return array();
    }
    $tables = $this->tables("$table-%");
    $join = array();
    foreach ($tables as $extension) {
      $join[] = "LEFT OUTER JOIN `$extension` USING (`id`)";
    }
    $join = implode(' ', $join);
    $where = $this->conditions($search);
    $options = $this->options($options);
    $query = "SELECT * FROM `$table` $join WHERE $where $options";
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
    foreach ($select as &$row) {
      $this->normalizeRow($class, $row);
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
    $fields = array();
    $class = get_class($model);
    $tables = $this->tablesFor($class);
    $this->link->autocommit(false);
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
        $values = array_intersect_key($fields,
          array_flip($this->columns($table)));
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
        $query = "INSERT INTO `$table` (`" . implode('`, `', $columns)
          . "`) VALUES ";
        $query .= "(" . implode(', ', $placeholders)
          . ") ON DUPLICATE KEY UPDATE " . implode(', ', $update);
        $this->query($query, $values);
        if (!$model->id) {
          $model->id = $fields['id'] = $this->link->insert_id;
        }
      }
    } catch (Exception $e) {
      $this->link->rollback();
      $this->link->autocommit(true);
      return false;
    }
    $this->link->commit();
    $this->link->autocommit(true);
    return true;
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
        return $this->query("DELETE FROM `$table`");
      }
    }
    foreach ($this->read($class, $search) as $model) {
      $this->delete($model);
    }
  }

  public function tag($model, $tags, $relation = array())
  {
    if (!$model->id) {
      $model->save();
    }
    foreach ($tags as $tag) {
      if (!$tag->id) {
        $tag->save();
      }
      $hasTable = $this->hasTable($model, $tag);
      if (!$this->tableExists($hasTable)) {
        $this->createHasTableFor($model, $tag);
      }
      $fields = array_merge(
        array(
          'model' => $model, 'tag' => $tag
        ), $relation);
      $this->denormalizeArray($fields);
      $columns = implode('`, `', array_keys($fields));
      $values = implode(', ',
        array_map('var_export', $fields, array_fill(0, count($fields), true)));
      $query = "REPLACE INTO `$hasTable` (`$columns`) VALUES ($values)";
      return $this->query($query);
    }
  }

  public function untag($model, $tags = array())
  {
    if (!$model->id) {
      return false;
    }
    if (empty($tags)) {
      foreach ($this->tables($this->hasTable($model)) as $table) {
        $this->query("DELETE FROM `$table` WHERE `model` = %s", $model->id);
      }
    }
    foreach ($tags as $tag) {
      if ($tag->id
        && $table = $this->tableExists($this->hasTable($model, $tag))) {
        $this
          ->query("DELETE FROM `$table` WHERE `model` = %s AND `tag` = %s",
            $model->id, $tag->id);
      }
    }
  }

  public function has($model, $tag = null, $relation = array(),
    $search = array(), $options = array())
  {
    if (!$model->id) {
      return array();
    }
    $tags = array();
    $relation['model'] = $model->id;
    if (!isset($options['sort'])) {
      $options['sort'] = array(
        'weight' => 1
      );
    }
    $options = $this->options($options);
    if (is_object($tag)) {
      if (!$tag->id) {
        return array();
      }
      $relation['tag'] = $tag->id;
      $where = $this->conditions($relation);
      if ($hasTable = $this->tableExists($this->hasTable($model, $tag))) {
        if (static::cfg('debug.has')) {
          static::cfg('debug.all', true);
        }
        if ($this
          ->query("SELECT `tag` FROM `%s` WHERE $where $options", $hasTable)) {
          $tags[] = $tag;
        }
        if (static::cfg('debug.has')) {
          static::cfg('debug.all', false);
        }
      }
    }
    elseif (is_subclass_of($tag, 'Aldu\Core\Model')) {
      $where = $this->conditions($relation);
      if ($hasTable = $this->tableExists($this->hasTable($model, $tag))) {
        if (static::cfg('debug.has')) {
          static::cfg('debug.all', true);
        }
        $rows = $this
          ->query("SELECT `tag` FROM `%s` WHERE $where $options", $hasTable);
        if (static::cfg('debug.has')) {
          static::cfg('debug.all', false);
        }
        if ($search['id'] = array_map(
          function ($row)
          {
            return $row['tag'];
          }, $rows)) {
          $tags = $tag::read($search,
            array(
              'sort' => array(
                'id' => $search['id']
              )
            ));
        }
      }
    }
    else {
      $where = $this->conditions($relation);
      $modelClass = $this->tableName($model);
      foreach ($this->tables($this->hasTable($model)) as $hasTable) {
        $tagClass = $this
          ->className(substr($hasTable, strlen("_tags-$modelClass-")));
        if (static::cfg('debug.has')) {
          static::cfg('debug.all', true);
        }
        $rows = $this
          ->query("SELECT `tag` FROM `%s` WHERE $where $options", $hasTable);
        if (static::cfg('debug.has')) {
          static::cfg('debug.all', false);
        }
        if ($search['id'] = array_map(
          function ($row)
          {
            return $row['tag'];
          }, $rows)) {
          $tags = array_merge($tags,
            $tagClass::read($search,
              array(
                'sort' => array(
                  'id' => $search['id']
                )
              )));
        }
      }
    }
    return $tags;
  }

  public function restore($model, $revision = null)
  {
    $revision = $revision ? : ($this->nextId($model) - 2);
    $table = $this->tableName($model);
    $query = "INSERT INTO `%s` SET `id` = %s, `_revision` = %s ON DUPLICATE KEY UPDATE `_revision` = VALUES (`_revision`)";
    $this->query($query, $table, $model->id, $revision);
  }

  protected function createRevisionsTables($table)
  {
    $this->createRevisionsTable($table);
    foreach ($this->tables("$table-%") as $extTable) {
      $this->createRevisionsTableExt($extTable, $table);
    }
    $this->createTriggerBeforeInsert($table);
    $this->createTriggerAfterInsert($table);
    $this->createTriggerBeforeUpdate($table);
    $this->createTriggerAfterUpdate($table);
    $this->createTriggerAfterDelete($table);
  }

  /**
   * Create a revision table based to original table.
   *
   * @param string $table
   */

  protected function createRevisionsTable($table)
  {
    foreach (explode('##',
      file_get_contents(
        __DIR__ . DS . 'MySQL' . DS . 'create-revision-table.sql')) as $sql) {
      $this->query($sql, $table);
    }
    $this
      ->query(
        "INSERT INTO `_rev-$table` SELECT *, NULL, 'INSERT', NOW() FROM `$table`");
  }

  /**
   * Create a revision table based to original table.
   *
   * @param string $table
   * @param string $parent
   */

  protected function createRevisionsTableExt($table, $parent)
  {
    $key = implode('`, `', $this->keys($table));
    foreach (explode('##',
      file_get_contents(
        __DIR__ . DS . 'MySQL' . DS . 'create-revision-table-ext.sql')) as $sql) {
      $this->query($sql, $table, $key, $parent);
    }
    $this
      ->query(
        "INSERT INTO `_rev-$table` SELECT `t`.*, `p`.`_revision` FROM `$table` AS `t` INNER JOIN `$parent` AS `p` ON `t`.`id`=`p`.`id`");
  }

  protected function createTriggerBeforeInsert($table)
  {
    $columns = $this->columns($table);
    $fields = implode('`, `', $columns);
    ;
    $varFields = implode('`, `var-', $columns);
    foreach ($columns as $field) {
      $declare[] = "DECLARE `var-$field` " . $this->type($table, $field);
      $new[] = "NEW.`$field` = `var-$field`";
    }
    $declare = implode(";\n    ", $declare);
    $new = implode(', ', $new);
    /*
    foreach ($this->keys($table) as $field) {
      $pk[] = "(NEW.`$field` != OLD.`$field` OR NEW.`$field` IS NULL != OLD.`$field` IS NULL)";
      $pk = implode(' OR ', $pk);
    }
     */
    $sql = file_get_contents(
      __DIR__ . DS . 'MySQL' . DS . 'create-revision-trigger-before-insert.sql');
    $this->query("DROP TRIGGER IF EXISTS `$table-beforeinsert`");
    $this->query("DELMITER //");
    $this->query(sprintf($sql, $table, $declare, $fields, $varFields, $new));
    $this->query("DELIMITER ;");
  }

  protected function createTriggerBeforeUpdate($table)
  {
    $columns = $this->columns($table);
    $fields = implode('`, `', $columns);
    ;
    $varFields = implode('`, `var-', $columns);
    foreach ($columns as $field) {
      $declare[] = "DECLARE `var-$field` " . $this->type($table, $field);
      $new[] = "NEW.`$field` = `var-$field`";
    }
    $declare = implode(";\n    ", $declare);
    $new = implode(', ', $new);
    /*
    foreach ($this->keys($table) as $field) {
      $pk[] = "(NEW.`$field` != OLD.`$field` OR NEW.`$field` IS NULL != OLD.`$field` IS NULL)";
      $pk = implode(' OR ', $pk);
    }
     */
    $sql = file_get_contents(
      __DIR__ . DS . 'MySQL' . DS . 'create-revision-trigger-before-update.sql');
    $this->query("DROP TRIGGER IF EXISTS `$table-beforeupdate`");
    $this->query("DELMITER //");
    $this->query(sprintf($sql, $table, $declare, $fields, $varFields, $new));
    $this->query("DELIMITER ;");
  }

  protected function createTriggerAfterInsert($table)
  {
    $this->createTriggerAfterAction($table, 'insert');
  }

  protected function createTriggerAfterUpdate($table)
  {
    $this->createTriggerAfterAction($table, 'update');
  }

  protected function createTriggerAfterAction($table, $action)
  {
    $key = implode('`, NEW.`', $this->keys($table));
    foreach ($this->columns($table) as $field) {
      $fields[] = "`$field` = NEW.`$field`";
    }
    $fields = implode(', ', $fields);
    $sql = file_get_contents(
      __DIR__ . DS . 'MySQL' . DS . 'create-revision-trigger-after-action.sql');
    $this->query("DROP TRIGGER IF EXISTS `$table-after$action`");
    $this->query("DELMITER //");
    $this->query($sql, $table, $action, $fields, $key);
    $this->query("DELIMITER ;");
  }

  protected function createTriggerAfterDelete($table)
  {
    $pk = implode('`, OLD.`', $this->keys($table));
    $sql = file_get_contents(
      __DIR__ . DS . 'MySQL' . DS . 'create-revision-trigger-after-delete.sql');
    $this->query("DROP TRIGGER IF EXISTS `$table-afterdelete`");
    $this->query("DELMITER //");
    $this->query($sql, $table, $pk);
    $this->query("DELIMITER ;");
  }
}
