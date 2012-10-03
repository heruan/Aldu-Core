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
use Aldu\Core\Model\Datasource;
use Mongo;
use MongoId;
use MongoDBRef;

class MongoDB extends Datasource\Driver
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

  public function save($models = array())
  {
    if (!is_array($models)) {
      $models = array(
        $models
      );
    }
    foreach ($models as &$model) {
      $collection = get_class($model);
      $id = $this->mongoId($model);
      foreach ($model as $attribute => &$value) {
        if (is_array($value)) {
          $this->normalizeArray($value);
        }
        elseif ($value instanceof Core\Model) {
          $this->normalizeReference($value);
        }
      }
      $doc = get_object_vars($model);
      $this->link->$collection->update(array(
        '_id' => $id
      ), $doc, array(
        'upsert' => true
      ));
    }
  }

  public function purge($class, $search = array())
  {
    if (empty($search)) {
      $collection = $this->collection($class);
      $this->link->$collection->drop();
      $autoincrement = static::cfg('autoincrement.collection');
      $this->link->$autoincrement->remove(array(
        '_id' => $collection
      ));
    }
  }

  public function first($class, $search = array())
  {
    $collection = $this->collection($class);
    if ($doc = $this->link->$collection->findOne($search)) {
      $this->normalizeDocument($doc);
      return new $class($doc);
    }
    return $doc;
  }

  public function read($class, $search = array())
  {
    $models = array();
    $collection = $this->collection($class);
    foreach ($this->link->$collection->find($search) as $doc) {
      if ($doc) {
        $this->normalizeDocument($doc);
        $models[] = new $class($doc);
      }
    }
    return $models;
  }

  protected function normalizeDocument(&$doc)
  {
    foreach ($doc as $key => &$value) {
      if (is_array($value)) {
        $this->normalizeArray($value);
      }
      elseif ($value instanceof Core\Model || MongoDBRef::isRef($value)) {
        $this->normalizeReference($value);
      }
    }
  }

  protected function normalizeArray(&$array, $split = false)
  {
    $new = array();
    foreach ($array as $label => &$value) {
      if (is_array($value)) {
        $this->normalizeArray($value, $split);
      }
      elseif ($value instanceof Core\Model || MongoDBRef::isRef($value)) {
        $this->normalizeReference($value);
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
        '_id' => new MongoId($value['$id'])
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

  protected function mongoId($model)
  {
    $class = get_class($model);
    if (!isset($this->mongoId[$class])) {
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
    }
    return $this->mongoIds[$class][$model->id];
  }
}
