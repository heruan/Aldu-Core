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
  public static $Model;
  public $model;
  public $render;
  public $locale;
  protected $router;
  protected $request;
  protected $response;

  protected static $configuration = array(
    __CLASS__ => array(
      'shortcuts' => array(
        'static' => array(
          'browse' => array(
            'title' => "Browse %s",
            'inflection' => 'plural',
            'attributes' => array(
              'data-icon' => 'grid'
            )
          ),
          'add' => array(
            'title' => "Create %s",
            'attributes' => array(
              'data-icon' => 'plus'
            )
          )
        ),
        'model' => array(
          'read' => array(
            'title' => "Read %s %s"
          ),
          'edit' => array(
            'title' => "Edit %s %s"
          ),
          'delete' => array(
            'title' => "Delete %s %s"
          )
        )
      )
    )
  );

  public function __construct($model, HTTP\Request $request = null, HTTP\Response $response = null)
  {
    parent::__construct();
    $this->model = is_object($model) ? $model : new $model();
    $this->locale = Locale::instance();
    $this->router = Router::instance();
    $this->request = $request ? : HTTP\Request::instance();
    $this->response = $response ? : HTTP\Response::instance();
    $this->render = $this->request->query('render');
  }

  public static function controller()
  {
    $self = get_called_class();
    $parts = explode(NS, $self);
    $class = array_pop($parts);
    array_pop($parts);
    $ns = implode(NS, $parts);
    $Controllers = array(
        $ns . NS . 'Controllers' . NS . $class
    );
    foreach (class_parents($self) as $model) {
      if (isset($model::$Controller)) {
        $Controllers[] = $model::$Controller;
      }
    }
    foreach ($Controllers as $Controller) {
      if (ClassLoader::classExists($Controller)) {
        $self::$Controller = $Controller;
        break;
      }
    }
    return new $self::$Controller();
  }

  public static function model()
  {
    $self = get_called_class();
    $parts = explode(NS, $self);
    $class = array_pop($parts);
    array_pop($parts);
    $ns = implode(NS, $parts);
    $Models = array(
        $ns . NS . 'Models' . NS . $class
    );
    foreach (class_parents($self) as $view) {
      if (isset($view::$Model)) {
        $Models[] = $view::$Model;
      }
    }
    foreach ($Models as $Model) {
      if (ClassLoader::classExists($Model)) {
        $self::$Model = $Model;
        break;
      }
    }
    return new $self::$Model();
  }

  public function select($form, $name, $_ = array())
  {
    $_ = array_merge(array(
      'title' => '',
      'description' => '',
      'add' => true,
      'required' => false,
      'value' => null,
      'model' => $this->model,
      'search' => array(),
      'options' => array()
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
    if ($add && $required && empty($models)) {
      $select->append('a', $this->locale->t('Add new'), array(
        'href' => $model->url('add')
      ));
    }
    return $select;
  }

  public function formElement($form, $field, $options = array())
  {
    if (is_numeric($field)) {
      $field = $options;
      $options = array();
    }
    extract(array_merge(array(
      'title' => $this->locale->t(ucfirst($field)),
      'type' => $this->model->cfg("attributes.$field.type") ? : 'text',
      'attributes' => array()
    ), $options));
    switch ($type) {
    case is_subclass_of($type, 'Aldu\Core\Model'):
      $element = $type::view()->select($form, $field, array(
        'title' => $title
      ));
      break;
    case 'bool':
    case 'boolean':
      $element = $form->checkbox($field, array(
        'title' => $title
      ));
      break;
    case is_array($type):
      $element = $form->select($field, array(
        'title' => $title,
        'options' => array_combine($type, $type),
        'attributes' => array(
          'multiple' => true
        )
      ));
      break;
    default:
      $element = $form->$type($field, array(
        'title' => $title,
        'attributes' => $attributes
      ));
    }
    return $element;
  }

  public function form($model, $action, $options = array())
  {
    $class = get_class($model);
    $form = new Helper\HTML\Form($model, $action);
    $form->hidden('id');
    $fields = static::cfg('form.fields') ? : array_keys(get_object_vars($model));
    foreach ($fields as $field => $options) {
      $this->formElement($form, $field, $options);
    }
    foreach (array(
      'has',
      'belongs'
    ) as $rel) {
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
        $rel_title = $rel_title ? : $rel_model::name('plural');
        if (ClassLoader::classExists($rel_model)) {
          if ($rel_fieldset) {
            $rel_fieldset = array_merge(array(
              'title' => $rel_title,
              'attributes' => array()
            ), (array) $rel_fieldset);
            $form->fieldset($rel_model, array(
              'title' => $rel_fieldset['title'],
              'attributes' => array_merge(array(
                'data-role' => 'collapsible'
              ), $rel_fieldset['attributes'])
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
            $rel_model::view()->select($form, $rel_model, array(
              'title' => $rel_title,
              'attributes' => $rel_attributes,
              'relation' => array(
                'type' => $rel
              )
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
              'relation' => array(
                'type' => $rel
              ),
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
      'columns' => static::cfg('table.columns') ? : array_keys(get_object_vars($this->model))
    ), $_));
    foreach ($columns as $name => $title) {
      if (is_numeric($name)) {
        $name = $title;
        $title = ucfirst($name);
      }
      $classes = explode('.', $name);
      $name = array_shift($classes);
      $headers[$name] = array(
        'title' => $this->locale->t($title),
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

  public function listview($models = array())
  {
    $ul = new Helper\HTML('ul.aldu-core-view-listview');
    foreach ($models as $model) {
      $li = $ul->li();
      $li->a($model->id)->href = $model->url();
    }
    return $ul;
  }

  protected function toJSON($models = array(), $options = 0)
  {
    if (!is_array($models)) {
      $models = array($models);
    }
    $array = array();
    foreach ($models as $i => $model) {
      $array[$i] = array();
      foreach (get_object_vars($model) as $attribute => $value) {
        if (false && $value instanceof Model) {
          $value = array(
            'url' => $value->url('read', array('absolute' => true)),
            'path' => $value->url('read'),
            'class' => get_class($value),
            'id' => $value->id,
          );
        }
        $array[$i][$attribute] = $value;
      }
    }
    return json_encode($array, $options);
  }

  public function browse($models = array())
  {
    $render = $this->request->query('render') ? : $this->render;
    switch ($render) {
    case 'json':
      $this->response->type('json');
      return $this->response->body($this->toJSON($models, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
    case 'embed':
      $embed = $this->listview($models);
      return $this->response->body($embed);
    case 'page':
    default:
      $page = new Helper\HTML\Page();
      $page->title($this->locale->t('Browse %s', $this->model->name('plural')));
      $table = $this->listview($models);
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
      $page->title($this->locale->t('Edit %s %s', $this->model->name(), $model->id));
      $form = $this->form($model, __FUNCTION__);
      $form->submit();
      return $this->response->body($page->compose($form));
    }
  }

  public function add()
  {
    switch ($this->render) {
    case 'embed':
      $form = $this->form($this->model, __FUNCTION__);
      return $this->response->body($form);
    case 'page':
    default:
      $page = new Helper\HTML\Page();
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
      foreach (static::cfg('shortcuts.static') as $action => $options) {
        extract(array_merge(array(
          'title' => null,
          'inflection' => null,
          'attributes' => array()
        ), $options));
        if ($route->controller->model->authorized($router->request->aro, $action)) {
          $href = $route->controller->model->url($action);
          $li = $ul->li();
          $li->a($locale->t($title, $route->controller->model->name($inflection)), $attributes)->href = $href;
          if ($href === $router->basePath) {
            $li->addClass('active');
          }
        }
      }
      if ($route->controller->view->model->id) {
        $model = $route->controller->view->model;
        foreach (static::cfg('shortcuts.model') as $action => $options) {
          extract(array_merge(array(
            'title' => null,
            'inflection' => null,
            'attributes' => array()
          ), $options));
          if ($model->authorized($router->request->aro, $action)) {
            $href = $model->url($action);
            $li = $ul->li();
            $li->a($locale->t($title, $model->name($inflection), $model->id), $attributes)->href = $href;
            if ($href === $router->basePath) {
              $li->addClass('active');
            }
          }
        }
      }
    }
    return $ul;
  }
}
