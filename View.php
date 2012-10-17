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
        'index' => "Index of %s",
        'create' => "Create new %s"
      ),
      'model' => array(
        'view' => "View %s %s",
        'update' => "Edit %s %s",
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
        'href' => $model->url('create')
      ));
    }
    if ($first) {
      $select->node('select')->prepend('option', $this->locale->t('Select %s', $model->name()))->value = '';
    }
    return $select;
  }

  public function form($model, $action)
  {
    $form = new Helper\HTML\Form($model, $action);
    foreach ($model->__toArray() as $field => $value) {
      $type = $this->model->cfg("attributes.$field.type") ? : 'text';
      switch ($type) {
      case is_subclass_of($type, 'Aldu\Core\Model', true):
        $this->select($form, $field, array(
          'title' => $field,
          'model' => $type
        ));
        break;
      case is_array($type):
        $form->select($field, array(
          'title' => $field,
          'options' => array_combine($type, $type),
          'attributes' => array('multiple' => true)
          ));
        break;
      default:
        $form->$type($field, array(
          'title' => $field
        ));
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
        if ($model->$attribute instanceof DateTime) {
          $tr->td($model->$attribute->format(ALDU_DATETIME_FORMAT));
        }
        else {
          $tr->td($model->$attribute);
        }
      }
    }
    return $table;
  }

  public function index($models = array(), $offset = 0, $limit = 10)
  {
    switch ($this->render) {
    case 'page':
    default:
      $page = new Helper\HTML\Page();
      $page->theme();
      $page->title(__METHOD__);
      $table = $this->table($models);
      return $this->response->body($page->compose($table));
    }
  }

  public function create()
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

  public function update($model)
  {
    $this->model = $model;
    switch ($this->render) {
    case 'page':
    default:
      $page = new Helper\HTML\Page();
      $page->theme();
      $page->title($this->locale->t('Add new %s', $this->model->name()));
      $form = $this->form($model, __FUNCTION__);
      $form->submit();
      return $this->response->body($page->compose($form));
    }
  }

  public function view($model)
  {
    $this->model = $model;
  }

  public static function shortcuts($block, $element)
  {
    $ul = new Helper\HTML('ul.menu.clearfix.aldu-core-view-shortcuts');
    $router = Router::instance();
    $locale = Locale::instance();
    if (($route = $router->current) && $route->controller) {
      foreach (static::cfg('shortcuts.static') as $action => $title) {
        $ul->li()->a($locale->t($title, $route->controller->model->name()))->href = $route->controller->model->url($action);
      }
      if ($route->controller->model->id) {
        foreach (static::cfg('shortcuts.model') as $action => $title) {
          $ul->li()->a($locale->t($title, $route->controller->model->name(), $route->controller->model->id))->href = $route->controller->model->url($action);
        }
      }
    }
    return $ul;
  }
}
