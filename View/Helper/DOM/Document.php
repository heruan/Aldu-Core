<?php
/**
 * Aldu\Core\View\Helper\DOM\Document
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
 * @uses          DOMImplementation
 * @uses          DOMDocument
 * @uses          DOMXPath
 * @since         AlduPHP(tm) v1.0.0
 * @license       Creative Commons Attribution-ShareAlike 3.0 Unported (CC BY-SA 3.0)
 */

namespace Aldu\Core\View\Helper\DOM;
use Aldu\Core\View\Helper;
use DOMImplementation;
use DOMDocument;
use DOMXPath;

class Document extends Helper\DOM
{
  public $root;
  public $type;
  public $encoding;
  protected $doctype;
  protected $document;
  protected $xpath;
  protected $voidElements = array(
    'area', 'base', 'br', 'col', 'command', 'embed', 'hr', 'img', 'input',
    'keygen', 'link', 'meta', 'param', 'source', 'track', 'wbr'
  );

  public function __construct($type = 'html', $publicId = null, $systemId = null)
  {
    parent::__construct();
    $this->type = $type;
    $this->encoding = "utf-8";
    $this->doctype = DOMImplementation::createDocumentType($this->type, $publicId, $systemId);
    $this->document = DOMImplementation::createDocument(null, $this->type,
      $this->doctype);
    $this->document->xmlVersion = "1.0";
    $this->document->xmlStandalone = true;
    $this->document->encoding = $this->encoding;
    $this->document->preserveWhiteSpace = false;
    $this->document->formatOutput = true;
    $this->xpath = new DOMXPath($this->document);
    $this->root = new Node($this, $this->document->documentElement);
  }
  
  public function doctype($type, $publicId = null, $systemId = null)
  {
    $this->type = $type;
    $this->doctype = DOMImplementation::createDocumentType($this->type, $publicId, $systemId);
  }

  public function __call($name, $arguments)
  {
    return call_user_func_array(array(
        $this->document, $name
      ), $arguments);
  }

  public function save($node = null)
  {
    switch ($this->type) {
    case 'html':
      $from = array();
      $to = array();
      foreach ($this->voidElements as $tag) {
        $from[] = "#</$tag>#";
        $to[] = '';
      }
      return preg_replace($from, $to, $this->document->saveHTML($node));
    case 'xhtml':
      return $this->document->saveXML($node, LIBXML_NOXMLDECL);
    case 'xml':
      return $this->document->saveXML($node);
    }
  }

  public function __toString()
  {
    return (string) $this->save();
  }

  public function load($filename, $options = 0)
  {
    libxml_use_internal_errors(true);
    switch ($this->type) {
    case 'html':
      if (file_exists($filename)) {
        $this->document->loadHtmlFile($filename);
      }
      else {
        $this->document->loadHTML($filename);
      }
      break;
    case 'xhtml':
    case 'xml':
    default:
      if (file_exists($filename)) {
        $this->document->load($filename, $options);
      }
      else {
        $this->document->loadXML($filename, $options);
      }
    }
    libxml_clear_errors();
    $this->root = new Node($this, $this->document->documentElement);
    $this->xpath = new DOMXPath($this->document);
    return $this->root;
  }

  public function query($selector, $context = null, $registerNodeNS = null)
  {
    $this->xpath = new DOMXPath($this->document);
    $expression = $this->selectorToQuery($selector);
    if ($context) {
      if ($context->node instanceof NodeList) {
        $context = $context->node->item(0);
      }
      else {
        $context = $context->node;
      }
    }
    return $this->xpath->query($expression, $context, $registerNodeNS);
  }

  public function node($node, $context = null)
  {
    if ($node instanceof DOMNode) {
      return new Node($this, $node);
    }
    elseif ($node instanceof Node) {
      return $node;
    }
    elseif (is_string($node)) {
      $nodes = $this->query($node, $context);
      if ($nodes->length) {
        return new Node($this, new NodeList($nodes));
      }
    }
    return null;
  }

  public function create($name, $value = null, $attributes = array())
  {
    if (substr(trim($name), 0, 1) === '<') {
      $new = new Document($this->type);
      $new
        ->load(
          '<?xml version="1.0" encoding="utf-8"?><fragment>' . $name
            . '</fragment>');
      $nodes = array();
      foreach ($new->root->node('fragment > *') as $node) {
        $n = $this->document->importNode($node->node, true);
        $nodes[] = $n;
      }
      return new Node($this, new NodeList($nodes));
    }
    return new Node($this, $this->createElement($name, $value, $attributes));
  }

  public function createElement($name, $value = null, $attributes = array())
  {
    $classes = explode('.', $name);
    $name = array_shift($classes);
    if (is_array($value) && !is_object(current($value))) {
      $attributes = $value;
      $value = null;
    }
    if (!empty($classes)) {
      if (isset($attributes['class'])) {
        $classes[] = $attributes['class'];
      }
      $attributes = array_merge($attributes,
        array(
          'class' => implode(" ", array_unique($classes))
        ));
    }
    list($name, $id) = explode('#', $name) + array(
        null, null
      );
    if ($id) {
      $attributes['id'] = $id;
    }
    $element = $this->document->createElement($name);
    foreach ($attributes as $_name => $_value) {
      switch ($_name) {
      default:
        $element->setAttribute($_name, $_value);
      }
    }
    switch ($name) {
    case "img":
      if (!$element->hasAttribute('alt')) $element->setAttribute('alt', '');
      break;
    }
    if (is_object($value)) {
      $element->appendChild($value->node);
    }
    elseif (is_array($value) && is_object(current($value))) {
      foreach ($value as $child) {
        $element->appendChild($child->node);
      }
    }
    elseif (!is_null($value)) {
      switch ($name) {
      case "script":
        $element->appendChild($this->document->createTextNode("//"));
        $element
          ->appendChild(
            $this->document->createCDATASection("\n" . $value . "\n//"));
        break;
      default:
        switch ($this->type) {
        case 'html':
          $element->appendChild($this->document->createCDATASection($value));
          break;
        default:
          $element->nodeValue = $value;
        }
      }
    }
    return $this->document->importNode($element);
  }

  protected function selectorToQuery($selector)
  {
    $selector = (string) $selector;
    $cssSelector = array(
      '/\*/', // *
      '/(\w)/', // E
      '/(\w)\s+(\w)/', // E F
      '/(\w)\s*>\s*(\w)/', // E > F
      '/(\w)\s*>\s*\*/', // E > *
      '/(\w):first-child/', // E:first-child
      '/(\w)\s*:nth-child\(([0-9]+)\)/', // E:nth-child(n)
      '/(\w)\s*\+\s*(\w)/', // E + F
      '/(\w)\[([\w\-]+)\]/', // E[foo]
      '/(\w)\[([\w\-]+)\=\"(.*)\"\]/', // E[foo="bar"]
      '/(\w+)+\.([\w\-]+)+/', // E.class
      '/\.([\w\-]+)+/', // .class
      '/(\w+)+\#([\w\-]+)/', // E#id
      '/\#([\w\-]+)/' // #id
    );
    $xPathQuery = array(
      '*', // *
      '\1', // E
      '\1//\2', // E F
      '\1/\2', // E > F
      '\1/*', // E > *
      '*[1]/self::\1', // E:first-child
      '\1[position() = \2]', // E:nth-child(n)
      '\1/following-sibling::*[1]/self::\2', // E + F
      '\1 [ @\2 ]',
      // E[foo]
      '\1[ contains( concat( " ", @\2, " " ), concat( " ", "\3", " " ) ) ]',
      // E[foo="bar"]
      '\1[ contains( concat( " ", @class, " " ), concat( " ", "\2", " " ) ) ]',
      // E.class
      '*[ contains( concat( " ", @class, " " ), concat( " ", "\1", " " ) ) ]',
      // .class
      '\1[ @id = "\2" ]', // E#id
      '*[ @id = "\1" ]' // #id
    );
    return (string) './/' . preg_replace($cssSelector, $xPathQuery, $selector);
  }
}
