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
    public function index(Request $request, $user)
    {
        $rules = [
            'limit' => 'integer|min:1|max:100',
            'page' => 'integer|min:1'
        ];
        $this->validate($request, $rules);
        $limit = $request->input('limit', 10);
        $page = $request->input('page', 1);
        $goodsCarModel = new GoodsCar();
        return $goodsCarModel->getItems($user->id, $limit, $page);
    }
    /**
     * [存贮购物车信息]
     * @param  Request $request [Request实例]
     * @return [integer]           [返回购物车ID]
     */
    public function store(Request $request, $user)
    {
        $rules = [
        'goods_id' => 'required|integer',
        'goods_num' => 'required|integer|max:999|min:1'
        ];
        $this->validate($request, $rules);
        $goodsId = $request->input('goods_id');
        $goodsModel = new Goods();
        $goodsCarModel = new GoodsCar();
        //检查商品是否是未开售或者是已过期的商品
        if(empty($goodsInfo = $goodsModel->get($goodsId)) || $goodsInfo->status != 0) {
            throw new ApiException(config('error.add_goods_exception.msg'), config('error.add_goods_exception.code'));
        }
        $goodsNum = $request->input('goods_num');
        $goodsCar = $goodsCarModel->hasGoods($user->id, $goodsId);
        if(!empty($goodsCar)) {
            $goodsCarModel->updateGoodsNum($user->id, $goodsCar->id, $goodsNum+$goodsCar->goods_num);
        } else {
            $goodsCarModel->add([
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
    public function update(Request $request, $user)
    {
        $rules = [
            'goods_num' => 'required|integer|min:1|max:999',
        ];
        $this->validate($request, $rules);
        $id = $request->route()[2]['id'];
        $goodsNum = $request->input('goods_num');
        $goodsCarModel = new GoodsCar();
        //获取购物车对象集合,判断是否存在异常的购物车
        $goodsCars = obj2arr($goodsCarModel->mgetByGoodsCarIds($user->id, [$id]));
        if(empty($goodsCars)) {
            throw new ApiException(config('error.goods_exception.msg'), config('error.goods_exception.code'));
        }
        $goodsCarModel->updateGoodsNum($user->id, $id, $goodsNum);
        return config('wx.msg');
    }
    /**
     * [删除购物车]
     * @param  Request $request [Request实例]
     * @return [Integer]           [0表示成功1表示失败]
     */
    public function delete(Request $request, $user)
    {
        $goodsCarId = $request->route()[2]['id'];
        (new GoodsCar())->remove($user->id, $goodsCarId);
        return config('wx.msg');
    }
    public function getAll(Request $request, $user)
    {
        $rsp = config('wx.msg');
        $rsp['num'] = (new GoodsCar())->getAllNum($user->id);
        return $rsp;
    }
}