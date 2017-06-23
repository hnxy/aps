<?php

namespace App\Models;

class GoodsCar
{
    private static $model = 'goods_car';

    public static function update($goodsCarIDs, $state = 0)
    {
        // var_dump($goodsCarIDs);
        // exit;
        foreach ($goodsCarIDs as $goodsCarID) {
            app('db')->table(self::$model)
                     ->where([
                        ['id', '=', $goodsCarID]
                    ])
                     ->update(['state' => $state]);
        }
        // return true;
    }

    public static function mget($goodsCarIDs)
    {
        $goodsCars = [];
        foreach($goodsCarIDs as $goodsCarID) {
            $goodsCars[] = app('db')->table(self::$model)
                                    ->where([
                                        ['id', '=', $goodsCarID],
                                    ])
                                    ->first();
        }
        return $goodsCars;
    }
}
?>