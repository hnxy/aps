<?php

namespace App\Models;

class Coupon
{
    private static $model = 'coupon';
    public function get($goodsId, $agentId)
    {
        return app('db')->table(self::$model)
                        ->where([
                            ['goods_id', '=', $goodsId],
                            ['agent_id', '=', $agentId],
                            ['state', '=', 0],
                        ])
                        ->first();
    }
    public function validateCode($couponCode)
    {
        return app('db')->table(self::$model)
                        ->where([
                            ['coupon_code', '=', $couponCode],
                            ['state', '=', 0],
                        ])
                        ->first();
    }
    public function validateId($couponId)
    {
        return app('db')->table(self::$model)
                        ->where([
                            ['id', '=', $couponId],
                            ['state', '=', 0],
                        ])
                        ->first();
    }
}

?>