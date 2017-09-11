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
    public function mget()
    {
        return DbExpress::mget();
    }
    public function getExpress($order, $express, $goods, $expressInfo)
    {
        $expressInfo['phone'] = config("wx.express_offic_phone.{$express->code}");
        return [
            'order_info' => $this->formatOrder($order),
            'goods_info' => [
                'goods_id' => $goods->id,
                'goods_img' => $goods->goods_img,
            ],
            'express_info' => $this->formatExpress($expressInfo, $express),
            'data_provide' => [
                'express_img' => "{$express->img}",
                'text' => "本数据由{$express->name}提供",
            ],
            'traces' => $this->getTraces($expressInfo),
        ];
    }
    protected function getTraces($expressInfo)
    {
        return $expressInfo['Traces'];
    }
    protected function formatExpress($expressInfo, $express)
    {
        return [
                ['name' => '承运公司:', 'value' => "{$express->code}"],
                ['name' => '运单编号:', 'value' => "{$expressInfo['LogisticCode']}"],
                ['name' => '官方电话:', 'value' => config("wx.express_offic_phone.{$express->code}")],
            ];
    }
    protected function formatOrder($order)
    {
        return  [
                'order_id' => $order->id,
                'order_num' => $order->order_num,
                'created_at' => formatTime($order->created_at),
            ];
    }
}