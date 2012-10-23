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
use Aldu\Core\Utility\Inflector;

use Aldu\Core\Utility\ClassLoader;

use Aldu\Core\View\Helper;
use Aldu\Core\Net\HTTP;
use DateTime;

class View extends Stub
{
  public static $Controller;
  public $model;
  public $render;
  public $locale;
  protected $router;
  protected $request;
  protected $response;

  protected static $configuration = array(__CLASS__ => array(
    'shortcuts' => array(
      'static' => array(
        'browse' => "Browse {%s:pluralize}",
        'add' => "Create %s"
      ),
      'model' => array(
        'read' => "Read %s %s",
        'edit' => "Edit %s %s",
        'delete' => "Delete %s %s"
      )
    )
  ));

  public function __construct(Model $model, HTTP\Request $request = null, HTTP\Response $response = null)
  {
    parent::__construct();
    $this->model = $model;
    $this->locale = Locale::instance();
    $this->router = Router::instance();
    $this->request = $request ?: HTTP\Request::instance();
    $this->response = $response ?: HTTP\Response::instance();
  }

  public function select($form, $name, $_ = array())
  {
    $_ = array_merge(array(
      'first' => true,
      'title' => '',
      'description' => '',
      'required' => false,
      'value' => null,
      'model' => $this->model,
      'search' => array(),
      'options' => array('limit' => 10)
    ), $_);
    extract($_);
    $models = array();
    $model = is_object($model) ? $model : new $model();
    if ($value) {
      $form->values[$name] = $value;
    }
    foreach ($model->read($search, $options) as $m) {
      $models[$m->id] = $m->label();
    }
    $select = $form->select($name, array_merge($_, array(
      'options' => $models
    )));
    if ($required && empty($models)) {
      $select->append('a', $this->locale->t('Add new'), array(
        'href' => $model->url('add')
      ));
    }
    if ($first) {
      $select->node('select')->prepend('option');//, $this->locale->t('Select %s', $model->name()));
    }
    return $select;
  }

  public function form($model, $action, $options = array())
  {
    $class = get_class($model);
    $form = new Helper\HTML\Form($model, $action);
    $form->hidden('id');
    $fields = static::cfg('form.fields') ?: array_keys($model->__toArray());
    foreach ($fields as $field => $options) {
      if (is_numeric($field)) {
        $field = $options;
        $title = ucfirst($field);
        $options = array();
      }
      extract(array_merge(array(
        'title' => ucfirst($field),
        'type' => $this->model->cfg("attributes.$field.type") ? : 'text',
        'attributes' => array()
        ), $options));
      switch ($type) {
      case is_subclass_of($type, 'Aldu\Core\Model'):
        $this->select($form, $field, array(
          'title' => $title,
          'model' => $type
        ));
        break;
      case 'bool':
      case 'boolean':
        $form->checkbox($field, array(
          'title' => $title
        ));
        break;
      case is_array($type):
        $form->select($field, array(
          'title' => $title,
          'options' => array_combine($type, $type),
          'attributes' => array('multiple' => true)
          ));
        break;
      default:
        $form->$type($field, array(
          'title' => $title,
          'attributes' => $attributes
        ));
      }
    }
    foreach (array('has', 'belongs') as $rel) {
      foreach (static::cfg("form.$rel") as $rel_name => $relation) {
        extract(array_merge(array(
        'model' => null,
        'search' => array(),
        'options' => array(),
        'fieldset' => false,
        'type' => 'checkbox',
        'title' => null,
        'attributes' => array(),
        'relation' => array()
        ), $relation), EXTR_PREFIX_ALL, 'rel');
        $rel_title = $rel_title ?: Inflector::pluralize($rel_model::name());
        if (ClassLoader::classExists($rel_model)) {
          if ($rel_fieldset) {
            $form->fieldset($rel_model, array(
              'title' => $rel_title
            ));
          }
          if ($rel_type === 'select') {
            foreach ($rel_model::read($rel_search, $rel_options) as $tag) {
              if ($model->id) {
                if ($existingRelation = $model->$rel($tag)) {
                  $form->values[$rel_model][$rel][$tag->id] = $tag->id;
                }
              }
            }
            $this->select($form, $rel_model, array(
                'model' => $rel_model,
                'title' => $rel_title,
                'attributes' => $rel_attributes,
                'relation' => array('type' => $rel)
            ));
            continue;
          }
          foreach ($rel_model::read($rel_search, $rel_options) as $tag) {
            if ($model->id) {
              if ($existingRelation = $model->$rel($tag)) {
                $form->values[$rel_model][$rel][$tag->id] = $tag->id;
              }
            }
            $relElement = $form->$rel_type($rel_model, array(
              'title' => $tag->label(),
              'attributes' => $rel_attributes,
              'relation' => array('type' => $rel),
              'value' => $tag->id
            ));
            foreach ($rel_relation as $_rel_name => $_relation) {
              if (isset($existingRelation) && isset($existingRelation[$_rel_name])) {
                $form->values[$rel_model][$rel]['relations'][$tag->id][$_rel_name] = $existingRelation[$_rel_name];
              }
              extract(array_replace_recursive(array(
                'type' => 'checkbox',
                'options' => array(
                  'value' => $tag->id,
                  'relation' => array(
                    'type' => $rel,
                    'name' => $_rel_name
                  )
                )
              ), $_relation), EXTR_PREFIX_ALL, '_rel');
              $relElement->append($form->$_rel_type($rel_model, $_rel_options));
            }
          }
          if ($rel_fieldset) {
            $form->unstack();
          }
        }
      }
    }
    return $form;
  }

  public function table($models = array(), $_ = array())
  {
    $class = get_class($this->model);
    extract(array_merge(array(
      'actions' => static::cfg('table.actions'),
      'headers' => static::cfg('table.headers'),
      'columns' => static::cfg('table.columns') ?
        : array_combine(array_keys($this->model->__toArray()), array_keys($this->model->__toArray()))
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
            'sortable',
            'searchable'
          ), $classes))
        )
      );
    }
    $table = new Helper\HTML\Table($headers);
    foreach ($models as $model) {
      $tr = $table->tr();
      foreach ($actions as $action) {
        switch ($action) {
        case 'select':
        }
      }
      foreach ($columns as $attribute => $column) {
        if (is_numeric($attribute)) {
          $attribute = $column;
        }
        if ($model->$attribute instanceof Model) {
          $tr->td($model->$attribute->label());
        }
        elseif ($model->$attribute instanceof DateTime) {
          $tr->td($model->$attribute->format(ALDU_DATETIME_FORMAT));
        }
        elseif (is_array($model->$attribute)) {
          $tr->td(implode(',', $model->$attribute));
        }
        else {
          $tr->td($model->$attribute);
        }
      }
    }
    return $table;
  }

  public function browse($models = array())
  {
    $render = $this->request->query('render') ?: $this->render;
    switch ($render) {
    case 'json':
      $json = array();
      foreach ($models as $model) {
        $json[] = $model->__toArray(true);
      }
      $this->response->type('json');
      return $this->response->body(json_encode($json, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
    case 'page':
    default:
      $page = new Helper\HTML\Page();
      $page->theme();
      $page->title(__METHOD__);
      $table = $this->table($models);
      return $this->response->body($page->compose($table));
    }
  }

  public function read($model)
  {
    $this->model = $model;
  }


  public function edit($model)
  {
    $this->model = $model;
    switch ($this->render) {
      case 'page':
      default:
        $page = new Helper\HTML\Page();
        $page->theme();
        $page->title($this->locale->t('Edit %s %s', $this->model->name(), $model->id));
        $form = $this->form($model, __FUNCTION__);
        $form->submit();
        return $this->response->body($page->compose($form));
    }
  }


  public function add()
  {
    switch ($this->render) {
    case 'page':
    default:
      $page = new Helper\HTML\Page();
      $page->theme();
      $page->title($this->locale->t('Add new %s', $this->model->name()));
      $form = $this->form($this->model, __FUNCTION__);
      $form->submit();
      return $this->response->body($page->compose($form));
    }
  }


  public static function shortcuts($block, $element)
  {
    $ul = new Helper\HTML('ul.menu.clearfix.aldu-core-view-shortcuts');
    $router = Router::instance();
    $locale = Locale::instance();
    if (($route = $router->current) && $route->controller) {
      foreach (static::cfg('shortcuts.static') as $action => $title) {
        if ($route->controller->model->authorized($router->request->aro, $action)) {
          $ul->li()->a($locale->t($title, $route->controller->model->name()))->href = $route->controller->model->url($action);
        }
      }
      if ($route->controller->view->model->id) {
        $model = $route->controller->view->model;
        foreach (static::cfg('shortcuts.model') as $action => $title) {
          if ($model->authorized($router->request->aro, $action)) {
            $ul->li()->a($locale->t($title, $model->name(), $model->id))->href = $model->url($action);
          }
        }
      }
    }
    return $ul;
  }
}
