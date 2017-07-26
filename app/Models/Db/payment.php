<?php

namespace App\Models\Db;

class Payment extends Model
{
    public static $model = 'payment';

    public static function get($arr)
    {
        return app('db')->table(self::$model)
                        ->where($arr['where'])
                        ->first();
    }
}