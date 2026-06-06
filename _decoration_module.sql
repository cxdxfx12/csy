-- ============================================
-- 装修管理模块 - 完整建表 + 菜单 + 权限
-- 执行方式: 导入到 dasheng 数据库
-- ============================================

-- 1. 装修申请表
DROP TABLE IF EXISTS `ds_decoration_apply`;
CREATE TABLE `ds_decoration_apply` (
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
  `decoration_type` varchar(30) DEFAULT '' COMMENT '装修类型:整装/精装/局部装修/其他',
  `drawing_urls` text COMMENT '施工图纸(JSON数组)',
  `status` tinyint(1) NOT NULL DEFAULT '0' COMMENT '0待审核 1通过待缴费 2施工中 3待验收 4已完成 5驳回 6取消',
  `audit_remark` varchar(500) DEFAULT '' COMMENT '审核意见/驳回原因',
  `audit_time` datetime DEFAULT NULL COMMENT '审核时间',
  `audit_admin_id` int(11) DEFAULT '0' COMMENT '审核人(admin_user.id)',
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='装修申请';

-- 2. 施工人员表
DROP TABLE IF EXISTS `ds_decoration_worker`;
CREATE TABLE `ds_decoration_worker` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `apply_id` int(11) NOT NULL COMMENT '装修申请ID',
  `name` varchar(50) NOT NULL COMMENT '姓名',
  `id_card` varchar(18) DEFAULT '' COMMENT '身份证号',
  `phone` varchar(20) DEFAULT '' COMMENT '手机号',
  `job_type` varchar(30) DEFAULT '' COMMENT '工种(水电工/木工/瓦工/油漆工/杂工/其他)',
  `card_no` varchar(30) DEFAULT '' COMMENT '出入证编号',
  `card_issue_date` date DEFAULT NULL COMMENT '发证日期',
  `card_expire_date` date DEFAULT NULL COMMENT '有效期至',
  `photo_url` varchar(255) DEFAULT '' COMMENT '照片URL',
  `create_time` datetime DEFAULT NULL,
  `delete_time` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_apply` (`apply_id`),
  KEY `idx_idcard` (`id_card`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='装修施工人员';

-- 3. 巡查记录表
DROP TABLE IF EXISTS `ds_decoration_inspect`;
CREATE TABLE `ds_decoration_inspect` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `apply_id` int(11) NOT NULL COMMENT '装修申请ID',
  `community_id` int(11) NOT NULL COMMENT '小区ID',
  `inspector_id` int(11) NOT NULL COMMENT '巡查人ID(admin_user.id)',
  `inspector_name` varchar(50) DEFAULT '' COMMENT '巡查人姓名',
  `inspect_time` datetime DEFAULT NULL COMMENT '巡查时间',
  `result` tinyint(1) NOT NULL DEFAULT '0' COMMENT '0正常 1异常',
  `content` varchar(500) DEFAULT '' COMMENT '巡查内容/备注',
  `photos` text COMMENT '现场照片(JSON数组)',
  `create_time` datetime DEFAULT NULL,
  `delete_time` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_apply` (`apply_id`),
  KEY `idx_community` (`community_id`),
  KEY `idx_inspector` (`inspector_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='装修巡查记录';

-- 4. 违规记录表
DROP TABLE IF EXISTS `ds_decoration_violation`;
CREATE TABLE `ds_decoration_violation` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `apply_id` int(11) NOT NULL COMMENT '装修申请ID',
  `community_id` int(11) NOT NULL COMMENT '小区ID',
  `violation_type` varchar(50) NOT NULL COMMENT '违规类型',
  `description` varchar(500) NOT NULL COMMENT '违规描述',
  `penalty_amount` decimal(10,2) DEFAULT '0.00' COMMENT '罚金/扣款金额',
  `rectify_deadline` date DEFAULT NULL COMMENT '整改截止日期',
  `rectify_result` varchar(500) DEFAULT '' COMMENT '整改结果描述',
  `status` tinyint(1) NOT NULL DEFAULT '0' COMMENT '0待整改 1已整改 2已扣款',
  `photos` text COMMENT '照片(JSON数组)',
  `create_admin_id` int(11) DEFAULT '0' COMMENT '登记人(admin_user.id)',
  `create_time` datetime DEFAULT NULL,
  `update_time` datetime DEFAULT NULL,
  `delete_time` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_apply` (`apply_id`),
  KEY `idx_community` (`community_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='装修违规记录';


-- ============================================
-- 5. 菜单数据 (parent_id 需要根据实际 ds_menu 表中的上级ID调整)
-- 假设: 一级菜单"装修管理"的 parent_id = 0
-- 请先查询: SELECT MAX(id)+1 FROM ds_menu; 确定新菜单起始ID
-- ============================================

-- 一级菜单: 装修管理
INSERT INTO `ds_menu` (`id`, `parent_id`, `name`, `icon`, `route`, `permission`, `type`, `sort`, `status`) VALUES
(200, 0, '装修管理', 'SetUp', '/decoration', '', 1, 45, 1);

-- 二级菜单
INSERT INTO `ds_menu` (`id`, `parent_id`, `name`, `icon`, `route`, `permission`, `type`, `sort`, `status`) VALUES
(201, 200, '装修申请', 'Document', '/decoration/apply', 'decoration:apply', 1, 1, 1),
(202, 200, '施工巡查', 'Van', '/decoration/inspect', 'decoration:inspect', 1, 2, 1),
(203, 200, '违规记录', 'Warning', '/decoration/violation', 'decoration:violation', 1, 3, 1),
(204, 200, '施工人员', 'User', '/decoration/worker', 'decoration:worker', 1, 4, 1);


-- ============================================
-- 6. 角色权限分配 (将装修管理菜单分配给各角色)
-- 超级管理员 role_id=1 跳过(全权限)
-- role_id=2 系统管理员: 也跳过(manager 角色加了*就全有了)
-- 其实manager角色在BaseAdmin里是预定义的，我们需要在代码里给manager加上Decoration
-- 这里先用role_menu把菜单直接绑给各角色
-- ============================================

-- 工程部 role_id=6(code=engineer): 装修申请+巡查+违规+施工人员
INSERT INTO `ds_role_menu` (`role_id`, `menu_id`) VALUES
(6, 200), (6, 201), (6, 202), (6, 203), (6, 204);

-- 客服/管家 role_id=3(code=service): 装修申请+施工人员(录入)
INSERT INTO `ds_role_menu` (`role_id`, `menu_id`) VALUES
(3, 200), (3, 201), (3, 204);

-- 安保 role_id=4(code=security): 巡查+违规
INSERT INTO `ds_role_menu` (`role_id`, `menu_id`) VALUES
(4, 200), (4, 202), (4, 203);

-- 财务 role_id=5(code=finance): 装修申请(收费/退押金)
INSERT INTO `ds_role_menu` (`role_id`, `menu_id`) VALUES
(5, 200), (5, 201);

-- 项目经理 role_id=2(code=manager): 全部
INSERT INTO `ds_role_menu` (`role_id`, `menu_id`) VALUES
(2, 200), (2, 201), (2, 202), (2, 203), (2, 204);
