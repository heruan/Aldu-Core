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
  protected static $configuration = array(
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
    $conn = array_merge(array(
      'host' => 'localhost',
      'port' => self::DEFAULT_PORT
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
    $rdn = $class::cfg('datasource.ldap.' . $class::cfg('datasource.ldap.type') . '.rdn') ? : 'name';
    $attribute = array_search($rdn, $class::cfg('datasource.ldap.'
      . $class::cfg('datasource.ldap.type') . '.mappings')) ? : $rdn;
    $dn[] = "$rdn={$model->$attribute}";
    if ($base = $class::cfg('datasource.ldap.' . $class::cfg('datasource.ldap.type') . '.base')) {
      $dn[] = $base;
    }
    $dn[] = $this->base;
    return implode(',', $dn);
  }

  protected function filter($class, $search = array())
  {
    if (empty($search)) {
      return self::FILTER_ALL;
    }
    $filter = array();
    foreach ($search as $key => $value) {
    }
    return $this->filterAnd($class, $search);
  }

  protected function filterAnd($class, $array = array())
  {
    $and = array();
    foreach ($array as $key => $value) {
      if (is_string($key)) {
        $key = array_search($key, array_flip($class::cfg('datasource.ldap.'
          . $class::cfg('datasource.ldap.type') . '.mappings'))) ? : $key;
      }
      if (is_string($key) && is_array($value)) {
        $or = array();
        foreach ($value as $_value) {
          $or[] = "$key=$_value";
        }
        $and[] = "|(" . implode(")(", $or) . ")";
      }
      elseif (is_string($key) && is_string($value)) {
        $and[] = "$key=$value";
      }
    }
    return "(&(" . implode(")(", $and) . "))";
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
          $key = array_search($key, $class::cfg("datasource.ldap.$type.mappings")) ? : $key;
          if ($ref = $class::cfg("datasource.ldap.$type.references.$key")) {
            array_walk($value, function (&$item, $key, $ref)
            {
              $refClass = $ref['class'];
              $item = $refClass::first(array(
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
      $base = implode(',', array(
        $class::cfg("datasource.ldap.$type.base"),
        $this->base
      ));
      if (!isset($search['objectClass'])) {
        $search['objectClass'] = $class::cfg("datasource.ldap.$type.objectClass");
      }
      $filter = $this->filter($class, $search);
    }
    $attributes = array_map(function($attribute)use($class, $type) {
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
    if (@ldap_bind($this->link, $class::cfg('datasource.auth.prefix') . $dn, $password)) {
      return $this->first($class, array(
        $dnKey => $dn
      ));
    }
    return false;
  }
}
