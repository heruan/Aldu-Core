<?php
/**
 * Aldu\Core\Stub
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
 * @uses          Aldu\Core\Utility
 * @since         AlduPHP(tm) v1.0.0
 * @license       Creative Commons Attribution-ShareAlike 3.0 Unported (CC BY-SA 3.0)
 */

namespace Aldu\Core;
use Aldu\Core\Utility;

abstract class Stub
{
  /**
   * Cache
   * @var array
   */
  protected static $_cache = array();

  /**
   * Object instances
   * @var array
   */
  protected static $_instances = array();

  /**
   * Object observers
   * @var array
   */
  protected static $_observers = array();

  /**
   * Configurations
   * @var array
   */
  protected static $_configurations = array();

  /**
   * Objects configuration
   * @var array
   */
  protected static $configuration = array();

  /**
   * Object attributes
   * @var array
   */
  private $__attributes = array();

  /**
   * Caches a value.
   *
   * @param string $type
   * @param string $key
   * @param mixed $value
   */
  protected static function _cache($type, $key, $value = false)
  {
    $key = '_' . $key;
    $type = '_' . $type;
    if ($value !== false) {
      static::$_cache[$type][$key] = $value;
    }
    if (!isset(static::$_cache[$type][$key])) {
      return ALDU_CACHE_FAILURE;
    }
    return static::$_cache[$type][$key];
  }

  /**
   * Build object's configuration.
   *
   * @param array $config
   */

  public static function configure($config = array())
  {
    $self = get_called_class();
    if ($config) {
      self::$_configurations[$self] = $config;
    }
    elseif (!isset(self::$_configurations[$self])) {
      if ($parent = get_parent_class($self)) {
        $config = $parent::configure();
      }
      if (isset(static::$configuration)) {
        $config = array_replace_recursive($config, static::$configuration);
      }
      if (is_dir(ALDU_CONFIG_DIR)) {
        if (ALDU_CACHE_FAILURE
          === ($_config = self::_cache(__CLASS__, __FUNCTION__))) {
          $ini = '';
          foreach (scandir(ALDU_CONFIG_DIR) as $filename) {
            if (preg_match('/\.ini$/', $filename)) {
              $ini .= file_get_contents(ALDU_CONFIG_DIR . DS . $filename)
                . "\n";
            }
          }
          $_config = Utility\Parser\Ini::parse($ini, true);
          self::_cache(__CLASS__, __FUNCTION__, $_config);
        }
        if (isset($_config[$self])) {
          $config = array_replace_recursive($config, $_config[$self]);
        }
      }
      static::$configuration = $config;
      self::$_configurations[$self] = $config;
    }
    return self::$_configurations[$self];
  }

  /**
   * Gets/sets configuration entries.
   *
   * @param string $key
   * @param mixed $value
   */
  public static function cfg($key = '', $value = null)
  {
    $self = get_called_class();
    $self::configure();
    if ($value) {
      self::$_configurations[$self] = array_replace_recursive(
        self::$_configurations[$self],
        Utility\Parser\Ini::parse(array(
          $key => $value
        )));
    }
    $config = array();
    $keys = explode('.', $key);
    if (isset(self::$_configurations[$self])) {
      $config = self::$_configurations[$self];
      foreach ($keys as $key) {
        if (!is_array($config) || !isset($config[$key])) {
          return array();
        }
        $config = $config[$key];
      }
    }
    return $config;
  }

  /**
   * Gets an existing object's instance or constructs a new one.
   *
   * @param int $key (optional) index of the instance to retrieve
   */

  public static function instance($key = 0)
  {
    $class = get_called_class();
    if (!isset(self::$_instances[$class][$key])) {
      self::$_instances[$class][$key] = new $class();
    }
    return self::$_instances[$class][$key];
  }

  /**
   * Replaces an existing object's instance.
   *
   * @param object $instance
   * @param int $key index of the instance to replace
   */

  public static function replace($instance, $key = 0)
  {
    $class = get_called_class();
    self::$_instances[$class][$key] = $instance;
    return $instance;
  }

  /**
   * Constructor
   *
   * @param array $attributes
   */

  public function __construct($attributes = array())
  {
    if ($attributes === null) {
      $attributes = array();
    }
    $this->__attributes = array_change_key_case($attributes, CASE_LOWER);
    $self = get_class($this);
    $hash = spl_object_hash($this);
    self::$_observers[$hash] = array();
    self::$_instances[$self][] = $this;
    static::configure();
  }

  /**
   * Destructor
   */

  public function __destruct()
  {
    foreach (array_keys(self::$_instances, $this, true) as $key) {
      unset(self::$_instances[$key]);
      unset(self::$_observers[spl_object_hash($this)]);
    }
  }

  /**
   * Magic method __call
   *
   * @param string $name
   * @param array $arguments
   * @throws Exception
   */

  public function ___call($name, $arguments)
  {
    if (is_callable(array(
      $this, $name
    ))) {
      return call_user_func_array(array(
          $this, $name
        ), $arguments);
    }
    throw new Exception("Method {$name} undefined in class " . get_class($this));
  }

  /**
   * Magic method __callStatic
   *
   * @param string $name
   * @param array $arguments
   * @throws Exception
   */

  public static function ___callStatic($name, $arguments)
  {
    throw new Exception(
      "Static method {$name} undefined in class " . get_called_class());
  }

  /**
   * Magic method __get
   *
   * @param string $name
   */

  public function __get($name)
  {
    $name = strtolower($name);
    if (isset($this->_{$name})) {
      return $this->_{$name};
    }
    if (array_key_exists($name, $this->__attributes)) {
      return $this->__attributes[$name];
    }
    return null;
  }

  /**
   * Magic method __set
   *
   * @param string $name
   * @param mixed $value
   */

  public function __set($name, $value)
  {
    $name = strtolower($name);
    $this->__attributes[$name] = $value;
  }

  /**
   * Magic method __isset
   * @param string $name
   */

  public function __isset($name)
  {
    $name = strtolower($name);
    return isset($this->__attributes[$name]);
  }

  /**
   * Magic method __unset
   *
   * @param string $name
   */

  public function __unset($name)
  {
    $name = strtolower($name);
    unset($this->__attributes[$name]);
  }

  /**
   * Magic method __toString
   *
   * @return object
   */

  public function __toString()
  {
    return get_class($this);
  }

  /**
   * Magic method __invoke
   * @param array $attributes
   */

  public function __invoke($attributes = array())
  {
    if (empty($attributes)) {
      return $this->__attributes;
    }
    $this->__attributes = $attributes;
  }

  /**
   * Magic method __set_state
   *
   * @param array $attributes
   * @return object
   */

  public static function __set_state($attributes)
  {
    $self = get_called_class();
    return new $self($attributes);
  }

  /**
   * Magic method __clone
   */

  public function __clone()
  {
  }

  /**
   * Attaches an observer, i.e. the instance of an object that will be notified of state change
   *
   * @param object $observer
   */

  public function __attach($observer)
  {
    self::$_observers[spl_object_hash($this)][] = $observer;
  }

  /**
   * Notifies registered observers of state change
   */

  public function __notify()
  {
    $args = func_get_args();
    $method = array_shift($args);
    foreach (self::$_observers[spl_object_hash($this)] as $observer) {
      if (method_exists($observer, $method)) {
        call_user_func_array(
          array(
            $observer, $method
          ), $args);
      }
    }
  }
}
