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

class Datasource extends Core\Stub
{
  protected static $drivers = array();
  
  public function __construct($url)
  {
    if (is_array($url)) {
      $parts = $url;
      $url = http_build_url($url);
    }
    else {
      $parts = parse_url($url);
    }
    if (!isset(static::$drivers[$url])) {
      switch ($parts['scheme']) {
      case 'mongodb':
        static::$drivers[$url] = new Datasource\Driver\MongoDB($url, $parts);
        break;
      }
    }
    $this->driver = static::$drivers[$url];
  }
}
