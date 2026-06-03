-- ============================================
-- 大圣物业管理系统 数据库安装脚本
-- 数据库: dasheng
-- 表前缀: ds_
-- 引擎: InnoDB
-- 字符集: utf8mb4
-- ============================================

CREATE DATABASE IF NOT EXISTS `dasheng` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE `dasheng`;

-- ============================================
-- 1. 系统管理 - 管理员表
-- ============================================
CREATE TABLE IF NOT EXISTS `ds_admin_user` (
  `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `username` varchar(50) NOT NULL DEFAULT '' COMMENT '用户名',
  `password` varchar(255) NOT NULL DEFAULT '' COMMENT '密码',
  `nickname` varchar(100) NOT NULL DEFAULT '' COMMENT '昵称',
  `avatar` varchar(255) NOT NULL DEFAULT '' COMMENT '头像',
  `email` varchar(100) NOT NULL DEFAULT '' COMMENT '邮箱',
  `phone` varchar(20) NOT NULL DEFAULT '' COMMENT '手机号',
  `role_id` int(11) UNSIGNED NOT NULL DEFAULT '0' COMMENT '角色ID',
  `community_ids` varchar(500) NOT NULL DEFAULT '' COMMENT '管理小区ID集合',
  `status` tinyint(1) NOT NULL DEFAULT '1' COMMENT '状态 1正常 0禁用',
  `last_login_time` datetime DEFAULT NULL COMMENT '最后登录时间',
  `last_login_ip` varchar(50) NOT NULL DEFAULT '' COMMENT '最后登录IP',
  `login_count` int(11) UNSIGNED NOT NULL DEFAULT '0' COMMENT '登录次数',
  `create_time` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT '创建时间',
  `update_time` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '更新时间',
  `delete_time` datetime DEFAULT NULL COMMENT '删除时间',
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_username` (`username`),
  KEY `idx_role_id` (`role_id`),
  KEY `idx_status` (`status`),
  KEY `idx_delete_time` (`delete_time`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='系统管理员';

-- ============================================
-- 2. 系统管理 - 角色表
-- ============================================
CREATE TABLE IF NOT EXISTS `ds_role` (
  `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL DEFAULT '' COMMENT '角色名称',
  `code` varchar(50) NOT NULL DEFAULT '' COMMENT '角色编码',
  `description` varchar(500) NOT NULL DEFAULT '' COMMENT '角色描述',
  `status` tinyint(1) NOT NULL DEFAULT '1' COMMENT '状态 1正常 0禁用',
  `create_time` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT '创建时间',
  `update_time` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '更新时间',
  `delete_time` datetime DEFAULT NULL COMMENT '删除时间',
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_code` (`code`),
  KEY `idx_status` (`status`),
  KEY `idx_delete_time` (`delete_time`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='角色';

-- ============================================
-- 3. 系统管理 - 菜单表
-- ============================================
CREATE TABLE IF NOT EXISTS `ds_menu` (
  `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `parent_id` int(11) UNSIGNED NOT NULL DEFAULT '0' COMMENT '父菜单ID',
  `name` varchar(100) NOT NULL DEFAULT '' COMMENT '菜单名称',
  `icon` varchar(50) NOT NULL DEFAULT '' COMMENT '图标',
  `route` varchar(200) NOT NULL DEFAULT '' COMMENT '路由地址',
  `permission` varchar(100) NOT NULL DEFAULT '' COMMENT '权限标识',
  `type` tinyint(1) NOT NULL DEFAULT '1' COMMENT '类型 1菜单 2按钮 3接口',
  `sort` int(11) NOT NULL DEFAULT '0' COMMENT '排序',
  `status` tinyint(1) NOT NULL DEFAULT '1' COMMENT '状态 1正常 0隐藏',
  `create_time` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT '创建时间',
  `update_time` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '更新时间',
  `delete_time` datetime DEFAULT NULL COMMENT '删除时间',
  PRIMARY KEY (`id`),
  KEY `idx_parent_id` (`parent_id`),
  KEY `idx_status` (`status`),
  KEY `idx_sort` (`sort`),
  KEY `idx_delete_time` (`delete_time`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='菜单';

-- ============================================
-- 4. 系统管理 - 角色菜单关联表
-- ============================================
CREATE TABLE IF NOT EXISTS `ds_role_menu` (
  `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `role_id` int(11) UNSIGNED NOT NULL DEFAULT '0' COMMENT '角色ID',
  `menu_id` int(11) UNSIGNED NOT NULL DEFAULT '0' COMMENT '菜单ID',
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_role_menu` (`role_id`,`menu_id`),
  KEY `idx_menu_id` (`menu_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='角色菜单关联';

-- ============================================
-- 5. 系统管理 - 系统配置表
-- ============================================
CREATE TABLE IF NOT EXISTS `ds_config` (
  `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `group` varchar(50) NOT NULL DEFAULT 'system' COMMENT '配置分组',
  `name` varchar(100) NOT NULL DEFAULT '' COMMENT '配置名称',
  `key` varchar(100) NOT NULL DEFAULT '' COMMENT '配置键名',
  `value` text COMMENT '配置值',
  `type` varchar(20) NOT NULL DEFAULT 'text' COMMENT '配置类型 text,textarea,image,number,select,switch',
  `options` text COMMENT '配置选项（JSON）',
  `sort` int(11) NOT NULL DEFAULT '0' COMMENT '排序',
  `remark` varchar(500) NOT NULL DEFAULT '' COMMENT '备注',
  `create_time` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT '创建时间',
  `update_time` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '更新时间',
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_key` (`key`),
  KEY `idx_group` (`group`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='系统配置';

-- ============================================
-- 6. 系统管理 - 操作日志表
-- ============================================
CREATE TABLE IF NOT EXISTS `ds_system_log` (
  `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
  `admin_id` int(11) UNSIGNED NOT NULL DEFAULT '0' COMMENT '管理员ID',
  `admin_name` varchar(100) NOT NULL DEFAULT '' COMMENT '管理员名称',
  `module` varchar(50) NOT NULL DEFAULT '' COMMENT '模块',
  `action` varchar(100) NOT NULL DEFAULT '' COMMENT '操作动作',
  `url` varchar(500) NOT NULL DEFAULT '' COMMENT '请求URL',
  `method` varchar(10) NOT NULL DEFAULT 'GET' COMMENT '请求方法',
  `params` text COMMENT '请求参数',
  `result` text COMMENT '操作结果',
  `ip` varchar(50) NOT NULL DEFAULT '' COMMENT 'IP地址',
  `user_agent` varchar(500) NOT NULL DEFAULT '' COMMENT '浏览器UA',
  `duration` int(11) NOT NULL DEFAULT '0' COMMENT '执行时长(ms)',
  `create_time` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT '操作时间',
  PRIMARY KEY (`id`),
  KEY `idx_admin_id` (`admin_id`),
  KEY `idx_module` (`module`),
  KEY `idx_action` (`action`),
  KEY `idx_create_time` (`create_time`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='操作日志';

-- ============================================
-- 7. 房产资源 - 小区表
-- ============================================
CREATE TABLE IF NOT EXISTS `ds_community` (
  `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `name` varchar(200) NOT NULL DEFAULT '' COMMENT '小区名称',
  `code` varchar(50) NOT NULL DEFAULT '' COMMENT '小区编码',
  `address` varchar(500) NOT NULL DEFAULT '' COMMENT '小区地址',
  `province` varchar(50) NOT NULL DEFAULT '' COMMENT '省',
  `city` varchar(50) NOT NULL DEFAULT '' COMMENT '市',
  `district` varchar(50) NOT NULL DEFAULT '' COMMENT '区',
  `latitude` decimal(10,7) NOT NULL DEFAULT '0.0000000' COMMENT '纬度',
  `longitude` decimal(10,7) NOT NULL DEFAULT '0.0000000' COMMENT '经度',
  `property_company` varchar(200) NOT NULL DEFAULT '' COMMENT '物业公司名称',
  `contact` varchar(50) NOT NULL DEFAULT '' COMMENT '联系人',
  `phone` varchar(20) NOT NULL DEFAULT '' COMMENT '联系电话',
  `cover_image` varchar(500) NOT NULL DEFAULT '' COMMENT '封面图片',
  `area` decimal(12,2) NOT NULL DEFAULT '0.00' COMMENT '占地面积(㎡)',
  `green_area` decimal(12,2) NOT NULL DEFAULT '0.00' COMMENT '绿化面积(㎡)',
  `building_count` int(11) NOT NULL DEFAULT '0' COMMENT '楼栋数量',
  `room_count` int(11) NOT NULL DEFAULT '0' COMMENT '房间数量',
  `owner_count` int(11) NOT NULL DEFAULT '0' COMMENT '业主数量',
  `parking_count` int(11) NOT NULL DEFAULT '0' COMMENT '车位数',
  `status` tinyint(1) NOT NULL DEFAULT '1' COMMENT '状态 1启用 0停用',
  `remark` varchar(500) NOT NULL DEFAULT '' COMMENT '备注',
  `create_time` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT '创建时间',
  `update_time` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '更新时间',
  `delete_time` datetime DEFAULT NULL COMMENT '删除时间',
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_code` (`code`),
  KEY `idx_status` (`status`),
  KEY `idx_city` (`city`),
  KEY `idx_delete_time` (`delete_time`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='小区';

-- ============================================
-- 8. 房产资源 - 楼栋表
-- ============================================
CREATE TABLE IF NOT EXISTS `ds_building` (
  `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `community_id` int(11) UNSIGNED NOT NULL DEFAULT '0' COMMENT '小区ID',
  `name` varchar(100) NOT NULL DEFAULT '' COMMENT '楼栋名称（如A栋、1号楼）',
  `unit_count` int(11) NOT NULL DEFAULT '1' COMMENT '单元数',
  `floor_count` int(11) NOT NULL DEFAULT '1' COMMENT '层数',
  `floor_rooms` int(11) NOT NULL DEFAULT '0' COMMENT '每层户数',
  `total_rooms` int(11) NOT NULL DEFAULT '0' COMMENT '总户数',
  `build_year` year DEFAULT NULL COMMENT '建造年份',
  `elevator_count` int(11) NOT NULL DEFAULT '0' COMMENT '电梯数量',
  `floor_height` decimal(5,2) NOT NULL DEFAULT '0.00' COMMENT '层高(米)',
  `sort` int(11) NOT NULL DEFAULT '0' COMMENT '排序',
  `status` tinyint(1) NOT NULL DEFAULT '1' COMMENT '状态 1正常 0停用',
  `remark` varchar(500) NOT NULL DEFAULT '' COMMENT '备注',
  `create_time` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT '创建时间',
  `update_time` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '更新时间',
  `delete_time` datetime DEFAULT NULL COMMENT '删除时间',
  PRIMARY KEY (`id`),
  KEY `idx_community_id` (`community_id`),
  KEY `idx_status` (`status`),
  KEY `idx_delete_time` (`delete_time`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='楼栋';

-- ============================================
-- 9. 房产资源 - 房间表
-- ============================================
CREATE TABLE IF NOT EXISTS `ds_room` (
  `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `community_id` int(11) UNSIGNED NOT NULL DEFAULT '0' COMMENT '小区ID',
  `building_id` int(11) UNSIGNED NOT NULL DEFAULT '0' COMMENT '楼栋ID',
  `building_name` varchar(100) NOT NULL DEFAULT '' COMMENT '楼栋名称',
  `unit` varchar(50) NOT NULL DEFAULT '' COMMENT '单元',
  `floor` int(11) NOT NULL DEFAULT '0' COMMENT '楼层',
  `room_number` varchar(100) NOT NULL DEFAULT '' COMMENT '房间编号（如101、A101）',
  `type` tinyint(1) NOT NULL DEFAULT '1' COMMENT '类型 1住宅 2商铺 3写字楼 4车位 5仓库',
  `area` decimal(10,2) NOT NULL DEFAULT '0.00' COMMENT '建筑面积(㎡)',
  `usable_area` decimal(10,2) NOT NULL DEFAULT '0.00' COMMENT '套内面积(㎡)',
  `orientation` varchar(10) NOT NULL DEFAULT '' COMMENT '朝向 东/南/西/北/南北',
  `layout` varchar(50) NOT NULL DEFAULT '' COMMENT '户型（如三室两厅）',
  `decorate_status` tinyint(1) NOT NULL DEFAULT '0' COMMENT '装修状态 0毛坯 1简装 2精装 3豪装',
  `status` tinyint(1) NOT NULL DEFAULT '1' COMMENT '状态 1未售 2已售 3已租 4自用 5空置',
  `owner_id` int(11) UNSIGNED NOT NULL DEFAULT '0' COMMENT '业主ID',
  `is_living` tinyint(1) NOT NULL DEFAULT '0' COMMENT '是否入住 0否 1是',
  `check_in_date` date DEFAULT NULL COMMENT '入住日期',
  `property_fee_rate` decimal(10,4) NOT NULL DEFAULT '0.0000' COMMENT '物业费单价(元/㎡/月)',
  `remark` varchar(500) NOT NULL DEFAULT '' COMMENT '备注',
  `create_time` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT '创建时间',
  `update_time` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '更新时间',
  `delete_time` datetime DEFAULT NULL COMMENT '删除时间',
  PRIMARY KEY (`id`),
  KEY `idx_community_id` (`community_id`),
  KEY `idx_building_id` (`building_id`),
  KEY `idx_owner_id` (`owner_id`),
  KEY `idx_status` (`status`),
  KEY `idx_room_number` (`room_number`),
  KEY `idx_delete_time` (`delete_time`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='房间';

-- ============================================
-- 10. 业主管理 - 业主表
-- ============================================
CREATE TABLE IF NOT EXISTS `ds_owner` (
  `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `community_id` int(11) UNSIGNED NOT NULL DEFAULT '0' COMMENT '小区ID',
  `realname` varchar(100) NOT NULL DEFAULT '' COMMENT '真实姓名',
  `gender` tinyint(1) NOT NULL DEFAULT '0' COMMENT '性别 0未知 1男 2女',
  `phone` varchar(20) NOT NULL DEFAULT '' COMMENT '手机号',
  `password` varchar(255) NOT NULL DEFAULT '' COMMENT '登录密码',
  `id_card` varchar(18) NOT NULL DEFAULT '' COMMENT '身份证号',
  `birthday` date DEFAULT NULL COMMENT '出生日期',
  `email` varchar(100) NOT NULL DEFAULT '' COMMENT '邮箱',
  `avatar` varchar(500) NOT NULL DEFAULT '' COMMENT '头像',
  `type` tinyint(1) NOT NULL DEFAULT '1' COMMENT '类型 1业主 2家属 3租户',
  `emergency_contact` varchar(50) NOT NULL DEFAULT '' COMMENT '紧急联系人',
  `emergency_phone` varchar(20) NOT NULL DEFAULT '' COMMENT '紧急联系电话',
  `status` tinyint(1) NOT NULL DEFAULT '1' COMMENT '状态 1正常 0禁用',
  `remark` varchar(500) NOT NULL DEFAULT '' COMMENT '备注',
  `register_time` datetime DEFAULT NULL COMMENT '注册时间',
  `last_login_time` datetime DEFAULT NULL COMMENT '最后登录时间',
  `create_time` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT '创建时间',
  `update_time` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '更新时间',
  `delete_time` datetime DEFAULT NULL COMMENT '删除时间',
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_phone` (`phone`),
  KEY `idx_community_id` (`community_id`),
  KEY `idx_id_card` (`id_card`),
  KEY `idx_status` (`status`),
  KEY `idx_delete_time` (`delete_time`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='业主';

-- ============================================
-- 11. 业主管理 - 家庭成员表
-- ============================================
CREATE TABLE IF NOT EXISTS `ds_owner_family` (
  `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `owner_id` int(11) UNSIGNED NOT NULL DEFAULT '0' COMMENT '业主ID',
  `community_id` int(11) UNSIGNED NOT NULL DEFAULT '0' COMMENT '小区ID',
  `room_id` int(11) UNSIGNED NOT NULL DEFAULT '0' COMMENT '房间ID',
  `realname` varchar(100) NOT NULL DEFAULT '' COMMENT '姓名',
  `relation` varchar(50) NOT NULL DEFAULT '' COMMENT '与业主关系',
  `gender` tinyint(1) NOT NULL DEFAULT '0' COMMENT '性别 0未知 1男 2女',
  `phone` varchar(20) NOT NULL DEFAULT '' COMMENT '手机号',
  `id_card` varchar(18) NOT NULL DEFAULT '' COMMENT '身份证号',
  `birthday` date DEFAULT NULL COMMENT '出生日期',
  `remark` varchar(500) NOT NULL DEFAULT '' COMMENT '备注',
  `create_time` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT '创建时间',
  `update_time` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '更新时间',
  `delete_time` datetime DEFAULT NULL COMMENT '删除时间',
  PRIMARY KEY (`id`),
  KEY `idx_owner_id` (`owner_id`),
  KEY `idx_community_id` (`community_id`),
  KEY `idx_room_id` (`room_id`),
  KEY `idx_delete_time` (`delete_time`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='家庭成员';

-- ============================================
-- 12. 业主管理 - 业主房间关联表
-- ============================================
CREATE TABLE IF NOT EXISTS `ds_owner_room` (
  `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `owner_id` int(11) UNSIGNED NOT NULL DEFAULT '0' COMMENT '业主ID',
  `room_id` int(11) UNSIGNED NOT NULL DEFAULT '0' COMMENT '房间ID',
  `community_id` int(11) UNSIGNED NOT NULL DEFAULT '0' COMMENT '小区ID',
  `relation` varchar(50) NOT NULL DEFAULT '业主' COMMENT '关系 业主/家属/租户',
  `is_primary` tinyint(1) NOT NULL DEFAULT '1' COMMENT '是否主要业主 1是 0否',
  `start_date` date DEFAULT NULL COMMENT '开始日期',
  `end_date` date DEFAULT NULL COMMENT '结束日期',
  `create_time` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT '创建时间',
  `update_time` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '更新时间',
  `delete_time` datetime DEFAULT NULL COMMENT '删除时间',
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_owner_room` (`owner_id`,`room_id`),
  KEY `idx_room_id` (`room_id`),
  KEY `idx_community_id` (`community_id`),
  KEY `idx_delete_time` (`delete_time`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='业主房间关联';

-- ============================================
-- 13. 收费管理 - 收费项目表
-- ============================================
CREATE TABLE IF NOT EXISTS `ds_charge_item` (
  `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `community_id` int(11) UNSIGNED NOT NULL DEFAULT '0' COMMENT '小区ID',
  `name` varchar(200) NOT NULL DEFAULT '' COMMENT '收费项目名称',
  `code` varchar(50) NOT NULL DEFAULT '' COMMENT '收费项目编码',
  `type` tinyint(1) NOT NULL DEFAULT '1' COMMENT '收费类型 1周期性收费 2固定费用 3抄表收费 4押金 5滞纳金',
  `cycle` tinyint(1) NOT NULL DEFAULT '1' COMMENT '周期 1月 2季 3半年 4年',
  `billing_mode` tinyint(1) NOT NULL DEFAULT '1' COMMENT '计费模式 1按面积 2按户 3按人数 4按用量 5固定金额',
  `unit_price` decimal(10,4) NOT NULL DEFAULT '0.0000' COMMENT '单价',
  `unit` varchar(20) NOT NULL DEFAULT '' COMMENT '单位（元/㎡/月, 元/户/月等）',
  `overdue_rate` decimal(5,4) NOT NULL DEFAULT '0.0000' COMMENT '滞纳金日费率(千分之)',
  `overdue_days` int(11) NOT NULL DEFAULT '0' COMMENT '免滞纳金天数',
  `tax_rate` decimal(5,2) NOT NULL DEFAULT '0.00' COMMENT '税率(%)',
  `status` tinyint(1) NOT NULL DEFAULT '1' COMMENT '状态 1启用 0停用',
  `sort` int(11) NOT NULL DEFAULT '0' COMMENT '排序',
  `remark` varchar(500) NOT NULL DEFAULT '' COMMENT '备注',
  `create_time` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT '创建时间',
  `update_time` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '更新时间',
  `delete_time` datetime DEFAULT NULL COMMENT '删除时间',
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_code` (`code`),
  KEY `idx_community_id` (`community_id`),
  KEY `idx_type` (`type`),
  KEY `idx_status` (`status`),
  KEY `idx_delete_time` (`delete_time`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='收费项目';

-- ============================================
-- 14. 收费管理 - 账单表
-- ============================================
CREATE TABLE IF NOT EXISTS `ds_bill` (
  `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
  `bill_no` varchar(50) NOT NULL DEFAULT '' COMMENT '账单编号',
  `community_id` int(11) UNSIGNED NOT NULL DEFAULT '0' COMMENT '小区ID',
  `owner_id` int(11) UNSIGNED NOT NULL DEFAULT '0' COMMENT '业主ID',
  `room_id` int(11) UNSIGNED NOT NULL DEFAULT '0' COMMENT '房间ID',
  `charge_item_id` int(11) UNSIGNED NOT NULL DEFAULT '0' COMMENT '收费项目ID',
  `charge_item_name` varchar(200) NOT NULL DEFAULT '' COMMENT '收费项目名称',
  `bill_period` varchar(20) NOT NULL DEFAULT '' COMMENT '账期（如2026-01）',
  `bill_year` year DEFAULT NULL COMMENT '账单年份',
  `bill_month` tinyint(2) DEFAULT NULL COMMENT '账单月份',
  `amount` decimal(12,2) NOT NULL DEFAULT '0.00' COMMENT '应收金额',
  `paid_amount` decimal(12,2) NOT NULL DEFAULT '0.00' COMMENT '已付金额',
  `discount_amount` decimal(12,2) NOT NULL DEFAULT '0.00' COMMENT '减免金额',
  `overdue_amount` decimal(12,2) NOT NULL DEFAULT '0.00' COMMENT '滞纳金',
  `total_amount` decimal(12,2) NOT NULL DEFAULT '0.00' COMMENT '总金额(含滞纳金)',
  `status` tinyint(1) NOT NULL DEFAULT '1' COMMENT '状态 1未缴 2部分缴纳 3已缴清 4已减免',
  `due_date` date DEFAULT NULL COMMENT '缴费截止日期',
  `pay_date` datetime DEFAULT NULL COMMENT '缴费完成日期',
  `remark` varchar(500) NOT NULL DEFAULT '' COMMENT '备注',
  `create_time` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT '创建时间',
  `update_time` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '更新时间',
  `delete_time` datetime DEFAULT NULL COMMENT '删除时间',
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_bill_no` (`bill_no`),
  KEY `idx_community_id` (`community_id`),
  KEY `idx_owner_id` (`owner_id`),
  KEY `idx_room_id` (`room_id`),
  KEY `idx_charge_item_id` (`charge_item_id`),
  KEY `idx_status` (`status`),
  KEY `idx_due_date` (`due_date`),
  KEY `idx_bill_period` (`bill_period`),
  KEY `idx_delete_time` (`delete_time`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='账单';

-- ============================================
-- 15. 收费管理 - 缴费记录表
-- ============================================
CREATE TABLE IF NOT EXISTS `ds_bill_payment` (
  `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
  `payment_no` varchar(50) NOT NULL DEFAULT '' COMMENT '缴费单号',
  `bill_id` bigint(20) UNSIGNED NOT NULL DEFAULT '0' COMMENT '账单ID',
  `community_id` int(11) UNSIGNED NOT NULL DEFAULT '0' COMMENT '小区ID',
  `owner_id` int(11) UNSIGNED NOT NULL DEFAULT '0' COMMENT '业主ID',
  `room_id` int(11) UNSIGNED NOT NULL DEFAULT '0' COMMENT '房间ID',
  `amount` decimal(12,2) NOT NULL DEFAULT '0.00' COMMENT '缴费金额',
  `pay_method` tinyint(1) NOT NULL DEFAULT '1' COMMENT '支付方式 1现金 2微信 3支付宝 4银行转账 5pos刷卡 6其他',
  `pay_account` varchar(100) NOT NULL DEFAULT '' COMMENT '付款账户',
  `trade_no` varchar(100) NOT NULL DEFAULT '' COMMENT '交易流水号',
  `pay_time` datetime DEFAULT NULL COMMENT '支付时间',
  `operator_id` int(11) UNSIGNED NOT NULL DEFAULT '0' COMMENT '操作员ID（物业员工）',
  `operator_type` tinyint(1) NOT NULL DEFAULT '1' COMMENT '操作员类型 1管理员 2物业员工',
  `remark` varchar(500) NOT NULL DEFAULT '' COMMENT '备注',
  `receipt_status` tinyint(1) NOT NULL DEFAULT '0' COMMENT '开票状态 0未开票 1已开票',
  `receipt_no` varchar(100) NOT NULL DEFAULT '' COMMENT '发票号',
  `create_time` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT '创建时间',
  `update_time` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '更新时间',
  `delete_time` datetime DEFAULT NULL COMMENT '删除时间',
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_payment_no` (`payment_no`),
  UNIQUE KEY `uk_trade_no` (`trade_no`),
  KEY `idx_bill_id` (`bill_id`),
  KEY `idx_community_id` (`community_id`),
  KEY `idx_owner_id` (`owner_id`),
  KEY `idx_room_id` (`room_id`),
  KEY `idx_pay_method` (`pay_method`),
  KEY `idx_pay_time` (`pay_time`),
  KEY `idx_delete_time` (`delete_time`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='缴费记录';

-- ============================================
-- 16. 收费管理 - 抄表读数表
-- ============================================
CREATE TABLE IF NOT EXISTS `ds_meter_reading` (
  `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
  `community_id` int(11) UNSIGNED NOT NULL DEFAULT '0' COMMENT '小区ID',
  `room_id` int(11) UNSIGNED NOT NULL DEFAULT '0' COMMENT '房间ID',
  `type` tinyint(1) NOT NULL DEFAULT '1' COMMENT '类型 1水表 2电表 3燃气表',
  `meter_no` varchar(100) NOT NULL DEFAULT '' COMMENT '表号',
  `previous_reading` decimal(12,2) NOT NULL DEFAULT '0.00' COMMENT '上次读数',
  `current_reading` decimal(12,2) NOT NULL DEFAULT '0.00' COMMENT '本次读数',
  `usage_amount` decimal(12,2) NOT NULL DEFAULT '0.00' COMMENT '用量',
  `reading_date` date NOT NULL COMMENT '抄表日期',
  `reading_by` varchar(100) NOT NULL DEFAULT '' COMMENT '抄表人',
  `operator_id` int(11) UNSIGNED NOT NULL DEFAULT '0' COMMENT '操作员ID',
  `photo` varchar(500) NOT NULL DEFAULT '' COMMENT '抄表照片',
  `status` tinyint(1) NOT NULL DEFAULT '1' COMMENT '状态 1正常 2异常',
  `remark` varchar(500) NOT NULL DEFAULT '' COMMENT '备注',
  `create_time` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT '创建时间',
  `update_time` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '更新时间',
  `delete_time` datetime DEFAULT NULL COMMENT '删除时间',
  PRIMARY KEY (`id`),
  KEY `idx_community_id` (`community_id`),
  KEY `idx_room_id` (`room_id`),
  KEY `idx_type` (`type`),
  KEY `idx_reading_date` (`reading_date`),
  KEY `idx_delete_time` (`delete_time`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='抄表读数';

-- ============================================
-- 17. 收费管理 - 财务流水表
-- ============================================
CREATE TABLE IF NOT EXISTS `ds_finance_flow` (
  `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
  `flow_no` varchar(50) NOT NULL DEFAULT '' COMMENT '流水编号',
  `community_id` int(11) UNSIGNED NOT NULL DEFAULT '0' COMMENT '小区ID',
  `type` tinyint(1) NOT NULL DEFAULT '1' COMMENT '类型 1收入 2支出 3退款',
  `category` varchar(50) NOT NULL DEFAULT '' COMMENT '分类',
  `amount` decimal(12,2) NOT NULL DEFAULT '0.00' COMMENT '金额',
  `balance` decimal(12,2) NOT NULL DEFAULT '0.00' COMMENT '余额',
  `source_type` varchar(50) NOT NULL DEFAULT '' COMMENT '来源类型 bill/payment/refund/other',
  `source_id` bigint(20) UNSIGNED NOT NULL DEFAULT '0' COMMENT '来源ID',
  `description` varchar(500) NOT NULL DEFAULT '' COMMENT '描述',
  `operator_id` int(11) UNSIGNED NOT NULL DEFAULT '0' COMMENT '操作员ID',
  `operator_name` varchar(100) NOT NULL DEFAULT '' COMMENT '操作员',
  `status` tinyint(1) NOT NULL DEFAULT '1' COMMENT '状态 1有效 0作废',
  `create_time` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT '创建时间',
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_flow_no` (`flow_no`),
  KEY `idx_community_id` (`community_id`),
  KEY `idx_type` (`type`),
  KEY `idx_source_type` (`source_type`),
  KEY `idx_create_time` (`create_time`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='财务流水';

-- ============================================
-- 18. 报修管理 - 报修工单表
-- ============================================
CREATE TABLE IF NOT EXISTS `ds_repair_order` (
  `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
  `order_no` varchar(50) NOT NULL DEFAULT '' COMMENT '工单编号',
  `community_id` int(11) UNSIGNED NOT NULL DEFAULT '0' COMMENT '小区ID',
  `owner_id` int(11) UNSIGNED NOT NULL DEFAULT '0' COMMENT '业主ID',
  `room_id` int(11) UNSIGNED NOT NULL DEFAULT '0' COMMENT '房间ID',
  `reporter` varchar(100) NOT NULL DEFAULT '' COMMENT '报修人',
  `reporter_phone` varchar(20) NOT NULL DEFAULT '' COMMENT '联系电话',
  `type` tinyint(1) NOT NULL DEFAULT '1' COMMENT '类型 1水 2电 3气 4门窗 5管道 6家电 7网络 8其他',
  `level` tinyint(1) NOT NULL DEFAULT '2' COMMENT '紧急程度 1普通 2紧急 3特急',
  `content` text COMMENT '报修内容',
  `images` text COMMENT '报修图片（JSON数组）',
  `source` tinyint(1) NOT NULL DEFAULT '1' COMMENT '来源 1业主端 2物业端 3电话 4现场',
  `assignee_id` int(11) UNSIGNED NOT NULL DEFAULT '0' COMMENT '维修人员ID',
  `assign_time` datetime DEFAULT NULL COMMENT '派单时间',
  `accept_time` datetime DEFAULT NULL COMMENT '接单时间',
  `finish_time` datetime DEFAULT NULL COMMENT '完成时间',
  `status` tinyint(1) NOT NULL DEFAULT '1' COMMENT '状态 1待派单 2待接单 3处理中 4待验收 5已完成 6已关闭',
  `handle_content` text COMMENT '处理内容',
  `handle_images` text COMMENT '处理图片',
  `fee` decimal(10,2) NOT NULL DEFAULT '0.00' COMMENT '维修费用',
  `fee_desc` varchar(500) NOT NULL DEFAULT '' COMMENT '费用说明',
  `rating` tinyint(1) NOT NULL DEFAULT '0' COMMENT '评分 1-5',
  `comment` varchar(500) NOT NULL DEFAULT '' COMMENT '评价',
  `remark` varchar(500) NOT NULL DEFAULT '' COMMENT '备注',
  `create_time` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT '创建时间',
  `update_time` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '更新时间',
  `delete_time` datetime DEFAULT NULL COMMENT '删除时间',
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_order_no` (`order_no`),
  KEY `idx_community_id` (`community_id`),
  KEY `idx_owner_id` (`owner_id`),
  KEY `idx_assignee_id` (`assignee_id`),
  KEY `idx_status` (`status`),
  KEY `idx_type` (`type`),
  KEY `idx_create_time` (`create_time`),
  KEY `idx_delete_time` (`delete_time`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='报修工单';

-- ============================================
-- 19. 报修管理 - 维修人员表
-- ============================================
CREATE TABLE IF NOT EXISTS `ds_repair_worker` (
  `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `community_id` int(11) UNSIGNED NOT NULL DEFAULT '0' COMMENT '小区ID',
  `name` varchar(100) NOT NULL DEFAULT '' COMMENT '姓名',
  `phone` varchar(20) NOT NULL DEFAULT '' COMMENT '手机号',
  `type` varchar(100) NOT NULL DEFAULT '' COMMENT '技能类型（水电、门窗等，逗号分隔）',
  `avatar` varchar(500) NOT NULL DEFAULT '' COMMENT '头像',
  `work_status` tinyint(1) NOT NULL DEFAULT '1' COMMENT '工作状态 1空闲 2繁忙 3休息',
  `order_count` int(11) NOT NULL DEFAULT '0' COMMENT '接单总数',
  `finish_count` int(11) NOT NULL DEFAULT '0' COMMENT '完成总数',
  `avg_rating` decimal(3,2) NOT NULL DEFAULT '0.00' COMMENT '平均评分',
  `status` tinyint(1) NOT NULL DEFAULT '1' COMMENT '状态 1在职 0离职',
  `remark` varchar(500) NOT NULL DEFAULT '' COMMENT '备注',
  `create_time` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT '创建时间',
  `update_time` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '更新时间',
  `delete_time` datetime DEFAULT NULL COMMENT '删除时间',
  PRIMARY KEY (`id`),
  KEY `idx_community_id` (`community_id`),
  KEY `idx_status` (`status`),
  KEY `idx_delete_time` (`delete_time`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='维修人员';

-- ============================================
-- 20. 安保管理 - 访客登记表
-- ============================================
CREATE TABLE IF NOT EXISTS `ds_visitor` (
  `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
  `community_id` int(11) UNSIGNED NOT NULL DEFAULT '0' COMMENT '小区ID',
  `owner_id` int(11) UNSIGNED NOT NULL DEFAULT '0' COMMENT '拜访业主ID',
  `room_id` int(11) UNSIGNED NOT NULL DEFAULT '0' COMMENT '拜访房间ID',
  `name` varchar(100) NOT NULL DEFAULT '' COMMENT '访客姓名',
  `phone` varchar(20) NOT NULL DEFAULT '' COMMENT '访客手机号',
  `id_card` varchar(18) NOT NULL DEFAULT '' COMMENT '身份证号',
  `gender` tinyint(1) NOT NULL DEFAULT '0' COMMENT '性别 0未知 1男 2女',
  `visitors` int(11) NOT NULL DEFAULT '1' COMMENT '随行人数',
  `plate_number` varchar(20) NOT NULL DEFAULT '' COMMENT '车牌号',
  `visit_reason` varchar(500) NOT NULL DEFAULT '' COMMENT '来访事由',
  `visit_time` datetime NOT NULL COMMENT '来访时间',
  `leave_time` datetime DEFAULT NULL COMMENT '离开时间',
  `photo` varchar(500) NOT NULL DEFAULT '' COMMENT '访客照片',
  `source` tinyint(1) NOT NULL DEFAULT '1' COMMENT '来源 1业主预约 2现场登记 3物业代登',
  `status` tinyint(1) NOT NULL DEFAULT '1' COMMENT '状态 1待来访 2已入场 3已离场 4已过期',
  `access_card_no` varchar(50) NOT NULL DEFAULT '' COMMENT '门禁卡号',
  `operator_id` int(11) UNSIGNED NOT NULL DEFAULT '0' COMMENT '登记人',
  `remark` varchar(500) NOT NULL DEFAULT '' COMMENT '备注',
  `create_time` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT '创建时间',
  `update_time` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '更新时间',
  `delete_time` datetime DEFAULT NULL COMMENT '删除时间',
  PRIMARY KEY (`id`),
  KEY `idx_community_id` (`community_id`),
  KEY `idx_owner_id` (`owner_id`),
  KEY `idx_room_id` (`room_id`),
  KEY `idx_status` (`status`),
  KEY `idx_visit_time` (`visit_time`),
  KEY `idx_delete_time` (`delete_time`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='访客登记';

-- ============================================
-- 21. 安保管理 - 巡更路线表
-- ============================================
CREATE TABLE IF NOT EXISTS `ds_patrol_route` (
  `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `community_id` int(11) UNSIGNED NOT NULL DEFAULT '0' COMMENT '小区ID',
  `name` varchar(200) NOT NULL DEFAULT '' COMMENT '路线名称',
  `points` text COMMENT '巡更点（JSON数组[{name,lng,lat}]）',
  `total_points` int(11) NOT NULL DEFAULT '0' COMMENT '总点数',
  `estimated_time` int(11) NOT NULL DEFAULT '0' COMMENT '预计时长(分钟)',
  `interval_hours` int(11) NOT NULL DEFAULT '4' COMMENT '巡更间隔(小时)',
  `status` tinyint(1) NOT NULL DEFAULT '1' COMMENT '状态 1启用 0停用',
  `remark` varchar(500) NOT NULL DEFAULT '' COMMENT '备注',
  `create_time` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT '创建时间',
  `update_time` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '更新时间',
  `delete_time` datetime DEFAULT NULL COMMENT '删除时间',
  PRIMARY KEY (`id`),
  KEY `idx_community_id` (`community_id`),
  KEY `idx_status` (`status`),
  KEY `idx_delete_time` (`delete_time`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='巡更路线';

-- ============================================
-- 22. 安保管理 - 巡更记录表
-- ============================================
CREATE TABLE IF NOT EXISTS `ds_patrol_record` (
  `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
  `community_id` int(11) UNSIGNED NOT NULL DEFAULT '0' COMMENT '小区ID',
  `route_id` int(11) UNSIGNED NOT NULL DEFAULT '0' COMMENT '路线ID',
  `route_name` varchar(200) NOT NULL DEFAULT '' COMMENT '路线名称',
  `point_name` varchar(200) NOT NULL DEFAULT '' COMMENT '巡更点名称',
  `point_index` int(11) NOT NULL DEFAULT '0' COMMENT '巡更点序号',
  `staff_id` int(11) UNSIGNED NOT NULL DEFAULT '0' COMMENT '巡逻人员ID',
  `staff_name` varchar(100) NOT NULL DEFAULT '' COMMENT '巡逻人员姓名',
  `check_time` datetime NOT NULL COMMENT '打卡时间',
  `latitude` decimal(10,7) NOT NULL DEFAULT '0.0000000' COMMENT '纬度',
  `longitude` decimal(10,7) NOT NULL DEFAULT '0.0000000' COMMENT '经度',
  `photo` varchar(500) NOT NULL DEFAULT '' COMMENT '现场照片',
  `result` tinyint(1) NOT NULL DEFAULT '1' COMMENT '结果 1正常 2异常',
  `remark` varchar(500) NOT NULL DEFAULT '' COMMENT '备注',
  `create_time` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT '创建时间',
  PRIMARY KEY (`id`),
  KEY `idx_community_id` (`community_id`),
  KEY `idx_route_id` (`route_id`),
  KEY `idx_staff_id` (`staff_id`),
  KEY `idx_check_time` (`check_time`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='巡更记录';

-- ============================================
-- 23. 安保管理 - 门禁卡表
-- ============================================
CREATE TABLE IF NOT EXISTS `ds_access_card` (
  `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `community_id` int(11) UNSIGNED NOT NULL DEFAULT '0' COMMENT '小区ID',
  `card_no` varchar(50) NOT NULL DEFAULT '' COMMENT '门禁卡号',
  `owner_id` int(11) UNSIGNED NOT NULL DEFAULT '0' COMMENT '持有人业主ID',
  `holder_name` varchar(100) NOT NULL DEFAULT '' COMMENT '持有人姓名',
  `holder_phone` varchar(20) NOT NULL DEFAULT '' COMMENT '持有人手机',
  `holder_type` tinyint(1) NOT NULL DEFAULT '1' COMMENT '持有人类型 1业主 2家属 3租户 4物业人员',
  `room_ids` varchar(500) NOT NULL DEFAULT '' COMMENT '可访问房间ID集合',
  `effective_date` date DEFAULT NULL COMMENT '生效日期',
  `expire_date` date DEFAULT NULL COMMENT '过期日期',
  `status` tinyint(1) NOT NULL DEFAULT '1' COMMENT '状态 1正常 0挂失 2注销',
  `remark` varchar(500) NOT NULL DEFAULT '' COMMENT '备注',
  `create_time` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT '创建时间',
  `update_time` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '更新时间',
  `delete_time` datetime DEFAULT NULL COMMENT '删除时间',
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_card_no` (`card_no`),
  KEY `idx_community_id` (`community_id`),
  KEY `idx_owner_id` (`owner_id`),
  KEY `idx_status` (`status`),
  KEY `idx_delete_time` (`delete_time`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='门禁卡';

-- ============================================
-- 24. 停车管理 - 车位表
-- ============================================
CREATE TABLE IF NOT EXISTS `ds_parking_space` (
  `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `community_id` int(11) UNSIGNED NOT NULL DEFAULT '0' COMMENT '小区ID',
  `space_no` varchar(50) NOT NULL DEFAULT '' COMMENT '车位编号',
  `type` tinyint(1) NOT NULL DEFAULT '1' COMMENT '类型 1地下 2地面 3立体车库',
  `area` decimal(8,2) NOT NULL DEFAULT '0.00' COMMENT '面积(㎡)',
  `floor` varchar(50) NOT NULL DEFAULT '' COMMENT '所在层',
  `owner_id` int(11) UNSIGNED NOT NULL DEFAULT '0' COMMENT '业主ID（归属）',
  `status` tinyint(1) NOT NULL DEFAULT '1' COMMENT '状态 1空闲 2已售 3已租 4已用',
  `monthly_fee` decimal(10,2) NOT NULL DEFAULT '0.00' COMMENT '月租费',
  `start_date` date DEFAULT NULL COMMENT '起租日期',
  `end_date` date DEFAULT NULL COMMENT '到期日期',
  `remark` varchar(500) NOT NULL DEFAULT '' COMMENT '备注',
  `create_time` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT '创建时间',
  `update_time` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '更新时间',
  `delete_time` datetime DEFAULT NULL COMMENT '删除时间',
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_community_space_no` (`community_id`,`space_no`),
  KEY `idx_owner_id` (`owner_id`),
  KEY `idx_status` (`status`),
  KEY `idx_delete_time` (`delete_time`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='车位';

-- ============================================
-- 25. 停车管理 - 车辆表
-- ============================================
CREATE TABLE IF NOT EXISTS `ds_vehicle` (
  `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `community_id` int(11) UNSIGNED NOT NULL DEFAULT '0' COMMENT '小区ID',
  `owner_id` int(11) UNSIGNED NOT NULL DEFAULT '0' COMMENT '业主ID',
  `plate_number` varchar(20) NOT NULL DEFAULT '' COMMENT '车牌号',
  `brand` varchar(100) NOT NULL DEFAULT '' COMMENT '品牌',
  `model` varchar(100) NOT NULL DEFAULT '' COMMENT '车型',
  `color` varchar(20) NOT NULL DEFAULT '' COMMENT '颜色',
  `vehicle_type` tinyint(1) NOT NULL DEFAULT '1' COMMENT '车辆类型 1轿车 2SUV 3面包车 4货车 5新能源',
  `parking_space_id` int(11) UNSIGNED NOT NULL DEFAULT '0' COMMENT '绑定车位ID',
  `status` tinyint(1) NOT NULL DEFAULT '1' COMMENT '状态 1正常 0注销',
  `remark` varchar(500) NOT NULL DEFAULT '' COMMENT '备注',
  `create_time` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT '创建时间',
  `update_time` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '更新时间',
  `delete_time` datetime DEFAULT NULL COMMENT '删除时间',
  PRIMARY KEY (`id`),
  KEY `idx_community_id` (`community_id`),
  KEY `idx_owner_id` (`owner_id`),
  KEY `idx_plate_number` (`plate_number`),
  KEY `idx_parking_space_id` (`parking_space_id`),
  KEY `idx_delete_time` (`delete_time`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='车辆';

-- ============================================
-- 26. 停车管理 - 停车记录表
-- ============================================
CREATE TABLE IF NOT EXISTS `ds_parking_record` (
  `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
  `community_id` int(11) UNSIGNED NOT NULL DEFAULT '0' COMMENT '小区ID',
  `plate_number` varchar(20) NOT NULL DEFAULT '' COMMENT '车牌号',
  `space_id` int(11) UNSIGNED NOT NULL DEFAULT '0' COMMENT '车位ID',
  `enter_time` datetime NOT NULL COMMENT '入场时间',
  `exit_time` datetime DEFAULT NULL COMMENT '出场时间',
  `duration` int(11) NOT NULL DEFAULT '0' COMMENT '停车时长(分钟)',
  `fee` decimal(10,2) NOT NULL DEFAULT '0.00' COMMENT '停车费用',
  `pay_status` tinyint(1) NOT NULL DEFAULT '0' COMMENT '缴费状态 0未缴 1已缴',
  `pay_time` datetime DEFAULT NULL COMMENT '缴费时间',
  `enter_image` varchar(500) NOT NULL DEFAULT '' COMMENT '入场抓拍',
  `exit_image` varchar(500) NOT NULL DEFAULT '' COMMENT '出场抓拍',
  `operator_id` int(11) UNSIGNED NOT NULL DEFAULT '0' COMMENT '操作员ID',
  `remark` varchar(500) NOT NULL DEFAULT '' COMMENT '备注',
  `create_time` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT '创建时间',
  `update_time` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '更新时间',
  `delete_time` datetime DEFAULT NULL COMMENT '删除时间',
  PRIMARY KEY (`id`),
  KEY `idx_community_id` (`community_id`),
  KEY `idx_plate_number` (`plate_number`),
  KEY `idx_space_id` (`space_id`),
  KEY `idx_enter_time` (`enter_time`),
  KEY `idx_pay_status` (`pay_status`),
  KEY `idx_delete_time` (`delete_time`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='停车记录';

-- ============================================
-- 27. 公告通知表
-- ============================================
CREATE TABLE IF NOT EXISTS `ds_notice` (
  `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `community_id` int(11) UNSIGNED NOT NULL DEFAULT '0' COMMENT '小区ID（0为全局公告）',
  `title` varchar(200) NOT NULL DEFAULT '' COMMENT '公告标题',
  `content` longtext COMMENT '公告内容',
  `type` tinyint(1) NOT NULL DEFAULT '1' COMMENT '类型 1小区公告 2通知 3温馨提示 4活动 5其他',
  `level` tinyint(1) NOT NULL DEFAULT '1' COMMENT '紧急程度 1普通 2重要 3紧急',
  `cover_image` varchar(500) NOT NULL DEFAULT '' COMMENT '封面图',
  `attachments` text COMMENT '附件（JSON）',
  `published_by` varchar(100) NOT NULL DEFAULT '' COMMENT '发布人',
  `publish_time` datetime DEFAULT NULL COMMENT '发布时间',
  `top_status` tinyint(1) NOT NULL DEFAULT '0' COMMENT '置顶 0否 1是',
  `top_expire` datetime DEFAULT NULL COMMENT '置顶过期时间',
  `status` tinyint(1) NOT NULL DEFAULT '1' COMMENT '状态 1草稿 2已发布 3已撤回',
  `read_count` int(11) NOT NULL DEFAULT '0' COMMENT '阅读数',
  `create_time` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT '创建时间',
  `update_time` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '更新时间',
  `delete_time` datetime DEFAULT NULL COMMENT '删除时间',
  PRIMARY KEY (`id`),
  KEY `idx_community_id` (`community_id`),
  KEY `idx_type` (`type`),
  KEY `idx_status` (`status`),
  KEY `idx_top_status` (`top_status`),
  KEY `idx_publish_time` (`publish_time`),
  KEY `idx_delete_time` (`delete_time`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='公告通知';

-- ============================================
-- 28. 设备管理 - 设备台账表
-- ============================================
CREATE TABLE IF NOT EXISTS `ds_equipment` (
  `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `community_id` int(11) UNSIGNED NOT NULL DEFAULT '0' COMMENT '小区ID',
  `category` tinyint(1) NOT NULL DEFAULT '1' COMMENT '类别 1电梯 2消防 3安防 4照明 5给排水 6供电 7燃气 8空调 9其他',
  `name` varchar(200) NOT NULL DEFAULT '' COMMENT '设备名称',
  `code` varchar(50) NOT NULL DEFAULT '' COMMENT '设备编号',
  `model` varchar(100) NOT NULL DEFAULT '' COMMENT '型号',
  `brand` varchar(100) NOT NULL DEFAULT '' COMMENT '品牌',
  `manufacturer` varchar(200) NOT NULL DEFAULT '' COMMENT '生产厂家',
  `spec` varchar(200) NOT NULL DEFAULT '' COMMENT '规格参数',
  `location` varchar(200) NOT NULL DEFAULT '' COMMENT '安装位置',
  `purchase_date` date DEFAULT NULL COMMENT '购买日期',
  `install_date` date DEFAULT NULL COMMENT '安装日期',
  `warranty_expire` date DEFAULT NULL COMMENT '保修截止',
  `service_life` int(11) NOT NULL DEFAULT '0' COMMENT '使用寿命(年)',
  `maintain_cycle` int(11) NOT NULL DEFAULT '0' COMMENT '维保周期(天)',
  `last_maintain_date` date DEFAULT NULL COMMENT '上次维保日期',
  `next_maintain_date` date DEFAULT NULL COMMENT '下次维保日期',
  `supplier` varchar(200) NOT NULL DEFAULT '' COMMENT '供应商',
  `supplier_phone` varchar(20) NOT NULL DEFAULT '' COMMENT '供应商电话',
  `status` tinyint(1) NOT NULL DEFAULT '1' COMMENT '状态 1正常 2维修中 3报废 4停用',
  `remark` varchar(500) NOT NULL DEFAULT '' COMMENT '备注',
  `create_time` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT '创建时间',
  `update_time` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '更新时间',
  `delete_time` datetime DEFAULT NULL COMMENT '删除时间',
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_code` (`code`),
  KEY `idx_community_id` (`community_id`),
  KEY `idx_category` (`category`),
  KEY `idx_status` (`status`),
  KEY `idx_next_maintain_date` (`next_maintain_date`),
  KEY `idx_delete_time` (`delete_time`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='设备台账';

-- ============================================
-- 29. 设备管理 - 设备维保记录表
-- ============================================
CREATE TABLE IF NOT EXISTS `ds_equipment_maintain` (
  `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
  `community_id` int(11) UNSIGNED NOT NULL DEFAULT '0' COMMENT '小区ID',
  `equipment_id` int(11) UNSIGNED NOT NULL DEFAULT '0' COMMENT '设备ID',
  `equipment_name` varchar(200) NOT NULL DEFAULT '' COMMENT '设备名称',
  `type` tinyint(1) NOT NULL DEFAULT '1' COMMENT '类型 1日常巡检 2定期维保 3故障维修 4紧急抢修',
  `content` text COMMENT '维保内容',
  `maintainer` varchar(100) NOT NULL DEFAULT '' COMMENT '维保人员',
  `maintain_company` varchar(200) NOT NULL DEFAULT '' COMMENT '维保公司',
  `cost` decimal(10,2) NOT NULL DEFAULT '0.00' COMMENT '维保费用',
  `maintain_date` date NOT NULL COMMENT '维保日期',
  `next_maintain_date` date DEFAULT NULL COMMENT '下次维保日期',
  `status` tinyint(1) NOT NULL DEFAULT '1' COMMENT '状态 1已完成 2待确认 3异常',
  `result` varchar(500) NOT NULL DEFAULT '' COMMENT '维保结果',
  `attachments` text COMMENT '附件（JSON）',
  `operator_id` int(11) UNSIGNED NOT NULL DEFAULT '0' COMMENT '操作员ID',
  `remark` varchar(500) NOT NULL DEFAULT '' COMMENT '备注',
  `create_time` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT '创建时间',
  `update_time` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '更新时间',
  `delete_time` datetime DEFAULT NULL COMMENT '删除时间',
  PRIMARY KEY (`id`),
  KEY `idx_community_id` (`community_id`),
  KEY `idx_equipment_id` (`equipment_id`),
  KEY `idx_maintain_date` (`maintain_date`),
  KEY `idx_type` (`type`),
  KEY `idx_delete_time` (`delete_time`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='设备维保记录';

-- ============================================
-- 30. 投诉建议表
-- ============================================
CREATE TABLE IF NOT EXISTS `ds_complaint` (
  `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
  `complaint_no` varchar(50) NOT NULL DEFAULT '' COMMENT '投诉编号',
  `community_id` int(11) UNSIGNED NOT NULL DEFAULT '0' COMMENT '小区ID',
  `owner_id` int(11) UNSIGNED NOT NULL DEFAULT '0' COMMENT '业主ID',
  `room_id` int(11) UNSIGNED NOT NULL DEFAULT '0' COMMENT '房间ID',
  `complaint_name` varchar(100) NOT NULL DEFAULT '' COMMENT '投诉人姓名',
  `complaint_phone` varchar(20) NOT NULL DEFAULT '' COMMENT '联系电话',
  `type` tinyint(1) NOT NULL DEFAULT '1' COMMENT '类型 1投诉 2建议 3表扬 4咨询',
  `category` varchar(50) NOT NULL DEFAULT '' COMMENT '分类 环境/服务/噪音/宠物/装修/其他',
  `content` text COMMENT '投诉/建议内容',
  `images` text COMMENT '图片（JSON）',
  `handler_id` int(11) UNSIGNED NOT NULL DEFAULT '0' COMMENT '处理人ID',
  `handler_name` varchar(100) NOT NULL DEFAULT '' COMMENT '处理人',
  `handle_time` datetime DEFAULT NULL COMMENT '处理时间',
  `handle_content` text COMMENT '处理内容',
  `handle_images` text COMMENT '处理图片',
  `rating` tinyint(1) NOT NULL DEFAULT '0' COMMENT '满意度评分 1-5',
  `status` tinyint(1) NOT NULL DEFAULT '1' COMMENT '状态 1待处理 2处理中 3已处理 4已关闭 5已评价',
  `is_public` tinyint(1) NOT NULL DEFAULT '0' COMMENT '是否公开 0否 1是',
  `remark` varchar(500) NOT NULL DEFAULT '' COMMENT '备注',
  `create_time` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT '创建时间',
  `update_time` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '更新时间',
  `delete_time` datetime DEFAULT NULL COMMENT '删除时间',
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_complaint_no` (`complaint_no`),
  KEY `idx_community_id` (`community_id`),
  KEY `idx_owner_id` (`owner_id`),
  KEY `idx_type` (`type`),
  KEY `idx_status` (`status`),
  KEY `idx_create_time` (`create_time`),
  KEY `idx_delete_time` (`delete_time`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='投诉建议';

-- ============================================
-- 31. 系统管理 - 附件表
-- ============================================
CREATE TABLE IF NOT EXISTS `ds_attachment` (
  `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
  `admin_id` int(11) UNSIGNED NOT NULL DEFAULT '0' COMMENT '上传者ID',
  `module` varchar(50) NOT NULL DEFAULT '' COMMENT '所属模块',
  `file_name` varchar(200) NOT NULL DEFAULT '' COMMENT '文件名',
  `file_path` varchar(500) NOT NULL DEFAULT '' COMMENT '文件路径',
  `file_url` varchar(500) NOT NULL DEFAULT '' COMMENT '文件URL',
  `file_size` bigint(20) NOT NULL DEFAULT '0' COMMENT '文件大小(字节)',
  `file_ext` varchar(20) NOT NULL DEFAULT '' COMMENT '文件扩展名',
  `file_type` varchar(100) NOT NULL DEFAULT '' COMMENT '文件MIME类型',
  `driver` varchar(20) NOT NULL DEFAULT 'local' COMMENT '存储驱动',
  `md5` varchar(32) NOT NULL DEFAULT '' COMMENT '文件MD5',
  `sha1` varchar(40) NOT NULL DEFAULT '' COMMENT '文件SHA1',
  `width` int(11) NOT NULL DEFAULT '0' COMMENT '图片宽度',
  `height` int(11) NOT NULL DEFAULT '0' COMMENT '图片高度',
  `duration` int(11) NOT NULL DEFAULT '0' COMMENT '视频时长(秒)',
  `status` tinyint(1) NOT NULL DEFAULT '1' COMMENT '状态 1有效 0无效',
  `create_time` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT '上传时间',
  PRIMARY KEY (`id`),
  KEY `idx_admin_id` (`admin_id`),
  KEY `idx_module` (`module`),
  KEY `idx_file_ext` (`file_ext`),
  KEY `idx_md5` (`md5`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='附件';

-- ============================================
-- 32. 系统管理 - 短信记录表
-- ============================================
CREATE TABLE IF NOT EXISTS `ds_sms_log` (
  `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
  `phone` varchar(20) NOT NULL DEFAULT '' COMMENT '手机号',
  `template_id` varchar(100) NOT NULL DEFAULT '' COMMENT '模板ID',
  `params` text COMMENT '模板参数（JSON）',
  `content` varchar(500) NOT NULL DEFAULT '' COMMENT '短信内容',
  `result` varchar(500) NOT NULL DEFAULT '' COMMENT '发送结果',
  `status` tinyint(1) NOT NULL DEFAULT '0' COMMENT '状态 0未发送 1成功 2失败',
  `create_time` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT '创建时间',
  PRIMARY KEY (`id`),
  KEY `idx_phone` (`phone`),
  KEY `idx_status` (`status`),
  KEY `idx_create_time` (`create_time`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='短信记录';

-- ============================================
-- 33. 消息推送表
-- ============================================
CREATE TABLE IF NOT EXISTS `ds_message` (
  `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
  `community_id` int(11) UNSIGNED NOT NULL DEFAULT '0' COMMENT '小区ID',
  `sender_id` int(11) UNSIGNED NOT NULL DEFAULT '0' COMMENT '发送者ID',
  `sender_type` varchar(20) NOT NULL DEFAULT 'system' COMMENT '发送者类型 system/admin/staff',
  `receiver_id` int(11) UNSIGNED NOT NULL DEFAULT '0' COMMENT '接收者ID',
  `receiver_type` varchar(20) NOT NULL DEFAULT 'owner' COMMENT '接收者类型 owner/staff/admin',
  `type` varchar(50) NOT NULL DEFAULT '' COMMENT '消息类型 notice/repair/bill/visit/other',
  `title` varchar(200) NOT NULL DEFAULT '' COMMENT '消息标题',
  `content` text COMMENT '消息内容',
  `extra` text COMMENT '扩展数据（JSON）',
  `is_read` tinyint(1) NOT NULL DEFAULT '0' COMMENT '是否已读 0否 1是',
  `read_time` datetime DEFAULT NULL COMMENT '阅读时间',
  `status` tinyint(1) NOT NULL DEFAULT '1' COMMENT '状态 1正常 0撤回',
  `create_time` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT '创建时间',
  PRIMARY KEY (`id`),
  KEY `idx_receiver` (`receiver_id`,`receiver_type`),
  KEY `idx_type` (`type`),
  KEY `idx_is_read` (`is_read`),
  KEY `idx_create_time` (`create_time`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='消息推送';

-- ============================================
-- 初始化数据 - 管理员
-- ============================================
INSERT INTO `ds_admin_user` (`id`, `username`, `password`, `nickname`, `role_id`, `status`, `create_time`) VALUES
(1, 'admin', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '超级管理员', 1, 1, NOW());

-- ============================================
-- 初始化数据 - 角色
-- ============================================
INSERT INTO `ds_role` (`id`, `name`, `code`, `description`, `status`, `create_time`) VALUES
(1, '超级管理员', 'super_admin', '系统超级管理员，拥有所有权限', 1, NOW()),
(2, '系统管理员', 'admin', '系统管理员，拥有系统管理和大部分业务权限', 1, NOW()),
(3, '项目经理', 'manager', '项目经理，查看和管理所属小区所有业务', 1, NOW()),
(4, '客服主管', 'service', '客服主管，管理报修和投诉', 1, NOW()),
(5, '财务专员', 'finance', '财务专员，管理收费和账单', 1, NOW()),
(6, '安保主管', 'security', '安保主管，管理巡更和门禁', 1, NOW()),
(7, '工程主管', 'engineer', '工程主管，管理设备维保', 1, NOW());

-- ============================================
-- 初始化数据 - 系统配置
-- ============================================
INSERT INTO `ds_config` (`group`, `name`, `key`, `value`, `type`, `sort`, `remark`) VALUES
('system', '系统名称', 'system_name', '大圣物业管理系统', 'text', 1, '系统显示名称'),
('system', '系统LOGO', 'system_logo', '', 'image', 2, '系统LOGO图片'),
('system', '系统版本', 'system_version', '1.0.0', 'text', 3, '系统版本号'),
('system', '备案号', 'icp_number', '', 'text', 4, 'ICP备案号'),
('system', '公司名称', 'company_name', '杭州喵喵至家网络有限公司', 'text', 5, '物业公司名称'),
('system', '客服电话', 'service_phone', '400-xxx-xxxx', 'text', 6, '客服热线'),
('system', '公司地址', 'company_address', '浙江省杭州市余杭区', 'text', 7, '公司地址'),
('charge', '滞纳金日费率', 'overdue_rate', '0.003', 'number', 1, '逾期未缴费用的日费率(千分之)'),
('charge', '免滞纳金天数', 'overdue_free_days', '30', 'number', 2, '逾期多少天内免收滞纳金'),
('charge', '缴费截止日', 'payment_due_day', '15', 'number', 3, '每月缴费截止日期'),
('parking', '临时停车免费时长(分钟)', 'parking_free_minutes', '30', 'number', 1, '临时停车免费时长'),
('parking', '临时停车首小时费用', 'parking_first_hour_fee', '5', 'number', 2, '首小时费用(元)'),
('parking', '临时停车续时费用', 'parking_extra_hour_fee', '2', 'number', 3, '超过1小时后每小时费用(元)'),
('parking', '临时停车日封顶', 'parking_daily_max', '20', 'number', 4, '每日最高收费(元)');

-- ============================================
-- 初始化数据 - 菜单（系统管理）
-- ============================================
INSERT INTO `ds_menu` (`id`, `parent_id`, `name`, `icon`, `route`, `permission`, `type`, `sort`, `status`) VALUES
(1, 0, '系统管理', 'layui-icon-set', '/system', 'system', 1, 1, 1),
(2, 1, '用户管理', 'layui-icon-user', '/system/admin', 'system:admin', 1, 1, 1),
(3, 1, '角色管理', 'layui-icon-user', '/system/role', 'system:role', 1, 2, 1),
(4, 1, '菜单管理', 'layui-icon-menu', '/system/menu', 'system:menu', 1, 3, 1),
(5, 1, '系统配置', 'layui-icon-set', '/system/config', 'system:config', 1, 4, 1),
(6, 1, '操作日志', 'layui-icon-log', '/system/log', 'system:log', 1, 5, 1),
(7, 0, '房产管理', 'layui-icon-home', '/property', 'property', 1, 2, 1),
(8, 7, '小区管理', 'layui-icon-home', '/property/community', 'property:community', 1, 1, 1),
(9, 7, '楼栋管理', 'layui-icon-home', '/property/building', 'property:building', 1, 2, 1),
(10, 7, '房间管理', 'layui-icon-home', '/property/room', 'property:room', 1, 3, 1),
(11, 0, '业主管理', 'layui-icon-user', '/owner', 'owner', 1, 3, 1),
(12, 11, '业主档案', 'layui-icon-user', '/owner/index', 'owner:index', 1, 1, 1),
(13, 11, '家庭成员', 'layui-icon-user', '/owner/family', 'owner:family', 1, 2, 1),
(14, 0, '收费管理', 'layui-icon-rmb', '/charge', 'charge', 1, 4, 1),
(15, 14, '收费项目', 'layui-icon-rmb', '/charge/item', 'charge:item', 1, 1, 1),
(16, 14, '账单管理', 'layui-icon-list', '/charge/bill', 'charge:bill', 1, 2, 1),
(17, 14, '缴费记录', 'layui-icon-rmb', '/charge/payment', 'charge:payment', 1, 3, 1),
(18, 14, '抄表管理', 'layui-icon-note', '/charge/meter', 'charge:meter', 1, 4, 1),
(19, 14, '财务流水', 'layui-icon-log', '/charge/finance', 'charge:finance', 1, 5, 1),
(20, 0, '报修管理', 'layui-icon-engine', '/repair', 'repair', 1, 5, 1),
(21, 20, '报修工单', 'layui-icon-engine', '/repair/order', 'repair:order', 1, 1, 1),
(22, 20, '维修人员', 'layui-icon-user', '/repair/worker', 'repair:worker', 1, 2, 1),
(23, 0, '安保管理', 'layui-icon-auz', '/security', 'security', 1, 6, 1),
(24, 23, '访客登记', 'layui-icon-user', '/security/visitor', 'security:visitor', 1, 1, 1),
(25, 23, '巡更路线', 'layui-icon-location', '/security/patrol', 'security:patrol', 1, 2, 1),
(26, 23, '门禁卡管理', 'layui-icon-card', '/security/access-card', 'security:access_card', 1, 3, 1),
(27, 0, '停车管理', 'layui-icon-car', '/parking', 'parking', 1, 7, 1),
(28, 27, '车位管理', 'layui-icon-car', '/parking/space', 'parking:space', 1, 1, 1),
(29, 27, '车辆管理', 'layui-icon-car', '/parking/vehicle', 'parking:vehicle', 1, 2, 1),
(30, 27, '停车记录', 'layui-icon-time', '/parking/record', 'parking:record', 1, 3, 1),
(31, 0, '公告通知', 'layui-icon-notice', '/notice', 'notice', 1, 8, 1),
(32, 31, '公告列表', 'layui-icon-notice', '/notice/index', 'notice:index', 1, 1, 1),
(33, 0, '设备管理', 'layui-icon-engine', '/equipment', 'equipment', 1, 9, 1),
(34, 33, '设备台账', 'layui-icon-engine', '/equipment/index', 'equipment:index', 1, 1, 1),
(35, 33, '维保记录', 'layui-icon-log', '/equipment/maintain', 'equipment:maintain', 1, 2, 1),
(36, 0, '投诉建议', 'layui-icon-speaker', '/complaint', 'complaint', 1, 10, 1),
(37, 36, '投诉管理', 'layui-icon-speaker', '/complaint/index', 'complaint:index', 1, 1, 1),
(38, 0, '打印中心', 'layui-icon-print', '/print', 'print', 1, 11, 1),
(39, 38, '收据打印', 'layui-icon-print', '/print/receipt', 'print:receipt', 1, 1, 1),
(40, 38, '催缴通知', 'layui-icon-print', '/print/notice', 'print:notice', 1, 2, 1),
(41, 0, '数据概览', 'layui-icon-chart', '/dashboard', 'dashboard', 1, 0, 1),
(42, 41, '数据概览', 'layui-icon-chart', '/dashboard', 'dashboard', 1, 1, 1);

-- ============================================
-- 初始化 - 超级管理员关联所有菜单
-- ============================================
INSERT INTO `ds_role_menu` (`role_id`, `menu_id`)
SELECT 1, `id` FROM `ds_menu`;

-- ============================================
-- 物业人员 - 员工管理
-- ============================================
INSERT INTO `ds_menu` (`id`, `parent_id`, `name`, `icon`, `route`, `permission`, `type`, `sort`, `status`) VALUES
(43, 0, '物业人员', 'layui-icon-user', '/staff', 'staff', 1, 12, 1),
(44, 43, '员工档案', 'layui-icon-user', '/staff/index', 'staff:index', 1, 1, 1),
(45, 43, '考勤管理', 'layui-icon-date', '/staff/attendance', 'staff:attendance', 1, 2, 1),
(46, 43, '排班管理', 'layui-icon-time', '/staff/schedule', 'staff:schedule', 1, 3, 1),
(47, 43, '工资管理', 'layui-icon-rmb', '/staff/salary', 'staff:salary', 1, 4, 1);

-- ============================================
-- 外部供应商管理
-- ============================================
INSERT INTO `ds_menu` (`id`, `parent_id`, `name`, `icon`, `route`, `permission`, `type`, `sort`, `status`) VALUES
(48, 0, '外部供应商', 'layui-icon-group', '/supplier', 'supplier', 1, 13, 1),
(49, 48, '供应商名录', 'layui-icon-group', '/supplier/index', 'supplier:index', 1, 1, 1),
(50, 48, '采购订单', 'layui-icon-cart', '/supplier/purchase', 'supplier:purchase', 1, 2, 1),
(51, 48, '合同管理', 'layui-icon-file', '/supplier/contract', 'supplier:contract', 1, 3, 1),
(52, 48, '服务评价', 'layui-icon-praise', '/supplier/evaluation', 'supplier:evaluation', 1, 4, 1);

-- 追加超管菜单权限
INSERT INTO `ds_role_menu` (`role_id`, `menu_id`)
SELECT 1, `id` FROM `ds_menu` WHERE `id` BETWEEN 43 AND 52;

-- ============================================
-- 物业人员相关表
-- ============================================
CREATE TABLE IF NOT EXISTS `ds_staff` (
  `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `job_no` varchar(30) NOT NULL DEFAULT '' COMMENT '工号',
  `realname` varchar(50) NOT NULL DEFAULT '' COMMENT '姓名',
  `gender` tinyint(1) NOT NULL DEFAULT 1 COMMENT '性别 1男 2女',
  `phone` varchar(20) NOT NULL DEFAULT '' COMMENT '手机号',
  `department` varchar(50) NOT NULL DEFAULT '' COMMENT '部门',
  `position` varchar(50) NOT NULL DEFAULT '' COMMENT '岗位',
  `community_id` int(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '所属小区',
  `base_salary` decimal(10,2) NOT NULL DEFAULT 0 COMMENT '基本工资',
  `entry_date` date DEFAULT NULL COMMENT '入职日期',
  `id_card` varchar(30) NOT NULL DEFAULT '' COMMENT '身份证号',
  `emergency_contact` varchar(100) NOT NULL DEFAULT '' COMMENT '紧急联系人',
  `remark` varchar(500) NOT NULL DEFAULT '' COMMENT '备注',
  `status` tinyint(1) NOT NULL DEFAULT 1 COMMENT '状态 1在职 0离职',
  `create_time` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `update_time` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `delete_time` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_community` (`community_id`),
  KEY `idx_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='物业员工';

CREATE TABLE IF NOT EXISTS `ds_staff_attendance` (
  `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `staff_id` int(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '员工ID',
  `attendance_date` date NOT NULL COMMENT '考勤日期',
  `sign_in_time` varchar(10) NOT NULL DEFAULT '' COMMENT '签到时间',
  `sign_out_time` varchar(10) NOT NULL DEFAULT '' COMMENT '签退时间',
  `type` tinyint(1) NOT NULL DEFAULT 1 COMMENT '1出勤 2迟到 3早退 4请假 5旷工',
  `remark` varchar(300) NOT NULL DEFAULT '' COMMENT '备注',
  `create_time` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_staff_date` (`staff_id`, `attendance_date`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='考勤记录';

CREATE TABLE IF NOT EXISTS `ds_staff_schedule` (
  `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `staff_id` int(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '员工ID',
  `schedule_date` date NOT NULL COMMENT '排班日期',
  `shift` varchar(20) NOT NULL DEFAULT '早班' COMMENT '班次',
  `start_time` varchar(10) NOT NULL DEFAULT '' COMMENT '上班时间',
  `end_time` varchar(10) NOT NULL DEFAULT '' COMMENT '下班时间',
  `work_area` varchar(100) NOT NULL DEFAULT '' COMMENT '工作区域',
  `remark` varchar(300) NOT NULL DEFAULT '' COMMENT '备注',
  `create_time` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_staff_date` (`staff_id`, `schedule_date`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='排班记录';

CREATE TABLE IF NOT EXISTS `ds_staff_salary` (
  `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `staff_id` int(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '员工ID',
  `salary_month` varchar(7) NOT NULL DEFAULT '' COMMENT '工资月份',
  `base_salary` decimal(10,2) NOT NULL DEFAULT 0 COMMENT '基本工资',
  `bonus` decimal(10,2) NOT NULL DEFAULT 0 COMMENT '奖金',
  `overtime_pay` decimal(10,2) NOT NULL DEFAULT 0 COMMENT '加班费',
  `subsidy` decimal(10,2) NOT NULL DEFAULT 0 COMMENT '补贴',
  `deduction` decimal(10,2) NOT NULL DEFAULT 0 COMMENT '扣款',
  `social_insurance` decimal(10,2) NOT NULL DEFAULT 0 COMMENT '社保扣缴',
  `net_salary` decimal(10,2) NOT NULL DEFAULT 0 COMMENT '实发工资',
  `status` tinyint(1) NOT NULL DEFAULT 0 COMMENT '0未发放 1已发放',
  `pay_time` datetime DEFAULT NULL COMMENT '发放时间',
  `remark` varchar(300) NOT NULL DEFAULT '' COMMENT '备注',
  `create_time` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `update_time` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_staff_month` (`staff_id`, `salary_month`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='工资记录';

CREATE TABLE IF NOT EXISTS `ds_supplier` (
  `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL DEFAULT '' COMMENT '供应商名称',
  `category` varchar(50) NOT NULL DEFAULT '' COMMENT '类别',
  `contact_person` varchar(50) NOT NULL DEFAULT '' COMMENT '联系人',
  `contact_phone` varchar(20) NOT NULL DEFAULT '' COMMENT '联系电话',
  `email` varchar(100) NOT NULL DEFAULT '' COMMENT '邮箱',
  `address` varchar(300) NOT NULL DEFAULT '' COMMENT '地址',
  `rating` decimal(2,1) NOT NULL DEFAULT 0 COMMENT '综合评分',
  `remark` varchar(500) NOT NULL DEFAULT '' COMMENT '备注',
  `status` tinyint(1) NOT NULL DEFAULT 1 COMMENT '状态 1正常 0停用',
  `create_time` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `update_time` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `delete_time` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_category` (`category`),
  KEY `idx_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='外部供应商';

CREATE TABLE IF NOT EXISTS `ds_purchase_order` (
  `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `order_no` varchar(30) NOT NULL DEFAULT '' COMMENT '订单编号',
  `title` varchar(200) NOT NULL DEFAULT '' COMMENT '订单标题',
  `supplier_id` int(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '供应商ID',
  `amount` decimal(12,2) NOT NULL DEFAULT 0 COMMENT '订单金额',
  `items` text COMMENT '采购明细',
  `order_date` date DEFAULT NULL COMMENT '下单日期',
  `status` tinyint(1) NOT NULL DEFAULT 1 COMMENT '0已取消 1待审批 2已审批 3已完成',
  `approve_time` datetime DEFAULT NULL COMMENT '审批时间',
  `complete_time` datetime DEFAULT NULL COMMENT '完成时间',
  `remark` varchar(500) NOT NULL DEFAULT '' COMMENT '备注',
  `create_time` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `update_time` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_supplier` (`supplier_id`),
  KEY `idx_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='采购订单';

CREATE TABLE IF NOT EXISTS `ds_contract` (
  `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `contract_no` varchar(30) NOT NULL DEFAULT '' COMMENT '合同编号',
  `title` varchar(200) NOT NULL DEFAULT '' COMMENT '合同名称',
  `supplier_id` int(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '供应商ID',
  `contract_amount` decimal(12,2) NOT NULL DEFAULT 0 COMMENT '合同金额',
  `content` text COMMENT '合同内容',
  `start_date` date DEFAULT NULL COMMENT '生效日期',
  `end_date` date DEFAULT NULL COMMENT '到期日期',
  `sign_date` date DEFAULT NULL COMMENT '签订日期',
  `expire_time` date DEFAULT NULL COMMENT '终止日期',
  `status` tinyint(1) NOT NULL DEFAULT 1 COMMENT '1执行中 2已到期 3已终止',
  `remark` varchar(500) NOT NULL DEFAULT '' COMMENT '备注',
  `create_time` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `update_time` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_supplier` (`supplier_id`),
  KEY `idx_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='合同管理';

CREATE TABLE IF NOT EXISTS `ds_supplier_evaluation` (
  `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `supplier_id` int(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '供应商ID',
  `rating` tinyint(1) NOT NULL DEFAULT 5 COMMENT '评分 1-5',
  `evaluator` varchar(50) NOT NULL DEFAULT '' COMMENT '评价人',
  `content` varchar(1000) NOT NULL DEFAULT '' COMMENT '评价内容',
  `create_time` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_supplier` (`supplier_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='供应商评价';

-- ============================================
-- 补充表：小区支付配置表
-- ============================================
CREATE TABLE IF NOT EXISTS `ds_community_payment_config` (
  `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `community_id` int(11) UNSIGNED NOT NULL DEFAULT '0' COMMENT '小区ID',
  `pay_channel` varchar(20) NOT NULL DEFAULT '' COMMENT '支付渠道 wechat/alipay/both',
  `wechat_app_id` varchar(100) NOT NULL DEFAULT '' COMMENT '微信AppID',
  `wechat_mch_id` varchar(50) NOT NULL DEFAULT '' COMMENT '微信商户号',
  `wechat_serial_no` varchar(100) NOT NULL DEFAULT '' COMMENT '微信证书序列号',
  `wechat_api_key` varchar(100) NOT NULL DEFAULT '' COMMENT '微信APIv2密钥',
  `wechat_api_v3_key` varchar(100) NOT NULL DEFAULT '' COMMENT '微信APIv3密钥',
  `alipay_app_id` varchar(100) NOT NULL DEFAULT '' COMMENT '支付宝AppID',
  `alipay_merchant_id` varchar(50) NOT NULL DEFAULT '' COMMENT '支付宝商户PID',
  `alipay_private_key` text COMMENT '支付宝应用私钥',
  `alipay_public_key` text COMMENT '支付宝公钥',
  `notify_url` varchar(500) NOT NULL DEFAULT '' COMMENT '回调地址',
  `status` tinyint(1) NOT NULL DEFAULT '0' COMMENT '状态 0禁用 1启用',
  `create_time` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT '创建时间',
  `update_time` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '更新时间',
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_community_id` (`community_id`),
  KEY `idx_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='小区支付配置';

-- ============================================
-- 补充表：小区公众号配置表
-- ============================================
CREATE TABLE IF NOT EXISTS `ds_community_wechat_config` (
  `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `community_id` int(11) UNSIGNED NOT NULL DEFAULT '0' COMMENT '小区ID',
  `app_id` varchar(100) NOT NULL DEFAULT '' COMMENT '公众号AppID',
  `app_secret` varchar(200) NOT NULL DEFAULT '' COMMENT '公众号AppSecret',
  `original_id` varchar(100) NOT NULL DEFAULT '' COMMENT '原始ID',
  `mch_id` varchar(50) NOT NULL DEFAULT '' COMMENT '微信支付商户号',
  `token` varchar(100) NOT NULL DEFAULT '' COMMENT '消息校验Token',
  `encoding_aes_key` varchar(100) NOT NULL DEFAULT '' COMMENT '消息加密密钥',
  `template_pay_success` varchar(100) NOT NULL DEFAULT '' COMMENT '缴费成功模板消息ID',
  `template_arrears` varchar(100) NOT NULL DEFAULT '' COMMENT '欠费催缴模板消息ID',
  `status` tinyint(1) NOT NULL DEFAULT '0' COMMENT '状态 0禁用 1启用',
  `create_time` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT '创建时间',
  `update_time` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '更新时间',
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_community_id` (`community_id`),
  KEY `idx_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='小区公众号配置';

-- ============================================
-- 补充表：催单记录表
-- ============================================
CREATE TABLE IF NOT EXISTS `ds_bill_dunning` (
  `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
  `community_id` int(11) UNSIGNED NOT NULL DEFAULT '0' COMMENT '小区ID',
  `room_id` int(11) UNSIGNED NOT NULL DEFAULT '0' COMMENT '房间ID',
  `owner_id` int(11) UNSIGNED NOT NULL DEFAULT '0' COMMENT '业主ID',
  `total_amount` decimal(12,2) NOT NULL DEFAULT '0.00' COMMENT '应收总额',
  `paid_amount` decimal(12,2) NOT NULL DEFAULT '0.00' COMMENT '已付总额',
  `arrears_amount` decimal(12,2) NOT NULL DEFAULT '0.00' COMMENT '欠费总额',
  `bill_count` int(11) NOT NULL DEFAULT '0' COMMENT '欠费账单数',
  `remark` varchar(500) NOT NULL DEFAULT '' COMMENT '备注',
  `channel` varchar(20) NOT NULL DEFAULT 'manual' COMMENT '催缴渠道 manual/sms/wechat',
  `admin_id` int(11) UNSIGNED NOT NULL DEFAULT '0' COMMENT '操作管理员ID',
  `create_time` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT '创建时间',
  PRIMARY KEY (`id`),
  KEY `idx_community_id` (`community_id`),
  KEY `idx_room_id` (`room_id`),
  KEY `idx_owner_id` (`owner_id`),
  KEY `idx_channel` (`channel`),
  KEY `idx_create_time` (`create_time`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='催单记录';

-- ============================================
-- 补充：账单表增加催缴字段
-- ============================================
-- ALTER TABLE `ds_bill` ADD COLUMN `dunning_count` int(11) NOT NULL DEFAULT '0' COMMENT '催缴次数' AFTER `remark`;
-- ALTER TABLE `ds_bill` ADD COLUMN `dunning_time` datetime DEFAULT NULL COMMENT '最后催缴时间' AFTER `dunning_count`;
