<?php

namespace App\Models;

class GoodsCar
{
    private static $model = 'goods_car';

    public static function updateState($goodsCarIDs, $state = 0)
    {
        foreach ($goodsCarIDs as $goodsCarID) {
            app('db')->table(self::$model)
                     ->where([
                        ['id', '=', $goodsCarID]
                    ])
                     ->update(['state' => $state]);
        }
    }
    public static function updateGoodsNum($goodsCarId, $goodsNum)
    {
        return app('db')->table(self::$model)
                        ->where('id', $goodsCarId)
                        ->update(['goods_num' => $goodsNum]);
    }
    public static function mget($goodsCarIDs)
    {
        $goodsCars = [];
        foreach($goodsCarIDs as $goodsCarID) {
            $tmp = app('db')->table(self::$model)
                                    ->where([
                                        ['id', '=', $goodsCarID],
                                        ['state', '<>', 1],
                                    ])
                                    ->first();
            if(empty($tmp)) {
                return [];
            }
            $goodsCars[] = $tmp;
        }
        return $goodsCars;
    }
    public static function add($msg)
    {
        return app('db')->table(self::$model)
                        ->insertGetId($msg);
    }
    public static function getItems($limit, $page)
    {
        return app('db')->table(self::$model)
                        ->limit($limit)
                        ->offset($page-1)
                        ->orderBy('created_at', 'desc')
                        ->get();
    }
    public function remove($goodsCarId)
    {
        return app('db')->table(self::$model)
                        ->where('goods_car_id', $goodsCarId)
                        ->delete();
    }
}
?>