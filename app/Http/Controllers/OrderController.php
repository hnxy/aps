<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Order;
use App\Models\GoodsCar;
use App\Models\OrderGoods;
use App\Models\Goods;
use App\Models\Address;
use App\Models\Area;
use App\Models\Province;
use App\Models\City;
use App\Models\Coupon;
use App\Exceptions\ApiException;
use Illuminate\Support\Facades\DB;

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
        $order = new Order();
        $address = new Address();
        // 获取购物车的信息,该返回的数据为对象数组
        $goodsCars = GoodsCar::mget($goodsCarIDs);
        if(empty($goodsCars)) {
            throw new ApiException(config('error.goods_exception.msg'), config('error.goods_exception.code'));
        }
        return [
            'rcv_info' => $address->getFullAddr($addrID),
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
        $order = new Order();
        $address = new Address();
        $orderMsg = Order::get($orderID);
        $rsp = config('wx.msg');
        if(empty($orderMsg)) {
            $rsp['state'] = 1;
            $rsp['msg'] = '该订单不存在';
            return $rsp;
        }
        $addrID = $orderMsg->addr_id;
        $couponID = $orderMsg->coupon_id;
        // 根据订单号获取相关联的购物车ID
        $orderGoodsObj = OrderGoods::getByOrderId($orderID);
        $goodsCarIDs = getGoodsCarIds($orderGoodsObj);
        // 根据购物车ID获取购物车信息
        $goodsCars = GoodsCar::mget($goodsCarIDs, 1);
        if(empty($goodsCars)) {
            throw new ApiException(config('error.goods_exception.msg'), config('error.goods_exception.code'));
        }
        return [
            'id' => $orderID,
            'pay_status' => config('wx.pay_status')[$orderMsg->pay_status],
            'pay_by' => config('wx.pay_by')[$orderMsg->pay_by],
            'order_status' => config('wx.order_status')[$orderMsg->order_status],
            'rcv_info' => $address->getFullAddr($addrID),
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
        $goodsCarIDs = $request->input('goods_car_ids');
        $addrID = $request->input('addr_id', null);
        $couponID = $request->input('coupon_id', null);
        $rsp = config('wx.msg');
        $order = new Order();
        $address = new Address();
        // 获取地址的id
        $addrId = getAddrId($address, $addrID);
        // 没有填写收货地址或者收货地址的ID不对
        if(is_null($addrId)) {
            return config('error.addr_null_err');
        }
        try {
            app('db')->beginTransaction();
             // 获取购物车的信息,该返回的数据为对象数组
            $goodsCars = GoodsCar::mget($goodsCarIDs);
            if(empty($goodsCars)) {
                throw new ApiException(config('error.goods_exception.msg'), config('error.goods_exception.code'));
            }
            // 更新购物车的状态
            if(GoodsCar::updateState($goodsCarIDs, 1)) {
                throw new ApiException("购物车更新失败", config('error.update_goods_car_err.code'));
            }
            /**
             * 创建订单
             */
            $order_num = getRandomString(16);
            $orderId = Order::create([
                'order_num' => $order_num,
                'addr_id' => $addrId,
                'send_time' => $order->getSendTime($goodsCars),
                'time_space' => 0,
                'send_price' => 0,
                'coupon_id' => $couponID,
                'pay_status' => 0,
                'pay_by' => 0,
                'order_status' => 0,
                'created_at' => time(),
            ]);
            OrderGoods::create($goodsCarIDs, $orderId);
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
            OrderGoods::remove($id);
            Order::remove($id);
            app('db')->commit();
        } catch(Exceptions $e) {
            $rsp['state'] = 1;
            $rsp['msg'] = '删除订单失败';
            app('db')->rollBack();
        }
        return  $rsp;
    }
    public function getClassesOrder(Request $request)
    {
        $rules = [
            'limit' => 'integer|max:100|min:10',
            'page' => 'integer|min:1'
        ];
        $this->validate($request, $rules);
        $rsp = config('wx.msg');
        $state = $request->route()[2]['state'];
        $limit = $request->input('limit', 10);
        $page = $request->input('page', 1);
        $orders = Order::mget($limit, $page, $state);
        if(empty($orders)) {
            $rsp['state'] = 1;
            $rsp['msg'] = '您还没有此类型的订单哦';
        } else {
            $rsp['state'] = 0;
            $rsp['msg'] = $orders;
        }
        return $rsp;
    }
}
?>