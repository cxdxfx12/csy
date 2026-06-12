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

            // 校验 token 类型，防止业主/员工端 token 越权访问后台
            if (($payload['type'] ?? '') !== 'admin') {
                $this->throwError('身份验证失败');
            }

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
            $this->throwError('无权限访问此模块 [controller=' . $controller . ' role=' . $roleInfo['code'] . ']');
        }

        // 对于小区级角色（role_id>2），自动注入 community_id 范围
        if ($roleId > 2 && !empty($this->adminInfo['community_ids'])) {
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

        // 超管/系统管理员：不过滤
        if ($roleId <= 2 || empty($boundIds)) {
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

        // 超管/系统管理员：传什么用什么
        if ($roleId <= 2) {
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
        if ($roleId <= 2) return; // 超管/系统管理员不受限
        $boundIds = $this->request->boundCommunityIds ?? [];
        if (empty($boundIds)) return; // 未绑定小区则不过滤
        if (!in_array($communityId, $boundIds)) {
            $this->throwError('无权限操作该小区数据');
        }
    }

    /**
     * 验证员工ID是否在当前管理员管辖的小区内
     * 超管不受限；非超管只能操作管辖小区内的员工
     * @param int $staffId
     */
    protected function validateStaffCommunity($staffId): void
    {
        $staffId = intval($staffId);
        if ($staffId <= 0) return;
        $roleId = $this->adminInfo['role_id'] ?? 0;
        if ($roleId <= 2) return;
        $boundIds = $this->request->boundCommunityIds ?? [];
        if (empty($boundIds)) return;
        $staff = \think\facade\Db::name('staff')->where('id', $staffId)->find();
        if ($staff && !in_array(intval($staff['community_id'] ?? 0), $boundIds)) {
            $this->throwError('所选员工不在您管辖的小区范围内');
        }
    }

    /**
     * 批量验证员工ID是否在管辖范围内
     * @param array $staffIds
     */
    protected function validateStaffIdsCommunity(array $staffIds): void
    {
        $roleId = $this->adminInfo['role_id'] ?? 0;
        if ($roleId <= 2) return;
        $boundIds = $this->request->boundCommunityIds ?? [];
        if (empty($boundIds)) return;
        $invalidIds = \think\facade\Db::name('staff')
            ->whereIn('id', $staffIds)
            ->whereNotIn('community_id', $boundIds)
            ->column('id');
        if (!empty($invalidIds)) {
            $this->throwError('所选员工不在您管辖的小区范围内');
        }
    }

    /**
     * 获取角色可访问的控制器列表
     * 预定义角色(code→硬编码列表) + 自定义角色(code→读取 ds_role_menu 推导)
     */
    protected function getRolePermissions($code)
    {
        // 公共模块：所有角色通用（Login 提供 info/logout 等基础接口）
        // Community 加入公共列表，其 add/edit/delete 内部已限制仅 role_id<=2，list 按绑定小区过滤
        $common = ['Profile', 'Dashboard', 'Upload', 'Login', 'AdminBadge', 'Community', 'Search'];

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
                'PushDevice', 'SseEvent', 'ServiceVendor', 'PushConfig',
                'WechatConfig', 'WechatUser', 'WechatMpFan', 'WechatMpTemplate', 'WechatTemplate',
                'BillDunning', 'Message', 'SmsLog', 'CommunityPaymentConfig', 'CommunityWechatConfig',
                'Decoration',
                'Iot', 'AiAssistant',
            ]),
            'service'   => array_merge($common, [
                'Complaint', 'RepairOrder', 'RepairWorker', 'Owner', 'Room', 'Building', 'Decoration',
            ]),
            'finance'   => array_merge($common, [
                'Bill', 'Payment', 'PaymentConfig', 'ChargeItem', 'Meter', 'Arrears', 'Finance',
                'Owner', 'Room', 'Building',
                'Equipment', 'EquipmentMaintain', 'Device', 'DeviceEvent', 'Elevator', 'ElevatorFault', 'ElevatorInspection',
                'Decoration',
            ]),
            'security'  => array_merge($common, [
                'Visitor', 'PatrolRoute', 'PatrolRecord', 'AccessCard', 'ParkingRecord', 'ParkingSpace', 'Vehicle',
                'Owner', 'Room', 'Building', 'Decoration',
            ]),
            'engineer'  => array_merge($common, [
                'Equipment', 'EquipmentMaintain', 'Purchase', 'Supplier', 'Contract',
                'Owner', 'Room', 'Building', 'Decoration',
            ]),
        ];

        if (isset($maps[$code])) {
            $base = $maps[$code];
            // 预定义角色（role_id>2 且非 *）：也支持通过 UI/role_menu 表动态扩展权限
            // 只做加法不做减法，硬编码的白名单始终保留
            if ($base !== '*') {
                $roleId = $this->adminInfo['role_id'] ?? 0;
                if ($roleId > 2) {
                    // 传入空 common，只获取 role_menu 表中实际分配的额外控制器
                    $dynamic = $this->getCustomRolePermissions([]);
                    if (!empty($dynamic)) {
                        $base = array_values(array_unique(array_merge($base, $dynamic)));
                    }
                }
            }
            return $base;
        }

        // 自定义角色（code 不在预定义 maps 中）：完全由 ds_role_menu + ds_menu.permission 推导
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

        // 小区管理：安全保护已在 Community 控制器内部实现
        // (add/edit/delete 仅限 role_id<=2，lists/listAll 按绑定小区过滤)
        $controllers = array_values(array_unique($controllers));

        return $controllers;
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
            'system:pushConfig'      => 'PushConfig',
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
            'parking:rate'           => 'ParkingFeeRule',
            'parking:payment'        => 'ParkingPayment',
            // 短信
            'sms:log'                => 'SmsLog',
            // 收费
            'charge:dunning'         => 'BillDunning',
            // 公告/消息
            'notice:message'         => 'Message',
            'notice:notification'    => 'Notification',
            // 微信（兼容 admin:wechat:config）
            'admin:wechat:config'    => 'WechatConfig',
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
            // 数据概览
            'dashboard'              => 'Dashboard',
            'dashboard:index'        => 'Dashboard',
            // 装修管理
            'decoration:apply'       => 'Decoration',
            'decoration:inspect'     => 'Decoration',
            'decoration:violation'   => 'Decoration',
            'decoration:worker'      => 'Decoration',
            // IoT 设备管理
            'iot:device'             => 'Iot',
            // AI 助手管理
            'ai:assistant'           => 'AiAssistant',
            // 停车 - 道闸
            'parking:gateConfig'     => 'GateConfig',
            'parking:gateDevice'     => 'GateDevice',
            // 安防 - 门禁
            'security:access_config' => 'AccessConfig',
            'security:access_device' => 'AccessDevice',
            // 监控管理
            'monitoring:surveillanceConfig' => 'SurveillanceConfig',
        ];
    }
}
