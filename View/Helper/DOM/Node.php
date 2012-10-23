<?php
/**
 * Aldu\Core\View\Helper\DOM\Node
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
 * @uses          Iterator
 * @uses          Countable
 * @since         AlduPHP(tm) v1.0.0
 * @license       Creative Commons Attribution-ShareAlike 3.0 Unported (CC BY-SA 3.0)
 */

namespace Aldu\Core\View\Helper\DOM;
use Aldu\Core\View\Helper;
use DOMNode;
use Iterator;
use Countable;

class Node extends Helper\DOM implements Iterator, Countable
{
  protected $document;
  protected $node;
  protected $position = 0;

  public function __construct(Document $document, $node = null)
  {
    $this->document = $document;
    if ($node instanceof Node) {
      $node = $node->node;
    }
    $this->node = $node;
  }

  public function prop()
  {
    $args = func_get_args();
    $prop = array_shift($args);
    if (is_array($prop)) {
      foreach ($prop as $p => $v) {
        $this->$p = $v;
      }
    }
    elseif ($v = array_shift($args)) {
      $this->$prop = $v;
    }
  }

  public function set($name, $value)
  {
    $this->$name = $value;
  }

  public function get($name)
  {
    return $this->$name;
  }

  public function __set($name, $value)
  {
    if ($this->node instanceof NodeList) {
      foreach ($this->node as $node) {
        if (is_null($value)) $node->removeAttribute($name);
        else $node->setAttribute($name, $value);
      }
    }
    else {
      if (is_null($value)) $this->node->removeAttribute($name);
      else {
        $this->node->setAttribute($name, $value);
      }
    }
  }

  public function index($index = null)
  {
    if ($index !== null) {
      return new Node($this->document, $this->node->childNodes->item($index));
    }
    $index = 0;
    $prev = $this->node->previousSibling;
    while ($prev) {
      $prev = $prev->previousSibling;
      $index++;
    }
    return $index;
  }

  public function __get($name)
  {
    switch ($name) {
      case 'node':
        return $this->node;
      case 'parent':
        return new self($this->document, $this->node->parentNode);
      default:
        if ($this->node instanceof NodeList) {
          return $this->node->length ? $this->node->item(0)->getAttribute($name) : null;
        }
        return $this->node->getAttribute($name);
    }
  }

  public function __isset($name)
  {
    if ($this->node instanceof NodeList) {
      return false;
    }
    return $this->node->hasAttribute($name);
  }

  public function __unset($name)
  {
    if ($this->node instanceof NodeList) {
      foreach ($this->node as $node) {
        $node->removeAttribute($name);
      }
    }
    else {
      $this->node-removeAttribute($name);
    }
  }

  public function __call($name, $arguments)
  {
    switch ($name) {
      default:
        array_unshift($arguments, $name);
        $node = call_user_func_array(array($this->document, 'create'), $arguments);
        return $this->append($node);
    }
  }

  public function shift()
  {
    $new = $this->first();
    $this->remove();
    return $new;
  }

  public function pop()
  {
    $new = $this->last();
    $this->remove();
    return $new;
  }

  public function create()
  {
    return call_user_func_array(array($this->document, 'create'), func_get_args());
  }

  public function remove()
  {
    if ($this->node instanceof NodeList) {
      foreach ($this->node as $node) {
        $node->parentNode->removeChild($node);
      }
    }
    else {
      $this->node->parentNode->removeChild($this->node);
    }
  }

  public function purge()
  {
    foreach ($this->node->childNodes as $child)
    {
      $this->node->removeChild($child);
    }
  }

  public function attributes($attributes = null)
  {
    if (!is_null($attributes)) {
      foreach ($attributes as $name => $value) {
        $this->set($name, $value);
      }
    }
    $attributes = array();
    foreach ($this->node->attributes as $name => $attr) {
      $attributes[$name] = $attr->value;
    }
    return $attributes;
  }

  public function node($node)
  {
    return $this->document->node($node, $this);
  }

  public function __clone()
  {
    if ($this->node instanceof NodeList) {
      $this->node = $this->node->item(0);
    }
    $this->node = $this->node->cloneNode(true);
  }

  public function count()
  {
    if ($this->node instanceof NodeList) {
      return $this->node->length;
    }
    return 1;
  }

  public function replaceChild($old, $new)
  {
    $this->node->replaceChild($old->node, $new->node);
  }

  public function append()
  {
    $nodes = func_get_args();
    if (is_string(current($nodes))) {
      $nodes = array(call_user_func_array(array($this->document, 'create'), $nodes));
    }
    if ($this->node instanceof NodeList) {
      foreach ($this->node as $item) {
        foreach ($nodes as $node) {
          if (!$node) continue;
          if ($node->node instanceof NodeList) {
            foreach ($node->node as $n) {
              if ($this->node->length === 1) {
                $item->appendChild($n);
              }
              else {
                $item->appendChild($n->cloneNode(true));
              }
            }
          }
          else {
            if ($this->node->length === 1) {
              $item->appendChild($node->node);
            }
            else {
              $item->appendChild($node->node->cloneNode(true));
            }
          }
        }
      }
    }
    else {
      foreach ($nodes as $node) {
        if (!$node) continue;
        if ($node->node instanceof NodeList) {
          foreach ($node->node as $n) {
            $this->node->appendChild($n);
          }
        }
        else {
          $this->node->appendChild($node->node);
        }
      }
    }
    return array_shift($nodes);
  }

  public function prepend()
  {
    $nodes = func_get_args();
    if (is_string(current($nodes))) {
      $nodes = array(call_user_func_array(array($this->document, 'create'), $nodes));
    }
    if ($this->node instanceof NodeList) {
      foreach ($this->node as $item) {
        foreach ($nodes as $node) {
          if (!$node) continue;
          if ($this->node->length === 1) {
            $item->insertBefore($node->node, $item->firstChild);
            break;
          }
          $item->inserBefore($node->node->cloneNode(true), $item->firstChild);
        }
      }
    }
    else {
      foreach ($nodes as $node) {
        if (!$node) continue;
        $this->node->insertBefore($node->node, $this->node->firstChild);
      }
    }
    return array_shift($nodes);
  }

  public function import($document)
  {
    $this->document = $document;
    $this->node = $this->document->import($this->node);
  }

  public function first()
  {
    if ($this->node instanceof NodeList) {
      return $this->node->length ? new self($this->document, $this->node->item(0)) : null;
    }
    return $this;
  }

  public function last()
  {
    if ($this->node instanceof NodeList) {
      return $this->node->length ? new self($this->document, $this->node->item($this->node->length - 1)) : null;
    }
    return $this;
  }

  public function value($text = null)
  {
    if ($text) {
      if ($this->node instanceof NodeList) {
        foreach ($this->node as $node) {
          $node->nodeValue = $text;
        }
      }
      else {
        $this->node->nodeValue = $text;
      }
    }
    if ($this->node instanceof NodeList) {
      $values = array();
      foreach ($this->node as $node) {
        $values = $node->nodeValue;
      }
      return $values;
    }
    return $this->node->nodeValue;
  }

  public function text($text = null)
  {
    $node = $this->document->createCDATASection($text);
    if ($this->node instanceof NodeList) {
      foreach ($this->node as $item) {
        foreach ($item->childNodes as $child) {
          $item->removeChild($child);
        }
        $item->appendChild(clone $node);
      }
    }
    else {
      foreach ($this->node->childNodes as $child) {
        $this->node->removeChild($child);
      }
      $this->node->appendChild($node);
    }
    return $this;
  }

  public function __toString()
  {
    return $this->save();
  }

  public function save()
  {
    if ($this->node instanceof NodeList) {
      $output = '';
      foreach ($this->node as $node) {
        $output .= $this->document->save($node);
      }
      return $output;
    }
    return $this->document->save($this->node);
  }

  public function rewind()
  {
    $this->position = 0;
  }

  public function current()
  {
    if ($this->node instanceof NodeList) {
      $node = $this->node->item($this->position);
    }
    elseif ($this->node->childNodes->length) {
      $node = $this->node->childNodes->item($this->position);
    }
    else {
      $node = $this->node;
    }
    return new self($this->document, $node);
  }

  public function key()
  {
    return $this->position;
  }

  public function next()
  {
    return ++$this->position;
  }

  public function valid()
  {
    if ($this->node instanceof NodeList) {
      $valid = (bool) $this->node->item($this->position);
    }
    else {
      $valid = (bool) $this->node->childNodes->item($this->position);
    }
    return $valid;
  }
}
