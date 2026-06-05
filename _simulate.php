<?php
// 模拟 Login::info() 逻辑，检查权限过滤结果
$pdo = new PDO('mysql:host=127.0.0.1;dbname=dasheng;charset=utf8mb4', 'root', 'cxdxfx12');

// 用 role_id=3 (项目经理) 模拟，因为服务器上的 guanli 可能 role_id=3
$roleId = 3;
$role = $pdo->query("SELECT id, name, code FROM ds_role WHERE id = $roleId")->fetch(PDO::FETCH_ASSOC);
echo "=== role_id=3: {$role['name']} (code={$role['code']}) ===\n\n";

// 模拟 getRolePermissions
$code = $role['code'];
$common = ['Profile', 'Dashboard', 'Upload', 'Login'];
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
    'service'   => array_merge($common, ['Community', 'Complaint', 'RepairOrder', 'RepairWorker', 'Owner', 'Room', 'Building']),
    'finance'   => array_merge($common, ['Community', 'Bill', 'Payment', 'PaymentConfig', 'ChargeItem', 'Meter', 'Arrears', 'Finance', 'Owner', 'Room', 'Building']),
    'security'  => array_merge($common, ['Community', 'Visitor', 'PatrolRoute', 'PatrolRecord', 'AccessCard', 'ParkingRecord', 'ParkingSpace', 'Vehicle', 'Owner', 'Room', 'Building']),
    'engineer'  => array_merge($common, ['Community', 'Equipment', 'EquipmentMaintain', 'Purchase', 'Supplier', 'Contract', 'Owner', 'Room', 'Building']),
];

$allowedControllers = $maps[$code] ?? ['Dashboard', 'Profile'];
echo "allowedControllers contains: ";
foreach(['Staff','Attendance','Schedule','Salary','Supplier','Purchase','Contract','Evaluation'] as $c) {
    echo "$c:" . (in_array($c, $allowedControllers) ? '✅' : '❌') . "  ";
}
echo "\n\n";

// 模拟 buildPermissionMap
$permMap = [
    'staff:index' => 'Staff', 'staff:attendance' => 'Attendance',
    'staff:schedule' => 'Schedule', 'staff:salary' => 'Salary',
    'supplier:index' => 'Supplier', 'supplier:purchase' => 'Purchase',
    'supplier:contract' => 'Contract', 'supplier:evaluation' => 'Evaluation',
];

// 模拟菜单过滤
$menuIds = $pdo->query("SELECT menu_id FROM ds_role_menu WHERE role_id = $roleId")->fetchAll(PDO::FETCH_COLUMN);
echo "role_menu 记录数: " . count($menuIds) . "\n";
echo "包含 43-52: " . (empty(array_intersect($menuIds, range(43,52))) ? '❌ 无!' : '✅ ' . implode(',', array_intersect($menuIds, range(43,52)))) . "\n\n";

if (!empty($menuIds)) {
    $menus = $pdo->query("SELECT id, parent_id, name, route, permission FROM ds_menu WHERE id IN (" . implode(',', array_map('intval', $menuIds)) . ") AND status = 1 ORDER BY sort")->fetchAll(PDO::FETCH_ASSOC);
}

// 模拟控制器过滤
echo "=== 过滤结果 (role_id=3) ===\n";
foreach ($menus as $m) {
    if ($m['id'] >= 43 && $m['id'] <= 52) {
        $perm = $m['permission'] ?? '';
        $pid = $m['parent_id'] ?? 0;
        $keep = (empty($perm) || $pid == 0) ? '父级保留' : 
            (isset($permMap[$perm]) && in_array($permMap[$perm], $allowedControllers) ? '通过' : '❌被过滤');
        echo "id={$m['id']} {$m['name']} perm={$perm} pid={$pid} → {$keep}\n";
    }
}
