<?php

namespace App\Models;

class OrderGoods
{
    private static $model = 'order_goods';

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
    public function getByOrderId($orderId)
    {
        return app('db')->table(self::$model)
                        ->select('goods_car_id')
                        ->where('order_id', $orderId)
                        ->get();
    }
}

?>