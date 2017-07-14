<?php

namespace App\Models\Db;

class GoodsImg extends Model
{
    public static $model = 'goods_img';
    /**
     * [获取商品轮播的集合]
     * @param  [Integer] $id [商品id]
     * @return [Object]     [包含商品的轮播图的集合对象]
     */
    public static function mget($arr)
    {
        return app('db')->table(self::$model)
                        ->where(isset($arr['where']) ? $arr['where'] : [])
                        ->select(['goods_img'])
                        ->get();
    }
}