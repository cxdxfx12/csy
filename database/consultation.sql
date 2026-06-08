-- 大圣智慧物业 — 官网咨询留言表
CREATE TABLE IF NOT EXISTS `ds_consultation` (
    `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
    `name` varchar(50) NOT NULL DEFAULT '' COMMENT '姓名',
    `phone` varchar(20) NOT NULL DEFAULT '' COMMENT '电话',
    `type` varchar(50) NOT NULL DEFAULT '' COMMENT '咨询类型',
    `content` text COMMENT '留言内容',
    `ip` varchar(50) NOT NULL DEFAULT '' COMMENT '提交IP',
    `user_agent` varchar(500) NOT NULL DEFAULT '' COMMENT '浏览器UA',
    `status` tinyint(1) NOT NULL DEFAULT 0 COMMENT '0未读 1已读',
    `remark` varchar(500) NOT NULL DEFAULT '' COMMENT '备注',
    `create_time` datetime DEFAULT NULL COMMENT '提交时间',
    `update_time` datetime DEFAULT NULL COMMENT '更新时间',
    PRIMARY KEY (`id`),
    KEY `idx_phone` (`phone`),
    KEY `idx_status` (`status`),
    KEY `idx_create_time` (`create_time`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='官网咨询留言';
