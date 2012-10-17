<?php
/**
 * Aldu\Core\Model\Datasource\Driver\MongoDB
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
 * @package       Aldu\Core\Model\Datasource\Driver
 * @uses          Aldu\Core\Model\Datasource
 * @since         AlduPHP(tm) v1.0.0
 * @license       Creative Commons Attribution-ShareAlike 3.0 Unported (CC BY-SA 3.0)
 */

namespace Aldu\Core\Model\Datasource\Driver;
use Aldu\Core\Model\Datasource\DriverInterface;
use Aldu\Core\Model\Datasource;
use Aldu\Core;
use DateTime;
use Mongo, MongoDBRef, MongoId, MongoDate, MongoRegex, MongoException;

class MongoDB extends Datasource\Driver implements DriverInterface
{
  protected static $configuration = array(__CLASS__ => array(
    'autoincrement' => array(
      'collection' => '_autoincrement'
    )
  ));
  protected $conn;
  private $mongoIds = array();

  public function __construct($url, $parts)
  {
    parent::__construct($url);
    if ($mongo = new Mongo($url)) {
      $db = ltrim($parts['path'], '/');
      $this->conn = $mongo;
      $this->link = $mongo->$db;
    }
  }

  public function __destruct()
  {
    $this->conn->close();
  }

  public function save($models = array())
  {
    if (!is_array($models)) {
      $models = array(
        $models
      );
    }
    foreach ($models as &$model) {
      if (!$model->id) {
        $model->created = new DateTime();
      }
      else {
        $model->updated = new DateTime();
      }
      $class = get_class($model);
      $collection = $this->collection($model);
      $ids = $this->mongoId($model);
      $doc = $model->__toArray();
      $this->denormalizeArray($doc);
      foreach ($ids as $id) {
        $doc['_id'] = $id;
        $this->link->$collection
          ->update(array(
              '_id' => $id,
            ), $doc, array(
              'upsert' => true
            ));
      }
      unset($doc['_id']);
      foreach ($class::cfg('extensions') as $extName => $ext) {
        if (isset($ext['attributes'])) {
          $extDoc = array_intersect_key($doc, $ext['attributes']);
          $doc = array_diff_key($doc, $extDoc);
          $this->link->$collection
            ->update(
              array(
                'id' => $doc['id']
              ), array(
                '$set' => $doc
              ), array(
                'multiple' => true
              ));
        }
      }
    }
  }

  public function delete($model)
  {
    $collection = $this->collection($model);
    $this->link->$collection->remove(array(
        'id' => $model->id
      ));
  }

  public function purge($class, $search = array())
  {
    $collection = $this->collection($class);
    if (empty($search)) {
      $this->link->$collection->drop();
      $autoincrement = static::cfg('autoincrement.collection');
      $this->link->$autoincrement
        ->remove(array(
          '_id' => $collection
        ));
    }
    else {
      $this->denormalizeSearch($search);
      $this->link->$collection->remove($search);
    }
  }

  public function first($class, $search = array(), $options = array())
  {
    $options['limit'] = 1;
    $read = $this->read($class, $search, $options);
    return array_shift($read);
  }

  public function read($class, $search = array(), $options = array())
  {
    $models = array();
    $docs = $this->cursor($class, $search, $options);
    foreach ($docs as $doc) {
      $this->normalizeArray($doc);
      $this->normalizeAttributes($class, $doc);
      $model = new $class($doc);
      $this->mongoId($model, $doc['_id']);
      $models[] = $model;
    }
    return $models;

  }

  public function count($class, $search = array(), $options = array())
  {
    return $this->cursor($class, $search, $options)->count();
  }

  protected function cursor($class, $search = array(), $options = array())
  {
    $collection = $this->collection($class);
    $this->denormalizeSearch($search);
    $cursor = $this->link->$collection->find($search);
    foreach ($options as $key => $option) {
      switch ($key) {
      case 'skip':
      case 'limit':
      case 'sort':
        if ($option) {
          $cursor = $cursor->$key($option);
        }
        break;
      }
    }
    return $cursor;
  }

  protected function denormalizeSearch(&$array)
  {
    foreach ($array as $key => &$value) {
      if ($value instanceof Core\Model) {
        $this->denormalizeReference($value);
      }
      else {
        try {
          $value = new MongoRegex($value);
        } catch (MongoException $me) {
        }
      }
    }
  }

  protected function normalizeArray(&$array)
  {
    foreach ($array as $label => &$value) {
      if ($value instanceof MongoDate) {
        $value = '@' . $value->sec;
      }
      elseif (MongoDBRef::isRef($value)) {
        $this->normalizeReference($value);
        if (is_null($value)) {
          unset($array[$label]);
        }
      }
      elseif (is_array($value)) {
        $this->normalizeArray($value);
      }
    }
  }

  protected function denormalizeArray(&$array)
  {
    foreach ($array as $label => &$value) {
      if ($value instanceof Core\Model) {
        $this->denormalizeReference($value);
      }
      elseif ($value instanceof DateTime) {
        $value = new MongoDate($value->format('U'));
      }
      elseif (is_array($value)) {
        $this->denormalizeArray($value);
      }
    }
  }

  protected function normalizeReference(&$value)
  {
    if (MongoDBRef::isRef($value)) {
      $class = $this->getClass($value['$ref']);
      $value = $this
        ->first($class, array(
          '_id' => $value['$id']
        ));
    }
  }

  protected function denormalizeReference(&$value)
  {
    if ($value instanceof Core\Model) {
      $model = $value;
      if (!$model->id) {
        $model->save();
      }
      $ids = $this->mongoId($model);
      $value = MongoDBRef::create($this->collection($model), array_shift($ids));
    }
  }

  protected function collection($class)
  {
    return is_object($class) ? get_class($class) : $class;
  }

  protected function getClass($collection)
  {
    return $collection;
  }

  protected function mongoId(&$model, $mongoId = null)
  {
    $class = get_class($model);
    if (!$model->id) {
      $autoincrement = static::cfg('autoincrement.collection');
      $collection = $this->collection($class);
      if (!$this->link->$autoincrement
        ->find(array(
          '_id' => $collection
        ))->count()) {
        $this->link->$autoincrement
          ->insert(
            array(
              '_id' => $collection, 'id' => 1
            ));
      }
      $result = $this->link
        ->command(
          array(
            'findandmodify' => $autoincrement,
            'query' => array(
              '_id' => $collection
            ),
            'update' => array(
              '$inc' => array(
                'id' => 1
              )
            )
          ));
      $model->id = $result['value']['id'];
    }
    if (!isset($this->mongoIds[$class])) {
      $this->mongoIds[$class] = array();
    }
    if (!isset($this->mongoIds[$class][$model->id])) {
      $this->mongoIds[$class][$model->id] = array();
    }
    $mongoIds = array();
    foreach ($class::cfg('extensions') as $extName => $ext) {
      $_key = $ext['key'];
      if ($key = $model->$_key) {
        if ($key instanceof Core\Model) {
          $key = $key->id;
        }
        if (!isset($this->mongoIds[$class][$model->id][$extName])) {
          $this->mongoIds[$class][$model->id][$extName] = array();
        }
        if (!isset($this->mongoIds[$class][$model->id][$extName][$key])) {
          $this->mongoIds[$class][$model->id][$extName][$key] = new MongoId(
            $mongoId);
        }
        $mongoIds[] = $this->mongoIds[$class][$model->id][$extName][$key];
      }
    }
    if (empty($mongoIds)) {
      if (!isset($this->mongoIds[$class][$model->id]['default'])) {
        $this->mongoIds[$class][$model->id]['default'] = array(
          $model->id => new MongoId($mongoId)
        );
      }
      $mongoIds[] = $this->mongoIds[$class][$model->id]['default'][$model->id];
    }
    return $mongoIds;
  }
}
