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

  public function __construct($url = '')
  {
    if (is_array($url)) {
      $parts = $url;
      $url = http_build_url($url);
    }
    else {
      $parts = array_merge(array('scheme' => null), parse_url($url));
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
      case null:
        throw new Exception("Scheme cannot be null.");
      default:
        throw new Exception("Scheme '$scheme' not supported.");
      }
      static::$drivers[$url] = $driver;
    }
    $this->driver = static::$drivers[$url];
  }

  public function save($models)
  {
    return $this->driver->save($models);
  }

  public function first($class, $search = array())
  {
    return $this->driver->first($class, $search);
  }

  public function read($class, $search = array())
  {
    return $this->driver->read($class, $search);
  }

  public function purge($class, $search = array())
  {
    return $this->driver->purge($class, $search);
  }
}
