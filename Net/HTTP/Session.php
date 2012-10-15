<?php
/**
 * Aldu\Core\Net\HTTP\Session
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
 * @package       Aldu\Core\Net\HTTP
 * @uses          Aldu\Core\Net
 * @since         AlduPHP(tm) v1.0.0
 * @license       Creative Commons Attribution-ShareAlike 3.0 Unported (CC BY-SA 3.0)
 */

namespace Aldu\Core\Net\HTTP;
use Aldu\Core\Net;

class Session extends Net\HTTP
{
  public $id;
  
  public function __construct()
  {
    $this->id = $this->start();
  }
  
  public function start()
  {
    if (!$id = session_id()) {
      session_start();
      $id = session_id();
      foreach (static::env('SESSION') as $key => $value) {
        if (is_callable($key)) {
          call_user_func_array($key, unserialize($value));
        }
      }
    }
    return $id;
  }
  
  public function close()
  {
    $this->id = null;
    return session_write_close();
  }
  
  public function destroy()
  {
    $this->close();
    session_unset();
    session_destroy();
  }
  
  public function save($key, $value)
  {
    $_SESSION[$key] = serialize($value);
  }
  
  public function read($key)
  {
    return isset($_SESSION[$key]) ? unserialize($_SESSION[$key]) : null;
  }
  
  public function delete($key)
  {
    if (isset($_SESSION[$key])) {
      unset($_SESSION[$key]);
    }
  }
}
