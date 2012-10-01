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

class Page extends Helper\HTML
{
  public static $configuration = array(
    'themes' => array(
      'base' => 'public/themes',
      'default' => array(
        'name' => 'default',
        'html' => 'index.html'
      )
    )
  );
  public $type;
  public $lang;
  public $head;
  public $title;
  public $charset;
  public $body;
  protected $router;

  public function __construct($document = null, $lang = 'en-us')
  {
    parent::__construct('html', $document);
    $this->node = $this->document->root->node;
    $this->lang = $lang;
    $this->head = $this->append('head');
    $this->charset = $this->head
      ->append('meta',
        array(
          'charset' => $this->document->encoding
        ));
    $this->title = $this->head->append('title');
    $this->body = $this->append('body');
    $this->router = Core\Router::instance();
  }
  
  public function theme($theme = null)
  {
    parent::theme($theme);
    $this->head = $this->node('head');
    $this->charset = $this->head->node('meta[charset]');
    $this->title = $this->head->node('title');
    $this->body = $this->node('body');
    return $this;
  }

  public function title($title)
  {
    $prepend = static::cfg('title.prepend') ? : null;
    $append = static::cfg('title.append') ? : null;
    $separator = static::cfg('title.separator') ? : null;
    if (is_array($title)) {
      $title = implode(array_filter($separator, $title));
    }
    $this->title
      ->text(
        implode($separator,
          array_filter(array(
            $prepend, $title, $append
          ))));
  }

  public function description($description)
  {
    if (!count($node = $this->head->node('meta[name=description]'))) {
      $node = $this->head
        ->append('meta', array(
          'name' => 'description'
        ));
    }
    $node->content = $description;
  }

  public function keywords($keywords)
  {
    if (is_array($keywords)) {
      $keywords = implode(',', $keywords);
    }
    if (!count($node = $this->head->node('meta[name=keywords]'))) {
      $node = $this->head
        ->append('meta', array(
          'name' => 'keywords'
        ));
    }
    $node->content = $keywords;
  }

  public function base($base = null)
  {
    if (!count($node = $this->head->node('base'))) {
      $node = $this->head->prepend('base');
    }
    $base = $base ? 
      : implode('/',
        array(
          static::cfg('themes.base'), $this->theme['name']
        ));
    $node->href = $this->router->fullBase . $base;
  }

  public function prefixAnchors($context = null)
  {
    if (!$context) {
      $context = $this->body;
    }
    foreach ($context->node('a') as $anchor) {
      $href = $anchor->href;
      if (substr($href, 0, 1) === '/' && !preg_match("#^{$this->router->basePrefix}#", $href)) {
        $anchor->href = $this->router->basePrefix . substr($href, 1);
      }
    }
  }
  
  public function populateBlocks($context = null)
  {
    if (!$context) {
      $context = $this->body;
    }
    foreach ($context->node('.aldu-core-view-helper-html-page-block') as $node) {
      if ($position = $node->data('position')) {
        $this->router->openContext($position);
        $Block = new Models\Block();
        foreach ($Block->get(array('position' => $position)) as $block) {
          $this->router->openContext($block->name);
          if (is_callable($block->callback)) {
            $node->append(call_user_func($block->callback, $block, $node));
          }
          $this->router->closeContext($block->name);
        }
        $this->router->closeContext($position);
      }
    }
  }
  
  public function compose($content = null)
  {
    $this->base();
    if (!$node = $this->body->node('#aldu-core-view-helper-html-page-content')) {
      $node = $this->body;
    }
    $node->append($content);
    $this->prefixAnchors($this->body);
    $this->populateBlocks($this->body);
    $this->body->data(array(
    ));
    return $this;
  }

  public function save()
  {
    return $this->document->save();
  }
}
