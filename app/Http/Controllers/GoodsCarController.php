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
        $this->checkGoodsWork($goodsModel, $goodsId);
        $goodsNum = $request->input('goods_num');
        $goodsCar = $goodsCarModel->hasGoods($user->id, $goodsId);
        if ($goodsCar !== false) {
            if ($goodsNum+$goodsCar->goods_num > config('wx.max_goods_num')) {
                throw new ApiException(config('error.goods_num_over.msg'), config('error.goods_num_over.code'));
            }
            $goodsCarModel->updateGoodsNum($user->id, $goodsCar->id, $goodsNum+$goodsCar->goods_num);
        } else {
            $goodsCarModel->add([
                        'goods_id' => $goodsId,
                        'goods_num' => $goodsNum,
                        'user_id' => $user->id,
                        'created_at' => time(),
                    ]);
        }
        return config('response.success');
    }
    /**
     * [更新购物车]
     * @param  Request $request [Request实例]
     * @return [Integer]           [0表示成功1表示失败]
     */
    public function update(Request $request, $user, $goodsCarId)
    {
        $rules = [
            'goods_num' => 'required|integer|min:1|max:999',
        ];
        $this->validate($request, $rules);
        $goodsNum = $request->input('goods_num');
        $goodsCarModel = new GoodsCar();
        $goodsCar = $goodsCarModel->get($user->id, $goodsCarId);
        if (!$goodsCarModel->canUpdate($goodsCar)) {
            return config('response.goods_car_update_fail');
        }
        $goodsCarModel->updateGoodsNum($user->id, $goodsCarId, $goodsNum);
        return config('response.success');
    }
    /**
     * [检查商品是否是未开售或者是已过期的商品]
     * @param  [type] $goodsModel [description]
     * @param  [type] $goodsId    [description]
     * @return [type]             [description]
     */
    private function checkGoodsWork($goodsModel, $goodsId)
    {
        if(empty($goodsInfo = $goodsModel->get($goodsId)) || $goodsInfo->status != 0) {
            throw new ApiException(config('error.add_goods_exception.msg'), config('error.add_goods_exception.code'));
        }
    }
    /**
     * [删除购物车]
     * @param  Request $request [Request实例]
     * @return [Integer]           [0表示成功1表示失败]
     */
    public function delete(Request $request, $user, $goodsCarId)
    {
        $goodsCarModel = new GoodsCar();
        $goodsCar = $goodsCarModel->get($user->id, $goodsCarId);
        if (!$goodsCarModel->canOperate($goodsCar)) {
            return config('response.addr_rm_fail');
        }
        $goodsCarModel->remove($user->id, $goodsCarId);
        return config('response.success');
    }
    /**
     * [获取购物车总数]
     * @param  Request $request [description]
     * @param  [type]  $user    [description]
     * @return [type]           [description]
     */
    public function getAll(Request $request, $user)
    {
        $rsp = config('response.success');
        $rsp['num'] = (new GoodsCar())->getAllNum($user->id);
        return $rsp;
    }
}