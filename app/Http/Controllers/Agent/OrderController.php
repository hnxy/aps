<?php

namespace App\Http\Controllers\Agent;

use Illuminate\Http\Request;
use App\Models\Goods;
use App\Models\Agent;
use App\Models\Order;
use App\Models\Wx;
use App\Models\GoodsCar;
use App\Execptions\ApiException;
use App\Http\Controllers\Controller;

class OrderController extends Controller
{
    /**
     * [商家获取订单]
     * @param  Request $request [description]
     * @return [type]           [description]
     */
    public function index(Request $request)
    {
        $rules = [
            'search' => 'required|integer',
            'start_time' => 'date',
            'end_time' => 'date',
            'limit' => 'integer|max:100',
            'page' => 'integer',
        ];
        $this->validate($request, $rules);
        $searchId = $request->input('search');
        $rsp = config('response.success');
        $orderModel = new Order();
        if($request->has('start_time') && $request->has('end_time')) {
            $rsp['code'] = 0;
            $limit = $request->input('limit', 10);
            $page = $request->input('page', 1);
            $orders = $orderModel->getByTime($searchId, strtotime($request->input('start_time')), strtotime($request->input('end_time')), $limit, $page);
            var_dump($orders);
            exit;
            $rsp['items'] = $orderModel->getOrdersInfo($orders);
            $rsp['num'] = count($rsp['items']);
        } else {
            $rsp['code'] = 1;
            $rsp['msg'] = '参数错误';
        }
        return $rsp;
    }
    /**
     * [获取某个订单]
     * @param  Request $request [description]
     * @param  [type]  $agent   [description]
     * @param  [type]  $orderId [description]
     * @return [type]           [description]
     */
    public function show(Request $request, $agent, $orderId)
    {
        $rules = [
            'search' => 'required|integer',
        ];
        $this->validate($request, $rules);
        $searchId = $request->input('search');
        $orderModel = new Order();
        $order = $orderModel->getByAgentId($searchId, $orderId);
        if (!$orderModel->isExist($order)) {
            return config('response.order_not_exist');
        }
        return $orderModel->getOrderInfo($order, $order->coupon_id);
    }
}