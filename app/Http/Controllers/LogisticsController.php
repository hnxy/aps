<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Exceptions\ApiException;
use App\Models\Logistics;
use App\Models\Order;
use App\Models\Goods;
use App\Models\Express;

class LogisticsController extends Controller
{

    public function getOrderTraces(Request $request, $user)
    {
        $orderId = $request->route()[2]['id'];
        $orderInfo = Order::get($user->id, $orderId);
        if(empty($orderInfo)) {
            throw new ApiException(config('error.order_empty_err.msg'), config('error.order_empty_err.code'));
        }
        if($orderInfo->order_status != 3 && $orderInfo->order_status != 4) {
            throw new ApiException('该订单还没有物流', config('error.no_traces_exception.code'));
        }
        $goods = Goods::getDetail($orderInfo->goods_id);
        $express = Express::get($orderInfo->express_id);
        $expressCode = $express->code;
        $logisticsCode = $orderInfo->logistics_code;
        $requestData = <<<Data
        {
            "OrderCode":"",
            "ShipperCode":"{$expressCode}",
            "LogisticCode":"{$logisticsCode}"
        }
Data;
        $datas = array(
            'EBusinessID' => config('wx.EBusinessID'),
            'RequestType' => '1002',
            'RequestData' => urlencode($requestData) ,
            'DataType' => '2',
        );
        $datas['DataSign'] = myEncrypt($requestData, config('wx.AppKey'));
        $expressInfo = json_decode(sendPost(config('wx.ReqURL'), $datas), true);
        if($expressInfo['Success'] !== true || array_key_exists('Reason', $expressInfo)) {
            throw new ApiException($expressInfo['Reason'], config('error.logistics_request_err.code'));
        }
        $expressInfo['phone'] = config("wx.express_offic_phone.{$express->code}");
        return [
            'express_info' => $expressInfo,
            'order_info' => [
                'id' => $orderInfo->id,
                'order_num' => $orderInfo->order_num,
                'created_at' => date('Y年-n月-d日 H:i:s', $orderInfo->created_at),
                'img' => $goods->goods_img,
            ],
        ];
    }
}