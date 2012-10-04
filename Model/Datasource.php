<?php
/**
 * Aldu\Core\Model\Datasource
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
 * @package       Aldu\Core\Model
 * @uses          Aldu\Core
 * @since         AlduPHP(tm) v1.0.0
 * @license       Creative Commons Attribution-ShareAlike 3.0 Unported (CC BY-SA 3.0)
 */

namespace Aldu\Core\Model;
use Aldu\Core;
use Aldu\Core\Exception;

class Datasource extends Core\Stub
{
  protected static $drivers = array();
  protected $driver;
  protected $cache;

  public function __construct($url = '')
  {
    if (is_array($url)) {
      $parts = $url;
      $url = http_build_url($url);
    }
    else {
      $parts = array_merge(array(
          'scheme' => null
        ), parse_url($url));
    }
    if (!isset(static::$drivers[$url])) {
      $scheme = $parts['scheme'];
      switch ($scheme) {
      case 'sqlite':
        $driver = new Datasource\Driver\SQLite($url, $parts);
        break;
      case 'mongodb':
        $driver = new Datasource\Driver\MongoDB($url, $parts);
        break;
      case 'ldap':
        $driver = new Datasource\Driver\LDAP($url, $parts);
        break;
      case null:
        throw new Exception("Scheme cannot be null.");
      default:
        throw new Exception("Scheme '$scheme' not supported.");
      }
      static::$drivers[$url] = $driver;
    }
    $this->driver = static::$drivers[$url];
    $this->cache = Core\Cache::instance();
  }

  public function __destruct()
  {
    unset($this->driver);
  }

  public function save($models = array())
  {
    if (!is_array($models)) {
      $models = array($models);
    }
    foreach ($models as $model) {
      $class = get_class($model);
      $cache = implode('::', array(
        $class
      ));
      $this->cache->delete($cache);
      $this->driver->save($model);
    }
  }

  public function first($class, $search = array())
  {
    $cache = implode('::',
      array(
        $class, __METHOD__, md5(serialize(func_get_args()))
      ));
    if (ALDU_CACHE_FAILURE === ($result = $this->cache->fetch($cache))) {
      $result = $this->driver->first($class, $search);
      $this->cache->store($cache, $result);
    }
    return $result;
  }

  public function read($class, $search = array())
  {
    $cache = implode('::',
      array(
        $class, __METHOD__, md5(serialize(func_get_args()))
      ));
    if (ALDU_CACHE_FAILURE === ($result = $this->cache->fetch($cache))) {
      $result = $this->driver->read($class, $search);
      $this->cache->store($cache, $result);
    }
    return $result;
  }

  public function delete($model)
  {
    if (!isset($model->id)) {
      return null;
    }
    $class = get_class($model);
    $cache = implode('::', array(
      $class
    ));
    $this->cache->delete($cache);
    return $this->driver->delete($model);
  }

  public function purge($class, $search = array())
  {
    $cache = implode('::', array(
      $class
    ));
    $this->cache->delete($cache);
    return $this->driver->purge($class, $search);
  }
}
