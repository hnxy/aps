<?php

namespace App\Models;

class OrderGoods
{
    private static $model = 'order_goods';
    /**
     * [创建订单购物车的联系]
     * @param  [Array] $goodsCarIDs [购物车ID集合]
     * @param  [Intrger] $orderID     [订单ID]
     * @return [Integer]              [返回影响的行数]
     */
    public static function create($goodsCarIDs, $orderID)
    {
        $data = [];
        foreach ($goodsCarIDs as $goodsCarID) {
            $temp['goods_car_id'] = $goodsCarID;
            $temp['order_id'] = $orderID;
            $data[] = $temp;
        }
        return app('db')->table(self::$model)
                        ->insert($data);
    }
    /**
     * [通过订单ID获取购物车ID]
     * @param  [Integer] $orderId [订单ID]
     * @return [Array]          [购物车ID集合]
     */
    public function getByOrderId($orderId)
    {
        return app('db')->table(self::$model)
                        ->select('goods_car_id')
                        ->where('order_id', $orderId)
                        ->get();
    }
}

?>