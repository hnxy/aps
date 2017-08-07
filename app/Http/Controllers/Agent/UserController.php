<?php

namespace App\Http\Controllers\Agent;

use Illuminate\Http\Request;
use App\Exceptions\ApiException;
use App\Models\Goods;
use App\Models\Agent;
use App\Models\Setting;
use App\Http\Controllers\Controller;

class UserController extends Controller
{
    /**
     * [addAgent description]
     * @param Request $request [description]
     */
    public function store(Request $request, $agent)
    {
        $rules = [
            'username' => 'required|string|max:16',
            'passwd' => 'required|string|max:16',
            'confirm' => 'required|string|same:passwd',
            'name' => 'required|string',
            'phone' => array('required', 'regex:/^\d{11}$/'),
            'id_num' => array('required', 'regex:/^\d{18}$/'),
            'address' => array('required'),
        ];
        $this->validate($request, $rules);
        $agentModel = new Agent();
        if($agentModel->hasUsername($request->input('username'))) {
            throw new ApiException (config('error.agent_exist_exception.msg'), config('error.agent_exist_exception.code'));
        }
        $AgentArr = [
            'username' => $request->input('username'),
            'passwd' => $request->input('passwd'),
            'name' => $request->input('name'),
            'phone' => $request->input('phone'),
            'id_num' => $request->input('id_num'),
            'address' => $request->input('address'),
        ];
        $AgentArr['passwd'] = password_hash($AgentArr['passwd'], PASSWORD_DEFAULT);
        $agentModel->add($AgentArr);
        return config('error.success');
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
    public function index($agent)
    {
        $agentModel = new Agent();
        return $agentModel->mget();
    }
    public function getQrcode(Request $request, $agent)
    {
        $settingModel = new Setting();
        $filename = getRandomString(32);
        $dir = config('wx.qrcode_path');
        if (!is_dir($dir)) {
            mkdir($dir, 0777, true);
        }
        $fullname = $dir . $filename . '.png';
        $content = app('qrcode')->format('png')
                                ->size(800)
                                ->generate('http://aps.cg0.me/v1/login3?agent_id=' . $agent->id, $fullname);
        $settingModel->add([
            'qrcode_url' => $fullname,
        ]);
        return $fullname;
    }
}