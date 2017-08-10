<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Coupon;
use App\Models\GoodsCar;
use App\Models\Goods;
use App\Exceptions\ApiException;

class CouponController extends Controller
{
    /**
     * [检查优惠码]
     * @param  Request $request [description]
     * @param  [type]  $user    [description]
     * @return [type]           [description]
     */
    public function checkCode(Request $request, $user)
    {
        $rules = [
            'code' => 'required|string|max:32',
            'goods_car_ids' => 'required|string',
            'agent_id' =>  'required|integer',
        ];
        $this->validate($request, $rules);
        $rsp = config('error.success');
        $code = $request->input('code');
        $goodsCarIds = explode(',', $request->input('goods_car_ids'));
        array_pop($goodsCarIds);
        $goodsCarModel = new GoodsCar();
        $couponModel = new Coupon();
        $goodsCars = $goodsCarModel->mgetByGoodsCarIds($user->id, $goodsCarIds);
        if (count(obj2arr($goodsCars)) != count($goodsCarIds)) {
            throw new ApiException(config('error.goods_exception.msg'), config('error.goods_exception.code'));
        }
        if ($coupon = $couponModel->couponValidate($goodsCars, $code)) {
            $rsp['code'] = 0;
            $rsp['msg'] = $coupon;
        } else {
            $rsp = config('error.coupon_not_work');
        }
        return $rsp;
    }
}