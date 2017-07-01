<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Exceptions\ApiException;
use App\Models\Logistics;
use App\Models\GoodsCar;
use App\Models\Order;
use App\Models\Goods;
use App\Models\Express;

class LogisticsController extends Controller
{

    public function getOrderTraces(Request $request)
    {
        $rules = [
            'goods_car_id' => 'required|integer',
            'order_id' => 'required|integer',
        ];
        $this->validate($request, $rules);
        $goodsCarId = $request->input('goods_car_id');
        $orderId = $request->input('order_id');
        $user = $request->user;
        $goodsCar = GoodsCar::get($user->id, $goodsCarId);
        if(empty($goodsCar)) {
            throw new ApiException(config('error.goods_car_empty_err.msg'), config('error.goods_car_empty_err.code'));
        }
        $orderInfo = Order::get($user->id, $orderId);
        if(empty($orderInfo)) {
            throw new ApiException(config('error.order_empty_err.msg'), config('error.order_empty_err.code'));
        }
        if(!in_array($goodsCarId, explode(',', $orderInfo->goods_car_ids))) {
            throw new ApiException(config('error.order_goods_car_diff_err.msg'), config('error.order_goods_car_diff_err.code'));
        }
        $goods = Goods::getNoDiff($goodsCar->goods_id);
        $express = Express::get($goodsCar->express_id);

        $expressCode = $express->code;
        $logisticsCode = $goodsCar->logistics_num;
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