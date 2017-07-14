<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Coupon;
use App\Models\GoodsCar;
use App\Models\Goods;

class CouponController extends Controller
{
    public function getCode(Request $request)
    {
        $rules = [
            'goods_id' => 'required|integer',
            'agent_id' => 'required|integer',
        ];
        $this->validate($request, $rules);
        $goodsId = $request->input('goods_id');
        $agentId = $request->input('agent_id');
        $rsp = config('wx.msg');
        $coupon = Coupon::get($goodsId, $agentId);
        if(empty($coupon) || $coupon->expired < time() ) {
            $rsp['state'] = 1;
            $rsp['msg'] = '无法兑换该优惠码';
        } else {
            $rsp['state'] = 0;
            $rsp['msg'] = $coupon->code;
        }
        return $rsp;
    }
    public function checkCode(Request $request, $user)
    {
        $rules = [
            'code' => 'required|string|max:32',
            'goods_car_ids' => 'required|string',
        ];
        $this->validate($request, $rules);
        $rsp = config('wx.msg');
        $code = $request->input('code');
        $goodsCarIds = explode(',', $request->input('goods_car_ids'));
        array_pop($goodsCarIds);
        $goodsCars = GoodsCar::mget($user->id, $goodsCarIds);
        if(count(obj2arr($goodsCars)) != count($goodsCarIds)) {
            throw new ApiException(config('error.goods_exception.msg'), config('error.goods_exception.code'));
        }
        $goods = getMap($goodsCars, 'goods_id');
        $couponModel = new Coupon();
        if($coupon = $couponModel->checkWork($code, 'code', array_keys($goods))) {
            $allPrice = (-1)*$coupon['price'];
            foreach (Goods::mgetByIds(array_keys($goods)) as $goodsInfo) {
                $allPrice += $goodsInfo->price*$goods[$goodsInfo->id]->goods_num;
            }
            $coupon['all_price'] = sprintf('%0.2f', $allPrice);
            $rsp['state'] = 0;
            $rsp['msg'] = $coupon;
        } else {
            $rsp['state'] = 1;
            $rsp['msg'] = '该优惠码无效';
        }
        return $rsp;
    }
}