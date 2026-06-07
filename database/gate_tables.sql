-- =====================================================
-- 道闸管理模块 - 数据表
-- =====================================================

-- 1. 小区道闸配置表（按小区 + 出入口 配置道闸品牌和接口参数）
CREATE TABLE IF NOT EXISTS `ds_gate_config` (
  `id` int UNSIGNED AUTO_INCREMENT,
  `community_id` int UNSIGNED NOT NULL DEFAULT 0 COMMENT '小区ID',
  `entrance_name` varchar(100) NOT NULL DEFAULT '' COMMENT '出入口名称（如：南门入口、北门出口）',
  `brand` varchar(50) NOT NULL DEFAULT 'generic' COMMENT '道闸品牌: jieshun|hikvision|dahua|fuji|hongmen|generic',
  `direction` varchar(20) NOT NULL DEFAULT 'in' COMMENT '方向: in=入口 out=出口',
  `api_url` varchar(500) NOT NULL DEFAULT '' COMMENT '接口地址',
  `api_token` varchar(500) NOT NULL DEFAULT '' COMMENT '接口密钥/Token',
  `api_username` varchar(100) NOT NULL DEFAULT '' COMMENT '接口用户名(如有)',
  `device_sn` varchar(100) NOT NULL DEFAULT '' COMMENT '设备序列号/编号',
  `channel_no` tinyint UNSIGNED DEFAULT 1 COMMENT '通道号',
  `enabled` tinyint(1) DEFAULT 1 COMMENT '启用: 1是 0否',
  `remark` varchar(500) DEFAULT '' COMMENT '备注',
  `create_time` datetime DEFAULT CURRENT_TIMESTAMP,
  `update_time` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `delete_time` datetime DEFAULT NULL COMMENT '软删除',
  PRIMARY KEY (`id`),
  KEY `idx_community_id` (`community_id`),
  KEY `idx_brand` (`brand`),
  UNIQUE KEY `uk_entrance` (`community_id`, `entrance_name`, `delete_time`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='小区道闸配置';

-- 2. 道闸设备表（物理设备信息）
CREATE TABLE IF NOT EXISTS `ds_gate_device` (
  `id` int UNSIGNED AUTO_INCREMENT,
  `config_id` int UNSIGNED DEFAULT 0 COMMENT '关联 ds_gate_config.id',
  `community_id` int UNSIGNED NOT NULL DEFAULT 0 COMMENT '小区ID',
  `entrance_name` varchar(100) NOT NULL DEFAULT '' COMMENT '出入口名称',
  `device_sn` varchar(100) NOT NULL DEFAULT '' COMMENT '设备序列号',
  `device_name` varchar(200) NOT NULL DEFAULT '' COMMENT '设备名称',
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='道闸设备';

-- 3. 道闸通行事件表（每次抬杆/落杆记录）
CREATE TABLE IF NOT EXISTS `ds_gate_event` (
  `id` bigint UNSIGNED AUTO_INCREMENT,
  `config_id` int UNSIGNED DEFAULT 0 COMMENT '关联 ds_gate_config.id',
  `device_id` int UNSIGNED DEFAULT 0 COMMENT '关联 ds_gate_device.id',
  `community_id` int UNSIGNED DEFAULT 0 COMMENT '小区ID',
  `plate_number` varchar(20) NOT NULL DEFAULT '' COMMENT '车牌号',
  `direction` varchar(20) NOT NULL DEFAULT 'in' COMMENT '方向: in/out',
  `action` varchar(30) NOT NULL DEFAULT 'unknown' COMMENT '动作: pass=放行 deny=拒绝 error=异常',
  `image_url` varchar(500) DEFAULT '' COMMENT '抓拍图片URL',
  `recognize_time` datetime DEFAULT NULL COMMENT '识别时间',
  `remark` varchar(500) DEFAULT '' COMMENT '备注',
  `create_time` datetime DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_community_id` (`community_id`),
  KEY `idx_plate_number` (`plate_number`),
  KEY `idx_recognize_time` (`recognize_time`),
  KEY `idx_config_id` (`config_id`),
  KEY `idx_device_id` (`device_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='道闸通行事件';
