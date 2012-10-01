<?php
/**
 * Aldu\Core\Cache\Engine\APC
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
 * @package       Aldu\Core\Cache\Engine
 * @uses          Aldu\Core\Cache
 * @uses          APCIterator
 * @since         AlduPHP(tm) v1.0.0
 * @license       Creative Commons Attribution-ShareAlike 3.0 Unported (CC BY-SA 3.0)
 */

namespace Aldu\Core\Cache\Engine;
use Aldu\Core\Cache;
use APCIterator;

class APC extends Cache\Engine
{

  public function fetch($key)
  {
    $success = false;
    $fetch = apc_fetch($key, $success);
    if ($success) {
      return $fetch;
    }
    return ALDU_CACHE_FAILURE;
  }

  public function store($key, $var, $ttl = ALDU_CACHE_TTL)
  {
    return apc_store($key, $var, $ttl);
  }

  public function delete($key)
  {
    $iterator = new APCIterator('user', '/^' . $key . '/', APC_ITER_VALUE);
    return apc_delete($iterator);
  }

  public function clear()
  {
    return apc_clear_cache('user');
  }

  public function info()
  {
    return apc_cache_info('user');
  }
}
