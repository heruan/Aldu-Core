<?php
/**
 * Aldu\Core\Net\HTTP\Request
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
 * @package       Aldu\Core\Net\HTTP
 * @uses          Aldu\Core\Net
 * @since         AlduPHP(tm) v1.0.0
 * @license       Creative Commons Attribution-ShareAlike 3.0 Unported (CC BY-SA 3.0)
 */

namespace Aldu\Core\Net\HTTP;
use Aldu\Core\Net;
use Aldu\Core;

class Request extends Net\HTTP
{
  /**
   * The default request method
   */
  const METHOD = 'GET';

  /**
   * The default request protocol
   */
  const PROTOCOL = 'HTTP/1.1';

  /**
   * The Server API
   */
  public $sapi;

  /**
   * The request method
   *
   * @var string
   */
  public $method;

  /**
   * The requested resource
   *
   * @var string
   */
  public $resource;

  /**
   * The request protocol
   *
   * @var string
   */
  public $protocol;

  /**
   * The protocol version
   *
   * @var string
   */
  public $version;

  /**
   * The request headers
   *
   * @var array
   */
  public $headers;

  /**
   * The request body
   *
   * @var string
   */
  public $body;

  public $scheme;
  public $host;
  public $port;
  public $user;
  public $pass;
  public $base;
  public $fullBase;
  public $path;
  public $query;
  public $data;
  public $time;
  public $referer;
  public $agent;
  public $ip;
  public $id;
  public $upload;
  public $session;
  public $cookie;
  public $cipher;

  /**
   * The Access Request Object (ARO)
   *
   * @var Aldu\Core\Model
   */
  public $aro;

  /**
   * The built in detectors used with `is()` can be modified with `addDetector()`.
   *
   * @var array
   */
  protected $_detectors = array(
    'cli' => array(
      'sapi' => 'PHP_SAPI'
    ),
    'escaped' => array(
      'env' => 'QUERY_STRING', 'pattern' => '/^_escaped/'
    ), 'get' => array(
      'env' => 'REQUEST_METHOD', 'value' => 'GET'
    ),
    'post' => array(
      'env' => 'REQUEST_METHOD', 'value' => 'POST'
    ), 'put' => array(
      'env' => 'REQUEST_METHOD', 'value' => 'PUT'
    ),
    'delete' => array(
      'env' => 'REQUEST_METHOD', 'value' => 'DELETE'
    ),
    'head' => array(
      'env' => 'REQUEST_METHOD', 'value' => 'HEAD'
    ),
    'options' => array(
      'env' => 'REQUEST_METHOD', 'value' => 'OPTIONS'
    ), 'ssl' => array(
      'env' => 'HTTPS', 'value' => 1
    ),
    'ajax' => array(
      'env' => 'HTTP_X_REQUESTED_WITH', 'value' => 'XMLHttpRequest'
    ),
    'chromeframe' => array(
      'env' => 'HTTP_USER_AGENT', 'pattern' => '/chromeframe/'
    ),
    'ie' => array(
      'env' => 'HTTP_USER_AGENT', 'pattern' => '/MSIE/'
    ),
    'ie6' => array(
      'env' => 'HTTP_USER_AGENT', 'pattern' => '/MSIE 6/'
    ),
    'ie<8' => array(
      'env' => 'HTTP_USER_AGENT', 'pattern' => '/MSIE [1-7]/'
    ),
    'ie<9' => array(
      'env' => 'HTTP_USER_AGENT', 'pattern' => '/MSIE [1-8]/'
    ),
    'flash' => array(
      'env' => 'HTTP_USER_AGENT', 'pattern' => '/^(Shockwave|Adobe) Flash/'
    ),
    'mobile' => array(
      'env' => 'HTTP_USER_AGENT',
      'options' => array(
        'Android', 'AvantGo', 'BlackBerry', 'DoCoMo', 'Fennec', 'iPod',
        'iPhone', 'iPad', 'J2ME', 'MIDP', 'NetFront', 'Nokia', 'Opera Mini',
        'PalmOS', 'PalmSource', 'portalmmm', 'Plucker', 'ReqwirelessWeb',
        'SonyEricsson', 'Symbian', 'UP\\.Browser', 'webOS', 'Windows CE',
        'Xiino'
      )
    )
  );

  public static function fetch($sapi)
  {
    switch ($sapi) {
    case 'cli':
      $resource = '/';
      $method = self::METHOD;
      $protocol = self::PROTOCOL;
      $network = array(
        'scheme' => 'cli',
        'server' => array(
          'ip' => null, 'port' => null, 'name' => null
        ), 'client' => array(
          'ip' => null, 'port' => null
        )
      );
      $headers = array();
      $get = array();
      $post = array();
      $files = array();
      $input = 'php://stdin';
      $body = null;//file_get_contents($input);
      break;
    default:
      $resource = static::env('REQUEST_URI') ? : '/';
      $method = static::env('REQUEST_METHOD') ? : self::METHOD;
      $protocol = static::env('SERVER_PROTOCOL') ? : self::PROTOCOL;
      $network = array(
        'scheme' => static::env('HTTPS') ? 'https' : 'http',
        'server' => array(
          'ip' => static::env('SERVER_ADDR'),
          'port' => static::env('SERVER_PORT'),
          'name' => static::env('SERVER_NAME')
        ),
        'client' => array(
          'ip' => static::env('REMOTE_ADDR'),
          'port' => static::env('REMOTE_PORT')
        )
      );
      if (function_exists('apache_request_headers')) {
        $headers = apache_request_headers();
      }
      else {
        $headers = array(
          'Host' => static::env('HTTP_HOST'),
          'User-Agent' => static::env('HTTP_USER_AGENT'),
          'Accept' => static::env('HTTP_ACCEPT'),
          'DNT' => static::env('HTTP_DNT'),
          'Accept-Encoding' => static::env('HTTP_ACCEPT_ENCODING'),
          'Accept-Language' => static::env('HTTP_ACCEPT_LANGUAGE'),
          'Cache-Control' => static::env('HTTP_CACHE_CONTROL'),
          'Cookie' => static::env('HTTP_COOKIE'),
          'Connection' => static::env('HTTP_CONNECTION')
        );
      }
      $get = static::env('GET');
      $post = static::env('POST');
      $files = static::env('FILES');
      $body = file_get_contents('php://input');
    }
    return new Request($resource, $method, $protocol, $network, $headers,
      $body, $get, $post, $files);
  }

  public function __construct($resource = '/', $method = 'GET',
    $protocol = 'HTTP/1.1', $network = array(), $headers = array(),
    $body = null, $get = array(), $post = array(), $files = array())
  {
    parent::__construct();
    $this->sapi = PHP_SAPI;
    $this->upload = new Upload();
    $this->session = new Session();
    $this->cookie = new Cookie();
    $this->cipher = new Core\Utility\Cipher();
    $this->resource = strtolower($resource);
    $this->method = strtoupper($method);
    list($this->protocol, $this->version) = explode('/', strtoupper($protocol));
    $this->scheme = $network['scheme'];
    $this->server = $network['server'];
    $this->port = $this->server['port'];
    $this->client = $network['client'];
    $this->ip = $this->client['ip'];
    $this->headers = $headers;
    $this->referer = $this->_referer();
    $this->query = $get;
    $this->data = $this->_data($post, $files);
    $this->body = $body;
    $this->host = $this->header('Host');
    $this->base = $this->_base($this->resource);
    $this->fullBase = $this->_base($this->resource, true);
    $this->path = $this->_path($this->resource, $this->base);
    $this->agent = $this->header('User-Agent');
    $this->time = microtime(true);//static::env('REQUEST_TIME') ? : time();
    $this->id = $this->_id();
  }

  public function time($round = 3)
  {
    $time = microtime(true) - $this->time;
    return round($time, $round);
  }

  protected function _data($data = array(), $files = array())
  {
    if ($files) {
      $data = $this->upload->merge($files, $data);
    }
    return $data;
  }

  protected function _base($resource, $full = false)
  {
    if ($full) {
      $base = $this->scheme . '://';
      $base .= $this->host;
      $base .= ($this->port != 80 && $this->port != 443) ? ':' . $this->port
        : '';
      $base .= $this->_base($resource);
    }
    else {
      $index = basename(static::env('SCRIPT_NAME'));
      $base = substr(static::env('SCRIPT_NAME'), 0, -strlen($index));
    }
    return $base;
  }

  protected function _path($uri, $base)
  {
    if (!$path = static::env('PATH_INFO')) {
      if (strlen($this->base) > 0 && strpos($uri, $this->base) === 0) {
        $uri = substr($uri, strlen($this->base));
      }
      if (strpos($uri, '?') !== false) {
        $uri = parse_url($uri, PHP_URL_PATH);
      }
      if ($fragment = $this->query('_escaped_fragment_')) {
        $uri = urldecode($fragment);
      }
      $path = $uri;
    }
    return trim($path, '/');
  }

  protected function _url()
  {
    $base = $this->_base(true);
    $path = $this->path;
    $query = $this->query ? '?' . http_build_query($this->query) : '';
    return $base . $path . $query;
  }

  protected function _referer($local = true)
  {
    $referer = static::env('HTTP_REFERER');
    if ($forwarded = static::env('HTTP_X_FORWARDED_HOST')) {
      $referer = $forwarder;
    }
    $base = $this->_base(true);
    if ($referer && $base) {
      if ($local && strpos($referer, $base) === 0) {
        $referer = substr($referer, strlen($base));
        if ($referer[0] != '/') {
          $referer = '/' . $referer;
        }
      }
    }
    return $referer;
  }

  /**
   * Get the IP the client is using, or says they are using.
   *
   * @param boolean $safe Use safe = false when you think the user might manipulate their HTTP_CLIENT_IP
   *   header.  Setting $safe = false will will also look at HTTP_X_FORWARDED_FOR
   * @return string The client IP.
   */

  protected function _ip($safe = true)
  {
    if (!$safe && static::env('HTTP_X_FORWARDED_FOR') != null) {
      $ipaddr = preg_replace('/(?:,.*)/', '',
        static::env('HTTP_X_FORWARDED_FOR'));
    }
    else {
      if (static::env('HTTP_CLIENT_IP') != null) {
        $ipaddr = static::env('HTTP_CLIENT_IP');
      }
      else {
        $ipaddr = static::env('REMOTE_ADDR');
      }
    }

    if (static::env('HTTP_CLIENTADDRESS') != null) {
      $tmpipaddr = static::env('HTTP_CLIENTADDRESS');

      if (!empty($tmpipaddr)) {
        $ipaddr = preg_replace('/(?:,.*)/', '', $tmpipaddr);
      }
    }
    return trim($ipaddr);
  }

  protected function _id()
  {
    $id = array(
      $this->method, $this->agent, $this->host, $this->path
    );
    return md5(implode('::', $id));
  }

  public static function updateAro($class, $id, $password = null, $encrypted = true)
  {
    $self = self::instance();
    if ($aro = $class::authenticate($id, $password, $encrypted)) {
      $self->aro = $aro;
    }
  }

  public function initialize()
  {
    $this->id = $this->_id();
    if ($this->user && $this->pass) {
      /*
      $K = Helpers\Cipher::instance();
      if ($aroController = Core\Main::cfg('aro.controller')) {
        $aro = new $aroController();
        $aro->m->updateAro($this->user, $K->encrypt($this->pass));
      }
       */
    }
    switch ($this->method) {
    case 'HEAD':
      break;
    case 'GET':
      break;
    case 'POST':
      break;
    case 'PUT':
      break;
    case 'DELETE':
      break;
    case 'TRACE':
      break;
    case 'OPTIONS':
      break;
    case 'CONNECT':
      break;
    case 'PATCH':
      break;
    }
  }

  public function is($type)
  {
    $type = strtolower($type);
    if (!isset($this->_detectors[$type])) {
      return false;
    }
    $detect = $this->_detectors[$type];
    if (isset($detect['env'])) {
      if (isset($detect['value'])) {
        return $this->env($detect['env']) == $detect['value'];
      }
      if (isset($detect['pattern'])) {
        return (bool) preg_match($detect['pattern'], $this->env($detect['env']));
      }
      if (isset($detect['options'])) {
        $pattern = '/' . implode('|', $detect['options']) . '/i';
        return (bool) preg_match($pattern, $this->env($detect['env']));
      }
    }
    if (isset($detect['sapi'])) {
      return (bool) preg_match("/$type/", PHP_SAPI);
    }
    return false;
  }

  public function query($key = null)
  {
    return $key ? (isset($this->query[$key]) ? $this->query[$key] : array())
      : $this->query;
  }

  public function data($key = null)
  {
    return $key ? (isset($this->data[$key]) ? $this->data[$key] : array())
      : $this->data;
  }

  public function header($header = null)
  {
    if ($header) {
      if (isset($this->headers[$header])) {
        return $this->headers[$header];
      }
    }
    return null;
  }
}
