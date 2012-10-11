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
    'configurations' => array(
      array(
        'class' => 'Aldu\Core\Model',
        'configuration' => array(
          'datasource' => array(
            'ldap' => array(
              'openldap' => array(
                'mappings' => array(
                  '_created' => 'createTimestamp',
                  '_updated' => 'modifyTimestamp'
                )
              ),
              'ad' => array(
                'mappings' => array(
                  '_created' => 'whenCreated',
                  '_updated' => 'whenChanged'
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

  protected function dn($model)
  {
    $dn = array();
    $class = get_class($model);
    $rdn = $class::cfg(
      'datasource.ldap.' . $class::cfg('datasource.ldap.type') . '.rdn') ? 
      : 'name';
    $attribute = array_search($rdn,
      $class::cfg(
        'datasource.ldap.' . $class::cfg('datasource.ldap.type') . '.mappings')) ? 
      : $rdn;
    $dn[] = "$rdn={$model->$attribute}";
    if ($base = $class::cfg(
      'datasource.ldap.' . $class::cfg('datasource.ldap.type') . '.base')) {
      $dn[] = $base;
    }
    $dn[] = $this->base;
    return implode(',', $dn);
  }

  protected function conditions($search = array(), $class = null, $op = '=',
    $logic = '$and')
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
        $relation = array_shift($value);
        $intersect = array();
        foreach ($tags as $tag) {
          $hasTable = $this->hasTable($class, $tag);
          if (!$tag->id || !$this->tableExists($hasTable)) {
            return '0';
          }
          $relation['tag'] = $tag;
          $hasWhere = $this->conditions($relation, $class);
          $hasQuery = "SELECT `model` AS `id` FROM `$hasTable` WHERE $hasWhere";
          $intersect[] = "($hasQuery)";
        }
        if ($intersect) {
          $where[] = "`id` IN " . implode(" AND `id` IN ", $intersect);
        }
        continue 2;
      case '$not':
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
                      '=' => $_v
                    )
                  );
                }
                $where[] = $this
                  ->conditions(
                    array(
                      '$not' => $nin
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
                // Not supported in LDAP
                continue 3;
                $op = "% {$v[0]} = ";
                $v = $v[1];
                break;
              case '$ne':
              case '<>':
              case '!=':
                $op = '!=';
                break;
              case '$regex':
                // Not supported in LDAP
                continue 3;
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
                // Not supported in LDAP
                continue 2;
                $op = 'REGEXP';
                $v = trim($v, $v[0]);
              }
              if (is_string($attribute)) {
                $k = array_search($attribute,
                  array_flip(
                    $class::cfg(
                      'datasource.ldap.' . $class::cfg('datasource.ldap.type')
                        . '.mappings'))) ? : $attribute;
              }
              $where[] = "$k$op$v";
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
              $attribute => array(
                '=' => $v
              )
            );
          }
          $where[] = $this
            ->conditions(array(
              '$or' => $or
            ), $class);
        }
        else {
          $and[] = array(
            $attribute => array(
              '=' => $value
            )
          );
        }
      }
    }
    if ($and) {
      $where[] = $this->conditions(array(
          '$and' => $and
        ), $class);
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
          $normalize[$key] = count($value) > 1 ? $value : $value[0];
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
      $filter = $this->conditions($search, $class);
    }
    $attributes = array_map(
      function ($attribute) use ($class, $type)
      {
        return $class::cfg("datasource.ldap.$type.mappings.$attribute") ? 
          : $attribute;
      }, array_keys(get_public_vars($class)));
    return ldap_search($this->link, $base, $filter, $attributes);
  }

  public function first($class, $search = array())
  {
    $search = $this->search($class, $search);
    if (!$result = ldap_first_entry($this->link, $search)) {
      return null;
    }
    $entry = ldap_get_attributes($this->link, $result);
    if (!is_array($entry)) {
      return null;
    }
    return new $class($this->normalize($class, $entry));
  }

  public function read($class, $search = array())
  {
    $models = array();
    $search = $this->search($class, $search);
    foreach (ldap_get_entries($this->link, $search) as $entry) {
      if (!is_array($entry)) {
        continue;
      }
      $models[] = new $class($this->normalize($class, $entry));
    }
    return $models;
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

  public function authenticate($class, $dn, $password, $dnKey, $pwKey, $pwEnc)
  {
    if (@ldap_bind($this->link, $id, $password)) {
      return $this->first($class, array(
          $idKey => $id
        ));
    }
    return false;
  }
}
