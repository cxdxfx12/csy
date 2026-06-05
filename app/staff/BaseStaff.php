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

            // 优先从 ds_staff 查找（普通物业员工）
            $this->staffInfo = Db::name('staff')->where('id', $this->staffId)->find();

            // 若找不到，通过 admin_user.phone 匹配 repair_worker（维修工）
            if (!$this->staffInfo && $this->staffId) {
                $adminUser = Db::name('admin_user')->where('id', $this->staffId)->find();
                if ($adminUser) {
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
                    // 兜底：admin_user 存在但 ds_staff/repair_worker 都找不到 → 直接使用 admin_user
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
