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
      case 'mysql':
        $driver = new Datasource\Driver\MySQL($url, $parts);
        break;
      case 'sqlite':
        $driver = new Datasource\Driver\SQLite($url, $parts);
        break;
      case 'mongodb':
        $driver = new Datasource\Driver\MongoDB($url, $parts);
        break;
      case 'ldap':
        $driver = new Datasource\Driver\LDAP($url, $parts);
        break;
      case 'odbc':
        $driver = new Datasource\Driver\ODBC($url, $parts);
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

  public function save(&$models = array())
  {
    if (!is_array($models)) {
      $models = array(
        $models
      );
    }
    foreach ($models as &$model) {
      $class = get_class($model);
      $cache = implode('::', array(
        $class
      ));
      $this->cache->delete($cache);
      foreach ($class::cfg('attributes') as $attribute => $type) {
        if (is_null($model->$attribute) && isset($type['null'])
          && !$type['null'] && isset($type['default'])) {
          $model->$attribute = $type['default'];
        }
      }
      $this->driver->save($model);
    }
  }

  public function count($class, $search = array(), $options = array())
  {
    $search = $this->driver->normalizeSearch($search);
    $cache = implode('::',
      array(
        $class, __METHOD__, md5(serialize(array($search, $options)))
      ));
    if (ALDU_CACHE_FAILURE === ($result = $this->cache->fetch($cache))) {
      $result = $this->driver->count($class, $search, $options);
      $this->cache->store($cache, $result);
    }
    return $result;
  }

  public function exists($model)
  {
    if (!isset($model->id)) {
      return false;
    }
    $class = get_class($model);
    return $this->driver->exists($model);
  }

  public function first($class, $search = array(), $options = array())
  {
    $search = $this->driver->normalizeSearch($search);
    $cache = implode('::',
      array(
        $class, __METHOD__, md5(serialize(array($search, $options)))
      ));
    if (ALDU_CACHE_FAILURE === ($result = $this->cache->fetch($cache))) {
      $result = $this->driver->first($class, $search, $options);
      $this->cache->store($cache, $result);
    }
    return $result;
  }

  public function read($class, $search = array(), $options = array())
  {
    $search = $this->driver->normalizeSearch($search);
    $cache = implode('::',
      array(
        $class, __METHOD__, md5(serialize(array($search, $options)))
      ));
    if (ALDU_CACHE_FAILURE === ($result = $this->cache->fetch($cache))) {
      $result = $this->driver->read($class, $search, $options);
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

  public function purge($class, $search = array(), $options = array())
  {
    $search = $this->driver->normalizeSearch($search);
    $cache = implode('::', array(
      $class
    ));
    $this->cache->delete($cache);
    return $this->driver->purge($class, $search, $options);
  }

  public function tag($model, $tags = array(), $relation = array())
  {
    if (!is_array($tags)) {
      $tags = array(
        $tags
      );
    }
    $class = get_class($model);
    $cache = implode('::', array(
      $class, 'tags'
    ));
    $this->cache->delete($cache);
    return $this->driver->tag($model, $tags, $relation);
  }

  public function untag($model, $tags = array())
  {
    if (!is_array($tags)) {
      $tags = array($tags);
    }
    if (!$model->id) {
      return true;
    }
    $class = get_class($model);
    $cache = implode('::', array(
      $class,
    	'tags'
    ));
    $this->cache->delete($cache);
    return $this->driver->untag($model, $tags);
  }

  public function has($model, $tag = null, $relation = array(),
    $search = array(), $options = array())
  {
    $class = get_class($model);
    $cache = implode('::',
      array(
        $class, 'tags', __METHOD__, md5(serialize(func_get_args()))
      ));
    if (ALDU_CACHE_FAILURE === ($result = $this->cache->fetch($cache))) {
      $result = $this->driver->has($model, $tag, $relation, $search, $options);
      $this->cache->store($cache, $result);
    }
    return $result;
  }

  public function belongs($tag, $model, $relation = array(),
    $search = array(), $options = array())
  {
    $class = is_object($model) ? get_class($model) : $model;
    $cache = implode('::',
      array(
        $class, 'tags', __METHOD__, md5(serialize(func_get_args()))
      ));
    if (ALDU_CACHE_FAILURE === ($result = $this->cache->fetch($cache))) {
      $result = $this->driver->belongs($tag, $model, $relation, $search, $options);
      $this->cache->store($cache, $result);
    }
    return $result;
  }

  public function authenticate($class, $id, $password)
  {
    if (is_object($class)) {
      $class = get_class($class);
    }
    $idKey = $class::cfg('datasource.authentication.id') ?: 'name';
    $pwKey = $class::cfg('datasource.authentication.password') ?: 'password';
    $pwEnc = $class::cfg('datasource.authentication.encryption') ?: 'md5';
    return $this->driver->authenticate($class, $id, $password, $idKey, $pwKey, $pwEnc);
  }
}
