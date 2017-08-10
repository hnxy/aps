<?php

namespace App\Models\Db;

class Area extends Model
{
    public static $model = 'area';
    public static function get($arr)
    {
        return app('db')->table(self::$model)
                        ->where($arr['where'])
                        ->first();
    }
}