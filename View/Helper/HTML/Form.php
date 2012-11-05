<?php
/**
 * Aldu\Core\View\Helper\HTML\Form
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
 * @package       Aldu\Core\View\Helper\HTML
 * @uses          Aldu\Core\View\Helper
 * @since         AlduPHP(tm) v1.0.0
 * @license       Creative Commons Attribution-ShareAlike 3.0 Unported (CC BY-SA 3.0)
 */

namespace Aldu\Core\View\Helper\HTML;
use Aldu\Core\Utility\Inflector;

use Aldu\Core\View\Helper;
use Aldu\Core;
use DateTime;

class Form extends Helper\HTML
{
  public $id;
  public $context;
  public $model;
  public $action;
  public $form;
  public $elements = array();
  public $values = array();
  protected $indexes = array();
  protected $stack = array();
  protected $defaults = array();
  protected $request;
  protected $response;
  protected $router;
  protected $locale;

  public function __construct($model, $action, $_ = array(), $document = null)
  {
    $this->request = Core\Net\HTTP\Request::instance();
    $this->response = Core\Net\HTTP\Response::instance();
    $this->router = Core\Router::instance();
    $this->locale = Core\Locale::instance();
    extract(array_merge(array(
      'id' => null,
      'index' => 0,
      'method' => 'post',
      'context' => null,
      'defaults' => array(),
      'redirect' => null,
      'references' => array(),
      'attributes' => array()
    ), $_));
    $this->indexes = $references;
    $this->defaults = $defaults;
    $this->setModel($model, $index);
    $this->context = $this->model;
    $this->action = $action;
    parent::__construct('div', $document);
    $this->class = 'aldu-core-view-helper-html-form';
    $this->id = $id ? : 'form' . uniqid(); //md5($this->model . $this->action);
    $this->form = $this->append('form', array_merge($attributes, array(
      'id' => $this->id,
      'name' => $this->action,
      'data-model' => $this->model ? get_class($this->model) : null,
      'data-id' => $this->model ? $this->model->id : null,
      'action' => $this->context->url($this->action),
      'method' => $method
    )));
    //$this->node = $this->form->node;
    if ($redirect) {
      $this->append('input', array(
        'form' => $this->id,
        'type' => 'hidden',
        'name' => 'redirect',
        'value' => $redirect
      ));
    }
  }

  public function setModel($model, $index = null)
  {
    $this->model = $model;
    $modelClass = get_class($this->model);
    if (!is_null($index)) {
      $this->indexes[$modelClass] = $index;
    }
    elseif (isset($this->indexes[$modelClass])) {
      $this->indexes[$modelClass]++;
    }
    else {
      $this->indexes[$modelClass] = 0;
    }
    $this->values = get_object_vars($this->model);
    $index = $this->currentIndex();
    if ($this->request->is('post')) {
      $data = $this->request->data($modelClass);
      if (isset($data[$index])) {
        //$this->values = $data[$index];
      }
    }
  }

  public function currentIndex()
  {
    return $this->model ? $this->indexes[get_class($this->model)] : 0;
  }

  public function stack($new)
  {
    $this->stack[] = $this->node;
    return $this->node = $new->node;
  }

  public function text($name = null)
  {
    $args = func_get_args();
    return $this->__call(__FUNCTION__, $args);
  }

  public function fieldset()
  {
    $args = func_get_args();
    $fieldset = $this->append('fieldset');
    $fieldset->append('legend', array_shift($args));
    $this->stack($fieldset);
    return $fieldset;
  }

  public function unstack()
  {
    $stack = $this->node;
    $this->node = array_pop($this->stack);
    return $stack;
  }

  protected function normalizeValue(&$value)
  {
    if ($value && $value instanceof DateTime) {
      $value = $value->format(ALDU_DATETIME_FORMAT);
    }
  }

  public function group($options)
  {
    extract(array_merge(array(
      'title' => '',
      'attributes' => array(),
      'controls' => array(),
      'required' => false
    ), $options));
    $group = $this->append('div.aldu-core-view-helper-html-form-element', $attributes);
    if ($title) {
      $group->label($title);
    }
    if ($required) {
      $group->addClass('aldu-core-view-helper-html-form-required');
    }
    foreach ($controls as $elements) {
      $row = $group->append('div.aldu-core-view-helper-html-form-controls controls-row');
      call_user_func_array(array(
        $row,
        'append'
      ), $elements);
    }
    return $group;
  }

  public function __call($type, $arguments)
  {
    $name = array_shift($arguments);
    if (is_object($name)) {
      $name = get_class($name);
    }
    $readonly = $this->model->id ? !$this->model->authorized($this->request->aro, $this->action, $name) : false;
    $_ = array_shift($arguments) ? : array();
    $_ = array_merge(array(
      'title' => '',
      'description' => '',
      'hint' => '',
      'none' => $this->locale->t('None'),
      'attributes' => array(),
      'options' => array(),
      'required' => false,
      'readonly' => $readonly,
      'multiple' => false,
      'value' => array_key_exists($name, $this->values) ? $this->values[$name] : false,
      'relation' => array(),
      'redirect' => false,
      'render' => 'group'
    ), $_);
    extract($_);
    $this->normalizeValue($value);
    $modelClass = get_class($this->model);
    if (($value === false || $value === 'false')
      && is_subclass_of($modelClass::cfg("attributes.$name.type"), 'Aldu\Core\Model')) {
      $referenceClass = $modelClass::cfg("attributes.$name.type");
      $referenceIndex = isset($this->indexes[$referenceClass]) ? $this->indexes[$referenceClass] : 0;
      $value = $referenceClass . ':' . $referenceIndex;
    }
    $id = uniqid(Inflector::slug($name));
    $_name = $modelClass . '[' . $this->indexes[$modelClass] . '][' . $name . ']';
    if ($multiple) {
      $_name .= '[]';
    }
    $group = $this->create('div', array(
      'title' => $description,
      'data-name' => $name,
      'class' => implode(' ', array_filter(array(
        "aldu-core-view-helper-html-form-element",
        "aldu-core-view-helper-html-form-$type",
        $required ? "aldu-core-view-helper-html-form-required" : null
      )))
    ));
    $controls = $this->create('div.aldu-core-view-helper-html-form-controls');
    $label = $title ? $this->create('label.aldu-core-view-helper-html-form-label', $title, array(
        'for' => $id
      )) : null;
    switch ($type) {
    case 'radiogroup':
      foreach ($options as $option) {
        $controls->append($this->radio($name, $option));
      }
      $group->append($label, $controls);
      break;
    case 'radio':
    case 'checkbox':
      if ($relation) {
        extract($relation, EXTR_PREFIX_ALL, 'rel');
        $__name = $_name;
        switch ($type) {
        case 'radio':
          $_name .= "[$rel_type][*]";
          break;
        case 'checkbox':
          $_name .= "[$rel_type][$value]";
          break;
        }
        if (isset($rel_name) && isset($rel_relation)) {
          $_name = $__name . "[$rel_type][relations][$value][$rel_name]";
        }
      }
      $checked = isset($attributes['checked']) ? $attributes['checked'] : false;
      if ($relation) {
        if (isset($rel_name)) {
          if (isset($this->values[$name][$rel_type]['relations'][$value][$rel_name])
            && isset($this->values[$name][$rel_type]['relations'][$value][$rel_name])) {
            $checked = true;
          }
        }
        elseif (isset($this->values[$name][$rel_type][$value])) {
          $checked = true;
        }
      }
      elseif (isset($this->values[$name]) && $this->values[$name]) {
        $checked = true;
      }
      if ($checked) {
        $controls->append($this->create('input', array(
          'form' => $this->id,
          'name' => $_name,
          'type' => 'hidden',
          'value' => '-'
        )));
      }
      $element = $this->create('input', array_merge($attributes, array(
        'id' => $id,
        'name' => $_name,
        'type' => $type,
        'value' => is_null($value) ? 0 : $value
      )));
      if ($checked) {
        $element->checked = 'checked';
      }
      $label->addClass($type);
      $label->prepend($element);
      $element = $label;
      $controls->append($element);
      $group->append($controls);
      break;
    case 'select':
      $__name = $_name;
      if ($relation) {
        extract($relation, EXTR_PREFIX_ALL, 'rel');
        $_name .= "[$rel_type][$value]";
        if (isset($rel_name)) {
          $_name = $__name . "[$rel_type][relations][$value][$rel_name]";
        }
        $__name = $value ? $_name : $__name . "[$rel_type]";
      }
      if ($value && isset($attributes['multiple'])) {
        $_name .= '[]';
      }
      $element = $this->create('select', array_merge($attributes, array(
        'id' => $id,
        'name' => $_name
      )));
      if ($hint) {
        $element->option($hint, array(
          'value' => null,
          'disabled' => true,
          'selected' => true
        ));
      }
      if (!$required) {
        $element->option($none, array(
          'value' => null
        ));
      }
      foreach ($options as $_value => $_label) {
        if (is_array($_label)) {
          $optgroup = $element->append('optgroup', array(
            'label' => $_value
          ));
          foreach ($_label as $__value => $__label) {
            $option = $optgroup->append('option', $__label, array(
              'value' => $__value
            ));
            if (isset($this->values[$name]) && $this->values[$name] == $__value) {
              $option->selected = 'selected';
            }
          }
        }
        else {
          $option = $element->append('option', $_label, array(
            'value' => $_value
          ));
          if (isset($this->values[$name])
            && ($this->values[$name] == $_value
              || (is_array($this->values[$name]) && in_array($_value, $this->values[$name])))) {
            $option->selected = 'selected';
          }
          elseif ($relation) {
            if (isset($rel_name)) {
              if (isset($this->values[$name][$rel_type]['relations'][$value][$rel_name])
                && ($this->values[$name][$rel_type]['relations'][$value][$rel_name] == $_value
                  || (is_array($this->values[$name][$rel_type]['relations'][$value][$rel_name])
                    && in_array($_value, $this->values[$name][$rel_type]['relations'][$value][$rel_name])))) {
                $option->selected = 'selected';
              }
            }
            elseif (isset($this->values[$name][$rel_type][$_value])) {
              $option->selected = 'selected';
            }
          }
          if (isset($option->selected)) {
            $group->append($this->create('input', array(
              'form' => $this->id,
              'name' => $__name . "[$_value]",
              'type' => 'hidden',
              'value' => '-'
            )));
          }
        }
      }
      $group->append($label, $controls->append($element));
      break;
    case 'fieldset':
      $element = $this->create('fieldset', $attributes);
      if ($title) {
        $element->append('legend', $title);
      }
      return $element;
    case 'datalist':
      $attributes['id'] = $id;
      $attributes['name'] = $_name;
      $element = $this->create($type, $attributes);
      foreach ($options as $field => $label) {
        $element->option($label)->value = $field;
      }
      $group->append($element);
      break;
    case 'submit':
      $element = $this->create('button', $title ? : $this->locale->t('Submit'), array_merge($attributes, array(
        'name' => 'submit',
        'type' => $type,
        'value' => $this->currentIndex()
      )));
      $actions = $this->create('div.aldu-core-view-helper-html-form-actions');
      $group->append($actions->append($element));
      if ($redirect) {
        $group->append('span.margin')->text($this->locale->t('and'));
        $model = $this->model;
        $index = $this->currentIndex();
        $this->setModel(false);
        $group->append($this->radio('redirect', array(
          'title' => $this->locale->t('Go back to the previous page'),
          'attributes' => array(
            'checked' => true
          ),
          'value' => $this->request->referer ? : $this->request->base . $this->request->path
        )));
        $group->append($this->radio('redirect', array(
          'title' => $this->locale->t('Come back to this page'),
          'value' => $this->request->base . $this->request->path
        )));
        $group->append($this->radio('redirect', array(
          'title' => $this->locale->t('View this %s', $model->name()),
          'value' => ''
        )));
        $this->setModel($model, $index);
      }
      break;
    case 'button':
      $element = $this->create('button', $title, array_merge($attributes, array(
        'name' => $name,
        'value' => $value
      )));
      $group->append($element);
      break;
    case 'textarea':
      $element = $this->create('textarea', $value ? : null, array_merge($attributes, array(
        'id' => $id,
        'name' => $_name
      )));
      if ($description) {
        $element->placeholder = $description;
      }
      $group->append($label, $controls->append($element));
      break;
    case 'file':
      $this->form->enctype = 'multipart/form-data';
      $thumb = null;
      $fileType = explode('/', $this->model->type);
      switch (array_shift($fileType)) {
      case 'image':
        if ($this->model->filepath || ($value = $value ? : null)) {
          $thumb = $this->create('img', array(
            'src' => $this->model->url('thumb'),
            'alt' => ''
          ));
        }
        break;
      }
      $hint = $this->create('span.help-block', $this->locale->t('Maximum file size: %sMB', round($this->request->upload->maxUploadSize()
        / 1024 / 1024)));
      $element = $this->create('input', array_merge($attributes, array(
        'id' => $id,
        'name' => $_name,
        'type' => $type
      )));
      $group->append($label, $controls->append($thumb, $element, $hint));
      break;
    case 'hidden':
      $element = $this->create('input', array_merge($attributes, array(
        'id' => $id,
        'name' => $_name,
        'title' => $title,
        'type' => $type,
        'value' => $value !== false ? $value : null
      )));
      $render = 'element';
      $this->append($element);
      break;
    case 'text':
    case 'password':
    default:
      $group->addClass('aldu-core-view-helper-html-form-input');
      $element = $this->create('input', array_merge($attributes, array(
        'id' => $id,
        'name' => $_name,
        'title' => $title,
        'type' => $type,
        'value' => $value !== false ? $value : null
      )));
      if ($description) {
        $element->placeholder = $description;
      }
      if ($hint) {
        $hint = $this->create('span.help-block', $hint);
      }
      $group->append($label, $controls->append($element, $hint));
    }
    if ($required) {
      $element->required = 'required';
    }
    if ($readonly) {
      switch ($type) {
      case 'radio':
      case 'select':
      case 'checkbox':
        $element->disabled = 'disabled';
        break;
      default:
        $element->readonly = 'readonly';
      }
      $group->addClass('aldu-core-view-helper-html-form-element-readonly');
    }
    foreach ($this->defaults as $attribute => $value) {
      switch ($attribute) {
      case 'class':
        $element->addClass($value);
        break;
      default:
        $element->$attribute = $value;
      }
    }
    $element->form = $this->id;
    $element->addClass("aldu-core-view-helper-html-form-$type");
    $element->data(array(
      'model' => get_class($this->model),
      'index' => $this->currentIndex(),
      'name' => $name
    ));
    $this->elements[$name] = $element;
    $this->append($$render);
    return $$render;
  }

  public function confirm($node, $reason = 'confirm')
  {
    if ($input = $this->document->node('input', $node)->first()) {
      $title = '';
      if ($label = $this->document->node('label', $node)->first()) {
        $title = $label->value() . ' (' . $reason . ')';
      }
      $model = $this->model;
      $index = $this->currentIndex();
      $confirm = $this->{$input->type}(null, array(
        'title' => $title,
        'value' => $input->value,
        'required' => $input->required
      ));
      $confirm->node('input')->data(array(
        'equals' => $input->id,
        'validation-matches-match' => addslashes($input->name)
      ));
      return $confirm;
    }
  }

  public function add($node)
  {
    $this->button('_add', array(
      'title' => 'Add',
      'value' => $this->id
    ));
  }
}
