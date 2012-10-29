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
class Locale extends Stub
{
  public $default;
  public $current;

  protected static $configuration = array(
    __CLASS__ => array(
      'default' => array(
        'id' => 1,
        'name' => 'en-us',
        'title' => 'English (United States)'
      )
    )
  );

  public function __construct($locale = null)
  {
    parent::__construct();
    $this->default = new Locale\Models\Locale(static::cfg('default'));
    $this->default->locale = $this->default;
    $this->current = $locale ? : $this->default;
  }

  public function t()
  {
    $args = func_get_args();
    $text = array_shift($args);
    //return vsprintf(_($text), $args);
    $attributes = array(
      'msgid' => $text,
      'locale' => $this->current
    );
    $message = new Locale\Models\Message($attributes);
    if ($msg = Locale\Models\Message::first($attributes)) {
      $message = $msg;
    }
    else {
      $message->msgstr = $text;
      $message->save();
    }
    return vsprintf($message->msgstr, $args);
  }
}
