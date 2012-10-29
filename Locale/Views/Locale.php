<?php
/**
 * Aldu\Core\Locale\Views\Term
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
 * @package       Aldu\Core\Locale\Views
 * @uses          Aldu\Core
 * @uses          Aldu\Core\View\Helper
 * @since         AlduPHP(tm) v1.0.0
 * @license       Creative Commons Attribution-ShareAlike 3.0 Unported (CC BY-SA 3.0)
 */

namespace Aldu\Core\Locale\Views;
use Aldu\Core;
use Aldu\Core\View\Helper;

class Locale extends Core\View
{
  public function select($form, $name, $_ = array())
  {
    $select = parent::select($form, $name, $_);
    if (!($selected = $select->node('option[selected]')->first()) || !$selected->value) {
      if ($option = $select->node("option[value=\"{$this->locale->current->id}\"]")) {
        $option->selected = 'selected';
      }
    }
    return $select;
  }
}
