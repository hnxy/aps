<?php

namespace App\Http\Controllers\Agent;

use Illuminate\Http\Request;
use App\Exceptions\ApiException;
use App\Models\Goods;
use App\Models\Agent;
use App\Http\Controllers\Controller;

class UserController extends Controller
{
    /**
     * [addAgent description]
     * @param Request $request [description]
     */
    public function store(Request $request)
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
        $AgentArr = $request->except('confirm');
        $AgentArr['passwd'] = password_hash($AgentArr['passwd'], PASSWORD_DEFAULT);
        $agentModel->add($AgentArr);
        return config('response.success');
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