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
            $this->throwError('请先登录');
        }

        try {
            $jwtConfig = config('jwt');
            $decoded = JWT::decode($token, new Key($jwtConfig['key'], $jwtConfig['algorithm']));
            if ($decoded->type !== 'manager') {
                $this->throwError('无效的登录类型');
            }
            $user = Db::name('admin_user')->where('id', $decoded->sub)->where('status', 1)->find();
            if (!$user) {
                $this->throwError('用户不存在或已被禁用');
            }

            // 角色权限校验：经理端仅限超管(1)、系统管理员(2)、项目经理(3)
            $roleId = $user['role_id'] ?? 0;
            if (!in_array($roleId, [1, 2, 3])) {
                $this->throwError('无权限访问经理端');
            }

            $this->managerId   = $user['id'];
            $this->managerInfo = $user;

            // 从 community_ids 中取第一个小区作为默认管理小区
            $allIds = array_values(array_filter(array_map('intval', explode(',', $user['community_ids'] ?? ''))));

            // 超管(role_id=1) community_ids 为空时视为管理全部小区
            $isSuperAdmin = ($roleId == 1) && empty($allIds);

            // 支持前端通过 X-Community-Id 请求头切换小区（超管允许任意，否则需在管理的范围内）
            $reqCid = intval($this->request->header('X-Community-Id', 0));
            if ($reqCid > 0 && ($isSuperAdmin || in_array($reqCid, $allIds))) {
                $this->communityId = $reqCid;
            } elseif ($isSuperAdmin) {
                // 超管未传 X-Community-Id 时，取第一个小区作为默认
                $first = Db::name('community')->where('delete_time', null)->order('id', 'asc')->value('id');
                $this->communityId = $first ? intval($first) : 0;
            } else {
                $this->communityId = !empty($allIds) ? $allIds[0] : 0;
            }

            $this->request->managerId   = $this->managerId;
            $this->request->managerInfo = $this->managerInfo;
            $this->request->communityId = $this->communityId;
            $this->request->managedCommunityIds = $allIds;
        } catch (\Exception $e) {
            $this->throwError('登录已过期，请重新登录');
        }
    }

    /** 获取当前经理管理的小区ID */
    protected function getCommunityId()
    {
        return $this->communityId;
    }
}
