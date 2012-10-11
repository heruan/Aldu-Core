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
use Aldu\Core\Model\Attribute;

class Model extends Stub
{
  public static $Controller;
  protected static $configuration = array(
    'datasource' => array(
      'url' => array(
        'scheme' => 'sqlite', 'path' => ALDU_DEFAULT_DATASOURCE
      ),
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
  );
  protected static $datasources = array();
  protected static $relations = array(
    'has' => array(
      'Aldu\Core\Model' => array(
        '_weight' => array(
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
    '_created' => array(
      'type' => 'datetime'
    ),
    '_updated' => array(
      'type' => 'datetime'
    ),
    '_weight' => array(
      'type' => 'int',
      'null' => false,
      'default' => 0
    )
  );

  public $id;
  public $_created;
  public $_updated;
  public $_weight;

  /**
   * 
   * Enter description here ...
   * @param unknown_type $configuration
   * @deprecated
   */
  protected static function configure($configuration = array())
  {
    $configuration['attributes'] = static::$attributes;
    $configuration['relations'] = static::$relations;
    return array_replace_recursive(parent::configure(), $configuration);
  }

  public function __construct($attributes = array())
  {
    parent::__construct();
    $class = get_class($this);
    $class::cfg(array('attributes' => $class::$attributes));
    $class::cfg(array('relations' => $class::$relations));
    if (!isset(self::$datasources[$class])) {
      self::$datasources[$class] = new Model\Datasource($class::cfg('datasource.url'));
    }
    foreach ($attributes as $name => $value) {
      if (!property_exists($this, $name)) {
        continue;
      }
      $this->$name = $value;
    }
  }

  public function __toArray()
  {
    $array = array();
    foreach (get_object_vars($this) as $name => $attribute) {
      $array[$name] = $attribute;
    }
    return $array;
  }

  public function tag($tags, $relation = array())
  {
    if (!is_array($tags)) {
      $tags = array($tags);
    }
    return self::$datasources[get_class($this)]->tag($this, $tags, $relation);
  }
  
  public function untag($tags = array())
  {
    return self::$datasources[get_class($this)]->untag($this, $tags);
  }
  
  public function has($tag = null, $relation = array(), $search = array(), $options = array())
  {
    return self::$datasources[get_class($this)]->has($this, $tag, $relation, $search, $options);
  }
  
  public function belongs($class, $relation = array(), $search = array(), $options = array()) {
    return self::$datasources[get_class($this)]->belongs($this, $class, $relation, $search, $options);
  }
  
  public function save($models = array())
  {
    if (empty($models)) {
      $models[] = $this;
    }
    return self::$datasources[get_class($this)]->save($models);
  }

  public static function count($search = array(), $options = array())
  {
    $class = get_called_class();
    if (!isset(self::$datasources[$class])) {
      self::$datasources[$class] = new Model\Datasource($class::cfg('datasource.url'));
    }
    return self::$datasources[$class]->count($class, $search, $options);
  }

  public static function first($search = array(), $options = array())
  {
    $class = get_called_class();
    if (!isset(self::$datasources[$class])) {
      self::$datasources[$class] = new Model\Datasource($class::cfg('datasource.url'));
    }
    return self::$datasources[$class]->first($class, $search, $options);
  }

  public static function read($search = array(), $options = array())
  {
    $class = get_called_class();
    if (!isset(self::$datasources[$class])) {
      self::$datasources[$class] = new Model\Datasource($class::cfg('datasource.url'));
    }
    return self::$datasources[$class]->read($class, $search, $options);
  }

  public static function purge($search = array(), $options = array())
  {
    $class = get_called_class();
    if (!isset(self::$datasources[$class])) {
      self::$datasources[$class] = new Model\Datasource($class::cfg('datasource.url'));
    }
    return self::$datasources[$class]->purge($class, $search, $options);
  }

  public function name()
  {
    if (!$name = static::cfg('name')) {
      $name = explode(NS, get_class($this));
      $name = array_pop($name);
    }
    return $name;
  }

  public function authorized()
  {
    return true;
  }

  public function url($action = 'view', $_ = array())
  {
    if (is_array($action)) {
      $_ = $action;
      $action = 'view';
    }
    extract(
      array_merge(
        array(
          'prefix' => null, 'absolute' => false, 'arguments' => array(),
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
          'Aldu\Core\Utility\Inflector', 'underscore'
        ), $parts);
      $class = array_pop($parts);
      array_pop($parts);
      $ns = implode(NS, $parts);
      $id = $this->id ? '/' . $this->id : '';
      $url = str_replace(NS, '/', strtolower($ns)) . '/' . $class . $id . '/'
        . $action;
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
