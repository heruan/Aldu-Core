<?php
/**
 * Aldu\Core\View\Helper\HTML\Table
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
 * @package       Aldu\Core\View\Helper\HTML
 * @uses          Aldu\Core\View\Helper
 * @since         AlduPHP(tm) v1.0.0
 * @license       Creative Commons Attribution-ShareAlike 3.0 Unported (CC BY-SA 3.0)
 */

namespace Aldu\Core\View\Helper\HTML;
use Aldu\Core\View\Helper;

class Table extends Helper\HTML
{
  public $head;
  public $body;
  public $foot;
  public $colgroup;
  protected $currentRow;
  
  public function __construct($headers = array(), $document = null)
  {
    parent::__construct('table.aldu-core-view-helper-html-table', $document);
    $this->colgroup = $this->append('colgroup');
    $this->head = $this->append('thead');
    $this->body = $this->append('tbody');
    $this->foot = $this->append('tfoot');
    if ($headers) {
      $headRow = $this->head->append('tr');
      $footRow = $this->foot->append('tr');
      foreach ($headers as $name => $column) {
        extract(array_merge(array(
          'title' => null,
          'fnote' => null,
          'attributes' => array()
        ), $column));
        $this->colgroup->append('col', $attributes)->data('name', $name);
        $headRow->append('th', $title, $attributes)->data('name', $name);
        $footRow->append('th', $fnote, $attributes)->data('name', $name);
      }
    }
  }
}
