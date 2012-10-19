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
use Aldu\Core\Utility\Inflector;
use DateTime;

class Controller extends Event\Listener
{
  public $model;
  public $view;
  protected $request;
  protected $response;

  public function __construct(HTTP\Request $request = null,
    HTTP\Response $response = null)
  {
    $this->request = $request ? : HTTP\Request::instance();
    $this->response = $response ? : HTTP\Response::instance();
    $this->router = Router::instance();
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

  public function browse($offset = 0, $limit = 10)
  {
    $search = $this->request->query('search');
    $options = array_merge($this->request->query('options'),
      array(
        'skip' => $offset, 'limit' => $limit
      ));
    $models = $this->model->read($search, $options);
    return $this->view->browse($models);
  }

  public function read($id)
  {
    if ($model = $this->model->first(array(
        'id' => $id
      ))) {
    }
    return $this->view->read($model);
  }

  public function edit($id)
  {
    if ($this->request->is('post')) {
      $modelClass = get_class($this->model);
      foreach ($this->request->data($modelClass) as $index => $attributes) {
        $model = new $modelClass($attributes);
        $model->save();
      }
    }
    if ($model = $this->model->first(array(
        'id' => $id
      ))) {
    }
    return $this->view->edit($model);
  }

  public function add()
  {
    if ($this->request->is('post')) {
      $this->post(__FUNCTION__, $this->request->data());
    }
    return $this->view->add();
  }

  protected function post($action, $data = array())
  {
    foreach ($data as $class => $array) {
      if (!ClassLoader::classExists($class)) {
        continue;
      }
      $model = new $class();
      $tags = array(
        '+has' => array(), '-has' => array(),
        '+belongs' => array(), '-belongs' => array()
      );
      while (list($index, $attributes) = each($array)) {
        foreach ($attributes as $attribute => $value) {
          if (ClassLoader::classExists($attribute)) {
            // TODO relationships
          }
          elseif ($model->authorized($this->request->aro, $action, $attribute)) {
            $type = $class::cfg("attributes.$attribute.type");
            switch ($type) {
            case is_subclass_of($type, 'Aldu\Core\Model'):
              if ($$type = $type::first($value)) {
                $value = $$type;
              }
              break;
            case 'datetime':
              $value = DateTime::createFromFormat(ALDU_DATETIME_FORMAT, $value);
              break;
            }
            $model->$attribute = $value;
          }
        }
        if ($model->authorized($this->request->aro, $action)) {// && $model->save()) {
          var_dump($model);
          $this->response
            ->message(
              $this->view->locale
                ->t('%s %s successfully %s.', $model->name(), $model->id,
                  Inflector::pastParticiple($action)));
          foreach ($tags as $type => $array) {
            foreach ($array as $tag) {
              extract($tag);
              switch ($type) {
              case '-has':
                if ($model->authorized($this->request->aro, $action, $tag)) {
                  $model->untag($tag);
                }
                break;
              case '+has':
                if ($model->authorized($this->request->aro, $action, $tag)) {
                  $model->tag($tag, $relation);
                }
                break;
              case '-belongs':
                if ($tag->authorized($this->request->aro, $action)) {
                  $tag->untag($model);
                }
                break;
              case '+belongs':
                if ($tag->authorized($this->request->aro, $action)) {
                  $tag->tag($model, $relation);
                }
                break;
              }
            }
          }
        }
        else {
          $this->response->message($this->view->locale->t('Cannot %s %s.', $action, $model->name()), LOG_ERR);
        }
      }
    }
  }

  public function delete($id)
  {
    if ($model = $this->model->first(array(
        'id' => $id
      ))) {
    }
    return $this->view->delete($model);
  }

}
