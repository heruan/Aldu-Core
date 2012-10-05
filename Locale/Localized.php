<?php
/**
 * Aldu\Core\Locale\Localized
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
 * @package       Aldu\Core\Locale
 * @uses          Aldu\Core
 * @since         AlduPHP(tm) v1.0.0
 * @license       Creative Commons Attribution-ShareAlike 3.0 Unported (CC BY-SA 3.0)
 */

namespace Aldu\Core\Locale;
use Aldu\Core;

abstract class Localized extends Core\Model
{
  protected static $configuration = array(
    'attributes' => array(
      'locale' => array(
        'type' => 'Aldu\Core\Locale\Models\Locale'
      )
    ),
    'extensions' => array(
      'localized' => array(
        'ref' => 'Aldu\Core\Locale\Models\Locale',
        'key' => 'locale',
        'attributes' => array(
          'locale' => true
        )
      )
    )
  );

  public $locale;
}
