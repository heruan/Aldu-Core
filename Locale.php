<?php
/**
 * Aldu\Core\Locale
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
use Aldu\Core\Utility\Inflector;

class Locale extends Stub
{
  public function t()
  {
    $args = func_get_args();
    $text = array_shift($args);
    $matches = array();
    preg_match_all('/\{(.+):(\w+)\}/', $text, $matches);
    $matches[0] = array_map(function($pattern) { return "/$pattern/"; }, $matches[0]);
    $text = preg_replace($matches[0], $matches[1], $text);
    foreach ($matches[2] as $i => $inflection) {
      $args[$i] = Inflector::$inflection($args[$i]);
    }
    return vsprintf(_($text), $args);
  }
}
