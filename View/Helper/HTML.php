<?php
/**
 * Aldu\Core\View\Helper\HTML
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
 * @package       Aldu\Core\View\Helper
 * @uses          Aldu\Core\View
 * @since         AlduPHP(tm) v1.0.0
 * @license       Creative Commons Attribution-ShareAlike 3.0 Unported (CC BY-SA 3.0)
 */

namespace Aldu\Core\View\Helper;
use Aldu\Core\View;

class HTML extends DOM\Node
{
  
  protected $theme;

  public function __construct($node = null, $document = null)
  {
    if (!$document) {
      $document = DOM\Document::instance();
    }
    if (!$node) {
      $node = $document->create('html');
    }
    elseif (is_string($node)) {
      $node = $document->create($node);
    }
    parent::__construct($document, $node);
  }

  public function theme($theme = null)
  {
    $this->theme = $theme ? : static::cfg('themes.default');
    if (is_array($this->theme) && isset($this->theme['html'])) {
      $html = ALDU_THEMES . DS . $this->theme['name'] . DS . $this->theme['html'];
    }
    $this->document->load($html);
    $this->node = $this->document->root->node;
    return $this;
  }

  protected function cast($function, $args)
  {
    $node = call_user_func_array(array(
          'parent', $function
        ), $args);
    return new HTML($node, $this->document);
  }

  public function prepend()
  {
    return $this->cast(__FUNCTION__, func_get_args());
  }
  
  public function append()
  {
    return $this->cast(__FUNCTION__, func_get_args());
  }

  public function node()
  {
    return $this->cast(__FUNCTION__, func_get_args());
  }

  public function addClass($class)
  {
    $classes = explode(' ', $this->class);
    $classes[] = $class;
    $this->class = implode(' ', array_unique($classes));
  }

  public function data($name, $value = null)
  {
    if (is_array($name)) {
      foreach ($name as $n => $v) {
        $n = "data-$n";
        $this->$n = $v;
      }
      return $name;
    }
    $name = "data-$name";
    if ($value) {
      $this->$name = $value;
    }
    return $this->$name;
  }
}
