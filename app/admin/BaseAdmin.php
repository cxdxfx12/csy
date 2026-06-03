<?php
// 后台基类控制器
namespace app\admin;

use app\BaseController;
use think\facade\Db;

class BaseAdmin extends BaseController
{
    protected $adminInfo = [];
    protected $adminId = 0;
    protected $noAuth = ['login', 'captcha', 'logout'];

    protected function initialize()
    {
        parent::initialize();

        // 自动认证
        $action = $this->request->action(true);
        if (!in_array($action, $this->noAuth)) {
            $this->auth();
        }
    }

    /**
     * 认证
     */
    protected function auth()
    {
        $token = $this->request->header('Authorization', '');
        if (empty($token)) {
            $token = $this->request->param('token', '');
        }
        $token = str_replace('Bearer ', '', $token);

        if (empty($token)) {
            $this->throwError('请先登录');
        }

        try {
            $jwtConfig = config('jwt');
            $payload = \Firebase\JWT\JWT::decode($token, new \Firebase\JWT\Key($jwtConfig['key'], $jwtConfig['algorithm']));
            $payload = (array) $payload;
            $this->adminId = $payload['sub'] ?? 0;
            $this->adminInfo = Db::name('admin_user')->where('id', $this->adminId)->find();

            if (!$this->adminInfo || $this->adminInfo['status'] != 1) {
                $this->throwError('账户已被禁用');
            }

            // 绑定到请求
            $this->request->adminInfo = $this->adminInfo;
            $this->request->adminId = $this->adminId;

            // 权限检查
            $this->checkPermission();

        } catch (\Firebase\JWT\ExpiredException $e) {
            $this->throwError('登录已过期，请重新登录');
        } catch (\Exception $e) {
            $this->throwError('身份验证失败');
        }
    }

    /**
     * 权限检查：基于角色的控制器访问控制
     */
    protected function checkPermission()
    {
        $roleId = $this->adminInfo['role_id'] ?? 0;
        if ($roleId == 1) return; // 超管跳过所有检查

        $controller = $this->request->controller();

        // 通过角色 code 获取该角色的权限定义
        $roleInfo = Db::name('role')->where('id', $roleId)->find();
        if (empty($roleInfo)) $this->throwError('角色不存在');

        // 角色对应的可访问控制器白名单
        $allowed = $this->getRolePermissions($roleInfo['code']);

        // 全控角色直接放行
        if ($allowed === '*') return;

        if (!in_array($controller, $allowed)) {
            $this->throwError('无权限访问此模块');
        }

        // 对于小区级角色，自动注入 community_id 范围
        if ($roleId >= 3 && !empty($this->adminInfo['community_ids'])) {
            $this->request->boundCommunityIds = array_filter(array_map('intval', explode(',', $this->adminInfo['community_ids'])));
        }
    }

    /**
     * 获取角色可访问的控制器列表
     */
    private function getRolePermissions($code)
    {
        // 公共模块：所有角色通用
        $common = ['Profile', 'Dashboard', 'Upload'];

        $maps = [
            'admin'     => '*',
            'manager'   => array_merge($common, [
                'Community', 'Building', 'Room',
                'Owner', 'Family', 'Staff', 'Attendance', 'Schedule', 'Salary',
                'ChargeItem', 'Bill', 'Payment', 'PaymentConfig', 'Meter', 'Arrears', 'Finance',
                'Complaint', 'RepairOrder', 'RepairWorker',
                'Visitor', 'PatrolRoute', 'PatrolRecord', 'AccessCard', 'ParkingRecord', 'ParkingSpace',
                'Equipment', 'EquipmentMaintain', 'Device', 'DeviceEvent', 'Elevator', 'ElevatorFault', 'ElevatorInspection',
                'Purchase', 'Supplier', 'Contract',
                'Notice', 'Activity', 'Vote', 'Evaluation', 'Print', 'PrintTemplate', 'PrintLog',
                'Sms', 'SmsCode', 'SmsTemplate',
                'Vehicle', 'Deposit', 'Invoice', 'InvoiceInfo', 'UnifiedPayment',
                'ParkingFeeRule', 'ParkingPayment', 'Notification',
                'LeaseProperty', 'LeaseTenant', 'LeaseContract', 'LeasePayment', 'LeaseTermination',
                'PushDevice', 'SseEvent', 'ServiceVendor',
                'WechatConfig', 'WechatUser', 'WechatMpFan', 'WechatMpTemplate', 'WechatTemplate',
                'BillDunning', 'Message', 'SmsLog', 'CommunityPaymentConfig', 'CommunityWechatConfig',
            ]),
            'service'   => array_merge($common, [
                'Complaint', 'RepairOrder', 'RepairWorker', 'Owner', 'Room', 'Building',
            ]),
            'finance'   => array_merge($common, [
                'Bill', 'Payment', 'PaymentConfig', 'ChargeItem', 'Meter', 'Arrears', 'Finance',
                'Owner', 'Room', 'Building',
            ]),
            'security'  => array_merge($common, [
                'Visitor', 'PatrolRoute', 'PatrolRecord', 'AccessCard', 'ParkingRecord', 'ParkingSpace', 'Vehicle',
                'Owner', 'Room', 'Building',
            ]),
            'engineer'  => array_merge($common, [
                'Equipment', 'EquipmentMaintain', 'Purchase', 'Supplier', 'Contract',
                'Owner', 'Room', 'Building',
            ]),
        ];

        return $maps[$code] ?? $common;
    }
}
