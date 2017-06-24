<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Orders;
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

class OrdersController extends Controller
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
            'goods_car_ids' => 'required|integer',
        ];
        $this->validate($request, $rules);
        $goodsCarIDs = $request->input('goods_car_ids');
        $addrID = $request->input('addr_id');
        $goodsCar = new GoodsCar();
        $goods = new Goods();
        $order = new Orders();
        $address = new Address();
        $coupon = new Coupon();
        // 获取购物车的信息,该返回的数据为对象数组
        $goodsCars = $goodsCar->mget($goodsCarIDs);
        if(empty($goodsCars)) {
            throw new ApiException(config('error.goods_exception.msg'), config('error.goods_exception.code'));
        }
        return [
            'rcv_info' => $address->getFullAddr($addrID),
            'price_info' => $order->getPrice($goodsCars, null, 'couponCode'),
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
        $order = new Orders();
        $goodsCar = new GoodsCar();
        $goods = new Goods();
        $address = new Address();
        $coupon = new Coupon();
        $orderGoods = new OrderGoods();
        $orderMsg = $order->get($orderID);
        $addrID = $orderMsg->addr_id;
        $couponID = $orderMsg->coupon_id;
        // 根据订单号获取相关联的购物车ID
        $orderGoodsObj = $orderGoods->getByOrderId($orderID);
        $goodsCarIDs = getGoodsCarIds($orderGoodsObj);
        // 根据购物车ID获取购物车信息
        $goodsCars = $goodsCar->mget($goodsCarIDs, 1);
        if(empty($goodsCars)) {
            throw new ApiException(config('error.goods_exception.msg'), config('error.goods_exception.code'));
        }
        return [
            'id' => $orderID,
            'pay_status' => $orderMsg->pay_status,
            'pay_by' => $orderMsg->pay_by,
            'order_status' => $orderMsg->order_status,
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
        $goodsCar = new GoodsCar();
        $order = new Orders();
        $goods = new Goods();
        $orderGoods = new OrderGoods();
        $address = new Address();
        // 获取地址的id
        $addrId = getAddrId($address, $addrID);
        if(is_null($addrId)) {
            return config('error.addr_null_err');
        }
        try {
            app('db')->beginTransaction();
             // 获取购物车的信息,该返回的数据为对象数组
            $goodsCars = $goodsCar->mget($goodsCarIDs);
            if(empty($goodsCars)) {
                throw new ApiException(config('error.goods_exception.msg'), config('error.goods_exception.code'));
            }
            // 更新购物车的状态
            if($goodsCar->updateState($goodsCarIDs, 1)) {
                throw new ApiException("购物车更新失败", config('error.update_goods_car_err.code'));
            }

            /**
             * 创建订单
             */
            $order_num = getRandomString(16);
            $orderId = $order->create([
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
            $orderGoods->create($goodsCarIDs, $orderId);
            app('db')->commit();
        } catch(Exceptions $e) {
            app('db')->rollBack();
        }

    }
    /**
     * [删除订单]
     * @param  Request $request [Request实例]
     * @return [Integer]           [0表示成功1表示失败]
     */
    public function delete(Request $request)
    {
        $id = $request->route()[2]['id'];
        $order = new Orders();
        return $order->remove($id) !== false ? 0 : 1;
    }
}
?>