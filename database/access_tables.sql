-- =====================================================
-- 门禁管理模块 - 数据表
-- =====================================================

-- 1. 小区门禁配置表（按小区 + 门点 配置门禁品牌和接口参数）
CREATE TABLE IF NOT EXISTS `ds_access_config` (
  `id` int UNSIGNED AUTO_INCREMENT,
  `community_id` int UNSIGNED NOT NULL DEFAULT 0 COMMENT '小区ID',
  `door_name` varchar(100) NOT NULL DEFAULT '' COMMENT '门点名称（如：南门、东门、1号单元门）',
  `door_type` varchar(30) NOT NULL DEFAULT 'gate' COMMENT '门点类型: gate=大门 unit=单元门 garage=车库门 side=侧门',
  `brand` varchar(50) NOT NULL DEFAULT 'generic' COMMENT '门禁品牌: zkteco|microengine|hikvision|dahua|tongfang|generic',
  `api_url` varchar(500) NOT NULL DEFAULT '' COMMENT '接口地址',
  `api_token` varchar(500) NOT NULL DEFAULT '' COMMENT '接口密钥/Token',
  `api_username` varchar(100) NOT NULL DEFAULT '' COMMENT '接口用户名(如有)',
  `device_sn` varchar(100) NOT NULL DEFAULT '' COMMENT '设备序列号/控制器编号',
  `door_no` tinyint UNSIGNED DEFAULT 1 COMMENT '门序号（控制器上的门编号）',
  `open_mode` varchar(50) DEFAULT 'card_and_remote' COMMENT '开门方式: card=刷卡 remote=远程 card_and_remote=刷卡+远程',
  `enabled` tinyint(1) DEFAULT 1 COMMENT '启用: 1是 0否',
  `remark` varchar(500) DEFAULT '' COMMENT '备注',
  `create_time` datetime DEFAULT CURRENT_TIMESTAMP,
  `update_time` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `delete_time` datetime DEFAULT NULL COMMENT '软删除',
  PRIMARY KEY (`id`),
  KEY `idx_community_id` (`community_id`),
  KEY `idx_brand` (`brand`),
  UNIQUE KEY `uk_door` (`community_id`, `door_name`, `delete_time`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='小区门禁配置';

-- 2. 门禁设备表（物理控制器信息）
CREATE TABLE IF NOT EXISTS `ds_access_device` (
  `id` int UNSIGNED AUTO_INCREMENT,
  `config_id` int UNSIGNED DEFAULT 0 COMMENT '关联 ds_access_config.id',
  `community_id` int UNSIGNED NOT NULL DEFAULT 0 COMMENT '小区ID',
  `door_name` varchar(100) NOT NULL DEFAULT '' COMMENT '门点名称',
  `device_sn` varchar(100) NOT NULL DEFAULT '' COMMENT '设备序列号',
  `device_name` varchar(200) NOT NULL DEFAULT '' COMMENT '设备名称（如：南门控制器）',
  `brand` varchar(50) NOT NULL DEFAULT '' COMMENT '品牌',
  `ip_address` varchar(50) DEFAULT '' COMMENT 'IP地址',
  `port` smallint UNSIGNED DEFAULT 80 COMMENT '端口',
  `online` tinyint(1) DEFAULT 0 COMMENT '在线状态: 1在线 0离线',
  `last_heartbeat` datetime DEFAULT NULL COMMENT '最后心跳时间',
  `status` varchar(50) DEFAULT 'normal' COMMENT '状态: normal/error/maintenance',
  `remark` varchar(500) DEFAULT '' COMMENT '备注',
  `create_time` datetime DEFAULT CURRENT_TIMESTAMP,
  `update_time` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `delete_time` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_config_id` (`config_id`),
  KEY `idx_community_id` (`community_id`),
  KEY `idx_device_sn` (`device_sn`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='门禁设备';

-- 3. 门禁刷卡通行事件表（每次刷卡/远程开门记录）
CREATE TABLE IF NOT EXISTS `ds_access_event` (
  `id` bigint UNSIGNED AUTO_INCREMENT,
  `config_id` int UNSIGNED DEFAULT 0 COMMENT '关联 ds_access_config.id',
  `device_id` int UNSIGNED DEFAULT 0 COMMENT '关联 ds_access_device.id',
  `community_id` int UNSIGNED DEFAULT 0 COMMENT '小区ID',
  `card_no` varchar(50) NOT NULL DEFAULT '' COMMENT '卡号',
  `holder_name` varchar(100) DEFAULT '' COMMENT '持卡人姓名（匹配到时填充）',
  `door_name` varchar(100) DEFAULT '' COMMENT '门点名称',
  `direction` varchar(20) NOT NULL DEFAULT 'in' COMMENT '方向: in=进门 out=出门',
  `open_method` varchar(30) NOT NULL DEFAULT 'card' COMMENT '开门方式: card=刷卡 remote=远程密码 password=密码 fingerprint=指纹 face=人脸',
  `action` varchar(30) NOT NULL DEFAULT 'unknown' COMMENT '动作: pass=放行 deny=拒绝 error=异常',
  `reason` varchar(100) DEFAULT '' COMMENT '拒绝原因（如：卡已过期、非本小区等）',
  `photo_url` varchar(500) DEFAULT '' COMMENT '抓拍图片URL',
  `event_time` datetime DEFAULT NULL COMMENT '事件发生时间',
  `create_time` datetime DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_community_id` (`community_id`),
  KEY `idx_card_no` (`card_no`),
  KEY `idx_event_time` (`event_time`),
  KEY `idx_config_id` (`config_id`),
  KEY `idx_device_id` (`device_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='门禁刷卡通行事件';
