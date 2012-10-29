<?php
/**
 * Aldu\Core\Router\Models\Route
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
 * @package       Aldu\Core\Router\Models
 * @uses          Aldu\Core\Net\HTTP
 * @uses          Aldu\Core\Utility\ClassLoader
 * @since         AlduPHP(tm) v1.0.0
 * @license       Creative Commons Attribution-ShareAlike 3.0 Unported (CC BY-SA 3.0)
 */

namespace Aldu\Core\Router\Models;
use Aldu\Core;

class Route extends Core\Model
{
  public $host;
  public $path;
  public $controller;
  public $action;
  public $arguments;
}
