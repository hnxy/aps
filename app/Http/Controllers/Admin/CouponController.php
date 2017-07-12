<?php

namespace App\Http\Controllers\Admin;

use Illuminate\Http\Request;
use App\Models\Coupon;
use App\Http\Controllers\Controller;

class CouponController extends Controller
{

    public function index(Request $request)
    {
        $rules = [
            'search' => 'required|integer',
            'limit' => 'integer|min:1|max:100',
            'page' => 'integer|min:1'
        ];
        $this->validate($request, $rules);
        $limit = $request->input('limit', 10);
        $page = $request->input('page', 1);
        $id = $request->input('search');
        $coupons = Coupon::mget($id, $limit, $page);
        $rsp = config('wx.addr');
        foreach ($coupons as &$coupon) {
            if($coupon->expired < time()) {
                $coupon->status = 1;
                $coupon->status_text = '该优惠券已过期';
            } else {
                $coupon->status = 0;
                $coupon->status_text = null;
            }
            $coupon->created_at = formatTime($coupon->created_at);
            $coupon->start_time = formatTime($coupon->start_time);
            $coupon->expired = formatTime($coupon->expired);
            $rsp['items'][] = $coupon;
        }
        unset($coupon);
        $rsp['num'] = count($rsp['items']);
        return $rsp;
    }
    public function store(Request $request)
    {
        $rules = [
            'goods_id' => 'required|integer',
            'price' => 'required|numeric',
            'expire_day' => 'required|integer',
            'all_times' => 'required|integer',
            'start_time' => 'required|date'
        ];
        $this->validate($request, $rules);
        $goodsId = $request->input('goods_id');
        $agentId = $request->route()[2]['agent_id'];
        //如果有该优惠券了，那就当成更新金额
        if(Coupon::has($goodsId)) {
            Coupon::modifyByGoodsId($goodsId, [
                'price' => $request->input('price'),
            ]);
        } else {
            $code = getRandomString(8);
            $time = time();
            $startTime = mktime($request->input('start_time'));
            Coupon::add([
                'agent_id' => $agentId,
                'goods_id' => $request->input('goods_id'),
                'price' => $request->input('price'),
                'code' => $code,
                'created_at' => $time,
                'start_time' => $startTime,
                'expired' => $startTime+$request->input('expire_day')*24*3600,
                'all_times' => $request->input('all_times'),
            ]);
        }
        return config('wx.msg');
    }
    public function delete(Request $request)
    {
        $rsp = config('wx.msg');
        if(!Coupon::remove($request->agent->id, $request->route()[2]['id'])) {
            $rsp['state'] = 1;
            $rsp['msg'] = '删除优惠券失败';
        }
        return $rsp;
    }
}