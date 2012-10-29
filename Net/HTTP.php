<?php
/**
 * Aldu\Core\Net\HTTP
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
 * @package       Aldu\Core\Net
 * @uses          Aldu\Core
 * @since         AlduPHP(tm) v1.0.0
 * @license       Creative Commons Attribution-ShareAlike 3.0 Unported (CC BY-SA 3.0)
 */

namespace Aldu\Core\Net;
use Aldu\Core;

class HTTP extends Core\Stub
{
  /**
   * Gets an environment variable from available sources, and provides emulation
   * for unsupported or inconsistent environment variables (i.e. DOCUMENT_ROOT on
   * IIS, or SCRIPT_NAME in CGI mode). Also exposes some additional custom
   * environment information.
   *
   * @param  string $key Environment variable name.
   * @return string Environment variable setting.
   */

  public function bytes($value)
  {
    if (is_numeric($value)) {
      return $value;
    }
    else {
      $value_length = strlen($value);
      $qty = substr($value, 0, $value_length - 1);
      $unit = strtolower(substr($value, $value_length - 1));
      switch ($unit) {
      case 'k':
        $qty *= 1024;
        break;
      case 'm':
        $qty *= 1048576;
        break;
      case 'g':
        $qty *= 1073741824;
        break;
      }
      return $qty;
    }
  }

  public static function env($key)
  {
    if ($key === 'HTTPS') {
      if (isset($_SERVER['HTTPS'])) {
        return (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off');
      }
      return (strpos(static::env('SCRIPT_URI'), 'https://') === 0);
    }

    if ($key === 'SCRIPT_NAME') {
      if (static::env('CGI_MODE') && isset($_ENV['SCRIPT_URL'])) {
        $key = 'SCRIPT_URL';
      }
    }

    $val = null;
    if (isset($_SERVER[$key])) {
      $val = $_SERVER[$key];
    }
    elseif (isset($_ENV[$key])) {
      $val = $_ENV[$key];
    }
    elseif (getenv($key) !== false) {
      $val = getenv($key);
    }

    if ($key === 'REMOTE_ADDR' && $val === static::env('SERVER_ADDR')) {
      $addr = static::env('HTTP_PC_REMOTE_ADDR');
      if ($addr !== null) {
        $val = $addr;
      }
    }

    if ($val !== null) {
      return $val;
    }

    switch ($key) {
    case 'GET':
      return isset($_GET) ? $_GET : array();
    case 'POST':
      return isset($_POST) ? $_POST : array();
    case 'FILES':
      return isset($_FILES) ? $_FILES : array();
    case 'SESSION':
      return isset($_SESSION) ? $_SESSION : array();
    case 'COOKIE':
      return isset($_COOKIE) ? $_COOKIE : array();
    case 'SCRIPT_FILENAME':
      if (defined('SERVER_IIS') && SERVER_IIS === true) {
        return str_replace('\\\\', '\\', static::env('PATH_TRANSLATED'));
      }
      break;
    case 'DOCUMENT_ROOT':
      $name = static::env('SCRIPT_NAME');
      $filename = static::env('SCRIPT_FILENAME');
      $offset = 0;
      if (!strpos($name, '.php')) {
        $offset = 4;
      }
      return substr($filename, 0, -(strlen($name) + $offset));
      break;
    case 'PHP_SELF':
      return str_replace(env('DOCUMENT_ROOT'), '', static::env('SCRIPT_FILENAME'));
      break;
    case 'CGI_MODE':
      return (PHP_SAPI === 'cgi');
      break;
    case 'HTTP_BASE':
      $host = static::env('HTTP_HOST');
      $parts = explode('.', $host);
      $count = count($parts);

      if ($count === 1) {
        return '.' . $host;
      }
      elseif ($count === 2) {
        return '.' . $host;
      }
      elseif ($count === 3) {
        $gTLD = array(
          'aero',
          'asia',
          'biz',
          'cat',
          'com',
          'coop',
          'edu',
          'gov',
          'info',
          'int',
          'jobs',
          'mil',
          'mobi',
          'museum',
          'name',
          'net',
          'org',
          'pro',
          'tel',
          'travel',
          'xxx'
        );
        if (in_array($parts[1], $gTLD)) {
          return '.' . $host;
        }
      }
      array_shift($parts);
      return '.' . implode('.', $parts);
      break;
    }
    return null;
  }
}
