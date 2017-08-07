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
     * [获取优惠码]
     * @param  Request $request [description]
     * @return [type]           [description]
     */
    public function getCode(Request $request)
    {
        $rules = [
            'goods_id' => 'required|integer',
            'agent_id' => 'required|integer',
        ];
        $this->validate($request, $rules);
        $goodsId = $request->input('goods_id');
        $agentId = $request->input('agent_id');
        $rsp = config('error.success');
        $couponModel = new Coupon();
        $coupon = $couponModel->get($goodsId, $agentId);
        if(empty($coupon) || $coupon->expired < time() ) {
            $rsp = config('error.coupon_get_fail');
        } else {
            $rsp['code'] = 0;
            $rsp['msg'] = $coupon->code;
        }
        return $rsp;
    }
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
        $agentId = $request->input('agent_id');
        $goodsCarIds = explode(',', $request->input('goods_car_ids'));
        array_pop($goodsCarIds);
        $goodsCarModel = new GoodsCar();
        $couponModel = new Coupon();
        $goodsCars = $goodsCarModel->mgetByGoodsCarIds($user->id, $goodsCarIds);
        if(count(obj2arr($goodsCars)) != count($goodsCarIds)) {
            throw new ApiException(config('error.goods_exception.msg'), config('error.goods_exception.code'));
        }
        if($coupon = $couponModel->couponValidate($goodsCars, $code, $agentId, $user->id)) {
            $rsp['code'] = 0;
            $rsp['msg'] = $coupon;
        } else {
            $rsp = config('error.coupon_not_work');
        }
        return $rsp;
    }
}