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

            // 角色权限校验：经理端仅限超管(1)、系统管理员(2)、项目经理(3)
            $roleId = $user['role_id'] ?? 0;
            if (!in_array($roleId, [1, 2, 3])) {
                $this->error('无权限访问经理端', 403);
            }

            $this->managerId   = $user['id'];
            $this->managerInfo = $user;

            // 从 community_ids 中取第一个小区作为默认管理小区
            $allIds = array_values(array_filter(array_map('intval', explode(',', $user['community_ids'] ?? ''))));
            $this->communityId = !empty($allIds) ? $allIds[0] : 0;

            // 支持前端通过 X-Community-Id 请求头切换小区（需在管理的范围内）
            $reqCid = intval($this->request->header('X-Community-Id', 0));
            if ($reqCid > 0 && in_array($reqCid, $allIds)) {
                $this->communityId = $reqCid;
            }

            $this->request->managerId   = $this->managerId;
            $this->request->managerInfo = $this->managerInfo;
            $this->request->communityId = $this->communityId;
            $this->request->managedCommunityIds = $allIds;
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
