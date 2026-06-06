<?php
/**
 * 角色菜单权限补全迁移脚本
 * 为 role_id=2~7 补全 ds_role_menu 数据
 * 
 * 用法：
 *   D:\phpstudy_pro\Extensions\php\php8.0.2nts\php.exe D:\phpstudy_pro\Extensions\php\migrate_role_menu.php
 * 
 * 或在项目目录使用 ThinkPHP 命令行：
 *   php think run 启动后执行
 */

// 直接模式：使用 PDO 连接数据库
$db = new PDO(
    'mysql:host=127.0.0.1;port=3306;dbname=dasheng;charset=utf8mb4',
    'root',
    'cxdxfx12',
    [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC]
);

echo "=== 角色菜单权限补全 ===\n\n";

// 先检查当前 ds_role_menu 分布
$stmt = $db->query("SELECT role_id, COUNT(*) as cnt FROM ds_role_menu GROUP BY role_id ORDER BY role_id");
$existing = $stmt->fetchAll();
echo "当前 ds_role_menu 分布:\n";
foreach ($existing as $r) {
    echo "  role_id={$r['role_id']}: {$r['cnt']} 条菜单\n";
}
echo "\n";

// 角色-菜单映射
$roleMenuMap = [
    // 系统管理员(2): 全部菜单 1-52
    2 => array_merge(
        range(1, 52)
    ),
    // 项目经理(3): 排除系统管理子菜单(2-6)和小区管理(8)
    3 => [
        7, 9, 10, 11, 12, 13,
        14, 15, 16, 17, 18, 19,
        20, 21, 22,
        23, 24, 25, 26,
        27, 28, 29, 30,
        31, 32,
        33, 34, 35,
        36, 37,
        38, 39, 40,
        41, 42,
        43, 44, 45, 46, 47,
        48, 49, 50, 51, 52,
    ],
    // 客服主管(4): 房产+业主+报修+投诉+数据概览
    4 => [7, 9, 10, 11, 12, 20, 21, 22, 36, 37, 41, 42],
    // 财务专员(5): 房产+业主+收费+数据概览
    5 => [7, 9, 10, 11, 12, 14, 15, 16, 17, 18, 19, 41, 42],
    // 安保主管(6): 房产+业主+安保+停车+数据概览
    6 => [7, 9, 10, 11, 12, 23, 24, 25, 26, 27, 28, 29, 30, 41, 42],
    // 工程主管(7): 房产+业主+设备+供应商+数据概览
    7 => [7, 9, 10, 11, 12, 33, 34, 35, 48, 49, 50, 51, 52, 41, 42],
];

$inserted = 0;
$skipped = 0;

$db->beginTransaction();
try {
    foreach ($roleMenuMap as $roleId => $menuIds) {
        // 检查该角色是否已有菜单分配
        $existingIds = $db->query(
            "SELECT menu_id FROM ds_role_menu WHERE role_id = $roleId"
        )->fetchAll(PDO::FETCH_COLUMN);

        if (count($existingIds) >= count($menuIds)) {
            echo "  role_id={$roleId}: 已有 " . count($existingIds) . " 条记录，跳过\n";
            $skipped++;
            continue;
        }

        $toInsert = array_diff($menuIds, $existingIds);
        $insertStmt = $db->prepare("INSERT INTO ds_role_menu (role_id, menu_id) VALUES (?, ?)");
        foreach ($toInsert as $menuId) {
            $insertStmt->execute([$roleId, $menuId]);
            $inserted++;
        }
        echo "  role_id={$roleId}: 插入 " . count($toInsert) . " 条记录\n";
    }

    $db->commit();
    echo "\n=== 完成 ===\n";
    echo "新插入: {$inserted} 条\n";
    echo "跳过:   {$skipped} 个角色（已有数据）\n";

} catch (Exception $e) {
    $db->rollBack();
    echo "\n错误: " . $e->getMessage() . "\n";
    exit(1);
}

// 最终验证
echo "\n最终 ds_role_menu 分布:\n";
$stmt = $db->query("SELECT r.id, r.name, r.code, COUNT(rm.menu_id) as cnt 
    FROM ds_role r 
    LEFT JOIN ds_role_menu rm ON r.id = rm.role_id 
    GROUP BY r.id ORDER BY r.id");
foreach ($stmt->fetchAll() as $r) {
    echo "  [{$r['id']}] {$r['name']} ({$r['code']}): {$r['cnt']} 条菜单\n";
}

echo "\n关键验证 - 小区管理菜单(menu_id=8)分配情况:\n";
$stmt = $db->query("SELECT r.id, r.name, r.code FROM ds_role r 
    INNER JOIN ds_role_menu rm ON r.id = rm.role_id AND rm.menu_id = 8
    ORDER BY r.id");
foreach ($stmt->fetchAll() as $r) {
    echo "  ✅ [{$r['id']}] {$r['name']} ({$r['code']})\n";
}
$stmt = $db->query("SELECT r.id, r.name, r.code FROM ds_role r 
    WHERE r.id NOT IN (SELECT role_id FROM ds_role_menu WHERE menu_id = 8)
    AND r.id BETWEEN 3 AND 7
    ORDER BY r.id");
foreach ($stmt->fetchAll() as $r) {
    echo "  ❌ [{$r['id']}] {$r['name']} ({$r['code']}) - 已排除，正确\n";
}
