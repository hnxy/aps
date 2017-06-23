<?php

namespace App\Models;

class Goods
{
    private static $model = 'goods';

    public function get($id)
    {
        return app('db')->table(self::$model)
                        ->where(['id' => $id])
                        ->select(['title', 'price', 'unit', 'send_time'])
                        ->first();
    }
    /**
     * [mget description]
     * @param  integer $limit [每次获取的条目]
     * @param  integer $page  [分页参数]
     * @return [Object]           [返回一个包含商品条目对象]
     */
    public function mget($limit, $page)
    {
        $nowTime = time();
        $goods = app('db')->table(self::$model)
        ->select(
            ['id', 'title', 'description', 'origin_price', 'price', 'start_time', 'end_time', 'goods_img', 'classes_id', 'unit', 'send_time']
        )
        ->where([['end_time', '>=', $nowTime]])
        ->offset($page - 1)
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
    public function getDetail($id)
    {
        $nowtime = time();
        $goods_datail = [];
        $goods = app('db')->table(self::$model)
        ->where([
            ['id', '=', $id],
            // ['start_time', '<=', $nowtime],
            // ['end_time', '>=', $nowtime],
            ])
        ->select(['id', 'title', 'description', 'origin_price', 'price', 'start_time', 'end_time', 'detail', 'classes_id', 'unit', 'send_time'])
        ->first();
        return $goods;
    }
}
?>
