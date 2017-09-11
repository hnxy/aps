<?php

namespace App\Http\Controllers\Admin;

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
            'created_at_start' => 'required|date',
            'created_at_end' => 'required|date',
            'status' => 'integer|max:5|min:1',
            'limit' => 'integer|max:10',
            'page' => 'integer',
        ];
        $this->validate($request, $rules);
        $rsp = config('error.success');
        $orderModel = new Order();
        $status = $request->input('status', 2);
        $start = strtotime($request->input('created_at_start'));
        $end = strtotime($request->input('created_at_end'));
        $rsp['code'] = 0;
        $limit = $request->input('limit', 10);
        $page = $request->input('page', 1);
        $orders = $orderModel->mgetByTimeWithStatus($start, $end, $status, $limit, $page);
        $rsp['items'] = $orderModel->getOrdersInfoByAdmin($orders);
        $rsp['num'] = count($rsp['items']);
        $totel = $orderModel->getAllBetweenTimeWithStatus($start, $end, $status);
        $rsp['totel'] = $totel;
        $rsp['pages'] =  intval($totel/$limit) + ($totel % $limit == 0 ? 0 : 1);
        return $rsp;
    }
    /**
     * [获取某个订单]
     * @param  Request $request [description]
     * @param  [type]  $agent   [description]
     * @param  [type]  $orderId [description]
     * @return [type]           [description]
     */
    public function show(Request $request, $admin, $orderNum)
    {
        $orderModel = new Order();
        $order = $orderModel->getByOrderNum($orderNum);
        if (!$orderModel->isExist($order)) {
            return config('error.order_not_exist');
        }
        return $orderModel->getOrderInfoByAdmin($order);
    }
    /**
     * [添加物流号]
     * @param Request $request [description]
     */
    public function update(Request $request)
    {
        $rules = [
            'order_ids' => 'required|string',
            'express_id' => 'integer',
            'logistics_code' => 'string|max:32',
        ];
        $this->validate($request, $rules);
        $rsp = config('error.success');
        $orderModel = new Order();
        $orderIds = explode(',', $request->input('order_ids'));
        array_pop($orderIds);
        $orders = $orderModel->getByIds($orderIds);
        if (count(obj2arr($orders)) != count($orderIds)) {
            throw new ApiException(config('error.contain_order_not_work_exception.msg'), config('error.contain_order_not_work_exception.code'));
        }
        $orderArr = $request->all();
        array_filter($orderArr);
        $orderArr['status'] = 3;
        $orderModel->mModify($orderIds, $orderArr);
        return $rsp;
    }
}