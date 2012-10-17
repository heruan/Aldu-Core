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

  public function __construct($model, $action, $_ = array(), $document = null)
  {
    $this->request = Core\Net\HTTP\Request::instance();
    $this->response = Core\Net\HTTP\Response::instance();
    $this->router = Core\Router::instance();
    extract(array_merge(array(
      'id' => null,
      'index' => 0,
      'method' => 'post',
      'context' => null,
      'defaults' => array(),
      'redirect' => null,
      'references' => array()
    ), $_));
    $this->indexes = $references;
    $this->defaults = $defaults;
    $this->setModel($model, $index);
    $this->context = $this->model;
    $this->action = $action;
    parent::__construct('div', $document);
    $this->class = 'aldu-core-view-helper-html-form';
    $this->id = $id ? : 'form' . uniqid(); //md5($this->model . $this->action);
    $this->form = $this->append('form', array(
      'id' => $this->id,
      'name' => $this->action,
      'data-model' => $this->model ? get_class($this->model) : null,
      'data-id' => $this->model ? $this->model->id : null,
      'action' => $this->context->url($this->action),
      'method' => $method
    ));
    if ($redirect) {
      $this->append('input', array(
        'form' => $this->id,
        'type' => 'hidden',
        'name' => '_redirect',
        'value' => $redirect
        ));
    }
  }

  public function setModel($model = null, $index = null)
  {
    $this->model = !is_null($model) ? $model : new Core\Model();
    if ($this->model) {
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
      $this->values = $this->model->__toArray();
      $index = $this->currentIndex();
      if ($this->request->is('post')) {
        //$this->response->debug($this->request->data());
        $data = $this->request->data($modelClass);
        if (isset($data[$index])) {
          $this->values = $data[$index];
        }
      }
      return;
      foreach ($this->model->tags() as $tag) {
        $relation = $tag->tagged($this->model) ? : array();
        $this->values[get_class($tag)]['tag'][$tag->id] = array();
        foreach ($relation as $key => $value) {
          $this->values[get_class($tag)]['tag'][$tag->id][$key][] = $value;
        }
      }
      foreach (explode(',', $this->model->acl) as $action) {
        $this->values['Aldu\Core\Model']['acl']['relations'][0]['acl'][] = trim($action);
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

  public function fieldset()
  {
    $args = func_get_args();
    $fieldset = $this->__call(__FUNCTION__, $args);
    $this->stack($fieldset->node('fieldset'));
    return $fieldset;
  }

  public function unstack()
  {
    $stack = $this->node;
    $this->node = array_pop($this->stack);
    return $stack;
  }

  public function text($text = null)
  {
    $args = func_get_args();
    return $this->__call(__FUNCTION__, $args);
  }

  protected function normalizeValue(&$value)
  {
    if ($value && $value instanceof DateTime) {
      $value = $value->format(ALDU_DATETIME_FORMAT);
    }
  }

  public function __call($type, $arguments)
  {
    $name = array_shift($arguments);
    $readonly = false;
    if ($this->model && $this->model->id) {
      $readonly = !$this->model->authorized($this->request->aro, $this->action, $name);
    }
    if (is_object($name)) {
      $name = get_class($name);
    }
    $_ = array_shift($arguments) ? : array();
    $_ = array_merge(array(
      'title' => '',
      'description' => '',
      'attributes' => array(),
      'class' => null,
      'options' => array(),
      'required' => false,
      'readonly' => $readonly,
      'value' => array_key_exists($name, $this->values) ? $this->values[$name]
        : (($this->model && $this->model->$name) ? $this->model->$name : false),
      'gateway' => false
    ), $_);
    extract($_);
    $this->normalizeValue($value);
    $modelClass = $this->model ? get_class($this->model) : null;
    if ($modelClass && ($value === false || $value === 'false')
      && is_subclass_of($modelClass::cfg("attributes.$name.type"), 'Aldu\Core\Model')) {
      $referenceClass = $modelClass::cfg("attributes.$name.type");
      $referenceIndex = isset($this->indexes[$referenceClass]) ? $this->indexes[$referenceClass] : 0;
      $value = $referenceClass . ':' . $referenceIndex;
    }
    $L = Core\Locale::instance();
    $id = uniqid($name);
    $_name = $modelClass ? $modelClass . '[' . $this->indexes[$modelClass] . '][' . $name . ']'
      : $name;
    $div = $this->create('div', array(
      'title' => $description,
      'data-name' => $name,
      'class' => trim(implode(' ', array(
        'aldu-helpers-html-form-element',
        'aldu-helpers-html-form-' . $type,
        $class,
        $required ? 'aldu-helpers-html-form-required' : null,
        isset($attributes['class']) ? $attributes['class'] : null
      )))
    ));
    unset($attributes['class']);
    $this->append($div);
    $label = $title ? $this->create('label', $title, array(
        'for' => $id
      )) : null;
    switch ($type) {
    case 'radiogroup':
      $div->append($label);
      foreach ($options as $option) {
        $div->append($this->radio($name, $option));
      }
      return $div;
    case 'radio':
    case 'checkbox':
      if (is_array($value)) {
        $__name = $_name;
        extract($value);
        $relation = isset($relation) ? $relation : array();
        $_id = $value;
        switch ($type) {
        case 'radio':
          $_name .= "[$aot][*]";
          break;
        case 'checkbox':
          $_name .= "[$aot][$value]";
          break;
        }
        if ($relation) {
          $_name = $__name . "[$aot][relations][$value][{$relation['key']}][]";
          $value = is_array($relation['value']) ? implode(',', $relation['value'])
            : $relation['value'];
        }
      }
      $checked = isset($attributes['checked']) ? $attributes['checked'] : false;
      if (isset($this->values[$name]) && is_array($this->values[$name])) {
        if (isset($this->values[$name][$aot])) {
          if (isset($this->values[$name][$aot][$_id])) {
            $checked = true;
            unset($this->values[$name][$aot][$_id]);
          }
          elseif ($value && isset($this->values[$name][$aot]['relations'])
            && isset($this->values[$name][$aot]['relations'][$_id])
            && isset($this->values[$name][$aot]['relations'][$_id][$relation['key']])
            && ((is_array($this->values[$name][$aot]['relations'][$_id][$relation['key']])
              && in_array($value, $this->values[$name][$aot]['relations'][$_id][$relation['key']]))
              || $value === $this->values[$name][$aot]['relations'][$_id][$relation['key']])) {
            $checked = true;
          }
        }
      }
      elseif (isset($this->values[$name]) && $this->values[$name]) {
        $checked = true;
      }
      $hidden = null;
      if ($checked && (!isset($relation) || !$relation)) {
        $hidden = $this->create('input', array(
          'form' => $this->id,
          'name' => ($type === 'radio' && isset($__name)) ? $__name .= "[$aot][$_id]" : $_name,
          'type' => 'hidden',
          'value' => 0
        ));
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
      $div->append($hidden, $element, $label);
      break;
    case 'fieldset':
      $div->class .= ' aldu-helpers-html-form-fieldset';
      $element = $this->create('fieldset', array_merge($attributes, array(
        'name' => $_name
      )));
      if ($title)
        $element->append('legend', $title);
      $div->append($element);
      break;
    case 'select':
      $aot = 'tag';
      if (is_array($value)) {/*
        $__name = $_name;
        extract($value);
        $relation = isset($relation) ? $relation : array();
        $_id = $value;
        $_name .= "[$aot][*]";
        if ($relation) {
          $_name = $__name . "[$aot][relations][$value][{$relation['key']}][]";
          $value = $relation['value'];
        }*/
      }
      $element = $this->create('select', array_merge($attributes, array(
        'id' => $id,
        'name' => $_name
      )));
      foreach ($options as $_value => $_label) {
        if (is_array($_label)) {
          $optgroup = $element->append('optgroup', array(
            'label' => $_value
          ));
          foreach ($_label as $__value => $__label) {
            $option = $optgroup->append('option', $__label, array(
              'value' => $__value
            ));
            if (isset($this->values[$name]) && $this->values[$name] === $__value) {
              $option->selected = 'selected';
            }
          }
          continue;
        }
        $option = $element->append('option', $_label, array(
          'value' => $_value
        ));
        if ($_value === '_disabled') {
          $option->disabled = true;
        }
        if (isset($this->values[$name]) && is_array($this->values[$name])) {
          if (isset($this->values[$name][$aot])) {
            if (isset($this->values[$name][$aot][$_id])) {
              $option->selected = 'selected';
            }
            elseif ($_value && isset($this->values[$name][$aot]['relations'])
              && isset($this->values[$name][$aot]['relations'][$_id])
              && isset($this->values[$name][$aot]['relations'][$_id][$relation['key']])
              && ((is_array($this->values[$name][$aot]['relations'][$_id][$relation['key']])
                && in_array($_value, $this->values[$name][$aot]['relations'][$_id][$relation['key']]))
                || $_value === $this->values[$name][$aot]['relations'][$_id][$relation['key']])) {
              $option->selected = 'selected';
            }
          }
        }
        elseif (isset($this->values[$name]) && $this->values[$name] == $_value) {
          $option->selected = 'selected';
        }
      }
      $div->append($label, $element);
      break;
    case 'submit':
      $element = $this->create('button', $title ? : $L->t('Submit'), array_merge($attributes, array(
        'name' => '_submit',
        'type' => $type,
        'value' => $this->currentIndex()
      )));
      $div->append($element);
      if ($gateway) {
        $div->append('span.margin')->text($L->t('and'));
        $model = $this->model;
        $index = $this->currentIndex();
        $this->setModel(false);
        $div->append($this->radio('_redirect', array(
          'title' => $L->t('Go back to the previous page'),
          'attributes' => array(
            'checked' => true
          ),
          'value' => $this->request->referer ? : $this->request->base . $this->request->path
        )));
        $div->append($this->radio('_redirect', array(
          'title' => $L->t('Come back to this page'),
          'value' => $this->request->base . $this->request->path
        )));
        if ($model) {
          $div->append($this->radio('_redirect', array(
            'title' => $L->t('View this %s', $model->name()),
            'value' => ''
          )));
        }
        $this->setModel($model, $index);
      }
      break;
    case 'button':
      $element = $this->create('button', $title, array_merge($attributes, array(
        'name' => $name,
        'value' => $value
        )));
      $div->append($element);
      break;
    case 'textarea':
      $div->class .= ' aldu-helpers-html-form-textarea';
      $element = $this->create('textarea', $value ? : null, array_merge($attributes, array(
        'id' => $id,
        'name' => $_name
      )));
      if ($description)
        $element->placeholder = $description;
      $div->append($label, $element);
      break;
    case 'file':
      $this->form->enctype = 'multipart/form-data';
      if (is_array($value)) {
        extract($value);
        $relation = isset($relation) ? $relation : array();
        if ($relation) {
          $_name .= "[$aot][relations][$value][{$relation['key']}]";
          $value = $relation['value'];
        }
        else {
          $_name .= "[$aot][$value]";
        }
      }
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
      if ($label) {
        $label->append('span.tooltip', $L->t('Maximum file size: %sMB', round($this->request->upload->maxUploadSize()
          / 1024 / 1024)));
      }
      $element = $this->create('input', array_merge($attributes, array(
        'id' => $id,
        'name' => $_name,
        'type' => $type
      )));
      $div->append($label, $thumb, $element);
      break;
    case 'hidden':
    case 'text':
    case 'password':
    default:
      if ($value && is_array($value)) {
        extract($value);
        $relation = isset($relation) ? $relation : array();
        if ($relation) {
          $_name .= "[$aot][relations][$value][{$relation['key']}]";
          $value = $relation['value'];
        }
        else {
          $_name .= "[$aot][$value]";
        }
      }
      $div->class .= ' aldu-helpers-html-form-input';
      $element = $this->create('input', array_merge($attributes, array(
        'id' => $id,
        'name' => $_name,
        'title' => $title,
        'type' => $type,
        'value' => $value !== false ? $value : null
      )));
      if ($description)
        $element->placeholder = $description;
      $div->append($label, $element);
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
      $div->addClass('aldu-helpers-html-form-element-readonly');
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
    $this->elements[$name] = $div;
    return $div;
  }

  public function add($node)
  {
    $this->button('_add', array(
      'title' => 'Add',
      'value' => $this->id
      ));
  }

  public function confirm($node, $reason = '')
  {
    if ($input = $this->document->node('input', $node)->first()) {
      $title = '';
      if ($label = $this->document->node('label', $node)->first()) {
        $title = $label->value() . ' (' . $reason . ')';
      }
      $model = $this->model;
      $index = $this->currentIndex();
      $this->setModel(false);
      $confirm = $this->{$input->type}(null, array(
        'title' => $title,
        'value' => $input->value
      ));
      $this->setModel($model, $index);
      $confirm->node('input')->first()->set('data-equals', $input->id);
      return $confirm;
    }
  }
}
