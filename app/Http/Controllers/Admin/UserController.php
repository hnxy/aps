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
    /**
     * [添加一级代理]
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
        $agentArr = [
            'username' => $request->input('username'),
            'passwd' => $request->input('passwd'),
            'name' => $request->input('name'),
            'phone' => $request->input('phone'),
            'id_num' => $request->input('id_num'),
            'address' => $request->input('address'),
            'is_detail' => 1,
        ];
        $agentArr['passwd'] = password_hash($agentArr['passwd'], PASSWORD_DEFAULT);
        $agentModel->add($agentArr);
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
        $filename = $admin->id. '_agent_' . getRandomString(32) . '.png';
        $dir = config('wx.qrcode_path');
        if (!is_dir($dir)) {
            mkdir($dir, 0760, true);
        }
        $codeUrl = 'http://' . $request->header('host') . '/v1/login3?admin_id=' . $admin->id;
        $visitUrl = 'http://' . config('wx.host') . config('wx.image_visit_path') . '/qr_code/' . $filename;
        $fullname = $dir . '/' . $filename;
        $content = app('qrcode')->format('png')
                                ->size(400)
                                ->generate($codeUrl, $fullname);
        $settingModel->add([
            'qrcode_url' => $visitUrl,
        ]);
        return ['agent_qrcode_url' => $visitUrl];
    }
    public function review(Request $request)
    {
        $rules = [
            'agent_id' => 'required|integer',
            'status' => 'required|integer|max:2|min:0',
        ];
        $this->validate($request, $rules);
        $agentModel = new Agent();
        $agentId = $request->input('agent_id');
        if (!$agentModel->canReview($agentId)) {
            throw new ApiException(config('error.can_not_review.msg'), config('error.can_not_review.code'));
        }
        $agentModel->modify($agentId, ['review' => $request->status]);
        return config('error.success');
    }
}