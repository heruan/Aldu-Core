<?php
/**
 * Aldu\Core\View\Helper\DOM\NodeList
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
 * @package       Aldu\Core\View\Helper\DOM
 * @uses          Aldu\Core\View\Helper
 * @uses          DOMNode
 * @since         AlduPHP(tm) v1.0.0
 * @license       Creative Commons Attribution-ShareAlike 3.0 Unported (CC BY-SA 3.0)
 */

namespace Aldu\Core\View\Helper\DOM;
use Aldu\Core\View\Helper;
use DOMNodeList;
use Iterator;

class NodeList extends Helper\DOM implements Iterator
{
  public $nodes;
  public $position;

  public function __construct($nodes = array())
  {
    $this->nodes = $nodes;
    $this->position = 0;
  }

  public function __set($name, $value)
  {
    foreach ($this->nodes as $node) {
      if (is_null($value)) $node->removeAttribute($name);
      else $node->setAttribute($name, $value);
    }
  }
  
  public function __get($name)
  {
    switch ($name) {
      case 'length':
        if ($this->nodes instanceof DOMNodeList) {
          return $this->nodes->length;
        }
        return count($this->nodes);
    }
    return $this->item(0)->$name;
  }

  public function item($index = 0)
  {
    if ($this->nodes instanceof DOMNodeList) {
      return $this->nodes->item($index);
    }
    return $this->nodes[$index];
  }

  public function rewind()
  {
    $this->position = 0;
  }

  public function current()
  {
    if ($this->nodes instanceof DOMNodeList) {
      return $this->nodes->item($this->position);
    }
    return $this->nodes[$this->position];
  }

  public function key()
  {
    return $this->position;
  }

  public function next()
  {
    ++$this->position;
  }

  public function valid()
  {
    if ($this->nodes instanceof DOMNodeList) {
      return ($this->position < $this->nodes->length);
    }
    return isset($this->nodes[$this->position]);
  }
}