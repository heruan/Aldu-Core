<?php
/**
 * Aldu\Core\Controller
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
use Aldu\Core\Net\HTTP;
use Aldu\Core\Utility\ClassLoader;

class Controller extends Event\Listener
{
  public $model;
  public $view;
  protected $request;
  protected $response;

  public function __construct(HTTP\Request $request = null, HTTP\Response $response = null)
  {
    $this->request = $request ?: HTTP\Request::instance();
    $this->response = $response ?: HTTP\Response::instance();
    $self = get_class($this);
    $parts = explode(NS, $self);
    $class = array_pop($parts);
    array_pop($parts); // Pop 'Controllers' part
    $ns = implode(NS, $parts);
    $Models = array(
      $ns . NS . 'Models' . NS . $class
    );
    $Views = array(
      $ns . NS . 'Views' . NS . $class
    );
    foreach (class_parents($this) as $controller) {
      if (isset($controller->model)) {
        $Models[] = get_class($controller->model);
      }
      if (isset($controller->view)) {
        $Views[] = get_class($controller->view);
      }
    }
    $Models[] = __NAMESPACE__ . NS . 'Model';
    $Views[] = __NAMESPACE__ . NS . 'View';
    foreach ($Models as $Model) {
      if (ClassLoader::classExists($Model)) {
        $this->model = new $Model();
        $Model::$Controller = get_class($this);
        break;
      }
    }
    foreach ($Views as $View) {
      if (ClassLoader::classExists($View)) {
        $this->view = new $View($this->model, $this->request, $this->response);
        $View::$Controller = get_class($this);
        $Model::$View = get_class($this->view);
        break;
      }
    }
    $this->view->__attach($this);
    $this->model->__attach($this->view);
  }

  public function index($offset = 0, $limit = 10)
  {
    $models = $this->model->read(array(), array('skip' => $offset, 'limit' => $limit));
    return $this->view->index($models, $offset, $limit);
  }

  public function add()
  {
    return $this->view->create();
  }

  public function edit($id)
  {
    return $this->update($id);
  }

  public function update($id)
  {
    if ($this->request->is('post')) {
      $modelClass = get_class($this->model);
      foreach ($this->request->data($modelClass) as $index => $attributes) {
        $model = new $modelClass($attributes);
        $model->save();
      }
    }
    $model = $this->model->first(array('id' => $id));
    return $this->view->update($model);
  }

  public function create()
  {
    if ($this->request->is('post')) {
      $modelClass = get_class($this->model);
      foreach ($this->request->data($modelClass) as $index => $attributes) {
        $model = new $modelClass($attributes);
        $model->save();
      }
    }
    return $this->view->create();
  }

  public function view($id)
  {
    $model = $this->model->first(array('id' => $id));
    return $this->view->view($model);
  }
}
