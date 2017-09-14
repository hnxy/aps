<?php

namespace App\Http\Controllers\Agent;

use Illuminate\Http\Request;
use App\Exceptions\ApiException;
use App\Models\Goods;
use App\Models\Agent;
use App\Models\User;
use App\Models\Setting;
use App\Http\Controllers\Controller;

class UserController extends Controller
{
    /**
     * [addAgent description]
     * @param Request $request [description]
     */
    public function update(Request $request, $agent, $subAgentId)
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
            'is_detail' => 1,
        ];
        $AgentArr['passwd'] = password_hash($AgentArr['passwd'], PASSWORD_DEFAULT);
        $agentModel->modifyByAgentId($subAgentId, $AgentArr);
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
    public function index(Request $request, $agent)
    {
        $rules = [
            'status' => 'required|integer|max:2|min:0',
            'limit' => 'integer|max:100',
            'page' => 'integer',
        ];
        $this->validate($request, $rules);
        $agentModel = new Agent();
        $userModel = new User();
        $limit = $request->input('limit', 10);
        $page = $request->input('page', 1);
        $agents = $agentModel->mgetByAgentId($agent->id, $request->status, $limit, $page, 2);
        $rsp = config('error.items');
        $userIds = [];
        $agentMap = [];
        foreach ($agents as $agentObj) {
            $userIds[] = $agentObj->user_id;
            $agentMap[$agentObj->user_id] = $agentObj;
        }
        $users = $userModel->mgetByIds($userIds);
        foreach ($users as $user) {
            $agentObj = $agentMap[$user->id];
            $rsp['items'][] = [
                'user_id' => $user->id,
                'username' => $agentObj->username,
                'id_num' => $agentObj->id_num,
                'phone' => $agentObj->phone,
                'address' => $agentObj->address,
                'nickname' => json_decode($user->nickname),
                'agent_id' => $agentObj->id,
                'level' => $agentObj->level,
                'status' => config('wx.review_status')[$agentObj->review],
                'is_detail' => $agentObj->is_detail === 0 ? '用户信息未完善' : '用户信息已完善',
            ];
        }
        $rsp['num'] = count($rsp['items']);
        $totel = $agentModel->getAll($agent->id, $request->status, 2);
        $rsp['totle'] = $totel;
        $rsp['pages'] = intval($totel/$limit) + ($totel % $limit == 0 ? 0 : 1);
        return $rsp;
    }
    public function createAgentQrcode(Request $request, $agent)
    {
        if (!empty($agent->qr_agent_url)) {
            return ['agent_qrcode_url' => $agent->qr_agent_url];
        }
        $agentModel = new Agent();
        $codeUrl = 'http://' . config('wx.back_host') . '/v1/login3?agent_id=' . $agent->id;
        $codeInfo = $agentModel->createQrCode($codeUrl);
        $agentModel->modifyByAgentId($agent->id, ['qr_agent_url' => $codeInfo['visit_url']]);
        return ['agent_qrcode_url' => $codeInfo['visit_url']];
    }
    public function createShareQrcode(Request $request, $agent)
    {
        if (!empty($agent->qr_share_url)) {
            return ['share_qrcode_url' => $agent->qr_share_url];
        }
        $agentModel = new Agent();
        $codeUrl = 'http://' . config('wx.host') . '?agent_id=' . $agent->id;
        $codeInfo = $agentModel->createQrCode($codeUrl);
        $agentModel->modifyByAgentId($agent->id, ['qr_share_url' => $codeInfo['visit_url']]);
        return ['share_qrcode_url' => $codeInfo['visit_url']];
    }
    public static function get(Request $request, $agent, $subAgentId)
    {
        header('Content-Type:application/json');
        return [
            'id' => $agent->id,
            'nickname' => json_decode($agent->nickname),
            'headimgurl' => $agent->headimgurl,
        ];
    }
    public function show($agent, $subAgentId)
    {
        return obj2arr((new Agent())->getSubAgent($agent->id, $subAgentId));
    }
    public function delete($agent, $subAgentId)
    {
        $agentModel = new Agent();
        if (!$agentModel->canDelete($agent->id, $subAgentId)) {
            throw new ApiException(config('error.agent_can_not_remove.msg'), config('error.agent_can_not_remove.code'));
        }
        $agentModel->remove($user->id);
        config('error.success');
    }
}