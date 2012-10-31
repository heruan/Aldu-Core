<?php
/**
 * Aldu\Core\Router
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
 * @uses          Aldu\Core\Utility\ClassLoader
 * @uses          Aldu\Core\Utility\Inflector
 * @since         AlduPHP(tm) v1.0.0
 * @license       Creative Commons Attribution-ShareAlike 3.0 Unported (CC BY-SA 3.0)
 */

namespace Aldu\Core;
use Aldu\Core\Net\HTTP;
use Aldu\Core\Utility\ClassLoader;
use Aldu\Core\Utility\Inflector;

class Router extends Stub
{
  protected static $configuration = array(
    __CLASS__ => array(
      'prefixes' => array(),
      'routes' => array(
        'action' => array(
          'path' => '/%controller/%action/%arg:*',
          'arguments' => array()
        ),
        'read' => array(
          'path' => '/%controller/%arg/%action:?/%arg:*',
          'action' => 'read',
          'arguments' => array()
        ),
        'browse' => array(
          'path' => '/%controller/%arg:*',
          'action' => 'browse',
          'arguments' => array()
        )
      )
    )
  );

  public $host;
  public $base;
  public $prefix;
  public $basePrefix;
  public $fullBase;
  public $fullBasePrefix;
  public $path;
  public $prefixPath;
  public $basePath;
  public $fullPath;
  public $current;
  public $request;
  public $response;
  protected $contexts = array();

  public function __construct(HTTP\Request $request, HTTP\Response $response)
  {
    parent::__construct();
    $this->request = $request;
    $this->response = $response;
  }

  public function resolve($path = null)
  {
    if (is_null($path)) {
      $path = $this->request->path;
    }

    $steps = $prefixSteps = explode('/', $path);
    $trace = array();

    foreach (static::cfg('prefixes') as $route) {
      if (is_array($route)) {
        $route = new Router\Models\Route($route);
      }
      if ($result = $this->_resolve($route, $steps)) {
        $trace[] = $result;
      }
    }

    $this->host = $this->request->host;
    $this->base = $this->request->base;
    $prefix = implode('/', array_diff($prefixSteps, $steps));
    $this->prefix = $prefix ? $prefix . '/' : '';
    $this->basePrefix = $this->base . $this->prefix;
    $this->fullBase = $this->request->fullBase;
    $this->fullBasePrefix = $this->fullBase . $this->prefix;
    $this->path = implode('/', $steps);
    $this->prefixPath = $this->prefix . $this->path;
    $this->basePath = $this->basePrefix . $this->path;
    $this->fullPath = $this->fullBasePrefix . $this->path;
    
    /*var_dump(array(
      'host' => $this->host,
      'base' => $this->base,
      'prefix' => $this->prefix,
      'basePrefix' => $this->basePrefix,
      'fullBase' => $this->fullBase,
      'fullBasePrefix' => $this->fullBasePrefix,
      'path' => $this->path,
      'prefixPath' => $this->prefixPath,
      'basePath' => $this->basePath,
      'fullPath' => $this->fullPath
    ));*/

    $this->openContext($this->path);
    $routeSteps = $steps;
    $routes = array_merge(static::cfg('routes'), Router\Models\Route::read());
    foreach ($routes as $route) {
      if (is_array($route)) {
        $route = new Router\Models\Route($route);
      }
      $routeSteps = $steps;
      $result = $this->_resolve($route, $routeSteps);
      if (empty($routeSteps) && $result) {
        $this->current = $result;
        $trace[] = $result;
        break;
      }
    }
    $this->closeContext($this->path);

    if (count($routeSteps) && empty($trace)) {
      $this->response->status(404);
    }
    return $trace;
  }

  protected function _resolve($route, &$steps)
  {
    if (isset($route->host) && !preg_match('/' . $route->host . '/', $this->host)) {
      return null;
    }
    $path = implode('/', $steps);
    $controller = $this->_controller($route);
    $action = null;
    $arguments = array();
    do {
      if ($route->path == $path) {
        $steps = array();
        break;
      }
      foreach (explode('/', $route->path) as $position => $pattern) {
        list($pattern, $cardinality) = explode(':', $pattern) + array(
            null,
            1
          );
        list($pattern, $_defaults) = explode('=', $pattern) + array(
            null,
            ''
          );
        list($pattern, $regex) = explode('~', $pattern) + array(
            null,
            null
          );
        preg_match_all('/(?<=,|{)([^,]*)+(?=,|})/', $_defaults, $__defaults);
        $defaults = empty($_defaults) ? array() : array_shift($__defaults);
        switch ($cardinality) {
        case '?':
          $min = 0;
          $max = 1;
          break;
        case '*':
          $min = 0;
          $max = false;
          break;
        case '+':
          $min = 1;
          $max = false;
          break;
        default:
          $min = $cardinality;
          $max = $cardinality;
        }
        switch ($pattern) {
        case '%namespace':
          $count = 0;
          $_steps = $steps;
          while ($step = array_shift($_steps)) {
            $count++;
            $route->namespace = isset($route->namespace) ? $route->namespace . NS . Inflector::camelize($step)
              : Inflector::camelize($step);
            if ($controller = $this->_controller($route, $controller)) {
              $action = $this->_action($route, $controller);
              $steps = array_slice($steps, $count);
            }
          }
          break;
        case '%controller':
          $namespaces = isset($route->namespace) ? explode(',', $route->namespace) : array();
          $namespace = '';
          do {
            $class = '';
            $count = count($steps);
            $_steps = $steps;
            while ($step = array_pop($_steps)) {
              $_class = Inflector::classify($step);
              $_namespace = $namespace ? $namespace . NS : '';
              foreach ($_steps as $ns) {
                $__namespace = $_namespace . Inflector::camelize($ns) . NS;
                if (!ClassLoader::nsExists($__namespace)) {
                  $__namespace = $_namespace . strtoupper($ns) . NS;
                }
                $_namespace = $__namespace;
              }
              $classes = array(
                $_namespace . 'Controllers' . NS . $_class,
                $_namespace . 'Controllers' . NS . strtoupper($_class)
              );
              foreach ($classes as $class) {
                $Model = str_replace('Controllers', 'Models', $class);
                if (ClassLoader::classExists($class)) {
                  $controller = new $class($this->request, $this->response);
                  $steps = array_slice($steps, $count);
                  break 3;
                }
                elseif (ClassLoader::classExists($Model)) {
                  class_alias('Aldu\Core\Controller', $class);
                }
              }
              $count--;
            }
          } while ($namespace = array_shift($namespaces));
          $controller = $this->_controller($route, $controller);
          if (!$controller)
            continue 3;
          break;
        case '%action':
          if (!$controller = $this->_controller($route, $controller)) {
            continue 3;
          }
          $method = current($steps);
          if (method_exists($controller, $method)) {
            $action = $method;
            array_shift($steps);
          }
          if (!$min) {
            $action = $this->_action($route, $controller, $action);
          }
          if (!$action)
            continue 3;
          break;
        case '%arg':
          $count = 0;
          while (count($steps)) {
            if ($regex && !preg_match($regex, current($steps)))
              break;
            if ($max && $max == $count)
              break;
            $count++;
            $arguments[] = array_shift($steps);
          }
          if ((($max && $count < $max) || ($min && $count < $min)) && count($defaults) > $count) {
            $remaining = array_slice($defaults, $count);
            while ($arg = array_shift($remaining)) {
              if ($max && $max == $count)
                break;
              $count++;
              $arguments[] = $arg;
            }
          }
          if (($min && $count < $min) || ($max && $count > $max)) {
            return null;
          }
          break;
        default:
          if ($pattern === current($steps))
            array_shift($steps);
          else
            array_unshift($steps, $pattern);
        }
      }
    } while (false);
    $action = $this->_action($route, $controller, $action);
    if (!$controller || !$action) {
      return null;
    }
    return new Router\Models\Route(array(
      'host' => $this->host,
      'path' => $this->path,
      'namespace' => $route->namespace,
      'controller' => $controller,
      'action' => $action,
      'arguments' => is_array($route->arguments) ? $arguments + $route->arguments
        : $arguments + explode('/', $route->arguments)
    ));
  }

  protected function _controller($route, $controller = null)
  {
    if ($controller instanceof Controller)
      return $controller;
    if (!isset($route->controller))
      return null;

    $namespaces = isset($route->namespace) ? explode(',', $route->namespace) : array();
    $namespace = '';

    $_class = $route->controller;

    do {
      $class = $namespace ? $namespace . NS . $_class : $_class;
      if (ClassLoader::classExists($class)) {
        $controller = new $class($this->request, $this->response);
        break;
      }
    } while ($namespace = array_shift($namespaces));

    return $controller;
  }

  protected function _action($route, $controller = null, $action = null)
  {
    if (!$controller = $this->_controller($route, $controller))
      return null;
    if (!$action) {
      if (isset($route->action) && method_exists($controller, $route->action)) {
        $action = $route->action;
      }
    }
    return $action;
  }

  public function redirect($uri = '', $prefix = null)
  {
    $uri = $uri ? : $this->base;
    if ($prefix) {
      $uri = $prefix . $uri;
    }
    $this->response->status(302);
    $this->response->header('Location', $uri);
    return $uri;
  }

  public function back()
  {
    $this->redirect($this->request->referer);
  }

  public function reload()
  {
    $this->redirect($this->prefix . $this->path);
  }

  public function context()
  {
    return $this->contexts;
  }

  public function openContext($context)
  {
    return $this->contexts[] = $context;
  }

  public function closeContext($context = null)
  {
    if ($context && isset($this->contexts[$context])) {
      unset($this->contexts[$context]);
    }
    elseif (!$context) {
      array_pop($this->contexts);
    }
  }

  public function inContext($context)
  {
    return is_array($context) ? array_intersect($this->_contexts, $context) : in_array($context, $this->_contexts);
  }
}
