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

            // 来源标记，防止 admin_user 和 repair_worker 跨表 ID 碰撞
            $source = $payload['source'] ?? '';

            if ($source === 'worker') {
                // 维修工登录：sub = repair_worker.id，直接从 repair_worker 表查
                $worker = Db::name('repair_worker')->where('id', $this->staffId)->where('status', 1)->find();
                if ($worker) {
                    $this->staffInfo = [
                        'id'           => $worker['id'],
                        'realname'     => $worker['name'],
                        'phone'        => $worker['phone'],
                        'community_id' => $worker['community_id'],
                        'status'       => $worker['status'],
                        'is_worker'    => true,
                    ];
                }
            } elseif ($source === 'admin') {
                // 管理员/员工登录：sub = admin_user.id
                $adminUser = Db::name('admin_user')->where('id', $this->staffId)->find();
                if ($adminUser) {
                    $allowedRoles = config('staff.allowed_roles', [2, 3, 4, 5, 6, 7, 8]);
                    if (!in_array((int)$adminUser['role_id'], $allowedRoles)) {
                        $this->throwError('无效的凭证类型');
                    }
                    if ($adminUser['status'] != 1) {
                        $this->throwError('账户已禁用');
                    }
                    if ($adminUser['phone']) {
                        // 用 admin_user.phone 匹配 repair_worker（维修工关联到管理账号）
                        $worker = Db::name('repair_worker')->where('phone', $adminUser['phone'])->find();
                        if ($worker) {
                            $this->staffInfo = [
                                'id'           => $worker['id'],
                                'realname'     => $worker['name'],
                                'phone'        => $worker['phone'],
                                'community_id' => $worker['community_id'],
                                'status'       => $worker['status'],
                                'is_worker'    => true,
                            ];
                        }
                    }
                    if (!$this->staffInfo) {
                        $commIds = array_values(array_filter(array_map('intval', explode(',', $adminUser['community_ids'] ?? ''))));
                        $this->staffInfo = [
                            'id'           => $adminUser['id'],
                            'realname'     => $adminUser['nickname'] ?: $adminUser['username'],
                            'phone'        => $adminUser['phone'] ?? '',
                            'community_id' => $commIds[0] ?? 0,
                            'status'       => 1,
                        ];
                    }
                }
            } else {
                // 向后兼容旧 token（无 source 字段），保持原有逻辑
                // 优先从 ds_staff 查找（普通物业员工）
                $this->staffInfo = Db::name('staff')->where('id', $this->staffId)->find();

                // 若找不到，通过 admin_user.phone 匹配 repair_worker（维修工）
                if (!$this->staffInfo && $this->staffId) {
                    $adminUser = Db::name('admin_user')->where('id', $this->staffId)->find();
                    if ($adminUser) {
                        $allowedRoles = config('staff.allowed_roles', [2, 3, 4, 5, 6, 7, 8]);
                        if (!in_array((int)$adminUser['role_id'], $allowedRoles)) {
                            $this->throwError('无效的凭证类型');
                        }
                        if ($adminUser['phone']) {
                            $worker = Db::name('repair_worker')->where('phone', $adminUser['phone'])->find();
                            if ($worker) {
                                $this->staffInfo = [
                                    'id'           => $worker['id'],
                                    'realname'     => $worker['name'],
                                    'phone'        => $worker['phone'],
                                    'community_id' => $worker['community_id'],
                                    'status'       => $worker['status'],
                                    'is_worker'    => true,
                                ];
                            }
                        }
                        if (!$this->staffInfo && $adminUser['status'] == 1) {
                            $commIds = array_values(array_filter(array_map('intval', explode(',', $adminUser['community_ids'] ?? ''))));
                            $this->staffInfo = [
                                'id'           => $adminUser['id'],
                                'realname'     => $adminUser['nickname'] ?: $adminUser['username'],
                                'phone'        => $adminUser['phone'] ?? '',
                                'community_id' => $commIds[0] ?? 0,
                                'status'       => 1,
                            ];
                        }
                    }
                }
                // 兜底：JWT sub 直接是 repair_worker.id（维修工手机号密码登录）
                if (!$this->staffInfo && $this->staffId) {
                    $worker = Db::name('repair_worker')->where('id', $this->staffId)->where('status', 1)->find();
                    if ($worker) {
                        $this->staffInfo = [
                            'id'           => $worker['id'],
                            'realname'     => $worker['name'],
                            'phone'        => $worker['phone'],
                            'community_id' => $worker['community_id'],
                            'status'       => $worker['status'],
                            'is_worker'    => true,
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
