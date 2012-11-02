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
use Aldu\Core\Utility\ClassLoader;
use Aldu\Core\Utility\Inflector;

class Model extends Stub
{
  public static $Controller;
  public static $View;
  protected static $configuration = array(
    __CLASS__ => array(
      'acls' => array(
        'default' => array(
          'model' => __CLASS__,
          'actions' => array(
            'browse'
          )
        )
      ),
      'datasource' => array(
        'urls' => array(
          'default' => array(
            'scheme' => 'sqlite',
            'path' => ALDU_DEFAULT_DATASOURCE
          )
        ),
        'search' => array(),
        'options' => array(
          'sort' => array(
            '_' => 1
          )
        ),
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
    )
  );
  protected static $instances = array();
  protected static $datasources = array();
  protected static $extensions = array();
  protected static $relations = array(
    'has' => array(
      'Aldu\Core\Model' => array(
        'created' => array(
          'type' => 'datetime'
        ),
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
      'type' => 'number',
      'null' => false,
      'step' => 1,
      'min' => 1
    ),
    'acl' => array(
      'type' => array(
        'read',
        'edit',
        'delete'
      ),
      'multiple' => true,
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
      'type' => 'number',
      'null' => false,
      'default' => 0
    )
  );

  public $id;
  public $acl = array(
    'read'
  );
  public $created;
  public $updated;
  public $_ = 0;

  protected static function configure($configuration = array())
  {
    $class = get_called_class();
    $configuration['attributes'] = array_map(function ($attribute) use ($class)
    {
      if (isset($attribute['type']) && $attribute['type'] === 'self') {
        $attribute['type'] = $class;
      }
      return $attribute;
    }, $class::$attributes);
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

  public static function controller()
  {
    $self = get_called_class();
    $parts = explode(NS, $self);
    $class = array_pop($parts);
    array_pop($parts);
    $ns = implode(NS, $parts);
    $Controllers = array(
      $ns . NS . 'Controllers' . NS . $class
    );
    foreach (class_parents($self) as $model) {
      if (isset($model::$Controller)) {
        $Controllers[] = $model::$Controller;
      }
    }
    foreach ($Controllers as $Controller) {
      if (ClassLoader::classExists($Controller)) {
        $self::$Controller = $Controller;
        break;
      }
    }
    return new $self::$Controller();
  }

  public static function view()
  {
    $self = get_called_class();
    $parts = explode(NS, $self);
    $class = array_pop($parts);
    array_pop($parts);
    $ns = implode(NS, $parts);
    $Views = array(
      $ns . NS . 'Views' . NS . $class
    );
    foreach (class_parents($self) as $model) {
      if (isset($model::$View)) {
        $Views[] = $model::$View;
      }
    }
    foreach ($Views as $View) {
      if (ClassLoader::classExists($View)) {
        $self::$View = $View;
        break;
      }
    }
    return new $self::$View($self);
  }

  public static function instance($id = 0, $model = null)
  {
    if (!$id) {
      return null;
    }
    static::configure();
    $class = get_called_class();
    if (!isset(self::$instances[$class][$id])) {
      self::$instances[$class][$id] = $class::first(array(
        'id' => $id
      ));
    }
    return self::$instances[$class][$id];
  }

  public static function datasource($function = 'default')
  {
    static::configure();
    $class = get_called_class();
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

  public function save()
  {
    return static::datasource(__FUNCTION__)->save($this);
  }

  public static function count($search = array(), $options = array())
  {
    return self::_read(__FUNCTION__, $search, $options);
  }

  public static function first($search = array(), $options = array())
  {
    if (!is_array($search)) {
      $search = array(
        'id' => $search
      );
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
    $args[1] = array_replace_recursive($class::cfg('datasource.search'), $args[1]);
    $args[2] = array_replace_recursive($class::cfg('datasource.options'), $args[2]);
    return call_user_func_array(array(
      static::datasource($function),
      $function
    ), $args);
    if (is_array($return)) {
      return array_filter($return, array(
        get_called_class(),
        'authorize'
      ));
    }
    elseif (is_object($return)) {
      if (!static::authorize($return)) {
        return false;
      }
      return $return;
    }
    return $return;
  }

  public static function name($inflection = null)
  {
    $locale = Locale::instance();
    if (!$name = static::cfg('name')) {
      $name = explode(NS, get_called_class());
      $name = array_pop($name);
    }
    switch ($inflection) {
    case 'p':
    case 'plural':
    case 'pluralize':
      $name = Inflector::pluralize($name);
      break;
    }
    return $locale->t($name);
  }

  public function label()
  {
    $label = static::cfg('label') ? : 'name';
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

  public function is($model, $search = array())
  {
    if (is_object($model)) {
      return $this == $model;
    }
    if (empty($search)) {
      return is_a($this, $model);
    }
    if (is_a($this, $model)) {
      foreach ($model::read($search) as $is) {
        if ($this == $is) {
          return true;
        }
      }
    }
    return false;
  }

  protected function _authorized($aro, $action = 'read', $attribute = null)
  {
    foreach (static::cfg('acls') as $acl_name => $acl) {
      extract(array_merge(array(
        'model' => null,
        'search' => array(),
        'options' => array(),
        'actions' => array()
      ), $acl), EXTR_PREFIX_ALL, 'acl');
      if (!$acl_model) {
        if (in_array($action, $acl_actions)) {
          return true;
        }
        continue;
      }
      if ($aro && $aro->is($acl_model, $acl_search) && in_array($action, $acl_actions)) {
        return true;
      }
      foreach ($acl_model::read($acl_search, $acl_options) as $owner) {
        if ($aro && $owner->has($aro) && in_array($action, $acl_actions)) {
          return true;
        }
      }
    }
    if ($aro) {
      $belongs = array_merge(array(
        $aro
      ), $aro->belongs());
      foreach ($belongs as $belongsAro) {
        if ($this->belongs($belongsAro, array(
          'acl' => array(
            array(
              $action
            )
          )
        ))) {
          return true;
        }
      }
    }
    return false;
  }

  public function authorized($aro, $action = 'read', $attribute = null)
  {
    if ($this->acl && in_array($action, $this->acl)) {
      return true;
    }
    $Cache = Cache::instance();
    $cache = implode('::', array_filter(array(
      get_class($this),
      __FUNCTION__,
      $this->id,
      get_class($aro),
      $aro ? $aro->id : null,
      $action,
      $attribute
    )));
    if (ALDU_CACHE_FAILURE === ($authorized = $Cache->fetch($cache))) {
      $authorized = $this->_authorized($aro, $action, $attribute);
      $Cache->store($cache, $authorized);
    }
    return $authorized;
  }

  public static function authorize($model)
  {
    $request = Net\HTTP\Request::instance();
    return $model->authorized($request->aro);
  }

  public function url($action = 'read', $_ = array())
  {
    if (is_array($action)) {
      $_ = $action;
      $action = 'read';
    }
    extract(array_merge(array(
      'prefix' => null,
      'absolute' => false,
      'arguments' => array(),
      'render' => null
    ), $_));
    $arguments = array_filter($arguments);
    $R = Router::instance();
    if ($action === 'read' && $this->path) {
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
      $url = '/' . str_replace(NS, '/', strtolower($ns)) . '/' . $class . $id . '/' . $action;
    }
    $prefix = is_null($prefix) ? null : rtrim($prefix, '/');
    if ($prefix && $absolute) {
      $url = $R->fullBase . $prefix . $url;
    }
    elseif ($prefix) {
      $url = $prefix . $url;
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
