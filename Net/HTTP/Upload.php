<?php
/**
 * Aldu\Core\Net\HTTP\Upload
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

class Upload extends Net\HTTP
{
  public function maxUploadSize($unit = 'b')
  {
    $max = min(array(
        $this->bytes(ini_get('upload_max_filesize')),
        $this->bytes(ini_get('post_max_size')),
        $this->bytes(ini_get('memory_limit'))
    ));
    switch ($unit) {
      case 'm':
        return $max / (1024 * 1024);
      case 'k':
        return $max / 1024;
      default:
        return $max;
    }
  }

  public function merge($files = array(), $data = array())
  {
    foreach ($files as $class => $fileinfo) {
      if (!isset($data[$class])) {
        $data[$class] = array();
      }
      foreach ($fileinfo['tmp_name'] as $key => $values) {
        foreach ($values as $attribute => $uploadpath) {
          if (!isset($data[$class][$key])) {
            $data[$class][$key] = array();
          }
          if (is_uploaded_file($uploadpath)) {
            $data[$class][$key][$attribute] = file_get_contents($uploadpath);
          }
        }
      }
    }
    return $data;
  }
}
