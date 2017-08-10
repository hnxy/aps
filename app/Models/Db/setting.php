<?php

namespace App\Models\Db;

class Setting extends Model
{
    public static $model = 'setting';
    public static function add($arr)
    {
        return app('db')->table(self::$model)
                        ->insert($arr);
    }
    public static function get($arr)
    {
        return app('db')->table(self::$model)
                        ->where(isset($arr['where']) ? $arr['where'] : [])
                        ->first();
    }
}