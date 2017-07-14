<?php

namespace App\Models\Db;

class City extends Model
{
    public static $model = 'city';

    public static function get($arr)
    {
        return app('db')->table(self::$model)
                        ->where($arr['where'])
                        ->first();
    }
}

?>