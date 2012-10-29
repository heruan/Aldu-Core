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
  protected $engine;
  protected $locale;

  public function __construct($page, $theme)
  {
    $this->document = $page->document;
    if (isset($theme['ui']) && isset($theme['ui']['engine'])) {
      $this->engine = $theme['ui']['engine'];
    }
    $this->locale = Core\Locale::instance();
  }

  public function style($node, $engine = null)
  {
    $engine = $engine ? : $this->engine;
    switch ($engine) {
    case 'bootstrap':
      $levels = array('info', 'success', 'error', 'notice', 'debug');
      foreach ($node->node('div.aldu-core-view-helper-html-ui-message') as $message) {
        $message->addClass('alert');
        foreach ($levels as $level) {
          if ($message->hasClass($level)) {
            $message->addClass("alert-$level");
          }
        }
      }
      $node->node('div.aldu-core-view-helper-html-form-file')->addClass('btn fileinput-button');
      $node->node('#aldu-core-view-helper-html-page-content table')->addClass('table table-striped');
      break;
    case 'jquery.mobile':
      foreach ($node->node('ul.aldu-blog-view-menu') as $menu) {
        $menu->data(array(
          'role' => 'listview',
        //'inset' => 'true'
        ));
        $menu->node('li > a')->data('transition', 'slide');
        $menu->node('li.active')->data('theme', 'a');
      }
      $node->node('div.aldu-core-view-helper-html-form-element')->data(array(
        'role' => 'fieldcontain'
      ));
      $node->node('button[type="submit"]')->data(array(
        'theme' => 'b'
      ));
      break;
    case 'jquery.ui':
      foreach ($node->node('input') as $input) {
        $input->addClass('ui-widget ui-widget-content ui-corner-all');
      }
      foreach ($node->node('textarea') as $textarea) {
        $textarea->addClass('ui-widget ui-widget-content ui-corner-all');
      }
      break;
    default:
    }
  }

  public function message($text, $priority = LOG_INFO)
  {
    switch ($priority) {
    case LOG_INFO:
      return $this->info($text, $this->locale->t("Info"));
    case LOG_NOTICE:
      return $this->success($text, $this->locale->t("Success"));
    case LOG_WARNING:
      return $this->notice($text, $this->locale->t("Notice"));
    case LOG_ERR:
      return $this->error($text, $this->locale->t("Error"));
    case LOG_DEBUG:
      return $this->debug($text);
    }
  }

  public function __call($function, $args)
  {
    switch ($function) {
    case 'info':
    case 'success':
    case 'notice':
    case 'error':
    case 'debug':
      list ($text, $title) = $args + array(null, null);
      $element = $this->document->create("div.aldu-core-view-helper-html-ui-message.$function");
      if ($title) {
        $element->strong($title);
      }
      $element->span($text);
      return $element;
    }
  }
}
