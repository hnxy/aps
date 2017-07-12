<?php

namespace App\Models\Db;

class User extends Model
{
    public static $model = 'user';

    public static function get($arr)
    {
        return app('db')->table(self::$model)->where($arr['where'])->first();
    }

    public static function mget($arr = [])
    {
        return app('db')->table(self::$model)->where(isset($arr['where']) ? $arr['where'] : [])->get();
    }
}
