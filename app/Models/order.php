<?php

namespace App\Models;

use App\Models\Goods;
use App\Models\Coupon;
use App\Models\Express;
use App\Exceptions\ApiException;

class Order
{
    private static $model = 'orders';
    /**
     * [创建新订单]
     * @param  [Array] $orderMsg [订单的信息]
     * @return [Integer]           [返回订单的ID]
     */
    public static function create($orderMsg)
    {
        return app('db')->table(self::$model)
                        ->insertGetId($orderMsg);
    }
    /**
     * [根据订单ID获取订单]
     * @param  [integer] $id [订单ID]
     * @return [Object]     [包含订单信息的对象]
     */
    public static function get($userId, $id)
    {
        return app('db')->table(self::$model)
                        ->where([
                            ['id', '=', $id],
                            ['user_id', '=', $userId],
                        ])
                        ->first();
    }
    /**
     * [获取价格有关的信息]
     * @param  [Object] $goodsCars [购物车对象的集合]
     * @param  [String] $value     [$key字段的值]
     * @param  string $key       [字段名称]
     * @return [Array]            [价格有关的信息]
     */
    public function getPrice($goodsCars, $value, $key = 'id') {
        $goodsInfos = [];
        $all_price = 0;
        $goods_ids = [];
        foreach ($goodsCars as $goodsCar) {
            $goods_ids[] = $goodsCar->goods_id;
            $goodsInfo = Goods::get($goodsCar->goods_id);
            if(empty($goodsInfo)) {
                throw new ApiException(config('error.add_goods_exception.msg'), config('error.add_goods_exception.code'));
            }
            $all_price += $goodsInfo->price*$goodsCar->goods_num;
            $tmp['goods_car_id'] = $goodsCar->id;
            $tmp['description'] = $goodsInfo->description;
            $tmp['title'] = $goodsInfo->title;
            $tmp['price'] = $goodsInfo->price;
            $tmp['unit'] = $goodsInfo->unit;
            $tmp['img'] = $goodsInfo->goods_img;
            $tmp['num'] = $goodsCar->goods_num;
            $tmp['logistics_num'] = $goodsCar->logistics_num;
            $express = null;
            if(!is_null($goodsCar->express_id)) {
                $express = Express::get($goodsCar->express_id);
                $express->phone = config("wx.express_offic_phone.{$express->code}");
            }
            $tmp['express_info'] = $express;
            $goodsInfos[] = $tmp;
        }
        if(!is_null($value)) {
            $result = Coupon::checkWork($value, $key);
            if(!empty($result) && in_array($result->goods_id, $goods_ids)) {
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
        $send_time = $this->getSendTime($goodsCars);
        return [
            'coupon_code' => $couponCode,
            'coupon_value' => $couponValue,
            'all_price' => $all_price,
            'send_price' => 0,
            'goods_info' => $goodsInfos,
            'send_time' => $send_time,
        ];
    }
    /**
     * [获取最早的发货时间]
     * @param  [Object] $goodsCars [购物车信息集合]
     * @return [Date]            [格式化后的时间]
     */
    public function getSendTime($goodsCars) {
        $send_time = 99999999999;
        foreach ($goodsCars as $goodsCar) {
            $goodsInfo = Goods::get($goodsCar->goods_id);
            if(empty($goodsInfo)) {
                throw new ApiException(config('error.add_goods_exception.msg'), config('error.add_goods_exception.code'));
            }
            $send_time = min($send_time, $goodsInfo->send_time);
        }
        return formatM($send_time);
    }
    /**
     * [删除订单]
     * @param  [integer] $id [订单ID]
     * @return [integer]     [返回影响的行数]
     */
    public static function remove($userId, $id)
    {
        return app('db')->table(self::$model)
                        ->where([
                            ['id', '=', $id],
                            ['user_id', '=', $userId],
                        ])
                        ->delete();
    }
    /**
     * [获取分类订单]
     * @param  integer $state [状态码]
     * @return [Object]         [包含该类型的对象]
     */
    public static function mget($userId, $limit, $page, $state)
    {
        if($state === '-1') {
        return app('db')->table(self::$model)
                        ->limit($limit)
                        ->offset($page-1)
                        ->where('user_id', $userId)
                        ->get();
        }
        return app('db')->table(self::$model)
                        ->limit($limit)
                        ->offset(($page-1)*$limit)
                        ->where([
                            ['user_id', '=', $userId],
                            ['order_status', '=', $state],
                        ])
                        ->get();
    }
    public static function modify($upArr)
    {
        return app('db')->table(self::$model)
                        ->where([
                            ['id', '=', $upArr['id']],
                            ['user_id', '=', $upArr['user_id']],
                        ])
                        ->update($upArr);
    }
}
?>
