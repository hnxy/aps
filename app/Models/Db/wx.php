<?php

namespace App\Models\Db;


class Wx extends Model
{
    public static $model = 'wx';

    public static function add($arr)
    {
        return app('db')->table(self::$model)
                        ->insert($arr);
    }
    public static function get($arr)
    {
        return app('db')->table(self::$model)
                        ->where($arr['where'])
                        ->first();
    }
    public static function modify($arr)
    {
        return app('db')->table(self::$model)
                        ->where($arr['where'])
                        ->update($arr['update']);
    }
}