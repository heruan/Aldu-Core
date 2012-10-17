<?php
/**
 * Aldu\Core\Cache
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
 * @uses          Aldu\Core\Utility\ClassLoader
 * @since         AlduPHP(tm) v1.0.0
 * @license       Creative Commons Attribution-ShareAlike 3.0 Unported (CC BY-SA 3.0)
 */

namespace Aldu\Core;
use Aldu\Core\Utility\ClassLoader;

class Cache extends Stub
{
  protected static $configuration = array(__CLASS__ => array(
    'enabled' => false,
    'engine' => 'APC'
  ));
  public $enabled;
  protected $engine;
  protected $prefix;
  protected $stored = 0;
  protected $fetched = 0;

  public function __construct($prefix = null)
  {
    parent::__construct();
    if ($Engine = static::cfg('engine')) {
      if (ClassLoader::classExists(__CLASS__ . NS . 'Engine' . NS . $Engine)) {
        $Engine = __CLASS__ . NS . 'Engine' . NS . $Engine;
      }
      if (ClassLoader::classExists($Engine)) {
        $this->engine = new $Engine();
      }
    }
    $this->enabled = static::cfg('enabled');
    $this->prefix = $prefix;
  }

  public function fetch($key)
  {
    if (!$this->enabled) return ALDU_CACHE_FAILURE;
    $fetch = $this->engine->fetch($this->prefix . $key);
    if ($fetch !== ALDU_CACHE_FAILURE) {
      $this->fetched++;
    }
    return $fetch;
  }

  public function store($key, $var, $ttl = ALDU_CACHE_TTL)
  {
    if (!$this->enabled) return ALDU_CACHE_FAILURE;
    $this->stored++;
    return $this->engine->store($this->prefix . $key, $var, $ttl);
  }

  public function fetched()
  {
    return $this->fetched;
  }

  public function stored()
  {
    return $this->stored;
  }

  public function delete($key, $iterate = true)
  {
    if (!$this->enabled) return ALDU_CACHE_FAILURE;
    return $this->engine->delete($this->prefix . addslashes($key));
  }

  public function clear()
  {
    if (!$this->enabled) return ALDU_CACHE_FAILURE;
    return $this->engine->clear();
  }

  public function info()
  {
    if (!$this->enabled) return ALDU_CACHE_FAILURE;
    return $this->engine->info();
  }
}
