<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Coupon;

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
        if(empty($coupon)) {
            $rsp['state'] = 1;
            $rsp['msg'] = '无法兑换该优惠码';
        } else {
            $rsp['state'] = 0;
            $rsp['msg'] = $coupon->code;
        }
        return $rsp;
    }
    public function checkCode(Request $request)
    {
        $rules = [
            'code' => 'required|string|max:32',
            'goods_ids.*' => 'required|integer',
        ];
        $this->validate($request, $rules);
        $rsp = config('wx.msg');
        $code = $request->input('code');
        $coupon = Coupon::checkWork($code, 'code');
        $goods_ids = $request->input('goods_ids');
        if(!empty($coupon) && in_array($coupon->goods_id, $goods_ids)) {
            $rsp['state'] = 0;
            $rsp['msg'] = $coupon;
        } else {
            $rsp['state'] = 1;
            $rsp['msg'] = '该优惠码无效';
        }
        return $rsp;
    }
    public function store(Request $request)
    {

    }
    public function delete(Request $request)
    {

    }
}