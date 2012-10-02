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
  public $locale;
  protected $router;
  protected $request;
  protected $response;

  public function __construct(Model $model, HTTP\Request $request, HTTP\Response $response)
  {
    parent::__construct();
    $this->model = $model;
    $this->locale = Locale::instance();
    $this->request = $request;
    $this->response = $response;
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
      $title = $form->text('title', array('title' => $this->locale->t('Title')));
      $form->add($title);
      $form->submit();
      return $this->response->body($page->compose($form));
    }
  }

  public function form($model, $action)
  {
    return new Helper\HTML\Form($model, $action);
  }

  public function table($models = array(), $_ = array())
  {
    $class = get_class($this->model);
    extract(array_merge(array(
      'actions' => static::cfg('table.actions'),
      'headers' => static::cfg('table.headers'),
      'columns' => static::cfg('table.columns') ?
        : array_combine(array_keys(get_object_vars($this->model)), array_keys(get_object_vars($this->model)))
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
      $table = $this->table($models);
      return $this->response->body($page->compose($table));
    }
  }
}
