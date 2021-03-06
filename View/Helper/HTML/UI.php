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
      // aldu-ui-container
      $node->node('.aldu-ui-container')->addClass('container');
      // aldu-ui-toolbar
      $toolbar = $node->node('.aldu-ui-toolbar')->addClass('navbar');
      $node->node('.aldu-ui-toolbar.top')->addClass('navbar-fixed-top');
      $node->node('.aldu-ui-toolbar.bottom')->addClass('navbar-fixed-bottom');
      $toolbar->node('.aldu-ui-toolbar-menu')->addClass('navbar-inner');
      $toolbar->node('.aldu-ui-toolbar-menu .menu')->addClass('nav');
      $toolbar->node('.aldu-ui-home')->addClass('brand');
      $node->node('.aldu-ui-toolbar-drawer ul')->addClass('nav nav-tabs');
      // aldu-ui-float
      $node->node('.aldu-ui-float-left')->addClass('pull-left');
      $node->node('.aldu-ui-float-right')->addClass('pull-right');
      // aldu-ui-messages
      $levels = array(
        'info',
        'success',
        'error',
        'notice',
        'debug'
      );
      foreach ($node->node('div.aldu-core-view-helper-html-ui-message') as $message) {
        $message->addClass('alert');
        foreach ($levels as $level) {
          if ($message->hasClass($level)) {
            $message->addClass("alert-$level");
          }
        }
      }
      // aldu-ui-form
      $form = $node->node('div.aldu-core-view-helper-html-form')->addClass('form-horizontal');
      $group = $form->node('div.aldu-core-view-helper-html-form-element')->addClass('control-group');
      $form->node('div.aldu-core-view-helper-html-form-element > label')->addClass('control-label');
      $group->node('div.aldu-core-view-helper-html-form-controls')->addClass('controls');
      $actions = $form->node('div.aldu-core-view-helper-html-form-actions')->addClass('form-actions');
      $actions->node('button[type="submit"]')->addClass('btn btn-primary');
      // aldu-ui-table
      $node->node('#aldu-core-view-helper-html-page-content table')->addClass('table table-striped');
      break;
    case 'jquery.mobile':
      foreach ($node->node('ul.aldu-blog-views-menu') as $menu) {
        $menu->data(array(
          'role' => 'listview'
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
      list($text, $title) = $args + array(
          null,
          null
        );
      $element = $this->document->create("div.aldu-core-view-helper-html-ui-message.$function");
      if ($title) {
        $element->strong($title . ' ');
      }
      $element->span($text);
      return $element;
    }
  }
}
