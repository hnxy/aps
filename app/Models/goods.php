<?php

namespace App\Models;

class Goods
{
    private static $model = 'goods';
    /**
     * [获取商品信息]
     * @param  [Integer] $id [商品ID]
     * @return [Object]     [商品信息对象]
     */
    public static function get($id)
    {
        $time = time();
        return app('db')->table(self::$model)
                        ->where([
                            ['id', '=', $id],
                            ['start_time', '<', $time],
                            ['end_time', '>', $time]
                        ])
                        ->select(['id', 'title', 'description', 'origin_price', 'price', 'unit', 'send_time', 'goods_img', 'classes_id'])
                        ->first();
    }
    public static function getNoDiff($id)
    {
        return app('db')->table(self::$model)
                        ->where('id', $id)
                        ->first();
    }
    /**
     * [获取商品的条目]
     * @param  integer $limit [每次获取的条目]
     * @param  integer $page  [分页参数]
     * @return [Object]           [返回一个包含商品条目对象]
     */
    public static function mget($limit, $page)
    {
        $nowTime = time();
        $goods = app('db')->table(self::$model)
        ->select(
            ['id', 'title', 'description', 'origin_price', 'price', 'start_time', 'end_time', 'goods_img', 'classes_id', 'unit', 'send_time']
        )
        ->where([['end_time', '>=', $nowTime]])
        ->offset(($page - 1)*$limit)
        ->limit($limit)
        ->orderBy('id','desc')
        ->get();
        return $goods;
    }

    /**
     * [通过商品ID获取商品的详细信息]
     * @param  [Integer] $id [商品的ID]
     * @return [Object]           [返回一个包含商品详细信息对象]
     */
    public static function getDetail($id)
    {
        $goods = app('db')->table(self::$model)
        ->where([
                ['id', '=', $id],
            ])
        ->select(['id', 'title', 'description', 'origin_price', 'price', 'start_time', 'end_time', 'detail', 'classes_id', 'goods_img', 'unit', 'send_time'])
        ->first();
        return $goods;
    }
    public static function add($goodsArr)
    {
        return app('db')->table(self::$model)
                        ->insert($goodsArr);
    }
}
?>
