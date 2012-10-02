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
      $models = array($models);
    }
    foreach ($models as $model) {
      $collection = get_class($model);
      $id = $this->id($model);
      foreach ($model as $attribute => &$value) {
        if (is_array($value)) {
          $this->normalizeArray($value);
        }
        elseif ($value instanceof Core\Model) {
          $this->normalizeReference($value);
        }
      }
      $this->link->$collection->update(array('_id' => $id), $model, array('upsert' => true));
    }
  }

  protected function normalizeArray(&$array)
  {
    foreach ($array as $key => &$value) {
      if (is_array($value)) {
        $this->normalizeArray($value);
      }
      elseif ($value instanceof Core\Model) {
        $this->normalizeReference($value);
      }
    }
  }
  
  protected function normalizeReference(&$value)
  {
    $model = $value;
    $model->save();
    $value = MongoDBRef::create($this->collection($model), $this->id($model));
  }
  
  protected function collection($model)
  {
    return get_class($model);
  }
  
  protected function MongoId($model)
  {
    return get_class($model) . ':' . $model->id;
  }
  
  protected function id($mongoId)
  {
    return explode(':', $mongoId);
  }
}
