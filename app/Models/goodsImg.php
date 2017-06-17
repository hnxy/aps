<?php

namespace app\Models;

use DB;

class GoodsImg
{
    public static $model = 'goods_img';
    /**
     * [获取商品轮播的集合]
     * @param  [Integer] $id [商品id]
     * @return [Object]     [包含商品的轮播图的集合对象]
     */
    public function mget($id)
    {
        $goods_imgs = app('db')->table(self::$model)
        ->where(['goods_id' => $id])
        ->select(['goods_img'])
        ->get();
        return $goods_imgs;
    }
}