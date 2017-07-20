<?php

namespace App\Models\Db;

class Goods extends Model
{
    public static $model = 'goods';
    /**
     * [获取商品的条目]
     * @param  integer $limit [每次获取的条目]
     * @param  integer $page  [分页参数]
     * @return [Object]           [返回一个包含商品条目对象]
     */
    public static function mget($limit, $page)
    {
        $nowTime = time();
        return app('db')->table(self::$model)
                          ->select(
                            ['id', 'title', 'description', 'origin_price', 'price', 'start_time', 'end_time', 'goods_img', 'classes_id', 'unit', 'send_time']
                        )
                          ->where([['end_time', '>=', $nowTime]])
                          ->limit($limit)
                          ->offset(($page - 1) * $limit)
                          ->orderBy('id', 'desc')
                          ->get();
    }

    /**
     * [通过商品ID获取商品的详细信息]
     * @param  [Integer] $id [商品的ID]
     * @return [Object]           [返回一个包含商品详细信息对象]
     */
    public static function get($arr)
    {
        return app('db')->table(self::$model)
                        ->where($arr['where'])
                        ->first();
    }
    public static function add($goodsArr)
    {
        return app('db')->table(self::$model)
                        ->insert($goodsArr);
    }
    public static function modifyStock($goods, $type = 'increment') {
        foreach ($goods as $id => $num) {
            app('db')->table(self::$model)
                     ->where([
                        ['id', '=', $id],
                    ])
                     ->$type('stock', $num);
        }
    }
    public static function mgetByIds($goodsIds)
    {
        return app('db')->table(self::$model)
                        ->whereIn('id', $goodsIds)
                        ->get();
    }
}
?>
