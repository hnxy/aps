<?php

namespace App\Http\Controllers\Admin;

use Illuminate\Http\Request;
use App\Models\Goods;
use App\Models\Agent;
use App\Models\Order;
use App\Models\GoodsCar;
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
            $rsp['items'] = $orderModel->getOrdersInfo($orders);
            $rsp['num'] = count($rsp['items']);
        } else {
            $rsp['code'] = 1;
            $rsp['msg'] = '参数错误';
        }
        return $rsp;
    }
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
    public  function addLogistics(Request $request)
    {
        $rules = [
            'order_ids' => 'required|string',
            'express_id' => 'required|integer',
            'logistics_code' => 'required|string|max:32',
        ];
        $this->validate($request, $rules);
        $rsp = config('response.success');
        $orderIds = explode(',', $request->input('order_ids'));
        array_pop($orderIds);
        $upDatas = $request->only(['logistics_code', 'express_id']);
        $orderModel = new Order();
        //更新状态为3
        $upDatas['order_status'] = 3;
        try{
            app('db')->beginTransaction();
            $orderModel->addLogistics($orderIds, $upDatas);
            app('db')->commit();
        } catch(Exceptions $e) {
            app('db')->rollback();
        }
        return $rsp;
    }
    /**
     * [获取分类订单]
     * @param  Request $request [description]
     * @param  [type]  $agent   [description]
     * @return [type]           [description]
     */
    public function getClassesOrder(Request $request, $agent)
    {
        $rules = [
            'limit' => 'integer|max:100|min:10',
            'page' => 'integer|min:1',
            'status' => 'required|integer'
        ];
        $this->validate($request, $rules);
        $rsp = config('response.items');
        $status = $request->input('status');
        $limit = $request->input('limit', 10);
        $page = $request->input('page', 1);
        $orderModel = new Order();
        $orders = $orderModel->mget($agent->id, $limit, $page, $status);
        if(empty(obj2arr($orders))) {
            $rsp['code'] = 0;
            $rsp['items'] = [];
            $rsp['num'] = 0;
        } else {
            $rsp['code'] = 0;
            $rsp['items'] = $orderModel->getOrdersInfo($orders);
            $rsp['num'] = count($rsp['items']);
        }
        return $rsp;
    }
}