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
  protected static $configuration = array(__CLASS__ => array(
    'title' => array(
      'separator' => ' - '
    ),
    'themes' => array(
      'default' => array(
        'base' => 'public/themes',
        'name' => 'default', 'html' => 'index.html'
      )
    )
  ));
  public $type;
  public $lang;
  public $head;
  public $title;
  public $charset;
  public $body;
  protected $ui;
  protected $theme;
  protected $router;
  protected $request;
  protected $response;

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
    $this->request = Core\Net\HTTP\Request::instance();
    $this->response = Core\Net\HTTP\Response::instance();
    $this->theme = static::cfg('themes.default');
    foreach (static::cfg('themes') as $name => $theme) {
      extract(array_merge(array(
      ), $theme), EXTR_PREFIX_ALL, 'theme');
      if (isset($theme_for) && $this->request->is($theme_for)) {
        $this->theme = array_merge(array(
          'base' => 'public/themes'), $theme);
      }
    }
    $this->ui = new UI($this, $this->theme);
    $this->theme();
  }

  public function theme($theme = null)
  {
    if ($theme) {
      $this->ui = new UI($this, $theme);
    }
    else {
      $theme = $this->theme;
    }
    if (is_array($theme) && isset($theme['html'])) {
      $html = ALDU_THEMES . DS . $theme['name'] . DS . $theme['html'];
    }
    $this->document->load($html);
    $this->node = $this->document->root->node;
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
    $this->body->node('.aldu-core-view-helper-html-page-title')->text($title);
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
          $this->theme['base'], $this->theme['name'], null
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
      if (substr($href, 0, 1) === '/'
        && !preg_match("#^{$this->router->basePrefix}#", $href)) {
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
      foreach (explode(' ', $node->data('position')) as $position) {
        $this->router->openContext($position);
        foreach (Models\Block::read(array(
            'position' => $position
          )) as $block) {
          $this->router->openContext($block->name);
          if ($block->showtitle) {
            $node->append('h2.aldu-core-view-helper-html-page-block-title', $block->title);
          }
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
    $this->body
      ->data(
        array(
          'aldu-core-router-base' => $this->router->base,
          'aldu-core-router-path' => $this->router->path
        ));
    if (!count(
      $node = $this->body->node('#aldu-core-view-helper-html-page-content'))) {
      $node = $this->body;
    }
    if ($content instanceof Helper\DOM\Node) {
      $content->import($this->document);
    }
    $node->append($content);
    $this->prefixAnchors($this->body);
    $this->populateBlocks($this->body);
    foreach ($this->body->node('#aldu-core-view-helper-html-page-messages') as $node) {
      foreach ($this->response->messages() as $priority => $messages) {
        foreach ($messages as $message) {
          $node->append($this->ui->message($message, $priority));
        }
      }
    }
    $this->ui->style($this);
    return $this;
  }

  public function save()
  {
    return $this->document->save();
  }
}
