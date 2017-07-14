<?php

namespace App\Models\Db;


class Express extends Model
{
    public static $model = 'express';

    public static function get($arr)
    {
        return app('db')->table(self::$model)
                        ->where($arr['where'])
                        ->first();
    }
}