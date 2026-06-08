-- ============================================
-- IoT 数字孪生 - 设备类型/协议/设备/数据表
-- 数据库: dasheng  表前缀: ds_
-- ============================================

-- ----------------------------
-- 1. IoT 通信协议表
-- ----------------------------
CREATE TABLE IF NOT EXISTS `ds_iot_protocol` (
  `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL DEFAULT '' COMMENT '协议名称',
  `code` varchar(50) NOT NULL DEFAULT '' COMMENT '协议编码',
  `type` varchar(20) NOT NULL DEFAULT 'wired' COMMENT '类型 wired/wireless',
  `description` varchar(500) NOT NULL DEFAULT '' COMMENT '协议描述',
  `transport` varchar(100) NOT NULL DEFAULT '' COMMENT '传输层 TCP/UDP/BLE/Serial',
  `port` varchar(20) NOT NULL DEFAULT '' COMMENT '默认端口',
  `frequency_band` varchar(50) NOT NULL DEFAULT '' COMMENT '频段（无线协议）',
  `data_rate` varchar(50) NOT NULL DEFAULT '' COMMENT '数据速率',
  `range` varchar(50) NOT NULL DEFAULT '' COMMENT '通信距离',
  `sort` int(11) NOT NULL DEFAULT '0' COMMENT '排序',
  `status` tinyint(1) NOT NULL DEFAULT '1' COMMENT '状态 1启用 0停用',
  `create_time` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_code` (`code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='IoT通信协议';

-- ----------------------------
-- 2. IoT 设备类型表
-- ----------------------------
CREATE TABLE IF NOT EXISTS `ds_iot_device_type` (
  `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `code` varchar(50) NOT NULL DEFAULT '' COMMENT '类型编码',
  `name` varchar(100) NOT NULL DEFAULT '' COMMENT '设备类型名称',
  `category` varchar(50) NOT NULL DEFAULT '' COMMENT '大类: fire/safety/energy/access/camera/env/water/parking/facility/lighting',
  `icon` varchar(100) NOT NULL DEFAULT '' COMMENT '3D图标标识',
  `unit` varchar(20) NOT NULL DEFAULT '' COMMENT '数据单位',
  `normal_range` varchar(100) NOT NULL DEFAULT '' COMMENT '正常范围',
  `alarm_high` decimal(10,2) DEFAULT NULL COMMENT '告警上限',
  `alarm_low` decimal(10,2) DEFAULT NULL COMMENT '告警下限',
  `warning_high` decimal(10,2) DEFAULT NULL COMMENT '预警上限',
  `warning_low` decimal(10,2) DEFAULT NULL COMMENT '预警下限',
  `protocol_support` varchar(200) NOT NULL DEFAULT '' COMMENT '支持协议（逗号分隔编码）',
  `y_height` decimal(5,2) NOT NULL DEFAULT '1.50' COMMENT '3D场景中Y轴高度',
  `description` varchar(500) NOT NULL DEFAULT '' COMMENT '描述',
  `sort` int(11) NOT NULL DEFAULT '0' COMMENT '排序',
  `status` tinyint(1) NOT NULL DEFAULT '1' COMMENT '状态 1启用 0停用',
  `create_time` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_code` (`code`),
  KEY `idx_category` (`category`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='IoT设备类型';

-- ----------------------------
-- 3. IoT 设备实例表
-- ----------------------------
CREATE TABLE IF NOT EXISTS `ds_iot_device` (
  `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `community_id` int(11) UNSIGNED NOT NULL DEFAULT '0' COMMENT '小区ID',
  `device_type_id` int(11) UNSIGNED NOT NULL DEFAULT '0' COMMENT '设备类型ID',
  `protocol_id` int(11) UNSIGNED NOT NULL DEFAULT '0' COMMENT '通信协议ID',
  `name` varchar(200) NOT NULL DEFAULT '' COMMENT '设备名称',
  `code` varchar(100) NOT NULL DEFAULT '' COMMENT '设备编号',
  `x` decimal(8,2) NOT NULL DEFAULT '0.00' COMMENT '3D场景X坐标',
  `y` decimal(8,2) NOT NULL DEFAULT '0.00' COMMENT '3D场景Y坐标(高度)',
  `z` decimal(8,2) NOT NULL DEFAULT '0.00' COMMENT '3D场景Z坐标',
  `install_location` varchar(200) NOT NULL DEFAULT '' COMMENT '安装位置描述',
  `floor` int(11) NOT NULL DEFAULT '0' COMMENT '所在楼层',
  `building` varchar(100) NOT NULL DEFAULT '' COMMENT '所在楼栋',
  `room` varchar(100) NOT NULL DEFAULT '' COMMENT '所在房间',
  `install_date` date DEFAULT NULL COMMENT '安装日期',
  `last_online` datetime DEFAULT NULL COMMENT '最后在线时间',
  `firmware_ver` varchar(50) NOT NULL DEFAULT '' COMMENT '固件版本',
  `battery_level` int(11) NOT NULL DEFAULT '100' COMMENT '电池电量(%)',
  `config` text COMMENT '设备配置(JSON)',
  `status` tinyint(1) NOT NULL DEFAULT '1' COMMENT '状态 1在线 0离线',
  `create_time` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `update_time` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_code` (`code`),
  KEY `idx_community_id` (`community_id`),
  KEY `idx_device_type_id` (`device_type_id`),
  KEY `idx_protocol_id` (`protocol_id`),
  KEY `idx_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='IoT设备实例';

-- ----------------------------
-- 4. IoT 设备实时数据表
-- ----------------------------
CREATE TABLE IF NOT EXISTS `ds_iot_device_data` (
  `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
  `device_id` int(11) UNSIGNED NOT NULL DEFAULT '0' COMMENT '设备ID',
  `value` decimal(12,2) DEFAULT NULL COMMENT '数值',
  `raw_value` varchar(100) NOT NULL DEFAULT '' COMMENT '原始值/显示值',
  `unit` varchar(20) NOT NULL DEFAULT '' COMMENT '单位',
  `is_online` tinyint(1) NOT NULL DEFAULT '1' COMMENT '是否在线 1是 0否',
  `device_status` varchar(20) NOT NULL DEFAULT 'normal' COMMENT '设备状态 normal/warning/alarm/offline',
  `alarm_msg` varchar(200) NOT NULL DEFAULT '' COMMENT '告警消息',
  `data_source` varchar(20) NOT NULL DEFAULT 'realtime' COMMENT '数据来源 realtime/simulated/history',
  `data_time` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT '数据产生时间',
  `create_time` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT '入库时间',
  PRIMARY KEY (`id`),
  KEY `idx_device_id` (`device_id`),
  KEY `idx_device_status` (`device_status`),
  KEY `idx_data_time` (`data_time`),
  KEY `idx_create_time` (`create_time`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='IoT设备实时数据';
