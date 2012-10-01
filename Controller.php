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

  public function __construct(HTTP\Request $request, HTTP\Response $response)
  {
    $this->request = $request;
    $this->response = $response;
    $self = get_class($this);
    $parts = explode(NS, $self);
    $class = array_pop($parts);
    array_pop($parts); // Pop 'Controller' part
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
        $Views[] = get_Class($controller->view);
      }
    }
    $Models[] = __NAMESPACE__ . NS . 'Model';
    $View[] = __NAMESPACE__ . NS . 'View';
    foreach ($Models as $Model) {
      if (ClassLoader::classExists($Model)) {
        $Model::$Controller = get_class($this);
        $this->model = new $Model();
        break;
      }
    }
    foreach ($Views as $View) {
      if (ClassLoader::classExists($View)) {
        $View::$Controller = get_class($this);
        $this->view = new $View($this->model, $this->request, $this->response);
        break;
      }
    }
    $this->view->__attach($this);
    $this->model->__attach($this->view);
  }
  
  public function index()
  {
    return $this->view->index();
  }
}
