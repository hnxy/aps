<?php

namespace App\Models;

use App\Models\Db\Express as DbExpress;

class Express extends Model
{
    public static $model = 'Express';

    public function get($id)
    {
        return DbExpress::get(['where' => ['id' => $id] ]);
    }
    public function getExpress($order, $express, $goods)
    {
        $expressInfo['phone'] = config("wx.express_offic_phone.{$express->code}");
        return [
            'express_info' => $expressInfo,
            'order_info' => [
                'id' => $order->id,
                'order_num' => $order->order_num,
                'created_at' => formatTime($order->created_at),
                'img' => $goods->goods_img,
            ],
        ];
    }
}