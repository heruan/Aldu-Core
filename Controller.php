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

  public function __construct(HTTP\Request $request = null, HTTP\Response $response = null)
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
    $options = array_merge($this->request->query('options'), array(
      'skip' => $offset,
      'limit' => $limit
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
      $this->post(__FUNCTION__, $this->request->data());
    }
    if ($model = $this->model->first(array(
      'id' => $id
    ))) {
    }
    return $this->view->edit($model);
  }

  public function add()
  {
    if (!$this->model->authorized($this->request->aro, __FUNCTION__)) {
      return $this->response->status(401);
    }
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
      while (list($index, $attributes) = each($array)) {
        if (!isset($attributes['id']) || !($model = $class::instance($attributes['id']))) {
          $model = new $class();
        }
        if (!$model->authorized($this->request->aro, $action)) {
          continue;
        }
        $tags = array(
          '-has' => array(),
          '+has' => array(),
          '-belongs' => array(),
          '+belongs' => array()
        );
        foreach ($attributes as $attribute => $value) {
          if (ClassLoader::classExists($attribute)) {
            $rel_model = $attribute;
            foreach ($value as $rel_type => $relations) {
              foreach ($relations as $relation => $tag_id) {
                if ($relation === 'relations') {
                  continue;
                }
                if ($tag_id == 0) {
                  $tag_id = $relation;
                  $relation = '-';
                }
                if ($tag = $rel_model::first($tag_id)) {
                  if ($relation === '-') {
                    $tags["-$rel_type"][$tag_id] = array(
                      'tag' => $tag
                    );
                  }
                  else {
                    $tags["+$rel_type"][$tag_id] = array(
                      'tag' => $tag,
                      'relation' => isset($relations['relations'], $relations['relations'][$tag_id]) ? $relations['relations'][$tag_id]
                        : array()
                    );
                  }
                }
              }
            }
          }
          elseif ($model->authorized($this->request->aro, $action, $attribute)) {
            $type = $class::cfg("attributes.$attribute.type");
            if (is_subclass_of($type, 'Aldu\Core\Model')) {
              if ($$type = $type::first($value)) {
                $value = $$type;
              }
            }
            elseif ($type === 'datetime') {
              $value = DateTime::createFromFormat(ALDU_DATETIME_FORMAT, $value);
            }
            $model->$attribute = $value;
          }
        }
        if ($model->save()) {
          $this->response->message($this->view->locale->t('%s %s successfully %s.', $model->name(), $model->id, $this->view->locale->t(Inflector::pastParticiple($action))));
          foreach ($tags as $type => $tagArray) {
            foreach ($tagArray as $tag) {
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
          $this->response->message($this->view->locale->t('Cannot %s %s.', $this->view->locale->t($action), $model->name()), LOG_ERR);
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
