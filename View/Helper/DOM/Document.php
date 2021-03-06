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
  protected $implementation;
  protected $doctype;
  protected $document;
  protected $xpath;
  protected $voidElements = array(
    'area',
    'base',
    'br',
    'col',
    'command',
    'embed',
    'hr',
    'img',
    'input',
    'keygen',
    'link',
    'meta',
    'param',
    'source',
    'track',
    'wbr'
  );

  public function __construct($type = 'html', $publicId = null, $systemId = null)
  {
    parent::__construct();
    $this->type = $type;
    $this->encoding = "utf-8";
    $this->implementation = new DOMImplementation();
    $this->doctype = $this->implementation->createDocumentType($this->type, $publicId, $systemId);
    $this->document = $this->implementation->createDocument(null, $this->type, $this->doctype);
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
    $this->doctype = $this->implementation->createDocumentType($this->type, $publicId, $systemId);
  }

  public function __call($name, $arguments)
  {
    return call_user_func_array(array(
      $this->document,
      $name
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
        $this->document->loadHTMLFile($filename);
      }
      elseif (preg_match('/^http/', $filename)) {
        $opts = array(
          'http' => array(
            'user_agent' => 'PHP libxml agent'
          )
        );
        $context = stream_context_create($opts);
        libxml_set_streams_context($context);
        $this->document->loadHTMLFile($filename);
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
    $expression = self::selectorToQuery($selector);
    $nodes = array();
    $context = $context ? : $this->root;
    if ($context->node instanceof NodeList) {
      foreach ($context->node as $node) {
        foreach ($this->xpath->query($expression, $node, $registerNodeNS) as $n) {
          $nodes[] = $n;
        }
      }
    }
    else {
      $context = $context->node;
      foreach ($this->xpath->query($expression, $context, $registerNodeNS) as $n) {
        $nodes[] = $n;
      }
    }
    return $nodes;
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
      return new Node($this, new NodeList($nodes));
    }
    return null;
  }

  public function create($name, $value = null, $attributes = array())
  {
    if (substr(trim($name), 0, 1) === '<') {
      $new = new Document($this->type);
      $new->load('<?xml version="1.0" encoding="utf-8"?><fragment>' . $name . '</fragment>');
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
      $attributes = array_merge($attributes, array(
        'class' => implode(" ", array_unique($classes))
      ));
    }
    list($name, $id) = explode('#', $name) + array(
        null,
        null
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
      if (!$element->hasAttribute('alt'))
        $element->setAttribute('alt', '');
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
        $element->appendChild($this->document->createCDATASection("\n" . $value . "\n//"));
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

  public function import($node)
  {
    return $this->document->importNode($node, true);
  }

  /**
   * Transform CSS expression to XPath
   *
   * @param  string $path
   * @return string
   */
  public static function selectorToQuery($path)
  {
    $path = (string) $path;
    if (strstr($path, ',')) {
      $paths = explode(',', $path);
      $expressions = array();
      foreach ($paths as $path) {
        $xpath = self::selectorToQuery(trim($path));
        if (is_string($xpath)) {
          $expressions[] = $xpath;
        }
        elseif (is_array($xpath)) {
          $expressions = array_merge($expressions, $xpath);
        }
      }
      return implode('|', $expressions);
    }

    $paths = array(
      './/'
    );
    $path = preg_replace('|\s+>\s+|', '>', $path);
    $segments = preg_split('/\s+/', $path);
    foreach ($segments as $key => $segment) {
      $pathSegment = self::_tokenize($segment);
      if (0 == $key) {
        if (0 === strpos($pathSegment, '[contains(')) {
          $paths[0] .= '*' . ltrim($pathSegment, '*');
        }
        else {
          $paths[0] .= $pathSegment;
        }
        continue;
      }
      if (0 === strpos($pathSegment, '[contains(')) {
        foreach ($paths as $key => $xpath) {
          $paths[$key] .= '//*' . ltrim($pathSegment, '*');
          $paths[] = $xpath . $pathSegment;
        }
      }
      else {
        foreach ($paths as $key => $xpath) {
          $paths[$key] .= '//' . $pathSegment;
        }
      }
    }

    if (1 == count($paths)) {
      return $paths[0];
    }
    return implode('|', $paths);
  }

  /**
   * Tokenize CSS expressions to XPath
   *
   * @param  string $expression
   * @return string
   */
  protected static function _tokenize($expression)
  {
    // Child selectors
    $expression = str_replace('>', '/', $expression);

    // IDs
    $expression = preg_replace('|#([a-z][a-z0-9_-]*)|i', '[@id=\'$1\']', $expression);
    $expression = preg_replace('|(?<![a-z0-9_-])(\[@id=)|i', '*$1', $expression);

    // arbitrary attribute strict equality
    $expression = preg_replace_callback('|\[([a-z0-9_-]+)=[\'"]([^\'"]+)[\'"]\]|i', function ($matches)
    {
      return '[@' . strtolower($matches[1]) . "='" . $matches[2] . "']";
    }, $expression);

    // arbitrary attribute contains full word
    $expression = preg_replace_callback('|\[([a-z0-9_-]+)~=[\'"]([^\'"]+)[\'"]\]|i', function ($matches)
    {
      return "[contains(concat(' ', normalize-space(@" . strtolower($matches[1]) . "), ' '), ' " . $matches[2] . " ')]";
    }, $expression);

    // arbitrary attribute contains specified content
    $expression = preg_replace_callback('|\[([a-z0-9_-]+)\*=[\'"]([^\'"]+)[\'"]\]|i', function ($matches)
    {
      return "[contains(@" . strtolower($matches[1]) . ", '" . $matches[2] . "')]";
    }, $expression);

    // Classes
    $expression = preg_replace('|\.([a-z][a-z0-9_-]*)|i', "[contains(concat(' ', normalize-space(@class), ' '), ' \$1 ')]", $expression);

    /** ZF-9764 -- remove double asterisk */
    $expression = str_replace('**', '*', $expression);

    return $expression;
  }
}
