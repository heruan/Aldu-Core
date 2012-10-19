<?php
/**
 * Aldu\Core\Model
 *
 * DBO-backed object data model, for mapping database tables to Aldu Models.
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
 * @package       Aldu\Core
 * @since         AlduPHP(tm) v1.0.0
 * @license       Creative Commons Attribution-ShareAlike 3.0 Unported (CC BY-SA 3.0)
 */

namespace Aldu\Core;

class Model extends Stub
{
  public static $Controller;
  public static $View;
  protected static $configuration = array(__CLASS__ => array(
    'datasource' => array(
      'urls' => array(
        'default' => array(
          'scheme' => 'sqlite',
          'path' => ALDU_DEFAULT_DATASOURCE
        )
      ),
      'search' => array(),
      'options' => array('sort' => array('_' => 1)),
      'ldap' => array(
        'openldap' => array(
          'mappings' => array(
            'created' => 'createTimestamp',
            'updated' => 'modifyTimestamp'
          )
        ),
        'ad' => array(
          'mappings' => array(
            'created' => 'whenCreated',
            'updated' => 'whenChanged'
          )
        )
      )
    )
  ));
  protected static $instances = array();
  protected static $datasources = array();
  protected static $extensions = array();
  protected static $relations = array(
    'has' => array(
      'Aldu\Core\Model' => array(
        '_' => array(
          'type' => 'int',
          'null' => false,
          'default' => 0
        )
      )
    )
  );
  protected static $attributes = array(
    'id' => array(
      'type' => 'int',
      'null' => false,
      'other' => 'unsigned'
    ),
    'acl' => array(
      'type' => array(
        'read',
        'edit',
        'delete'
      ),
      'default' => array(
        'read'
      )
    ),
    'created' => array(
      'type' => 'datetime'
    ),
    'updated' => array(
      'type' => 'datetime'
    ),
    '_' => array(
      'type' => 'int',
      'null' => false,
      'default' => 0
    )
  );

  public $id;
  public $acl;
  public $created;
  public $updated;
  public $_ = 0;

  /**
   *
   * Enter description here ...
   * @param unknown_type $configuration
   * @deprecated
   */

  protected static function configure($configuration = array())
  {
    $class = get_called_class();
    $configuration['attributes'] = $class::$attributes;
    $configuration['extensions'] = $class::$extensions;
    $configuration['relations'] = $class::$relations;
    $configuration = parent::configure($configuration);
    if (!isset(self::$instances[$class])) {
      self::$instances[$class] = array();
    }
    if (!isset(self::$datasources[$class])) {
      self::$datasources[$class] = array();
      foreach ($class::cfg('datasource.urls') as $function => $url) {
        self::$datasources[$class][$function] = new Model\Datasource($url);
      }
    }
    return $configuration;
  }

  public static function instance($id = 0, $model = null)
  {
    $class = get_called_class();
    $class::configure();
    end(self::$instances[$class]);
    $id = $id ?: key($class::$instances[$class]);
    if (!isset(self::$instances[$class][$id])) {
      self::$instances[$class][$id] = $class::first(array('id' => $id));
    }
    return self::$instances[$class][$id];
  }

  public static function datasource($function = 'default')
  {
    $class = get_called_class();
    $class::configure();
    if (isset(self::$datasources[$class][$function])) {
      return self::$datasources[$class][$function];
    }
    return self::$datasources[$class]['default'];
  }

  public function __construct($attributes = array())
  {
    parent::__construct();
    $class = get_class($this);
    foreach ($attributes as $name => $value) {
      if (!property_exists($this, $name)) {
        continue;
      }
      $this->$name = $value;
    }
    if ($this->id) {
      self::$instances[$class][$this->id] = $this;
    }
  }

  public function __toArray()
  {
    $array = array();
    foreach (get_object_vars($this) as $name => $attribute) {
      if ($attribute instanceof DateTime) {
        $attribute = $attribute->format(ALDU_DATETIME_FORMAT);
      }
      elseif ($attribute instanceof self) {
        $attribute = $attribute->id;
      }
      $array[$name] = $attribute;
    }
    return $array;
  }

  public function tag($tags, $relation = array())
  {
    if (!is_array($tags)) {
      $tags = array(
        $tags
      );
    }
    return static::datasource(__FUNCTION__)->tag($this, $tags, $relation);
  }

  public function untag($tags = array())
  {
    return static::datasource(__FUNCTION__)->untag($this, $tags);
  }

  public function has($tag = null, $relation = array(), $search = array(), $options = array())
  {
    return static::datasource(__FUNCTION__)->has($this, $tag, $relation, $search, $options);
  }

  public function belongs($class = null, $relation = array(), $search = array(), $options = array())
  {
    return static::datasource(__FUNCTION__)->belongs($this, $class, $relation, $search, $options);
  }

  public function save($models = array())
  {
    if (empty($models)) {
      $models[] = $this;
    }
    return static::datasource(__FUNCTION__)->save($models);
  }

  public static function count($search = array(), $options = array())
  {
    return self::_read(__FUNCTION__, $search, $options);
  }

  public static function first($search = array(), $options = array())
  {
    if (!is_array($search)) {
      $search = array('id' => $search);
    }
    return self::_read(__FUNCTION__, $search, $options);
  }

  public static function read($search = array(), $options = array())
  {
    return self::_read(__FUNCTION__, $search, $options);
  }

  public static function purge($search = array(), $options = array())
  {
    return self::_read(__FUNCTION__, $search, $options);
  }

  protected static function _read($function)
  {
    $class = get_called_class();
    $request = Net\HTTP\Request::instance();
    $args = func_get_args();
    $function = $args[0];
    $args[0] = $class;
    if ($class::cfg('datasource.acl')) {
      $args[1]['$or'][] = array(
        'acl' => array(
          '=' => array(
            'read'
          )
        )
      );
      if ($request->aro) {
        $belongs = $request->aro->belongs();
        $belongs[] = $request->aro;
        $args[1]['$or'][] = array(
          '$belongs' => array(
            $belongs,
            array(
              'acl' => array(
                '=' => array(
                  'read'
                )
              )
            )
          )
        );
      }
    }
    $args[2] = array_replace_recursive($class::cfg('datasource.options'), $args[2]);
    return call_user_func_array(array(
      static::datasource($function),
      $function
    ), $args);
  }

  public function name()
  {
    if (!$name = static::cfg('name')) {
      $name = explode(NS, get_class($this));
      $name = array_pop($name);
    }
    return $name;
  }

  public function label()
  {
    $label = static::cfg('label') ?: 'name';
    return $this->$label;
  }

  public static function authenticate($username, $password = null, $encrypted = false)
  {
    $class = get_called_class();
    if ($encrypted) {
      $cipher = Utility\Cipher::instance();
      $password = $cipher->decrypt($password);
    }
    return static::datasource(__FUNCTION__)->authenticate($class, $username, $password);
  }

  public function authorized($aro, $action = 'read', $attribute = null)
  {
    foreach (static::cfg('acls') as $acl) {
      if (is_a($aro, $acl['model']) && in_array($action, $acl['actions'])) {
        return true;
      }
    }
    if ($this->acl && in_array($action, $this->acl)) {
      return true;
    }
    return false;
  }

  public function url($action = 'view', $_ = array())
  {
    if (is_array($action)) {
      $_ = $action;
      $action = 'view';
    }
    extract(array_merge(array(
      'prefix' => null,
      'absolute' => false,
      'arguments' => array(),
      'render' => null
    ), $_));
    $arguments = array_filter($arguments);
    $R = Router::instance();
    if ($action === 'view' && $this->path) {
      $url = $this->path;
    }
    else {
      $action = is_bool($action) ? '' : $action;
      $parts = explode(NS, get_class($this));
      $parts = array_map(array(
        'Aldu\Core\Utility\Inflector',
        'underscore'
      ), $parts);
      $class = array_pop($parts);
      array_pop($parts);
      $ns = implode(NS, $parts);
      $id = $this->id ? '/' . $this->id : '';
      $url = str_replace(NS, '/', strtolower($ns)) . '/' . $class . $id . '/' . $action;
    }
    $prefix = is_null($prefix) ? null : rtrim($prefix, '/');
    if ($prefix && $absolute) {
      $url = $R->fullBase . $prefix . '/' . $url;
    }
    elseif ($prefix) {
      $url = $prefix . '/' . $url;
    }
    elseif ($absolute) {
      $url = $R->fullBasePrefix . $url;
    }
    elseif (is_null($prefix)) {
      $url = $R->basePrefix . $url;
    }
    else {
      $url = $R->base . $url;
    }
    if (count($arguments)) {
      $url .= '/' . implode('/', $arguments);
    }
    if ($render) {
      $url .= ':' . $render;
    }
    return $url;
  }
}
