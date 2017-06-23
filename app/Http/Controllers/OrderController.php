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

class OrderController extends Controller
{
    public function showPreOrder(Request $request)
    {
        $rules = [
            'addr_id' => 'integer',
        ];
        $this->validate($request, $rules);
        $goodsCarIDs = $request->input('goods_car_ids');
        $addrID = $request->input('addr_id');
        $agentID = $request->input('agent_id', '');
        $goodsCar = new GoodsCar();
        $goods = new Goods();
        $address = new Address();
        $province = new Province();
        $city = new City();
        $area = new Area();
        $coupon = new Coupon();
        // 更新购物车的状态
        if($goodsCar->update($goodsCarIDs, 1)) {
            throw new ApiException("Error Processing Request", 1);
        }
        // 获取购物车的信息,该返回的数据为对象
        $goodsCars = $goodsCar->mget($goodsCarIDs);
        return [
            'rcv_info' => getFullAddr($province, $city, $area, $address, $addrID),
            'price_info' => getPrice($goodsCars, $coupon, $goods, null, 'couponCode'),
        ];
    }
    public function show(Request $request)
    {
        $orderID = $request->route()[2]['id'];
        $order = new Order();
        $goodsCar = new GoodsCar();
        $goods = new Goods();
        $address = new Address();
        $province = new Province();
        $city = new City();
        $area = new Area();
        $coupon = new Coupon();
        $orderGoods = new OrderGoods();
        $orderMsg = $order->get($orderID);
        $addrID = $orderMsg->addr_id;
        $couponID = $orderMsg->coupon_id;
        // 根据订单号获取相关联的购物车ID
        $orderGoodsObj = $orderGoods->getByOrderId($orderID);
        $goodsCarIDs = getGoodsCarIds($orderGoodsObj);
        // 根据购物车ID获取购物车信息
        $goodsCars = $goodsCar->mget($goodsCarIDs);
        return [
            'id' => $orderID,
            'rcv_info' => getFullAddr($province, $city, $area, $address, $addrID),
            'price_info' => getPrice($goodsCars, $coupon, $goods, $couponID),
        ];
    }
    public function store(Request $request)
    {
        $rules = [
            'addr_id' => 'integer',
        ];
        $this->validate($request, $rules);
        // 前台传入购物车的信息
        $goodsCarIDs = $request->input('goods_car_ids');
        $addrID = $request->input('addr_id', null);
        $couponID = $request->input('coupon_id', null);
        $goodsCar = new GoodsCar();
        $order = new Order();
        $goods = new Goods();
        $orderGoods = new OrderGoods();
        $address = new Address();
        // 获取购物车的信息,该返回的数据为对象
        $goodsCars = $goodsCar->mget($goodsCarIDs);
        // 获取地址的id
        $addrId = getAddrId($address, $addrID);
        /**
         * 创建订单
         */
        $order_num = getRandomString(16);
        $orderId = $order->create([
            'order_num' => $order_num,
            'addr_id' => $addrId,
            'send_time' => getSendTime($goodsCars, $goods),
            'time_space' => 0,
            'send_price' => 0,
            'coupon_id' => $couponID,
            'pay_status' => '未支付',
            'pay_by' => '微信支付',
            'order_status' => '待支付'
        ]);
        $orderGoods->create($goodsCarIDs, $orderId);
        // $this->pay();
    }
}
?>