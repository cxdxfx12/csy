-- ============================================================
-- 修复用户角色错配问题
-- 执行前请先备份: SELECT * FROM ds_admin_user;
-- ============================================================

-- guanli: 系统管理员(2) → 小区管理员(8)
UPDATE `ds_admin_user` SET `role_id` = 8 WHERE `id` = 2;

-- caiwu: 小区物管经理(3) → 财务专员(5)
UPDATE `ds_admin_user` SET `role_id` = 5 WHERE `id` = 3;

-- baoxiu: 客服主管(4) → 工程主管(7)
UPDATE `ds_admin_user` SET `role_id` = 7 WHERE `id` = 4;

-- anbao: 财务专员(5) → 安保主管(6)
UPDATE `ds_admin_user` SET `role_id` = 6 WHERE `id` = 5;

-- kefu: 安保主管(6) → 客服主管(4)
UPDATE `ds_admin_user` SET `role_id` = 4 WHERE `id` = 6;

-- 验证修复结果
SELECT u.id, u.username, u.nickname, u.role_id, r.name as role_name
FROM ds_admin_user u
LEFT JOIN ds_role r ON u.role_id = r.id
WHERE u.id BETWEEN 1 AND 15
ORDER BY u.id;
