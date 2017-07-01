<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Goods;
use App\Models\Agent;

class AdminController extends Controller
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
            'send_time' => 'required|date'
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
    public function index(Request $request)
    {

    }
    /**
     * [addLogistics description]
     * @param Request $request [description]
     */
    public  function addLogistics(Request $request)
    {
        $rules = [
            'goods_car_ids.*' => 'required|integer',
            'express_id' => 'required|integer',
            'logistics_num' => 'required|string|max:32',
        ];
        $this->validate($request, $rules);
        $rsp = config('wx.msg');
        $goodsCarIds = $request->input('goods_car_ids');
        if(GoodsCar::addLogistics($goodsCarIds, $request->only(['logistics_num', 'express_id']))) {
            return $rsp;
        }
        $rsp['state'] = 1;
        $rsp['msg'] = '添加物流单号失败';
        return $rsp;
    }
    public function addAgent(Request $request)
    {
        $rules = [
            'username' => 'required|string|max:16',
            'password' => 'required|string|max:16',
            'confirm' => 'required|string|same:password',
            'name' => 'required|string',
            'phone' => array('required', 'regex:/\d{11}/'),
            'id_num' => array('required', 'regex:/\d{18}/'),
            'address' => array('required'),
        ];
        $this->validate($request, $rules);
        $AgentArr = $request->except('confirm');
        $AgentArr['password'] = password_hash($AgentArr['password'], PASSWORD_DEFAULT);
        if(Agent::add($AgentArr)) {
            return config('wx.msg');
        }
    }
    public function login(Request $request)
    {
        $rules = [
            'username' => 'required|max:32|string',
            'passwd' => 'required|max:32|string',
        ];
        $this->validate($request, $rules);
        $lastIp = getIp();
        $userAgent = $request->header('User-Agent');
        $agentArr = [
            'username' => $request->username,
            'passwd' => $request->passwd,
            'last_ip' => $lastIp,
            'user_agent' => $userAgent,
        ];
        $agentModel = new Agent();
        $agent = $agentModel->login($agentArr);
        if (empty($agent)) {
            throw new ApiException("账号或密码错误", 1);
        }
        return $agent;
    }
}