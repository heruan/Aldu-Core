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

  protected function initialize()
  {
    Exception::initialize();
  }

  public function __construct(HTTP\Request $request, HTTP\Response $response = null)
  {
    parent::__construct();
    $this->initialize();
    $this->request = $request;
    $this->request->initialize();
    $this->response = $response ? : new HTTP\Response($this->request);
    $this->response->initialize();
    if (static::cfg('aro.required') && !$this->request->aro) {
      $this->request->path = static::cfg('aro.login.path');
    }
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
    elseif (!$this->request->aro && $this->request->is('get')
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
        $callback = array(
          $result->controller,
          $result->action
        );
        if (!is_callable($callback) || !call_user_func_array($callback, $result->arguments)) {
          $this->response->status(404);
          break;
        }
      }
      if ($this->request->is('get') && !$this->request->aro) {
        $this->cache->store($this->request->id, $this->response);
      }
      $this->response->header('X-Aldu-Cached', 'no');
      $this->trigger('afterRouting');
      $locale = Locale::instance();
      switch ($status = $this->response->status()) {
      case 404:
        $page = new View\Helper\HTML\Page();
        $page->theme();
        $this->response->message($locale->t("Page not found."), LOG_WARNING);
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
