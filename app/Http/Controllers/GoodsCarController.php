<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\GoodsCar;
use App\Models\Goods;
use App\Exceptions\ApiException;

class GoodsCarController extends Controller
{
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
        foreach ($goodsCars as $goodsCar) {
            $temp['goods_car_id'] = $goodsCar->id;
            $temp['goods_num'] = $goodsCar->goods_num;
            $goodsInfo = $goods->get($goodsCar->goods_id);
            $goodsInfo->send_time = formatM($goodsInfo->send_time);
            $temp['goods_info'] = $goodsInfo;
            $rsp[] = $temp;
        }
        return $rsp;
    }
    public function store(Request $request)
    {
        $rules = [
        'goods_id' => 'required|integer',
        'goods_num' => 'required|integer'
        ];
        $this->validate($request, $rules);
        $goodsId = $request->input('goods_id');
        $goodsNum = $request->input('goods_num');
        $goodsCar = new GoodsCar();
        $goossCarId = $goodsCar->add([
                        'goods_id' => $goodsId,
                        'goods_num' => $goodsNum,
                        'created_at' => time(),
                    ]);
        return $goossCarId;
    }
    public function update(Request $request)
    {
        $rules = [
            'goods_num' => 'required|integer|min:1|max:999',
        ];
        $this->validate($request, $rules);
        $id = $request->route()[2]['id'];
        $goodsNum = $request->input('goods_num');
        $goodsCar = new GoodsCar();
        $goodsCars = $goodsCar->mget([$id]);
        if(empty($goodsCars)) {
            throw new ApiException(config('error.goods_exception.msg'), config('error.goods_exception.code'));
        }
        return $goodsCar->updateGoodsNum($id, $goodsNum);
    }
    public function delate(Request $request)
    {
        $rules = [
            'goods_car_id' => 'required|integer'
        ];
        $this->validate($request, $rules);
        $goodsCarId = $request->input('goods_car_id');
        $goodsCar = new GoodsCar();
        return $goodsCar->remove($goodsCarId);
    }
}