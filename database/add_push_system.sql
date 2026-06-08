-- ============================================
-- 主动推送系统数据库迁移
-- 新增表 + 字段修改
-- ============================================

-- 1. 维修人员表：增加微信openid
ALTER TABLE `ds_repair_worker` ADD COLUMN IF NOT EXISTS `openid` varchar(64) DEFAULT '' COMMENT '微信openid' AFTER `phone`;

-- 2. 小区公众号配置：增加报修派单模板消息ID
ALTER TABLE `ds_community_wechat_config` ADD COLUMN IF NOT EXISTS `template_repair_assign` varchar(100) DEFAULT '' COMMENT '报修派单模板消息ID' AFTER `template_arrears`;

-- 3. 统一推送事件表
CREATE TABLE IF NOT EXISTS `ds_push_event` (
  `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
  `event_type` varchar(30) NOT NULL DEFAULT 'repair_new' COMMENT '事件类型: repair_new/repair_assign/repair_complete',
  `title` varchar(200) NOT NULL DEFAULT '' COMMENT '推送标题',
  `content` text COMMENT '推送内容',
  `target_type` varchar(20) NOT NULL DEFAULT '' COMMENT '目标类型: worker/admin/staff',
  `target_id` int(11) UNSIGNED NOT NULL DEFAULT '0' COMMENT '目标用户ID',
  `target_data` text COMMENT '附加数据JSON',
  `community_id` int(11) UNSIGNED NOT NULL DEFAULT '0' COMMENT '小区ID',
  `sse_sent` tinyint(1) NOT NULL DEFAULT '0' COMMENT 'SSE推送状态 0未推送 1已推送',
  `wechat_sent` tinyint(1) NOT NULL DEFAULT '0' COMMENT '微信推送状态 0未推送 1已推送 2推送失败',
  `sms_sent` tinyint(1) NOT NULL DEFAULT '0' COMMENT '短信推送状态 0未推送 1已推送 2推送失败',
  `create_time` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT '创建时间',
  PRIMARY KEY (`id`),
  KEY `idx_target` (`target_type`, `target_id`),
  KEY `idx_community` (`community_id`),
  KEY `idx_event_type` (`event_type`),
  KEY `idx_sse_sent` (`sse_sent`),
  KEY `idx_create_time` (`create_time`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='统一推送事件记录表';
