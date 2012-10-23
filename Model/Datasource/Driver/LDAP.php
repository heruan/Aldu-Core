<?php
/**
 * Aldu\Core\Model\Datasource\Driver\LDAP
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
use Aldu\Core\Model\Datasource\DriverInterface;
use Aldu\Core\Model\Datasource;
use Aldu\Core;
use Aldu\Core\Exception;

class LDAP extends Datasource\Driver implements DriverInterface
{
  const DEFAULT_PORT = 389;
  const FILTER_ALL = '(objectClass=*)';
  const DATETIME_FORMAT = 'U';
  protected static $configuration = array(
    __CLASS__ => array(
      'debug' => array(
        'all' => false
      ),
      'openldap' => array(
        'authentication' => array(
          'dn' => true
        )
      ),
      'configurations' => array(
        array(
          'class' => 'Aldu\Core\Model',
          'configuration' => array(
            'datasource' => array(
              'ldap' => array(
                'openldap' => array(
                  'mappings' => array(
                    'created' => 'createTimestamp',
                    'updated' => 'modifyTimestamp'
                  )
                ),
                'ad' => array(
                  'mappings' => array(
                    'created' => 'whenCreated', 'updated' => 'whenChanged'
                  )
                )
              )
            )
          )
        )
      )
    )
  );
  protected $base;

  public function __construct($url, $parts)
  {
    parent::__construct($url);
    $conn = array_merge(
      array(
        'host' => 'localhost', 'port' => self::DEFAULT_PORT
      ), $parts);
    if (!$this->link = ldap_connect($conn['host'], $conn['port'])) {
    }
    ldap_set_option($this->link, LDAP_OPT_PROTOCOL_VERSION, 3);
    if (isset($conn['user']) && isset($conn['pass'])) {
      ldap_bind($this->link, $conn['user'], $conn['pass']);
    }
    $this->base = ltrim($conn['path'], '/');
    foreach (static::cfg('configurations') as $conf) {
      $conf['class']::cfg($conf['configuration']);
    }
  }

  public function __destruct()
  {
    ldap_close($this->link);
  }

  protected function dn($model, $value = null)
  {
    $dn = array();
    $class = is_object($model) ? get_class($model) : $model;
    $rdn = $class::cfg(
      'datasource.ldap.' . $class::cfg('datasource.ldap.type') . '.rdn') ?
      : 'name';
    $attribute = array_search($rdn,
      $class::cfg(
        'datasource.ldap.' . $class::cfg('datasource.ldap.type') . '.mappings')) ?
      : $rdn;
    $dn[] = $value ? "$rdn=$value" : "$rdn={$model->$attribute}";
    if ($base = $class::cfg(
      'datasource.ldap.' . $class::cfg('datasource.ldap.type') . '.base')) {
      $dn[] = $base;
    }
    $dn[] = $this->base;
    return implode(',', $dn);
  }

  protected function _conditions($class, $search = array(), $logic = '$and',
    $op = '=')
  {
    $where = array();
    foreach ($search as $conditions) {
      foreach ($conditions as $attribute => $condition) {
        switch ($attribute) {
        case '$has':
          if (!$class) {
            $where[] = 'FALSE';
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
              $where[] = 'FALSE';
              break;
            }
            $hasTable = $this->hasTable($class, $tag);
            if (!$tag->id || !$this->tableExists($hasTable)) {
              $where[] = 'FALSE';
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
            $where[] = 'FALSE';
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
              $where[] = 'FALSE';
              break;
            }
            $hasTable = $this->hasTable($model, $class);
            if (!$model->id || !$this->tableExists($hasTable)) {
              $where[] = 'FALSE';
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
          $where[] = $this->_conditions($class, $condition, $attribute);
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
            $where[] = $this->_conditions($class, $or, '$or');
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
            $where[] = $this->_conditions($class, $in, '$or');
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
            $where[] = $this->_conditions($class, $nin);
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
            $where[] = $this->_conditions($class, $all);
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
          if (is_string($attribute)) {
            $k = array_search($attribute,
              array_flip(
                $class::cfg(
                  'datasource.ldap.' . $class::cfg('datasource.ldap.type')
                    . '.mappings'))) ? : $attribute;
          }
          if ($v instanceof Core\Model) {
            if (!$v->id) {
              $v->save();
            }
            $v = $v->id;
          }
          elseif (is_array($v)) {
            $where[] = $this->conditions($class, array(
                $k => $v
              ));
            continue 2;
          }
          elseif ($v instanceof DateTime) {
            $v = $v->format(self::DATETIME_FORMAT);
          }
          elseif ($this->isRegex($v)) {
            $op = 'REGEXP';
            $v = trim($v, $v[0]);
          }
          $where[] = "$k$op$v";
        }
      }
    }
    switch ($logic) {
    case '$not':
      $logic = '!';
      break;
    case '$and':
      $logic = '&';
      break;
    case '$or':
      $logic = '|';
      break;
    }
    if (count($where) === 1) {
      return array_shift($where);
    }
    $where = "$logic(" . implode(")(", $where) . ")";
    return $where ? $where : self::FILTER_ALL;
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
        $where[] = $this->_conditions($class, $value, $attribute);
        break;
      default:
        $and[] = array(
          $attribute => $value
        );
      }
    }
    foreach ($and as $_and) {
      $where[] = $this->_conditions($class, array(
          $_and
        ));
    }
    switch ($logic) {
    case '$not':
      $logic = '!';
      break;
    case '$and':
      $logic = '&';
      break;
    case '$or':
      $logic = '|';
      break;
    }
    if (count($where) === 1) {
      return array_shift($where);
    }
    $where = "$logic(" . implode(")(", $where) . ")";
    return $where ? "($where)" : self::FILTER_ALL;
  }

  protected function normalize($class, $array)
  {
    $normalize = array();
    foreach ($array as $key => $value) {
      if (is_string($key)) {
        if ($key == 'count') {
          continue;
        }
        if (is_array($value)) {
          unset($value['count']);
          $key = $key;
          $type = $class::cfg('datasource.ldap.type');
          $key = array_search($key,
            $class::cfg("datasource.ldap.$type.mappings")) ? : $key;
          if ($ref = $class::cfg("datasource.ldap.$type.references.$key")) {
            array_walk($value,
              function (&$item, $key, $ref)
              {
                $refClass = $ref['class'];
                $item = $refClass::first(
                  array(
                    $ref['attribute'] => $item
                  ));
              }, $ref);
          }
          $this->normalizeAttribute($class, $key, $value);
          if (!$class::cfg("attributes.$key.multiple")) {
            $value = array_shift($value);
          }
          $normalize[$key] = $value;
        }
      }
    }
    return $normalize;
  }

  protected function normalizeArray(&$array)
  {
    foreach ($array as $label => &$value) {
      if ($value instanceof Core\Model || MongoDBRef::isRef($value)) {
        $this->normalizeReference($value);
        if (is_null($value)) {
          unset($array[$label]);
        }
      }
      elseif (is_array($value)) {
        $this->normalizeArray($value);
      }
    }
  }

  protected function search($class, $search = array())
  {
    $models = array();
    if (is_string($search)) {
      $base = $search;
      $filter = self::FILTER_ALL;
    }
    else {
      $type = $class::cfg('datasource.ldap.type');
      $base = implode(',',
        array(
          $class::cfg("datasource.ldap.$type.base"), $this->base
        ));
      if (!isset($search['objectClass'])) {
        $search['objectClass'] = $class::cfg(
          "datasource.ldap.$type.objectClass");
      }
      $search = array_replace_recursive($search,
        $class::cfg("datasource.ldap.$type.filter"));
      $filter = $this->conditions($class, $search);
    }
    $attributes = array_map(
      function ($attribute) use ($class, $type)
      {
        return $class::cfg("datasource.ldap.$type.mappings.$attribute") ?
          : $attribute;
      }, array_keys(get_public_vars($class)));
    if (static::cfg('debug.all')) {
      var_dump($filter);
    }
    return ldap_search($this->link, $base, $filter, $attributes);
  }

  protected function mapping($class, &$array, $flip = false)
  {
    $type = $class::cfg('datasource.ldap.type');
    $keys = array();
    if (!$map = $class::cfg("datasource.ldap.$type.mappings")) {
      return $array;
    }
    $map = $flip ? array_flip($map) : $map;
    if (!is_array($array)) {
      return $array = array_search($array, $map) ? : $array;
    }
    foreach ($array as $k => $v) {
      $keys[] = array_search($k, $map) ? : $k;
    }
    return $array = array_combine($keys, $array);
  }

  protected function options($class, $options = array(), &$search)
  {
    foreach ($options as $option => $value) {
      switch ($option) {
      case 'sort':
      case 'order':
        $sort = array();
        if (!is_array($value)) {
          $value = array(
            $value => 1
          );
        }
        $value = array_reverse($value);
        foreach ($value as $k => $v) {
          if (is_numeric($k)) {
            $k = $v;
            $v = 1;
          }
          $k = $this->mapping($class, $k, true);
          ldap_sort($this->link, $search, $k);
        }
      }
    }
  }

  public function first($class, $search = array(), $options = array())
  {
    $search = $this->search($class, $search);
    $this->options($class, $options, $search);
    if (!$result = ldap_first_entry($this->link, $search)) {
      return null;
    }
    $entry = ldap_get_attributes($this->link, $result);
    if (!is_array($entry)) {
      return null;
    }
    return new $class($this->normalize($class, $entry));
  }

  public function read($class, $search = array(), $options = array())
  {
    $models = array();
    $search = $this->search($class, $search);
    $this->options($class, $options, $search);
    if ($result = ldap_first_entry($this->link, $search)) {
      do {
        if ($entry = ldap_get_attributes($this->link, $result)) {
          $models[] = new $class($this->normalize($class, $entry));
        }
      } while ($result = ldap_next_entry($this->link, $result));
    }
    return $models;
  }

  public function count($class, $search = array(), $options = array())
  {
    $search = $this->search($class, $search);
    $this->options($class, $options, $search);
    return ldap_count_entries($this->link, $search);
  }

  public function save($models = array())
  {
    if (!is_array($models)) {
      $models = array(
        $models
      );
    }
    foreach ($models as &$model) {
      $class = get_class($model);
      $dn = $this->dn($model);
      $attributes = array();
      foreach ($model->__toArray() as $attribute => $value) {
        $type = $class::cfg('datasource.ldap.type');
        if (is_object($value)) {
          ;
        }
        else {
          $attribute = $class::cfg("datasource.ldap.$type.mappings.$attribute") ?
            : $attribute;
          $attributes[$attribute] = $value;
        }
      }
      if (ldap_search($this->link, $dn, self::FILTER_ALL)) {
        if (!ldap_modify($this->link, $dn, $attributes)) {
          throw new Exception(ldap_error($this->link));
        }
      }
      else {
        if (!ldap_add($this->link, $dn, $attributes)) {
          throw new Exception(ldap_error($this->link));
        }
      }
    }
  }

  public function has($model, $tag = null, $relation = array(),
    $search = array(), $options = array())
  {
    $has = array();
    $modelClass = $this->getClass($model);
    if ($tagClass = $this->getClass($tag)) {
      if (is_object($tag)) {
        return in_array($tag, $model->members);
      }
      foreach ($model->members as $member) {
        $has += $this->read($tagClass, array('uid' => $member->name));
      }
    }
    return $has;
  }

  public function belongs($tag, $model, $relation = array(), $search = array(),
    $options = array())
  {
    $belongs = array();
    $tagClass = $this->getClass($tag);
    $modelClass = $this->getClass($model);
    $belongs += $this->read('Aldu\Auth\Models\Group', array('memberUid' => $tag->name));
    return $belongs;
  }

  public function authenticate($class, $id, $password, $idKey, $pwKey, $pwEnc)
  {
    $type = $class::cfg('datasource.ldap.type');
    if (static::cfg("$type.authentication.dn")) {
      $dn = $this->dn($class, $id);
    }
    else {
      $dn = $id;
    }
    if (@ldap_bind($this->link, $dn, $password)) {
      if (strpos($dn, NS)) {
        $explode = explode(NS, $dn);
        $id = array_pop($explode);
      }
      if (strpos($dn, '@')) {
        $explode = explode('@', $dn);
        $id = array_shift($explode);
      }
      return $class::first(array(
        $idKey => $id
      ));
    }
    return false;
  }
}
