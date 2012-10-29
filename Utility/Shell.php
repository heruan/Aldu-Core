<?php
/**
 * Aldu\Core\Utility\Shell
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
 * @package       Aldu\Core\Utility
 * @uses          Aldu\Core\Exception
 * @since         AlduPHP(tm) v1.0.0
 * @license       Creative Commons Attribution-ShareAlike 3.0 Unported (CC BY-SA 3.0)
 */

namespace Aldu\Core\Utility;

use Aldu\Core;

class Shell extends Core\Stub
{
  public static function exec($cmd, $stdin = null, &$code = null)
  {
    $descriptorspec = array(
      0 => array(
        "pipe", "r"
      ),
      1 => array(
        "pipe", "w"
      ),
      2 => array(
        "pipe", "w"
      )
    );
    $pipes = array();
    $process = proc_open($cmd, $descriptorspec, $pipes);
    $output = "";
    if (!is_resource($process)) {
      return false;
    }
    if ($stdin) {
      fwrite($pipes[0], $stdin);
    }
    fclose($pipes[0]);

    stream_set_blocking($pipes[1], false);
    stream_set_blocking($pipes[2], false);

    $todo = array(
      $pipes[1], $pipes[2]
    );

    while (true) {
      $read = array();
      if (!feof($pipes[1])) {
        $read[] = $pipes[1];
      }
      if (!feof($pipes[2])) {
        $read[] = $pipes[2];
      }
      if (!$read) {
        break;
      }
      $write = null;
      $ex = null;
      $ready = stream_select($read, $write, $ex, 2);
      if ($ready === false) {
        break; #should never happen - something died
      }
      foreach ($read as $r) {
        $s = fread($r, 1024);
        $output .= $s;
      }
    }

    fclose($pipes[1]);
    fclose($pipes[2]);

    $code = proc_close($process);
    return $output;
  }
}
