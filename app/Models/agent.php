<?php

namespace App\Models;

use App\Models\Db\Agent as DbAgent;
use App\Exceptions\ApiException;

class Agent extends Model
{
    public static $model = 'Agent';

    public function get($id)
    {
        $this->primaryValue = $id;
        return $this;
    }

    public function add($arr)
    {
        return DbAgent::add($arr);
    }
    public function mgetByAgentId($agentId, $status, $limit, $page)
    {
        $arr['limit'] = $limit;
        $arr['page'] = $page;
        $arr['where'] = [
            ['created_by', '=', $agentId],
            ['review', '=', $status],
            ['level', '=', 2],
        ];
        return DbAgent::mget($arr);
    }
     /**
     * [login description]
     * 用户登录
     * @author cg
     * @param  [array] $userArr [username,passwd,last_ip]
     * @return [array]          [[id => '用户id',token => '安全token']]
     */
    public function login($userArr)
    {
        $user = DbAgent::get(['where' => [
                ['username', '=', $userArr['username']],
                ['review', '=', 1],
                ['is_del', '=', 0],
            ]]);
        if (empty($user)) {
            throw new ApiException("此用户不存在", 2);
        }
        // 密码不正确
        if (!password_verify($userArr['passwd'], $user->passwd)) {
            return [];
        }
        $token = genToken();
        $lastLoginTime = time();
        $arr['where'] = ['id' => $user->id];
        $arr['update'] = [
            'last_login_time' => $lastLoginTime,
            'login_count' => $user->login_count + 1,
            'token' => $token,
            'token_expired' => $lastLoginTime + 3600 * 24,
            'user_agent' => $userArr['user_agent'],
            'last_ip' => $userArr['last_ip'],
            ];
        DbAgent::update($arr);
        return ['id' => $user->id, 'token' => $token, 'level' => $user->level];
    }
    public function has($agentId)
    {
        if(empty($agent = Dbagent::get(['where' => ['id' => $agentId] ]))) {
            return false;
        }
        return true;
    }
    public function hasUsername($username)
    {
        if (empty($agent = Dbagent::get(['where' => ['username' => $username] ]))) {
            return false;
        }
        return true;
    }
    public function hasApply($userId)
    {
        $arr['where'] = [
            ['user_id', '=', $userId],
        ];
        if (empty($agent = Dbagent::get($arr))) {
            return false;
        }
        return $agent;
    }
    public function getAll($agentId, $status)
    {
        $arr['where'] = [
            ['created_by', '=', $agentId],
            ['review', '=', $status],
        ];
        return DbAgent::getAll($arr);
    }
    public function getSubAgent($agentId, $subAgentId)
    {
        $arr['where'] = [
            ['id', '=', $subAgentId],
            ['created_by', '=', $agentId],
            ['level', '=', 2],
        ];
        return DbAgent::get($arr);
    }
    public function modifyByAgentId($agentId, $arr)
    {
        $uarr['where'] = ['id' => $agentId];
        $uarr['update'] = $arr;
        DbAgent::modify($uarr);
    }
    public function canReview($agentId)
    {
        $arr['where'] = [
            ['id', '=', $agentId],
        ];
        $agent = DbAgent::get($arr);
        if ($agent->review != 0 || $agent->is_del == 1) {
            return false;
        }
        return $agent;
    }
    public function canDelete($agentId, $subAgentId)
    {
        $arr['where'] = [
            ['id', '=', $subAgentId],
            ['created_by', '=', $agentId],
            ['review', '=', 2],
        ];
        $agent = DbAgent::get($arr);
        if (empty($agent)) {
            return false;
        }
        return true;
    }
    public function remove($agentId)
    {
        return DbAgent::remove(['where' => ['id' => agentId]]);
    }
}

?>