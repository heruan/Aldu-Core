<?php
/**
 * Aldu\Core\Exception
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
 * @uses          Exception
 * @since         AlduPHP(tm) v1.0.0
 * @license       Creative Commons Attribution-ShareAlike 3.0 Unported (CC BY-SA 3.0)
 */

namespace Aldu\Core;
use Exception as stdException;

class Exception extends stdException
{
  public function __construct($message = '')
  {
    $args = func_get_args();
    $message = array_shift($args);
    parent::__construct(vsprintf($message, $args));
  }

  public static function initialize()
  {
    set_exception_handler(array(__CLASS__, 'catcher'));
  }

  public static function catcher($e)
  {
    $page = new View\Helper\HTML\Page();
    $response = Net\HTTP\Response::instance();
    $response->message($e->getMessage(), LOG_ERR);
    $response->message($e->getTraceAsString(), LOG_DEBUG);
    $response->body($page->compose());
    $response->send();
    while (ob_get_level()) {
      ob_end_flush();
    }
  }
}
