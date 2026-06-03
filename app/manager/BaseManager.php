<?php
// 经理端基类
namespace app\manager;

use app\BaseController;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use think\facade\Db;

class BaseManager extends BaseController
{
    protected $noAuth = ['login', 'wechatOAuth', 'wechatCallback', 'wechatLogin', 'wechatRegister'];
    protected $managerId = 0;
    protected $managerInfo = null;
    protected $communityId = 0;

    protected function initialize()
    {
        parent::initialize();
        $action = $this->request->action(true);
        if (!in_array($action, $this->noAuth)) {
            $this->auth();
        }
    }

    protected function auth()
    {
        $token = $this->request->header('Authorization', '');
        if (strpos($token, 'Bearer ') === 0) {
            $token = substr($token, 7);
        }
        if (empty($token)) {
            $token = $this->request->param('token', '');
        }
        if (empty($token)) {
            $this->error('请先登录', 401);
        }

        try {
            $jwtConfig = config('jwt');
            $decoded = JWT::decode($token, new Key($jwtConfig['key'], $jwtConfig['algorithm']));
            if ($decoded->type !== 'manager') {
                $this->error('无效的登录类型', 401);
            }
            $user = Db::name('admin_user')->where('id', $decoded->sub)->where('status', 1)->find();
            if (!$user) {
                $this->error('用户不存在或已被禁用', 401);
            }
            $this->managerId   = $user['id'];
            $this->managerInfo = $user;

            // 从 community_ids 中取第一个小区作为管理小区
            $ids = array_filter(explode(',', $user['community_ids'] ?? ''));
            $this->communityId = !empty($ids) ? intval($ids[0]) : 0;

            $this->request->managerId   = $this->managerId;
            $this->request->managerInfo = $this->managerInfo;
            $this->request->communityId = $this->communityId;
        } catch (\Exception $e) {
            $this->error('登录已过期，请重新登录', 401);
        }
    }

    /** 获取当前经理管理的小区ID */
    protected function getCommunityId()
    {
        return $this->communityId;
    }
}
