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
    public function index(Request $request)
    {
        $rules = [
            'search' => 'required|integer',
            'order_id' => 'integer',
            'start_time' => 'date',
            'end_time' => 'date',
            'limit' => 'integer|max:100',
            'page' => 'integer',
        ];
        $this->validate($request, $rules);
        $searchId = $request->input('search');
        $rsp = config('wx.msg');
        $orderModel = new Order();
        if($request->has('order_id')) {
            $order = $orderModel->getByAgentId($searchId, $request->order_id);
            if(empty($order)) {
                $rsp['state'] = 1;
                $rsp['msg'] = '该订单不存在';
                return $rsp;
            }
            $couponID = $order->coupon_id;
            $order = formatOrder($order);
            return [
                'order_info' => $order,
                'price_info' => $orderModel->getOrderInfo($order, $couponID),
            ];
        } else if($request->has('start_time') && $request->has('end_time')) {
            $rsp['state'] = 0;
            $limit = $request->input('limit', 10);
            $page = $request->input('page', 1);
            $orders = $orderModel->getByTime($searchId, strtotime($request->input('start_time')), strtotime($request->input('end_time')), $limit, $page);
            $rsp['items'] = $orderModel->getOrdersInfo($orders);
            $rsp['num'] = count($rsp['items']);
        } else {
            $rsp['state'] = 1;
            $rsp['msg'] = '参数错误';
        }
        return $rsp;
    }
    public  function addLogistics(Request $request)
    {
        $rules = [
            'order_ids' => 'required|string',
            'express_id' => 'required|integer',
            'logistics_code' => 'required|string|max:32',
        ];
        $this->validate($request, $rules);
        $rsp = config('wx.msg');
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
    public function getClassesOrder(Request $request, $agent)
    {
        $rules = [
            'limit' => 'integer|max:100|min:10',
            'page' => 'integer|min:1',
            'status' => 'required|integer'
        ];
        $this->validate($request, $rules);
        $rsp = config('wx.addr');
        $status = $request->input('status');
        $limit = $request->input('limit', 10);
        $page = $request->input('page', 1);
        $orderModel = new Order();
        $orders = $orderModel->mget($agent->id, $limit, $page, $status);
        if(empty(obj2arr($orders))) {
            $rsp['state'] = 1;
            $rsp['msg'] = '您还没有此类型的订单哦';
        } else {
            $rsp['state'] = 0;
            $rsp['items'] = $orderModel->getOrdersInfo($orders);
            $rsp['num'] = count($rsp['items']);
        }
        return $rsp;
    }
}