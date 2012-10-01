<?php
/**
 * Aldu\Core\View
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
 * @package       Aldu\Core
 * @since         AlduPHP(tm) v1.0.0
 * @license       Creative Commons Attribution-ShareAlike 3.0 Unported (CC BY-SA 3.0)
 */

namespace Aldu\Core;
use Aldu\Core\View\Helper;
use Aldu\Core\Net\HTTP;

class View extends Stub
{
  public static $Controller;
  public $model;
  public $render;
  protected $router;
  protected $request;
  protected $response;

  public function __construct(Model $model, HTTP\Request $request,
    HTTP\Response $response)
  {
    parent::__construct();
    $this->model = $model;
    $this->request = $request;
    $this->response = $response;
  }

  public function table($models = array(), $_ = array())
  {
    $class = get_class($this->model);
    extract(
      array_merge(
        array(
          'actions' => static::cfg('table.actions'),
          'headers' => static::cfg('table.headers'),
          'columns' => static::cfg('table.columns') ? 
            : array_combine(array_keys(get_object_vars($this->model)),
              array_keys(get_object_vars($this->model)))
        ), $_));
    foreach ($columns as $name => $title) {
      if (is_numeric($name)) {
        $name = $title;
        $title = ucfirst($name);
      }
      $classes = explode('.', $name);
      $name = array_shift($classes);
      $headers[$name] = array(
        'title' => _($title),
        'attributes' => array(
          'class' => implode(' ', array_merge(array(
            'sortable', 'searchable'
          ), $classes))
        )
      );
    }
    $table = new Helper\HTML\Table($headers);
    return $table;
  }

  public function index($models = array())
  {
    switch ($this->render) {
    case 'page':
    default:
      $page = new Helper\HTML\Page();
      $page->theme();
      $page->title(__METHOD__);
      $page->keywords('keyword');
      $table = $this->table();
      $page->body->append($table);
      return $this->response->body($page->compose());
    }
  }
}
