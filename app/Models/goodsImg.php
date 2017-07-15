<?php

namespace App\Models;

use App\Models\Db\GoodsImg as DbGoodsImg;

class GoodsImg extends Model
{
    public static $model = 'GoodsImg';
    /**
     * [获取商品轮播的集合]
     * @param  [Integer] $id [商品id]
     * @return [Object]     [包含商品的轮播图的集合对象]
     */
    public function mget($goodsId)
    {
        return DbGoodsImg::mget(['where' => ['goods_id' => $goodsId] ]);
    }
}