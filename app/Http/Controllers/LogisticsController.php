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
        $orderModel = new Order();
        $goodsModel = new Goods();
        $expressModel = new Express();
        $orderInfo = $orderModel->get($user->id, $orderId);
        if(empty($orderInfo)) {
            throw new ApiException(config('error.order_empty_err.msg'), config('error.order_empty_err.code'));
        }
        if($orderInfo->order_status != 3 && $orderInfo->order_status != 4) {
            throw new ApiException('该订单还没有物流', config('error.no_traces_exception.code'));
        }
        $goods = $goodsModel->getDetail($orderInfo->goods_id);
        $express = $expressModel->get($orderInfo->express_id);
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
        return $expressModel->getExpress($orderInfo, $express, $goods);
    }
}