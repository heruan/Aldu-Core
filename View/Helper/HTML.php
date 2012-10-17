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

  public function __construct($node = null, $text = null, $attributes = array(), $document = null)
  {
    if (!$document) {
      $document = DOM\Document::instance();
    }
    if (!$node) {
      $node = $document->create('html');
    }
    elseif (is_string($node)) {
      $node = $document->create($node, $text, $attributes);
    }
    parent::__construct($document, $node);
  }

  protected function cast($function, $args)
  {
    $callback = array(
      get_parent_class(__CLASS__), $function
    );
    if (is_callable($callback)) {
      $node = call_user_func_array($callback, $args);
      return new self($node, $this->document);
    }
    return null;
  }

  public function create()
  {
    return $this->cast(__FUNCTION__, func_get_args());
  }

  public function prepend()
  {
    return $this->cast(__FUNCTION__, func_get_args());
  }

  public function append()
  {
    return $this->cast(__FUNCTION__, func_get_args());
  }

  public function node($node)
  {
    return $this->cast(__FUNCTION__, func_get_args());
  }

  public function current()
  {
    return new self(parent::current(), $this->document);
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
