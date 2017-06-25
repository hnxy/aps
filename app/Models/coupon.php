<?php

namespace App\Models;

class Coupon
{
    private static $model = 'coupon';
    /**
     * [get description]
     * @param  [type] $goodsId [description]
     * @param  [type] $agentId [description]
     * @return [type]          [description]
     */
    public static function get($goodsId, $agentId)
    {
        return app('db')->table(self::$model)
                        ->where([
                            ['goods_id', '=', $goodsId],
                            ['agent_id', '=', $agentId],
                            ['state', '=', 0],
                        ])
                        ->first();
    }
    /**
     * [验证优惠码是否有效]
     * @param  [String] $couponCode [优惠码]
     * @return [Object]             [包含该优惠码信息的对象]
     */
    public static function validateCode($couponCode)
    {
        return app('db')->table(self::$model)
                        ->where([
                            ['code', '=', $couponCode],
                            ['state', '=', 0],
                        ])
                        ->first();
    }
    /**
     * [验证优惠券ID是否有效]
     * @param  [integer] $couponId [优惠券ID]
     * @return [Object]             [包含该优惠券ID信息的对象]
     */
    public static function validateId($couponId)
    {
        return app('db')->table(self::$model)
                        ->where([
                            ['id', '=', $couponId],
                            ['state', '=', 0],
                        ])
                        ->first();
    }
    /**
     * [检查字段是否有效]
     * @param  [String] $value [字段值]
     * @param  [String] $key   [字段名]
     * @return [Object]        [包含该优惠券ID信息的对象]
     */
    public static function checkWork($value, $key) {
        if($key == 'id') {
            $result = static::validateId($value);
        } else{
            $result = static::validateCode($value);
        }
        return $result;
    }
}

?>