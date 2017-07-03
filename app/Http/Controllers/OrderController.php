<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Order;
use App\Models\GoodsCar;
use App\Models\Goods;
use App\Models\Address;
use App\Models\Area;
use App\Models\Province;
use App\Models\City;
use App\Models\Coupon;
use App\Exceptions\ApiException;
use Illuminate\Support\Facades\DB;
use App\Models\User;

class OrderController extends Controller
{
    /**
     * [展示临时订单]
     * @param  Request $request [Request实例]
     * @return [Array]           [返回包含临时订单的信息]
     */
    public function showPreOrder(Request $request)
    {
        $rules = [
            'addr_id' => 'integer',
            'goods_car_ids.*' => 'required|integer',
        ];
        $this->validate($request, $rules);
        $goodsCarIDs = $request->input('goods_car_ids');
        $addrID = $request->input('addr_id', null);
        $user = $request->user;
        $order = new Order();
        $address = new Address();
        // 获取购物车的信息,该返回的数据为对象数组
        $goodsCars = GoodsCar::mget($user->id, $goodsCarIDs);
        if(count(obj2arr($goodsCars)) != count($goodsCarIDs)) {
            throw new ApiException(config('error.goods_exception.msg'), config('error.goods_exception.code'));
        }
        return [
            'rcv_info' => $address->getFullAddr($user->id, $addrID),
            'price_info' => $order->getPrice($goodsCars, null, 'code'),
        ];
    }
    /**
     * [展示某一个订单]
     * @param  Request $request [Request实例]
     * @return [Array]           [返回订单有关的信息]
     */
    public function show(Request $request)
    {
        $orderID = $request->route()[2]['id'];
        $user = $request->user;
        $order = new Order();
        $address = new Address();
        $orderMsg = Order::get($user->id, $orderID);
        $rsp = config('wx.msg');
        if(empty($orderMsg)) {
            $rsp['state'] = 1;
            $rsp['msg'] = '该订单不存在';
            return $rsp;
        }
        $addrID = $orderMsg->addr_id;
        $couponID = $orderMsg->coupon_id;
        // 根据订单号获取相关联的购物车ID
        $goodsCarIDs = explode(',', $orderMsg->goods_car_ids);
        // 根据购物车ID获取购物车信息
        $goodsCars = GoodsCar::mget($user->id, $goodsCarIDs, 1);
        // if(empty($goodsCars)) {
        //     throw new ApiException(config('error.goods_exception.msg'), config('error.goods_exception.code'));
        // }
        $orderMsg->created_at = date("Y年n月d日 H:i:s", $orderMsg->created_at);
        if(!is_null($orderMsg->pay_time))
            $orderMsg->pay_time = date("Y年n月d日 H:i:s", $orderMsg->pay_time);
        $orderMsg->pay_status = config('wx.pay_status')[$orderMsg->pay_status];
        $orderMsg->pay_by = config('wx.pay_by')[$orderMsg->pay_by];
        $orderMsg->order_status = config('wx.order_status')[$orderMsg->order_status];
        return [
            'order_info' => $orderMsg,
            'rcv_info' => $address->getFullAddr($user->id, $addrID),
            'price_info' => $order->getPrice($goodsCars, $couponID),
        ];
    }
    /**
     * [创建订单]
     * @param  Request $request [Request实例]
     * @return [type]           [description]
     */
    public function store(Request $request)
    {
        $rules = [
            'addr_id' => 'integer',
            'goods_car_ids.*' => 'required|integer',
        ];
        $this->validate($request, $rules);
        // 前台传入购物车的信息
        $user = $request->user;
        $goodsCarIDs = $request->input('goods_car_ids');
        $addrID = $request->input('addr_id', null);
        $couponID = $request->input('coupon_id', null);
        $rsp = config('wx.msg');
        $order = new Order();
        $address = new Address();
        // 获取地址的id
        $addrId = Address::getAddrId($user->id, $addrID);
        // 没有填写收货地址或者收货地址的ID不对
        if(is_null($addrId)) {
            return config('error.addr_null_err');
        }
        try {
            app('db')->beginTransaction();
             // 获取购物车的信息,该返回的数据为对象数组
            $goodsCars = obj2arr(GoodsCar::mget($user->id, $goodsCarIDs));
            if(count($goodsCars) != count($goodsCarIDs)) {
                throw new ApiException(config('error.goods_exception.msg'), config('error.goods_exception.code'));
            }
            // 更新购物车的状态,购物车已加入某个订单中
            if(GoodsCar::updateState($user->id, $goodsCarIDs, 1)) {
                throw new ApiException("购物车更新失败", config('error.update_goods_car_err.code'));
            }
            /**
             * 创建订单
             */
            $order_num = getRandomString(16);
            $orderId = Order::create([
                'order_num' => $order_num,
                'addr_id' => $addrId,
                'goods_car_ids' => implode($goodsCarIDs, ','),
                'send_time' => mktime(0, 0, 0, date('m'), date('d')+1, date('Y')),
                'time_space' => 3,
                'send_price' => 0,
                'coupon_id' => $couponID,
                'pay_status' => 0,
                'pay_by' => 0,
                'order_status' => 0,
                'user_id' => $user->id,
                'created_at' => time(),
            ]);
            app('db')->commit();
        } catch(Exceptions $e) {
            $rsp['state'] = 1;
            $rsp['msg'] = '订单创建失败';
            app('db')->rollBack();
        }
        return  $rsp;
    }
    /**
     * [删除订单]
     * @param  Request $request [Request实例]
     * @return [Integer]           [0表示成功1表示失败]
     */
    public function delete(Request $request)
    {
        $id = $request->route()[2]['id'];
        $rsp = config('wx.msg');
        try {
            app('db')->beginTransaction();
            Order::modify([
                'user_id' => $request->user->id,
                'id' => $id,
                'order_status' => 4,
            ]);
            app('db')->commit();
        } catch(Exceptions $e) {
            $rsp['state'] = 1;
            $rsp['msg'] = '删除订单失败';
            app('db')->rollBack();
        }
        return  $rsp;
    }
    /**
     * [getClassesOrder description]
     * @param  Request $request [description]
     * @return [type]           [description]
     */
    public function getClassesOrder(Request $request)
    {
        $rules = [
            'limit' => 'integer|max:100|min:10',
            'page' => 'integer|min:1'
        ];
        $this->validate($request, $rules);
        $rsp = config('wx.addr');
        $state = $request->route()[2]['state'];
        $user = $request->user;
        $limit = $request->input('limit', 10);
        $page = $request->input('page', 1);
        $orderObj = new Order();
        $orders = Order::mget($user->id, $limit, $page, $state);
        if(empty($orders)) {
            $rsp['state'] = 1;
            $rsp['msg'] = '您还没有此类型的订单哦';
        } else {
            $rsp['state'] = 0;
            foreach ($orders as $order) {
                $goodsCarIDs = explode(',', $order->goods_car_ids);
                // 根据购物车ID获取购物车信息
                $goodsCars = GoodsCar::mget($user->id, $goodsCarIDs, 1);
                $order->created_at = date("Y年n月d日 H:i:s", $order->created_at);
                $order->pay_time = date("Y年n月d日 H:i:s", $order->pay_time);
                $order->pay_status = config('wx.pay_status')[$order->pay_status];
                $order->pay_by = config('wx.pay_by')[$order->pay_by];
                $order->order_status = config('wx.order_status')[$order->order_status];
                $rsp['items'][] = [
                            'order_info' => $order,
                            'price_info' => $orderObj->getPrice($goodsCars, $order->coupon_id),
                        ];
            }
            $rsp['num'] = count($rsp['items']);
        }
        return $rsp;
    }
    /**
     * [update description]
     * @param  Request $request [description]
     * @return [type]           [description]
     */
    public function update(Request $request)
    {
        $rules = [
            'pay_status' => 'integer|max:1',
            'order_status' => 'integer|max:5',
            'pay_by' => 'integer|max:0',
        ];
        $this->validate($request, $rules);
        $upMsg = [];
        $upMsg['id'] = $request->route()[2]['id'];
        $upMsg['user_id'] = $request->user->id;
        if($request->has('pay_status')) {
            $upMsg['order_status'] = $upMsg['pay_status'] = $request->input('pay_status');
            $upMsg['pay_time'] = time();
        }
        if($request->has('pay_by')) {
            $upMsg['pay_by'] = $request->input('pay_by');
        }
        if($request->has('order_status')) {
            $upMsg['order_status'] = $request->input('order_status');
        }
        Order::modify($upMsg);
        return config('wx.msg');
    }
}
?>