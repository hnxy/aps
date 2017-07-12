<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\GoodsCar;
use App\Models\OrderGoods;
use App\Models\Goods;
use App\Models\Address;
use App\Models\Area;
use App\Models\Province;
use App\Models\City;
use App\Exceptions\ApiException;

class OrderController extends Controllers
{
    public function show()
    {

    }
    public function store(Request $request)
    {
        $rules = [
            'goods_car_ids' => 'max:9999 | integer',
            'addr_id' => 'integer',
        ];
        $this->validate($request, $rules);
        $goodsCarIDs = $request->input('goods_car_ids');
        $addrID = $request->input('addr_id');
        $goodsCar = new GoodsCar();
        $order = new Order();
        $goods = new Goods();
        $orderGoods = new OrderGoods();
        $address = new Address();
        // 更新购物车的状态
        if($goodCar->update($goodsCarIDs, 1)) {
            throw new ApiException("Error Processing Request", 1);
        }
        // 获取购物车的信息,该返回的数据为对象
        $goodsCars = $goodsCar->mget($request);
        // 通过购物车的信息来取得商品的信息
        $goodsInfo = [];
        foreach ($goodsCars as $goodsCar) {
            $goodsInfo[$goodsCar->good_id] = $goods->get($goodsCar->good_id);
        }
        //获取地址的详细信息
        if(!empty($addrID)) {
            $addrDetail = $address->get();
        } else {
            $addrDetail = $address->get($addrID);
        }
        //根据获取的地址的详细信息来获取省,市,县区的名称

    }
}


?>