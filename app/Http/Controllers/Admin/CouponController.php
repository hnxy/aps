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
        $couponModel = new Coupon();
        $rsp = config('wx.addr');
        $rsp['items'] = $couponModel->getItems($id, $limit, $page);
        $rsp['num'] = count($rsp['items']);
        return $rsp;
    }
    public function store(Request $request, $agent)
    {
        $rules = [
            'goods_id' => 'required|integer',
            'price' => 'required|numeric',
            'expired_day' => 'required|integer',
            'all_times' => 'required|integer',
            'start_time' => 'required|date'
        ];
        $this->validate($request, $rules);
        $goodsId = $request->input('goods_id');
        $couponModel = new Coupon();
        //如果有该优惠券了，那就当成更新金额
        if($couponModel->has($goodsId)) {
            $couponModel->modifyByGoodsId($goodsId, [
                'price' => $request->input('price'),
            ]);
        } else {
            $code = getRandomString(8);
            $time = time();
            $startTime = strtotime($request->input('start_time'));
            $couponModel->add([
                'agent_id' => $agent->id,
                'goods_id' => $request->input('goods_id'),
                'price' => $request->input('price'),
                'code' => $code,
                'created_at' => $time,
                'start_time' => $startTime,
                'expired' => $startTime+$request->input('expired_day')*24*3600,
                'all_times' => $request->input('all_times'),
            ]);
        }
        return config('wx.msg');
    }
    public function delete(Request $request, $agent)
    {
        $rsp = config('wx.msg');
        if(!(new Coupon())->remove($agent->id, $request->route()[2]['id'])) {
            $rsp['state'] = 1;
            $rsp['msg'] = '删除优惠券失败';
        }
        return $rsp;
    }
}