<?php

namespace App\Models\Db;

class Admin extends Model
{
    public static $model = 'admin';
    public static function get($arr)
    {
        return app('db')->table(self::$model)
                        ->where($arr['where'])
                        ->first();
    }

    public static function add($AdminArr)
    {
        return app('db')->table(self::$model)
                        ->insert($AdminArr);
    }
    public static function update($arr)
    {
        return app('db')->table(self::$model)
                        ->where($arr['where'])
                        ->update($arr['update']);
    }
    public static function mget()
    {
        return app('db')->table(self::$model)
                        ->select('id', 'username')
                        ->get();
    }
}

?>