<?php

namespace App\Http\Controllers\Admin;

use Illuminate\Http\Request;
use App\Models\Goods;
use App\Models\Agent;
use App\Http\Controllers\Controller;

class GoodsController extends Controller
{
    /**
     * [store description]
     * @param  Request $request [description]
     * @return [type]           [description]
     */
    public function store(Request $request)
    {
        $rules = [
            'title' => 'required|string|max:256',
            'description' => 'required|string|max:256',
            'origin_price' => 'required|numeric',
            'price' => 'required|numeric',
            'start_time' => 'required|date',
            'end_time' => 'required|date',
            'detail' => 'required|string',
            'classes_id' => 'integer',
            'unit' => 'required|string',
            'send_time' => 'required|date',
            'stock' => 'required|integer',
        ];
        $this->validate($request, $rules);
        $goodsInfo = $request->all();
        if(!array_key_exists('classes_id', $goodsInfo)) {
            $goodsInfo['classes_id'] = 0;
        }
        $goodsInfo['start_time'] = strtotime($goodsInfo['start_time']);
        $goodsInfo['end_time'] = strtotime($goodsInfo['end_time']);
        $goodsInfo['send_time'] = strtotime($goodsInfo['send_time']);
        $goodsInfo['created_at'] = time();
        if(Goods::add($goodsInfo)) {
            return config('wx.msg');
        }
    }
}