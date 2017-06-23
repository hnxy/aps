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

class OrdersController extends Controller
{
    public function showPreOrder(Request $request)
    {
        $rules = [
            'addr_id' => 'integer',
            'goods_car_ids.*' => 'integer',
        ];
        $this->validate($request, $rules);
        $goodsCarIDs = $request->input('goods_car_ids');
        $addrID = $request->input('addr_id');
        $goodsCar = new GoodsCar();
        $goods = new Goods();
        $order = new Orders();
        $address = new Address();
        $province = new Province();
        $city = new City();
        $area = new Area();
        $coupon = new Coupon();

        // 获取购物车的信息,该返回的数据为对象数组
        $goodsCars = $goodsCar->mget($goodsCarIDs);
        if(empty($goodsCars)) {
            throw new ApiException(config('error.goods_exception.msg'), config('error.goods_exception.code'));
        }
        return [
            'rcv_info' => $address->getFullAddr($province, $city, $area, $addrID),
            'price_info' => $order->getPrice($goodsCars, $coupon, $goods, null, 'couponCode'),
        ];
    }
    public function show(Request $request)
    {
        $orderID = $request->route()[2]['id'];
        $order = new Orders();
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
            'pay_status' => $orderMsg->pay_status,
            'pay_by' => $orderMsg->pay_by,
            'order_status' => $orderMsg->order_status,
            'rcv_info' => $address->getFullAddr($province, $city, $area, $addrID),
            'price_info' => $order->getPrice($goodsCars, $coupon, $goods, $couponID),
        ];
    }
    public function store(Request $request)
    {
        $rules = [
            'addr_id' => 'integer',
            'goods_car_ids.*' => 'integer',
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
        // 获取购物车的信息,该返回的数据为对象数组
        $goodsCars = $goodsCar->mget($goodsCarIDs);
        if(empty($goodsCars)) {
            throw new ApiException(config('error.goods_exception.msg'), config('error.goods_exception.code'));
        }
        // 更新购物车的状态
        if($goodsCar->updateState($goodsCarIDs, 1)) {
            throw new ApiException("购物车更新失败", config('error.update_goods_car_err.code'));
        }
        // 获取地址的id
        $addrId = getAddrId($address, $addrID);

        /**
         * 创建订单
         */
        $order_num = getRandomString(16);
        $orderId = $order->create([
            'order_num' => $order_num,
            'addr_id' => $addrId,
            'send_time' => $order->getSendTime($goodsCars, $goods),
            'time_space' => 0,
            'send_price' => 0,
            'coupon_id' => $couponID,
            'pay_status' => '未支付',
            'pay_by' => '微信支付',
            'order_status' => '待支付',
            'created_at' => time(),
        ]);
        $orderGoods->create($goodsCarIDs, $orderId);
        // $this->pay();
    }
    public function delete(Request $request)
    {
        $id = $request->route()[2]['id'];
        $order = new Orders();
        return $order->remove($id);
    }
}
?>