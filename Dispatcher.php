<?php
/**
 * Aldu\Core\Dispatcher
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
 * @uses          Aldu\Core\Net\HTTP
 * @since         AlduPHP(tm) v1.0.0
 * @license       Creative Commons Attribution-ShareAlike 3.0 Unported (CC BY-SA 3.0)
 */

namespace Aldu\Core;
use Aldu\Core\Net\HTTP;

class Dispatcher extends Event\Listener
{
  public $request;
  public $response;
  public $cache;

  public function __construct(HTTP\Request $request, HTTP\Response $response = null)
  {
    parent::__construct();
    $this->request = $request;
    $this->response = $response ? : new HTTP\Response($this->request);
    $this->response->initialize();
    $this->cache = new Cache();
    if ($this->request->query('clearcache')) {
      $this->cache->clear();
    }
    if ($this->request->query('nocache')) {
      $this->cache->enabled = false;
    }
    $this->trigger('beforeCache');
    if ($this->request->is('cli')) {
      $this->trigger('afterCache');
      return;
    }
    elseif ($this->request->is('get')
      && ALDU_CACHE_FAILURE !== ($cached = $this->cache->fetch($this->request->id))) {
      $this->trigger('requestIsCached');
      $this->response = $cached;
      $this->response->header('X-Aldu-Cached', 'yes');
    }
    else {
      $this->trigger('requestIsNotCached');
      $this->trigger('beforeRouting');
      $router = new Router($this->request, $this->response);
      foreach ($router->resolve($this->request->path) as $result) {
        extract(array_merge(array(
          'controller' => null,
          'action' => null,
          'arguments' => array()
        ), $result));
        $callback = array(
          $controller,
          $action
        );
        if (!is_callable($callback) || !call_user_func_array($callback, $arguments)) {
          $this->response->status(404);
          break;
        }
      }
      $this->cache->store($this->request->id, $this->response);
      $this->response->header('X-Aldu-Cached', 'no');
      $this->trigger('afterRouting');
      switch ($status = $this->response->status()) {
      case 401:
      case 404:
        $page = new View\Helper\HTML\Page();
        $page->theme();
        $this->response->message($status, LOG_NOTICE);
        $this->response->body($page->compose());
        break;
      }
    }
    $this->trigger('afterCache');
  }

  public function dispatch(HTTP\Response $response = null)
  {
    if ($response === null) {
      $response = $this->response;
    }
    $this->trigger('beforeDispatch');
    $response->session->close();
    if (!$response->sent) {
      $response->send();
    }
    $this->trigger('afterDispatch');
  }
}
