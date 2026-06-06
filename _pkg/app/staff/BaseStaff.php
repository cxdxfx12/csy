<?php
// 员工端基类
namespace app\staff;

use app\BaseController;
use think\facade\Db;

class BaseStaff extends BaseController
{
    protected $staffInfo = [];
    protected $staffId = 0;
    protected $noAuth = ['login', 'captcha'];

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
        $token = str_replace('Bearer ', '', $token);

        if (empty($token)) {
            $this->throwError('请先登录');
        }

        try {
            $jwtConfig = config('jwt');
            $payload = \Firebase\JWT\JWT::decode($token, new \Firebase\JWT\Key($jwtConfig['key'], $jwtConfig['algorithm']));
            $payload = (array) $payload;
            $this->staffId = $payload['sub'] ?? 0;

            // 校验 token type 必须为 staff，防止跨端 token 混用
            $tokenType = $payload['type'] ?? '';
            if (!in_array($tokenType, ['staff', 'manager'])) {
                $this->throwError('无效的凭证类型');
            }

            // 优先从 ds_staff 查找（普通物业员工）
            $this->staffInfo = Db::name('staff')->where('id', $this->staffId)->find();

            // 若找不到，通过 admin_user.phone 匹配 repair_worker（维修工）
            if (!$this->staffInfo && $this->staffId) {
                $adminUser = Db::name('admin_user')->where('id', $this->staffId)->find();
                if ($adminUser) {
                    // 限制：仅允许 role_id 属于员工角色（如维修工/巡检员等）的 admin_user
                    // 管理员(role=1)不允许通过 staff 端登录
                    $allowedRoles = config('staff.allowed_roles', [2, 3, 4, 5, 6, 7, 8]);
                    if (!in_array((int)$adminUser['role_id'], $allowedRoles)) {
                        $this->throwError('无效的凭证类型');
                    }
                    if ($adminUser['phone']) {
                        $worker = Db::name('repair_worker')->where('phone', $adminUser['phone'])->find();
                        if ($worker) {
                            // 将维修工信息伪装成 staffInfo 以兼容后续逻辑
                            $this->staffInfo = [
                                'id' => $worker['id'],
                                'realname' => $worker['name'],
                                'phone' => $worker['phone'],
                                'community_id' => $worker['community_id'],
                                'status' => $worker['status'],
                                'is_worker' => true,
                            ];
                        }
                    }
                    // 兜底：仅在 role_id 允许的范围内使用 admin_user 信息
                    if (!$this->staffInfo && $adminUser['status'] == 1) {
                        $commIds = array_values(array_filter(array_map('intval', explode(',', $adminUser['community_ids'] ?? ''))));
                        $this->staffInfo = [
                            'id' => $adminUser['id'],
                            'realname' => $adminUser['nickname'] ?: $adminUser['username'],
                            'phone' => $adminUser['phone'] ?? '',
                            'community_id' => $commIds[0] ?? 0,
                            'status' => 1,
                        ];
                    }
                }
            }

            if (!$this->staffInfo || $this->staffInfo['status'] != 1) {
                $this->throwError('账户异常');
            }

            $this->request->staffInfo = $this->staffInfo;

        } catch (\Exception $e) {
            $this->throwError('身份验证失败');
        }
    }
}
