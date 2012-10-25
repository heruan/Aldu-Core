<?php
/**
 * Aldu\Core\View\Helper\HTML\Page
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
use Aldu\Core;

class UI extends Helper\HTML
{

  public function __construct($page)
  {
    $this->document = $page->document;
  }
  
  public function style($node, $engine = 'jqueryui')
  {
    switch ($engine) {
    case 'jquery.mobile':
      foreach ($node->node('ul.aldu-blog-view-menu') as $menu) {
        $menu->data(array(
          'role' => 'listview',
          'inset' => 'true' 
        ));
        foreach ($menu->node('li.active') as $li) {
          $li->data('theme', 'a');
        }
      }
      $node->node('div.aldu-core-view-helper-html-form-element')->data(array(
        'role' => 'fieldcontain'
      ));
      $node->node('div.aldu-core-view-helper-html-form-submit')->data(array(
        'theme' => 'b'
      ));
      break;
    case 'jquery.ui':
    default:
      foreach ($node->node('input') as $input) {
        $input->addClass('ui-widget ui-widget-content ui-corner-all');
      }
      foreach ($node->node('textarea') as $textarea) {
        $textarea->addClass('ui-widget ui-widget-content ui-corner-all');
      }
    }
  }

  public function message($text, $priority = LOG_INFO)
  {
    switch ($priority) {
    case LOG_INFO:
      return $this->success($text);
    case LOG_NOTICE:
      return $this->notice($text);
    case LOG_ERR:
      return $this->error($text);
    case LOG_DEBUG:
      return $this->debug($text);
    }
  }

  public function success()
  {
    $args = func_get_args();
    $element = $this->document->create('p.aldu-helpers-ui-success');
    $text = $this->document->create('span', array_shift($args));
    $element->append($text);
    return $element;
  }

  public function notice()
  {
    $args = func_get_args();
    $element = $this->document->create('p.aldu-helpers-ui-notice');
    $title = $this->document->create('strong', _("Notice") . ': ');
    $text = $this->document->create('span', array_shift($args));
    $element->append($title, $text);
    return $element;
  }

  public function error()
  {
    $args = func_get_args();
    $element = $this->document->create('p.aldu-helpers-ui-error');
    $title = $this->document->create('strong', _("Error") . ': ');
    $text = $this->document->create('span', array_shift($args));
    $element->append($title, $text);
    return $element;
  }

  public function debug()
  {
    $args = func_get_args();
    $element = $this->document->create('p.aldu-helpers-ui-debug');
    $text = $this->document->create('span', array_shift($args));
    $element->append($text);
    return $element;
  }
}
