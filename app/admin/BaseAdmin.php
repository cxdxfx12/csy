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
        } catch (\think\exception\HttpResponseException $e) {
            throw $e; // 透传业务层抛出的异常，不掩盖真实错误信息
        } catch (\Exception $e) {
            $this->throwError('身份验证失败: ' . $e->getMessage());
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
            $this->throwError('无权限访问此模块 [controller=' . $controller . ' role=' . $roleInfo['code'] . ']');
        }

        // 对于小区级角色（manager及以上），自动注入 community_id 范围
        if ($roleId >= 2 && !empty($this->adminInfo['community_ids'])) {
            $this->request->boundCommunityIds = array_filter(array_map('intval', explode(',', $this->adminInfo['community_ids'])));
        }
    }

    /**
     * 获取小区过滤条件，用于多小区数据隔离
     * @param string $field 数据库字段名，如 'a.community_id' 或 'community_id'
     * @return array 返回空数组(超管无限制) 或 包含 in 条件的数组
     */
    protected function getCommunityFilter(string $field = 'community_id'): array
    {
        $roleId = $this->adminInfo['role_id'] ?? 0;
        $boundIds = $this->request->boundCommunityIds ?? null;

        // 超管：不过滤
        if ($roleId == 1 || empty($boundIds)) {
            return [];
        }

        return [[$field, 'in', $boundIds]];
    }

    /**
     * 获取前端传入的小区ID（结合权限校验）
     * 超管可传任意小区ID；非超管只能在绑定的小区内筛选
     * @return int|null 返回小区ID，null表示不过滤（超管且未传值）
     */
    protected function getFilteredCommunityId(): ?int
    {
        $roleId = $this->adminInfo['role_id'] ?? 0;
        $boundIds = $this->request->boundCommunityIds ?? null;
        $communityId = (int)$this->request->param('community_id', 0);

        // 超管：传什么用什么
        if ($roleId == 1) {
            return $communityId > 0 ? $communityId : null;
        }

        // 非超管且有绑定小区
        if (!empty($boundIds)) {
            if ($communityId > 0 && in_array($communityId, $boundIds)) {
                return $communityId; // 在绑定范围内，允许精确筛选
            }
            return -1; // 返回-1表示需要返回所有绑定小区（用 in 条件）
        }

        return $communityId > 0 ? $communityId : null;
    }

    /**
     * 校验写操作的社区访问权限
     * 超管不受限；非超管只能在绑定小区范围内操作
     * @param int|mixed $communityId 待操作的社区ID
     */
    protected function validateCommunityAccess($communityId): void
    {
        $communityId = intval($communityId);
        if ($communityId <= 0) return; // 未传 community_id 跳过（添加时前端必传）
        $roleId = $this->adminInfo['role_id'] ?? 0;
        if ($roleId == 1) return; // 超管不受限
        $boundIds = $this->request->boundCommunityIds ?? [];
        if (empty($boundIds)) return; // 未绑定小区则不过滤
        if (!in_array($communityId, $boundIds)) {
            $this->throwError('无权限操作该小区数据');
        }
    }

    /**
     * 获取角色可访问的控制器列表
     * 预定义角色(code→硬编码列表) + 自定义角色(code→读取 ds_role_menu 推导)
     */
    protected function getRolePermissions($code)
    {
        // 公共模块：所有角色通用（Login 提供 info/logout 等基础接口）
        $common = ['Profile', 'Dashboard', 'Upload', 'Login'];

        $maps = [
            'admin'     => '*',
            'manager'   => array_merge($common, [
                'Building', 'Room',
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

        if (isset($maps[$code])) return $maps[$code];

        // 自定义角色：根据 ds_role_menu + ds_menu.permission 推导控制器列表
        return $this->getCustomRolePermissions($common);
    }

    /**
     * 根据当前角色的 ds_role_menu 记录，推导可访问的控制器
     */
    private function getCustomRolePermissions($common): array
    {
        $roleId = $this->adminInfo['role_id'] ?? 0;
        if ($roleId <= 0) return $common;

        // ds_menu.permission → 控制器名转换表
        static $permMap = null;
        if ($permMap === null) {
            $permMap = $this->buildPermissionMap();
        }

        $menuIds = Db::name('role_menu')->where('role_id', $roleId)->column('menu_id');
        if (empty($menuIds)) return $common;

        $permissions = Db::name('menu')
            ->whereIn('id', $menuIds)
            ->where('status', 1)
            ->column('permission');

        $controllers = $common;
        foreach ($permissions as $perm) {
            if (empty($perm)) continue;
            if (isset($permMap[$perm])) {
                $controllers[] = $permMap[$perm];
            }
        }

        return array_unique($controllers);
    }

    /**
     * 构建 permission→控制器名 映射表
     */
    protected function buildPermissionMap(): array
    {
        return [
            // 系统管理
            'system:admin'           => 'AdminUser',
            'system:role'            => 'Role',
            'system:menu'            => 'Menu',
            'system:config'          => 'Config',
            'system:log'             => 'Log',
            'system:pushDevice'      => 'PushDevice',
            'system:sseEvent'        => 'SseEvent',
            'system:serviceVendor'   => 'ServiceVendor',
            // 房产管理
            'property:community'     => 'Community',
            'property:building'      => 'Building',
            'property:room'          => 'Room',
            // 业主管理
            'owner:index'            => 'Owner',
            'owner:family'           => 'Family',
            'owner:vote'             => 'Vote',
            'owner:activity'         => 'Activity',
            // 收费管理
            'charge:item'            => 'ChargeItem',
            'charge:bill'            => 'Bill',
            'charge:payment'         => 'Payment',
            'charge:meter'           => 'Meter',
            'charge:arrears'         => 'Arrears',
            'charge:finance'         => 'Finance',
            'charge:Deposit'         => 'Deposit',
            'charge:Invoice'         => 'Invoice',
            'charge:InvoiceInfo'     => 'InvoiceInfo',
            'charge:UnifiedPayment'  => 'UnifiedPayment',
            // 报修管理
            'repair:order'           => 'RepairOrder',
            'repair:worker'          => 'RepairWorker',
            // 安保管理
            'security:visitor'       => 'Visitor',
            'security:patrol'        => 'PatrolRecord',
            'security:patrol_route'  => 'PatrolRoute',
            'security:access_card'   => 'AccessCard',
            // 停车管理
            'parking:space'          => 'ParkingSpace',
            'parking:vehicle'        => 'Vehicle',
            'parking:record'         => 'ParkingRecord',
            // 公告
            'notice:index'           => 'Notice',
            // 设备管理
            'equipment:index'        => 'Equipment',
            'equipment:maintain'     => 'EquipmentMaintain',
            'equipment:Device'       => 'Device',
            'equipment:DeviceEvent'  => 'DeviceEvent',
            'equipment:Elevator'     => 'Elevator',
            'equipment:ElevatorFault' => 'ElevatorFault',
            'equipment:ElevatorInspection' => 'ElevatorInspection',
            // 投诉建议
            'complaint:index'        => 'Complaint',
            // 打印中心
            'print:receipt'          => 'PrintTemplate',
            'print:notice'           => 'PrintTemplate',
            'print:PrintTemplate'    => 'PrintTemplate',
            'print:PrintLog'         => 'PrintLog',
            // 物业人员
            'staff:index'            => 'Staff',
            'staff:attendance'       => 'Attendance',
            'staff:schedule'         => 'Schedule',
            'staff:salary'           => 'Salary',
            // 供应商
            'supplier:index'         => 'Supplier',
            'supplier:purchase'      => 'Purchase',
            'supplier:contract'      => 'Contract',
            'supplier:evaluation'    => 'Evaluation',
            // 租赁
            'lease:index'            => 'LeaseProperty',
            'lease:LeaseProperty'    => 'LeaseProperty',
            'lease:LeaseTenant'      => 'LeaseTenant',
            'lease:LeaseContract'    => 'LeaseContract',
            'lease:LeasePayment'     => 'LeasePayment',
            'lease:LeaseTermination' => 'LeaseTermination',
            // 短信
            'sms:SmsCode'            => 'SmsCode',
            'sms:SmsTemplate'        => 'SmsTemplate',
            'sms:config'             => 'SmsConfig',
            // 支付
            'payment:config'         => 'PaymentConfig',
            // 微信
            'wechat:user'            => 'WechatUser',
            'admin:wechat'           => 'WechatConfig',
        ];
    }
}
