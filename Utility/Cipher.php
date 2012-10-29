<?php
/**
 * Aldu\Core\Utility\Cipher
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

class Cipher extends Core\Stub
{
  protected $salt;
  protected $seed;
  protected $iv;

  protected static $configuration = array(
    __CLASS__ => array(
      'salt' => 'GegjenwivOsooHunRygNoyquikiccex1',
      'seed' => '76859309657453542496749683645'
    )
  );

  public function __construct()
  {
    parent::__construct();
    $this->salt = hash('sha256', static::cfg('salt'), true);
    $this->seed = static::cfg('seed');
    if (function_exists('mcrypt_get_iv_size')) {
      $size = mcrypt_get_iv_size(MCRYPT_RIJNDAEL_256, MCRYPT_MODE_ECB);
      $this->iv = null; //mcrypt_create_iv($size, MCRYPT_DEV_RANDOM);
    }
    else {
    }
  }

  protected function cipher($input)
  {
    srand($this->seed);
    $output = '';
    $keyLength = strlen($this->salt);
    for ($i = 0, $textLength = strlen($input); $i < $textLength; $i++) {
      $j = ord(substr($this->salt, $i % $keyLength, 1));
      while ($j--) {
        rand(0, 255);
      }
      $mask = rand(0, 255);
      $output .= chr(ord(substr($input, $i, 1)) ^ $mask);
    }
    srand();
    return $output;
  }

  public function encrypt($input)
  {
    if (function_exists('mcrypt_encrypt')) {
      return base64_encode(@mcrypt_encrypt(MCRYPT_RIJNDAEL_256, $this->salt, $input, MCRYPT_MODE_ECB));
    }
    return $this->cipher($input);
  }

  public function decrypt($input)
  {
    if (function_exists('mcrypt_decrypt')) {
      return trim(@mcrypt_decrypt(MCRYPT_RIJNDAEL_256, $this->salt, base64_decode($input), MCRYPT_MODE_ECB));
    }
    return $this->cipher($input);
  }
}
