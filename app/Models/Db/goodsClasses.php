<?php

namespace App\Models\Db;

class GoodsClasses extends Model
{
    public static $model = 'goods_classes';

    public static function mget($arr)
    {
        return app('db')->table(self::$model)
                        ->where(isset($arr['where']) ? $arr['where'] : [])
                        ->select(['name', 'id'])
                        ->get();
    }
    public static function get($arr)
    {
        return app('db')->table(self::$model)
                        ->where($arr['where'])
                        ->select(['name', 'id'])
                        ->first();
    }
}
?>