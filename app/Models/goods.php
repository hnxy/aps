<?php
namespace App\Models;

class Goods
{
    private static $model = 'goods';

    /**
     * [mget description]
     * @param  integer $limit [每次获取的条目]
     * @param  integer $page  [分页参数]
     * @return [Object]           [返回一个包含商品条目对象]
     */
    public function mget($limit, $page)
    {
        $goods = app('db')->table(self::$model)
        ->select(
            ['title','description','origin_price','price','start_time','end_time','goods_img','classes_id']
        )
        ->offset($page - 1)
        ->limit($limit)
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
        $goods_datail = [];
        $goods = app('db')->table(self::$model)
        ->where(['id' => $id])
        ->select(['detail'])
        ->first();
        return $goods;
    }
}
?>