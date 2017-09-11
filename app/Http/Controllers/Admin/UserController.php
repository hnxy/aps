<?php

namespace App\Http\Controllers\Admin;

use Illuminate\Http\Request;
use App\Exceptions\ApiException;
use App\Models\Goods;
use App\Models\Agent;
use App\Models\Admin;
use App\Models\User;
use App\Models\Setting;
use App\Http\Controllers\Controller;

class UserController extends Controller
{

    public function login(Request $request)
    {
        $rules = [
            'username' => 'required|max:32|string',
            'passwd' => 'required|max:32|string',
        ];
        $this->validate($request, $rules);
        $lastIp = getIp();
        $userAgent = $request->header('User-Agent');
        $adminArr = [
            'username' => $request->username,
            'passwd' => $request->passwd,
            'last_ip' => $lastIp,
            'user_agent' => $userAgent,
        ];
        $adminModel = new Admin();
        $admin = $adminModel->login($adminArr);
        if (empty($admin)) {
            throw new ApiException("账号或密码错误", 1);
        }
        return $admin;
    }
    public function createAgentQrcode(Request $request, $admin)
    {
        $settingModel = new Setting();
        $agentModel = new Agent();
        $codeUrl = 'http://' . $request->header('host') . '/v1/login3?admin_id=' . $admin->id;
        $codeInfo = $agentModel->createQrCode($codeUrl);
        $settingModel->add([
            'qrcode_url' => $codeInfo['visit_url'],
        ]);
        return ['agent_qrcode_url' => $codeInfo['visit_url']];
    }
}