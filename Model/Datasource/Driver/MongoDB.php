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
use Mongo, MongoId, MongoDBRef;

class MongoDB extends Datasource\Driver implements DriverInterface
{
  protected static $configuration = array(
    'autoincrement' => array(
      'collection' => '_autoincrement'
    )
  );

  private $mongoIds = array();

  public function __construct($url, $parts)
  {
    parent::__construct($url);
    if ($mongo = new Mongo($url)) {
      $db = ltrim($parts['path'], '/');
      $this->link = $mongo->$db;
    }
  }

  public function __destruct()
  {

  }

  public function save($models = array())
  {
    if (!is_array($models)) {
      $models = array(
        $models
      );
    }
    foreach ($models as &$model) {
      $class = get_class($model);
      $collection = $this->collection($model);
      $id = $this->mongoId($model);
      $doc = $model->__toArray();
      $doc['_id'] = $id;
      $extensions = array();
      foreach ($doc as $attribute => &$value) {
        if (MongoDBRef::isRef($value)) {
        }
        elseif (is_array($value)) {
          $this->normalizeArray($value);
        }
        elseif ($value instanceof Core\Model) {
          $this->normalizeReference($value);
        }
      }
      $this->link->$collection->update(array(
        '_id' => $id,
      ), $doc, array(
        'upsert' => true,
        'multiple' => true
      ));
    }
  }

  public function delete($model)
  {
    $collection = $this->collection($model);
    $id = $this->mongoId($model);
    $this->link->$collection->remove(array(
      '_id' => $id
    ));
  }

  public function purge($class, $search = array())
  {
    $collection = $this->collection($class);
    if (empty($search)) {
      $this->link->$collection->drop();
      $autoincrement = static::cfg('autoincrement.collection');
      $this->link->$autoincrement->remove(array(
        '_id' => $collection
      ));
    }
    else {
      $this->normalizeSearch($search);
      $this->link->$collection->remove($search);
    }
  }

  public function first($class, $search = array())
  {
    $collection = $this->collection($class);
    $this->normalizeSearch($search);
    if ($doc = $this->link->$collection->findOne($search)) {
      $this->normalizeArray($doc);
      $model = new $class($doc);
      $this->mongoId($model, $doc['_id']);
      return new $class($doc);
    }
    return $doc;
  }

  public function read($class, $search = array())
  {
    $models = array();
    $collection = $this->collection($class);
    $this->normalizeSearch($search);
    foreach ($this->link->$collection->find($search) as $doc) {
      if ($doc) {
        $this->normalizeArray($doc);
        $model = new $class($doc);
        $this->mongoId($model, $doc['_id']);
        $models[] = $model;
      }
    }
    return $models;
  }

  protected function normalizeSearch(&$array)
  {
    foreach ($array as $key => &$value) {
      if ($value instanceof Core\Model) {
        $this->normalizeReference($value);
      }
    }
  }

  protected function normalizeArray(&$array)
  {
    foreach ($array as $label => &$value) {
      if ($value instanceof Core\Model || MongoDBRef::isRef($value)) {
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

  protected function normalizeReference(&$value)
  {
    if ($value instanceof Core\Model) {
      $model = $value;
      $model->save();
      $value = MongoDBRef::create($this->collection($model), $this->mongoId($model));
    }
    elseif (MongoDBRef::isRef($value)) {
      $class = $this->getClass($value['$ref']);
      $value = $this->first($class, array(
        '_id' => $value['$id']
      ));
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
    if (!isset($this->mongoIds[$class])) {
      $this->mongoIds[$class] = array();
    }
    if (!$model->id) {
      $autoincrement = static::cfg('autoincrement.collection');
      $collection = $this->collection($class);
      if (!$this->link->$autoincrement->find(array(
        '_id' => $collection
      ))->count()) {
        $this->link->$autoincrement->insert(array(
          '_id' => $collection,
          'id' => 1
        ));
      }
      $result = $this->link->command(array(
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
      $this->mongoIds[$class][$model->id] = new MongoId();
    }
    if (!isset($this->mongoIds[$class][$model->id])) {
      $this->mongoIds[$class][$model->id] = $mongoId;
    }
    return $this->mongoIds[$class][$model->id];
  }
}
