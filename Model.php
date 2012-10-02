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

class Model extends Stub
{
  public static $Controller;
  public static $references = array();
  public $id;

  public function __construct($attributes = array())
  {
    parent::__construct();
    foreach ($attributes as $name => $value) {
      $this->$name = $value;
    }
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
    extract(array_merge(array(
        'prefix' => null,
        'absolute' => false,
        'arguments' => array(),
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
      $parts = array_map(array('Aldu\Core\Utility\Inflector', 'underscore'), $parts);
      $class = array_pop($parts);
      array_pop($parts);
      $ns = implode(NS, $parts);
      $id = $this->id ? '/' . $this->id : '';
      $url = str_replace(NS, '/', strtolower($ns)) . '/' . $class . $id . '/' . $action;
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
