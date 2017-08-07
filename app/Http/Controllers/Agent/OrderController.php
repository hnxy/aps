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
        $rsp = config('error.success');
        $orderModel = new Order();
        if($request->has('start_time') && $request->has('end_time')) {
            $rsp['code'] = 0;
            $limit = $request->input('limit', 10);
            $page = $request->input('page', 1);
            $orders = $orderModel->getByTime($searchId, strtotime($request->input('start_time')), strtotime($request->input('end_time')), $limit, $page);
            $rsp['items'] = $orderModel->getOrdersInfoByAgent($orders);
            $rsp['num'] = count($rsp['items']);
            $totel = $orderModel->getAll($searchId);
            $rsp['totel'] = $totel;
            $rsp['pages'] =  intval($totel/$limit) + ($totel % $limit == 0 ? 0 : 1);
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
    public function show(Request $request, $agent, $orderNum)
    {
        $rules = [
            'search' => 'required|integer',
        ];
        $this->validate($request, $rules);
        $searchId = $request->input('search');
        $orderModel = new Order();
        $order = $orderModel->getByAgentId($searchId, $orderNum);
        if (!$orderModel->isExist($order)) {
            return config('error.order_not_exist');
        }
        return $orderModel->getOrderInfoByAgent($order, $order->coupon_id);
    }
    public function trade(Request $request)
    {
        $rules = [
            'search' => 'required|integer',
            'start_time' => 'required|date',
            'end_time' => 'required|date',
        ];
        $this->validate($request, $rules);
        $searchId = $request->input('search');
        $startTime = strtotime($request->input('start_time'));
        $endTime = strtotime($request->input('end_time'));
        $orderModel = new Order();
        $orders = $orderModel->getTrade($searchId, $startTime, $endTime);
        $day = intval(($endTime - $startTime) / (3600 * 24)) + 1;
        for($i = 0; $i < $day; $i++) {
            $key = date('m-d', $startTime + $i * 3600 * 24);
            $trades[$key] = 0;
        }
        foreach ($orders as $order) {
            $trades[$order->day] = $order->totel;
        }
        return $trades;
    }
}