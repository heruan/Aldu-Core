<?php
/**
 * Aldu\Core\Event\Listener
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
 * @package       Aldu\Core\Event
 * @uses          Aldu\Core
 * @since         AlduPHP(tm) v1.0.0
 * @license       Creative Commons Attribution-ShareAlike 3.0 Unported (CC BY-SA 3.0)
 */

namespace Aldu\Core\Event;
use Aldu\Core;

class Listener extends Core\Stub
{
  /**
   * Stores events' data.
   * @var array
   */
  protected $events = array();

  /**
   * Listener constructor
   * @param array $events
   */
  public function __construct($cfg = array())
  {
    parent::__construct($cfg);
    $this->events = static::cfg('events');
  }

  /**
   * Sets $callback to be called with $arguments when $name is triggered.
   *
   * @param string $name
   * @param callable $callback
   * @param array $arguments
   * @param boolean $once
   * @throws Exception
   */
  public function on($name, $callback = null, $arguments = array(),
    $once = false)
  {
    if (is_array($name)) {
      foreach ($name as $event) {
        extract(
          array_merge(
            array(
              'on' => null, 'callback' => null, 'arguments' => array(),
              'once' => false
            ), $event));
        if ($on && is_callable($callback)) {
          $this->on($on, $callback, $arguments, $once);
        }
      }
    }
    elseif (is_callable($callback)) {
      if (!isset($this->events[$name])) {
        $this->events[$name] = array();
      }
      $this->events[$name][] = array(
        'callback' => $callback, 'arguments' => $arguments, 'once' => $once
      );
    }
    else {
      throw new Exception("Invalid callback $callback.");
    }
  }

  /**
   * Sets $callback to be called with $arguments when $name is triggered the first time.
   *
   * @param string $name
   * @param callable $callback
   * @param array $arguments
   */
  public function once($name, $callback, $arguments = array())
  {
    return $this->on($name, $callback, $arguments, true);
  }

  /**
   * Removes events occurring on $name
   * @param string $name
   */
  public function off($name)
  {
    foreach ($this->events as $key => $events) {
      if (preg_match("/^$name$|$name\.)/", $key)) {
        unset($this->events[$key]);
      }
    }
  }

  /**
   * Triggers $name
   * @param string $name
   * @throws Exception
   */
  public function trigger($name)
  {
    foreach ($this->events as $key => $events) {
      $name = preg_replace('/\\\\/', '\\\\\\', $name);
      if (preg_match("/^($name$)|($name\.)/", $key)) {
        foreach ($events as $event) {
          extract($event);
          if (is_callable($callback)) {
            call_user_func_array($callback, $arguments);
            if ($once) {
              $this->off($name);
            }
          }
          else {
            throw new Exception("Invalid callback $callback.");
          }
        }
      }
    }
  }
}
