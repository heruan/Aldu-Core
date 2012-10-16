<?php
/**
 * Aldu\Core\Model\Datasource\Driver
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
 * @package       Aldu\Core\Model\Datasource
 * @uses          Aldu\Core
 * @since         AlduPHP(tm) v1.0.0
 * @license       Creative Commons Attribution-ShareAlike 3.0 Unported (CC BY-SA 3.0)
 */

namespace Aldu\Core\Model\Datasource;
use Aldu\Core;
use DateTime;

class Driver extends Core\Stub implements DriverInterface
{
  protected $url;
  protected $link;

  public function __construct($url)
  {
    $this->url = $url;
  }

  protected function isRegex($string)
  {
    return ($string && is_string($string) && preg_match('/[^a-z0-9\s]/i', $string[0])
      && $string[0] === substr($string, -1) && preg_match($string, '') !== false);
  }

  protected function normalizeAttributes($class, &$attributes)
  {
    foreach ($attributes as $attribute => &$value) {
      $this->normalizeAttribute($class, $attribute, $value);
    }
  }

  protected function normalizeAttribute($class, $attribute, &$value)
  {
    if (is_array($value)) {
      foreach ($value as &$_value) {
        $this->normalizeAttribute($class, $attribute, $_value);
      }
    }
    elseif ($value) {
      switch ($type = $class::cfg("attributes.$attribute.type")) {
      case is_array($type):
        $value = explode(',', $value);
        break;
      case 'date':
      case 'time':
      case 'datetime':
        if (preg_match('/[0-9]+\..Z/', $value)) {
          $value = explode('.', $value);
          $value = array_shift($value);
        }
        $value = new DateTime($value);
        break;
      }
    }
  }
}
