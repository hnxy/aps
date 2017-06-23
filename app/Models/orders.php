<?php

namespace App\Models;

class Orders
{
    private static $model = 'orders';
    public static function create($orderMsg)
    {
        return app('db')->table(self::$model)
                        ->insertGetId($orderMsg);
    }
    public static function get($id)
    {
        return app('db')->table(self::$model)
                        ->where([
                            ['id', '=', $id],
                        ])
                        ->first();
    }
    public function getPrice($goodsCars, $coupon, $goods, $value, $key = 'id') {
        $goodsInfos = [];
        $all_price = 0;
        foreach ($goodsCars as $goodsCar) {
            $goodsInfo = $goods->get($goodsCar->goods_id);
            $all_price += $goodsInfo->price*$goodsCar->goods_num;
            $tmp['id'] = $goodsCar->id;
            $tmp['title'] = $goodsInfo->title;
            $tmp['price'] = $goodsInfo->price;
            $tmp['unit'] = $goodsInfo->unit;
            $tmp['num'] = $goodsCar->goods_num;
            $goodsInfos[] = $tmp;
        }
        if(!is_null($value)) {
            $result = $coupon->checkWork($value, $key);
            if(!empty($result)) {
                $couponValue = $result->price;
                $couponCode = $result->code;
            } else {
                $couponValue = 0;
                $couponCode = null;
            }
        } else {
            $couponValue = 0;
            $couponCode = null;
        }
        $all_price -= $couponValue;
        $send_time = $this->getSendTime($goodsCars, $goods);
        return [
            'coupon_code' => $couponCode,
            'coupon_value' => $couponValue,
            'all_price' => $all_price,
            'send_price' => 0,
            'goods_info' => $goodsInfos,
            'send_time' => $send_time,
        ];
    }
    public function getSendTime($goodsCars, $goods) {
        $send_time = 99999999999;
        foreach ($goodsCars as $goodsCar) {
            $goodsInfo = $goods->get($goodsCar->goods_id);
            $send_time = min($send_time, $goodsInfo->send_time);
        }
        return formatM($send_time);
    }
    public function remove($id)
    {
        return app('db')->table(self::$model)
                        ->where('id', $id)
                        ->delete();
    }
}
?>
