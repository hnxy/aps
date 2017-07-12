<?php
/**
 * view model
 */
namespace App\Models;

use App\Exceptions\ApiException;

class Model implements ModelInterface
{
    public static $model;

    public static $primaryKey;

    public function __get($name)
    {
        if (isset($this->$name)) {
            return $this->$name;
        }
        $param = ['where' => [$this->getPrimaryKey() => $this->primaryValue]];
        $model = call_user_func([$this->getCallClass(), 'get'], $param);
        if (is_null($model)) {
            return null;
        }
        if (! isset($model->$name)) {
            throw new ApiException($name . '属性不存在', -1);
        }
        $this->append($model);
        return $this->$name;
    }

    public function __toString()
    {
        return json_encode($this);
    }

    private function append($object, $name = null)
    {
        if ($name) {
            $this->$name = $object;
        } else {
            foreach ($object as $key => $value) {
                $this->$key = $value;
            }
        }
    }

    public function getPrimaryKey()
    {
        return static::$primaryKey ? static::$primaryKey : 'id';
    }

    private function getCallClass()
    {
        return __NAMESPACE__ . '\Db\\' . $this->getClassName();
    }

    private function getClassName()
    {
        return static::$model;
    }
}
