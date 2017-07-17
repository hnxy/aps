<?php

namespace App\Models\Db;

class GoodsCar extends Model
{
    public static $model = 'goods_car';

    public static function mModify($arr)
    {
        return  app('db')->table(self::$model)
                         ->where(isset($arr['where']) ? $arr['where'] : [])
                         ->whereIn($arr['whereIn']['key'], $arr['whereIn']['values'])
                         ->update($arr['update']);
    }

    public static function modify($arr)
    {
        return app('db')->table(self::$model)
                        ->where($arr['where'])
                        ->update($arr['update']);
    }

    public static function mgetByGoodsCarIds($userId, $goodsCarIDs, $status)
    {
        return  app('db')->table(self::$model)
                         ->where([
                            ['user_id', '=', $userId],
                            ['status', '=', $status],
                            ['is_del', '=', 0],
                         ])
                         ->whereIn('id', $goodsCarIDs)
                         ->get();
    }
    public static function add($msg)
    {
        return app('db')->table(self::$model)
                        ->insertGetId($msg);
    }
    public static function mget($arr)
    {
        $limit = isset($arr['limit']) ? $arr['limit'] : 10;
        $page = isset($arr['page']) ? $arr['page'] : 1;
        return app('db')->table(self::$model)
                        ->limit($limit)
                        ->offset(($page - 1) * $limit)
                        ->where(isset($arr['where']) ? $arr['where'] : [])
                        ->orderBy('created_at', 'desc')
                        ->get();
    }
    public static function remove($userId, $goodsCarId)
    {
        return app('db')->table(self::$model)
                        ->where([
                            ['user_id', '=', $userId],
                            ['id', '=', $goodsCarId]
                        ])
                        ->update(['is_del' => 1]);
    }
    public static function get($arr)
    {
        return app('db')->table(self::$model)
                        ->where($arr['where'])
                        ->first();
    }
    public static function getAllNum($userId)
    {
        return app('db')->table(self::$model)
                        ->where([
                            ['user_id', '=', $userId],
                            ['status', '=', 0],
                            ['is_del', '=', 0],
                        ])
                        ->count();
    }
}