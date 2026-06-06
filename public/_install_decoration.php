<?php
/**
 * 装修管理模块一键安装脚本
 * 访问: http://www.hbdxm.com/_install_decoration.php
 * 执行完后请删除此文件
 */

$host = '127.0.0.1';
$user = 'root';
$pass = 'cxdxfx12';
$db   = 'dasheng';
$prefix = 'ds_';

echo '<h2>装修管理模块安装</h2>';
echo '<pre>';

try {
    $pdo = new PDO("mysql:host={$host};dbname={$db};charset=utf8mb4", $user, $pass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
    ]);

    // 先检查表是否存在
    $tables = ['decoration_apply', 'decoration_worker', 'decoration_inspect', 'decoration_violation'];
    foreach ($tables as $t) {
        $stmt = $pdo->query("SHOW TABLES LIKE '{$prefix}{$t}'");
        $exists = $stmt->rowCount() > 0;
        echo ($exists ? '✅' : '⚠️ 需创建') . " 表 {$prefix}{$t}" . ($exists ? ' (已存在)' : '') . "\n";
    }
    echo "\n";

    // 1. 装修申请表
    $pdo->exec("CREATE TABLE IF NOT EXISTS `{$prefix}decoration_apply` (
      `id` int(11) NOT NULL AUTO_INCREMENT,
      `community_id` int(11) NOT NULL COMMENT '小区ID',
      `room_id` int(11) NOT NULL COMMENT '房间ID',
      `owner_id` int(11) NOT NULL COMMENT '业主ID',
      `apply_no` varchar(32) NOT NULL COMMENT '申请编号(DSZ开头)',
      `company_name` varchar(100) DEFAULT '' COMMENT '装修公司名称',
      `company_phone` varchar(20) DEFAULT '' COMMENT '公司联系电话',
      `leader_name` varchar(50) DEFAULT '' COMMENT '施工负责人',
      `leader_phone` varchar(20) DEFAULT '' COMMENT '负责人电话',
      `start_date` date DEFAULT NULL COMMENT '计划开工日期',
      `end_date` date DEFAULT NULL COMMENT '计划竣工日期',
      `content` text COMMENT '装修内容描述',
      `decoration_type` varchar(30) DEFAULT '' COMMENT '装修类型',
      `drawing_urls` text COMMENT '施工图纸(JSON数组)',
      `status` tinyint(1) NOT NULL DEFAULT '0' COMMENT '0待审核 1通过待缴费 2施工中 3待验收 4已完成 5驳回 6取消',
      `audit_remark` varchar(500) DEFAULT '' COMMENT '审核意见',
      `audit_time` datetime DEFAULT NULL COMMENT '审核时间',
      `audit_admin_id` int(11) DEFAULT '0' COMMENT '审核人',
      `deposit_amount` decimal(10,2) DEFAULT '0.00' COMMENT '装修押金',
      `manage_fee` decimal(10,2) DEFAULT '0.00' COMMENT '装修管理费',
      `trash_fee` decimal(10,2) DEFAULT '0.00' COMMENT '垃圾清运费',
      `other_fee` decimal(10,2) DEFAULT '0.00' COMMENT '其他费用',
      `total_fee` decimal(10,2) DEFAULT '0.00' COMMENT '费用合计',
      `paid_amount` decimal(10,2) DEFAULT '0.00' COMMENT '已缴金额',
      `paid_time` datetime DEFAULT NULL COMMENT '缴费时间',
      `refund_amount` decimal(10,2) DEFAULT '0.00' COMMENT '已退押金',
      `refund_time` datetime DEFAULT NULL COMMENT '退款时间',
      `accept_result` varchar(500) DEFAULT '' COMMENT '验收结论',
      `accept_time` datetime DEFAULT NULL COMMENT '验收时间',
      `accept_admin_id` int(11) DEFAULT '0' COMMENT '验收人',
      `remark` varchar(500) DEFAULT '' COMMENT '备注',
      `create_time` datetime DEFAULT NULL,
      `update_time` datetime DEFAULT NULL,
      `delete_time` datetime DEFAULT NULL,
      PRIMARY KEY (`id`),
      KEY `idx_community` (`community_id`),
      KEY `idx_room` (`room_id`),
      KEY `idx_owner` (`owner_id`),
      KEY `idx_status` (`status`),
      KEY `idx_apply_no` (`apply_no`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='装修申请';");
    echo "✅ 表 {$prefix}decoration_apply 创建/确认成功\n";

    // 2. 施工人员表
    $pdo->exec("CREATE TABLE IF NOT EXISTS `{$prefix}decoration_worker` (
      `id` int(11) NOT NULL AUTO_INCREMENT,
      `apply_id` int(11) NOT NULL COMMENT '装修申请ID',
      `name` varchar(50) NOT NULL COMMENT '姓名',
      `id_card` varchar(18) DEFAULT '' COMMENT '身份证号',
      `phone` varchar(20) DEFAULT '' COMMENT '手机号',
      `job_type` varchar(30) DEFAULT '' COMMENT '工种',
      `card_no` varchar(30) DEFAULT '' COMMENT '出入证编号',
      `card_issue_date` date DEFAULT NULL COMMENT '发证日期',
      `card_expire_date` date DEFAULT NULL COMMENT '有效期至',
      `photo_url` varchar(255) DEFAULT '' COMMENT '照片URL',
      `create_time` datetime DEFAULT NULL,
      `delete_time` datetime DEFAULT NULL,
      PRIMARY KEY (`id`),
      KEY `idx_apply` (`apply_id`),
      KEY `idx_idcard` (`id_card`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='装修施工人员';");
    echo "✅ 表 {$prefix}decoration_worker 创建/确认成功\n";

    // 3. 巡查记录表
    $pdo->exec("CREATE TABLE IF NOT EXISTS `{$prefix}decoration_inspect` (
      `id` int(11) NOT NULL AUTO_INCREMENT,
      `apply_id` int(11) NOT NULL COMMENT '装修申请ID',
      `community_id` int(11) NOT NULL COMMENT '小区ID',
      `inspector_id` int(11) NOT NULL COMMENT '巡查人ID',
      `inspector_name` varchar(50) DEFAULT '' COMMENT '巡查人姓名',
      `inspect_time` datetime DEFAULT NULL COMMENT '巡查时间',
      `result` tinyint(1) NOT NULL DEFAULT '0' COMMENT '0正常 1异常',
      `content` varchar(500) DEFAULT '' COMMENT '巡查内容',
      `photos` text COMMENT '现场照片(JSON数组)',
      `create_time` datetime DEFAULT NULL,
      `delete_time` datetime DEFAULT NULL,
      PRIMARY KEY (`id`),
      KEY `idx_apply` (`apply_id`),
      KEY `idx_community` (`community_id`),
      KEY `idx_inspector` (`inspector_id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='装修巡查记录';");
    echo "✅ 表 {$prefix}decoration_inspect 创建/确认成功\n";

    // 4. 违规记录表
    $pdo->exec("CREATE TABLE IF NOT EXISTS `{$prefix}decoration_violation` (
      `id` int(11) NOT NULL AUTO_INCREMENT,
      `apply_id` int(11) NOT NULL COMMENT '装修申请ID',
      `community_id` int(11) NOT NULL COMMENT '小区ID',
      `violation_type` varchar(50) NOT NULL COMMENT '违规类型',
      `description` varchar(500) NOT NULL COMMENT '违规描述',
      `penalty_amount` decimal(10,2) DEFAULT '0.00' COMMENT '罚金',
      `rectify_deadline` date DEFAULT NULL COMMENT '整改截止日期',
      `rectify_result` varchar(500) DEFAULT '' COMMENT '整改结果',
      `status` tinyint(1) NOT NULL DEFAULT '0' COMMENT '0待整改 1已整改 2已扣款',
      `photos` text COMMENT '照片(JSON数组)',
      `create_admin_id` int(11) DEFAULT '0' COMMENT '登记人',
      `create_time` datetime DEFAULT NULL,
      `update_time` datetime DEFAULT NULL,
      `delete_time` datetime DEFAULT NULL,
      PRIMARY KEY (`id`),
      KEY `idx_apply` (`apply_id`),
      KEY `idx_community` (`community_id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='装修违规记录';");
    echo "✅ 表 {$prefix}decoration_violation 创建/确认成功\n";

    // 5. 菜单数据（使用 INSERT IGNORE 避免重复）
    $maxId = $pdo->query("SELECT COALESCE(MAX(id),0)+1 FROM {$prefix}menu")->fetchColumn();
    echo "\n📌 当前菜单最大ID: " . ((int)$maxId - 1) . ", 使用 ID 200-204\n";

    $pdo->exec("INSERT IGNORE INTO `{$prefix}menu` (`id`, `parent_id`, `name`, `icon`, `route`, `permission`, `type`, `sort`, `status`) VALUES
    (200, 0, '装修管理', 'SetUp', '/decoration', '', 1, 45, 1),
    (201, 200, '装修申请', 'Document', '/decoration/apply', 'decoration:apply', 1, 1, 1),
    (202, 200, '施工巡查', 'Van', '/decoration/inspect', 'decoration:inspect', 1, 2, 1),
    (203, 200, '违规记录', 'Warning', '/decoration/violation', 'decoration:violation', 1, 3, 1),
    (204, 200, '施工人员', 'User', '/decoration/worker', 'decoration:worker', 1, 4, 1);");
    echo "✅ 菜单 (200-204) 创建/确认成功\n";

    // 6. 角色权限分配
    $roleMenus = [
        3 => [200, 201, 204],        // 客服/管家: 装修申请+施工人员
        4 => [200, 202, 203],        // 安保: 巡查+违规
        5 => [200, 201],             // 财务: 装修申请(收费)
        6 => [200, 201, 202, 203, 204], // 工程部: 全部
    ];

    $inserted = 0;
    $insert = $pdo->prepare("INSERT IGNORE INTO `{$prefix}role_menu` (`role_id`, `menu_id`) VALUES (?, ?)");
    foreach ($roleMenus as $roleId => $menuIds) {
        foreach ($menuIds as $menuId) {
            $insert->execute([$roleId, $menuId]);
            $inserted++;
        }
    }
    echo "✅ 角色权限分配完成 ({$inserted} 条记录)\n";

    echo "\n<h2>✅ 装修管理模块安装完成！</h2>";
    echo '<p style="color:red"><b>⚠️ 请立即删除此文件：_install_decoration.php</b></p>';

    // 检查：确认权限映射表是否已补全
    echo '<hr><h3>📋 当前装修相关菜单状态：</h3>';
    $menus = $pdo->query("SELECT id, name, permission, route, status FROM {$prefix}menu WHERE id BETWEEN 200 AND 204")->fetchAll(PDO::FETCH_ASSOC);
    foreach ($menus as $m) {
        echo "  id={$m['id']} {$m['name']} (route={$m['route']}, status={$m['status']})\n";
    }

} catch (PDOException $e) {
    echo "<h2>❌ 安装失败</h2>";
    echo "<pre>" . $e->getMessage() . "</pre>";
}

echo '</pre>';
