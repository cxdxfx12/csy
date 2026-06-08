-- ============================================
-- 推送配置管理 - 数据库迁移
-- ds_push_config: 推送渠道开关
-- ds_sms_config: 短信服务商配置
-- ============================================

-- 1. 推送配置表（按小区维度，控制各渠道开关）
CREATE TABLE IF NOT EXISTS `ds_push_config` (
  `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `community_id` int(11) UNSIGNED NOT NULL DEFAULT '0' COMMENT '小区ID，0=全局默认',
  `sse_enable` tinyint(1) NOT NULL DEFAULT '1' COMMENT 'SSE实时推送开关',
  `wechat_enable` tinyint(1) NOT NULL DEFAULT '1' COMMENT '微信模板消息开关',
  `sms_enable` tinyint(1) NOT NULL DEFAULT '0' COMMENT '短信推送开关',
  `app_push_enable` tinyint(1) NOT NULL DEFAULT '0' COMMENT 'App Push开关',
  `repair_new_enable` tinyint(1) NOT NULL DEFAULT '1' COMMENT '新报修通知开关',
  `repair_assign_enable` tinyint(1) NOT NULL DEFAULT '1' COMMENT '派单通知开关',
  `dunning_enable` tinyint(1) NOT NULL DEFAULT '1' COMMENT '催缴通知开关',
  `create_time` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `update_time` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_community` (`community_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='推送渠道配置表';

-- 2. 短信服务商配置
CREATE TABLE IF NOT EXISTS `ds_sms_config` (
  `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `community_id` int(11) UNSIGNED NOT NULL DEFAULT '0' COMMENT '小区ID，0=全局',
  `provider` varchar(20) NOT NULL DEFAULT 'aliyun' COMMENT '服务商: aliyun/tencent',
  `access_key_id` varchar(100) NOT NULL DEFAULT '' COMMENT '阿里云AccessKeyId / 腾讯云SecretId',
  `access_key_secret` varchar(100) NOT NULL DEFAULT '' COMMENT '阿里云AccessKeySecret / 腾讯云SecretKey',
  `sign_name` varchar(50) NOT NULL DEFAULT '' COMMENT '短信签名',
  `repair_template` varchar(30) NOT NULL DEFAULT '' COMMENT '报修通知短信模板CODE',
  `dunning_template` varchar(30) NOT NULL DEFAULT '' COMMENT '催缴通知短信模板CODE',
  `status` tinyint(1) NOT NULL DEFAULT '0' COMMENT '是否启用',
  `create_time` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `update_time` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_community` (`community_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='短信服务商配置表';
