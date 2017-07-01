<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\GoodsCar;
use App\Models\Goods;
use App\Models\GoodsClasses;
use App\Exceptions\ApiException;

class GoodsCarController extends Controller
{
    /**
     * [获取购物车信息]
     * @param  Request $request [Request实例]
     * @return [Array]           [包含购物车信息的数组]
     */
    public function index(Request $request)
    {
        $rules = [
            'limit' => 'integer|min:1|max:100',
            'page' => 'integer|min:1'
        ];
        $this->validate($request, $rules);
        $limit = $request->input('limit', 10);
        $page = $request->input('page', 1);
        $user = $request->user;
        $goodsCars = GoodsCar::getItems($user->id, $limit, $page);
        $rsp = [];
        foreach ($goodsCars as $goodsCarItem) {
            //购物车包含过期商品
            $goodsInfo = Goods::getDetail($goodsCarItem->goods_id);
            if($goodsInfo->end_time <= time() ) {
                $temp['state'] = 1;
                $temp['goods_car_id'] = null;
                $temp['goods_num'] = null;
                $goodsInfo->status_text = '该商品已下架';
                $goodsInfo->send_time = formatM($goodsInfo->send_time);
                $temp['goods_info'] = $goodsInfo;
            } else {
                $temp['state'] = 0;
                $temp['goods_car_id'] = $goodsCarItem->id;
                $temp['goods_num'] = $goodsCarItem->goods_num;
                $goodsClasses = GoodsClasses::get($goodsInfo->classes_id);
                if(empty($goodsClasses)) {
                    $goodsInfo->status_text = null;
                } else {
                    $goodsInfo->status_text = $goodsClasses->name;
                }
                $goodsInfo->send_time = formatM($goodsInfo->send_time);
                $temp['goods_info'] = $goodsInfo;
            }
            $rsp[] = $temp;
        }
        return $rsp;
    }
    /**
     * [存贮购物车信息]
     * @param  Request $request [Request实例]
     * @return [integer]           [返回购物车ID]
     */
    public function store(Request $request)
    {
        $rules = [
        'goods_id' => 'required|integer',
        'goods_num' => 'required|integer'
        ];
        $this->validate($request, $rules);
        $goodsId = $request->input('goods_id');
        $user = $request->user;
        //检查商品是否是未开售或者是已过期的商品
        if(empty(Goods::get($goodsId))) {
            throw new ApiException(config('error.add_goods_exception.msg'), config('error.add_goods_exception.code'));
        }
        $goodsNum = $request->input('goods_num');

        $goodsCar = GoodsCar::hasGoods($user->id, $goodsId);
        if(!empty($goodsCar)) {
            GoodsCar::updateGoodsNum($user->id, $goodsCar->id, $goodsNum+$goodsCar->goods_num);
        } else {
            GoodsCar::add([
                        'goods_id' => $goodsId,
                        'goods_num' => $goodsNum,
                        'user_id' => $user->id,
                        'created_at' => time(),
            ]);
        }
        return config('wx.msg');
    }
    /**
     * [更新购物车]
     * @param  Request $request [Request实例]
     * @return [Integer]           [0表示成功1表示失败]
     */
    public function update(Request $request)
    {
        $rules = [
            'goods_num' => 'required|integer|min:1|max:999',
        ];
        $this->validate($request, $rules);
        $id = $request->route()[2]['id'];
        $goodsNum = $request->input('goods_num');
        $user = $request->user;
        //获取购物车对象集合,判断是否存在异常的购物车
        $goodsCars = obj2arr(GoodsCar::mget($user->id, [$id]));
        // var_dump($goodsCars);
        // exit;
        if(empty($goodsCars)) {
            throw new ApiException(config('error.goods_exception.msg'), config('error.goods_exception.code'));
        }
        GoodsCar::updateGoodsNum($user->id, $id, $goodsNum);
        return config('wx.msg');
    }
    /**
     * [删除购物车]
     * @param  Request $request [Request实例]
     * @return [Integer]           [0表示成功1表示失败]
     */
    public function delete(Request $request)
    {
        $goodsCarId = $request->route()[2]['id'];
        GoodsCar::remove($request->user->id, $goodsCarId);
        return config('wx.msg');
    }
}