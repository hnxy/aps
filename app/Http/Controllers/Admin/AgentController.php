<?php

namespace App\Http\Controllers\Admin;

use Illuminate\Http\Request;
use App\Exceptions\ApiException;
use App\Models\Agent;
use App\Models\Admin;
use App\Models\User;
use App\Http\Controllers\Controller;

class AgentController extends Controller
{
    /**
     * [index description]
     * @param  Request $request [description]
     * @return [type]           [description]
     */
    public function index(Request $request)
    {
        $rules = [
            'status' => 'required|integer|max:2|min:0',
            'level' => 'required|integer|max:2|min:1',
            'limit' => 'integer|max:100',
            'page' => 'integer',
        ];
        $this->validate($request, $rules);
        $agentModel = new Agent();
        $userModel = new User();
        $limit = $request->input('limit', 10);
        $page = $request->input('page', 1);
        $agents = $agentModel->mgetByAgentId(0, $request->status, $limit, $page, $request->level);
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
        $totel = $agentModel->getAll(0, $request->status, $request->level);
        $rsp['totle'] = $totel;
        $rsp['pages'] = intval($totel/$limit) + ($totel % $limit == 0 ? 0 : 1);
        return $rsp;
    }
    public function update(Request $request, $admin, $agent)
    {
        $rules = [
            'status' => 'required|integer|max:2|min:0',
        ];
        $this->validate($request, $rules);
        if ($request->status == 1) {
            $this->pass($request, $agent);
        } else {
            $this->noPass($request, $agent);
        }
        return config('error.success');
    }
    protected function noPass(Request $request, $agent)
    {
        $agentModel = new Agent();
        $agentModel->modifyByAgentId($agent->id, ['review' => $request->status]);
    }
    protected function pass(Request $request, $agent)
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
        if ($agentModel->hasUsername($request->input('username'))) {
            throw new ApiException (config('error.agent_exist_exception.msg'), config('error.agent_exist_exception.code'));
        }
        $AgentArr = [
            'username' => $request->input('username'),
            'name' => $request->input('name'),
            'phone' => $request->input('phone'),
            'id_num' => $request->input('id_num'),
            'address' => $request->input('address'),
            'review' => $request->input('status'),
            'is_detail' => 1,
        ];
        if (empty($agent->qr_agent_url)) {
            $codeUrl = 'http://' . $request->header('host') . '/v1/login3?agent_id=' . $agent->id;
            $codeInfo = $agentModel->createQrCode($codeUrl);
            $agentArr['qr_agent_url'] = $codeInfo['visit_url'];
        }
        if ($request->has('passwd')) {
            $agentArr['passwd'] = password_hash($agentArr['passwd'], PASSWORD_DEFAULT);
        }
        $agentModel->modifyByAgentId($agent->id, $AgentArr);
    }
}