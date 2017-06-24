<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\GoodsCar;
use App\Models\Goods;
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
        $goodsCar = new GoodsCar();
        $goods = new Goods();
        $goodsCars = $goodsCar->getItems($limit, $page);
        $rsp = [];
        foreach ($goodsCars as $goodsCarItem) {
            //购物车包含过期商品
            $goodsInfo = $goods->get($goodsCarItem->goods_id);
            if(empty($goodsInfo)) {
                $temp['state'] = 1;
                $temp['goods_info'] = '该商品已下架';
                $temp['goods_car_id'] = null;
                $temp['goods_num'] = null;
            } else {
                $temp['state'] = 0;
                $goodsInfo->send_time = formatM($goodsInfo->send_time);
                $temp['goods_car_id'] = $goodsCarItem->id;
                $temp['goods_num'] = $goodsCarItem->goods_num;
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
        $goods = new Goods();
        //检查商品是否是未开售或者是已过期的商品
        if(empty($goods->get($goodsId))) {
            throw new ApiException(config('error.add_goods_exception.msg'), config('error.add_goods_exception.code'));
        }
        $goodsNum = $request->input('goods_num');
        $goodsCar = new GoodsCar();
        $goossCarId = $goodsCar->add([
                        'goods_id' => $goodsId,
                        'goods_num' => $goodsNum,
                        'created_at' => time(),
                    ]);
        return $goossCarId;
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
        $goodsCar = new GoodsCar();
        //获取购物车对象集合,判断是否存在异常的购物车
        $goodsCars = $goodsCar->mget([$id]);
        if(empty($goodsCars)) {
            throw new ApiException(config('error.goods_exception.msg'), config('error.goods_exception.code'));
        }
        return $goodsCar->updateGoodsNum($id, $goodsNum) !== false ? 0 : 1;
    }
    /**
     * [删除购物车]
     * @param  Request $request [Request实例]
     * @return [Integer]           [0表示成功1表示失败]
     */
    public function delate(Request $request)
    {
        $rules = [
            'goods_car_id' => 'required|integer'
        ];
        $this->validate($request, $rules);
        $goodsCarId = $request->input('goods_car_id');
        $goodsCar = new GoodsCar();
        return $goodsCar->remove($goodsCarId) !== false ? 0 : 1;
    }
}