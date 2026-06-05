<?php
/**
 * 一次性建表脚本 - 执行后请立即删除
 * 访问: http://www.hbdxm.com/_run_create_table.php
 */

$host = '127.0.0.1';
$user = 'root';
$pass = 'cxdxfx12';
$db   = 'dasheng';

$sql = "CREATE TABLE IF NOT EXISTS `ds_notification` (
  `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
  `community_id` int(11) UNSIGNED NOT NULL DEFAULT '0' COMMENT '小区ID',
  `title` varchar(200) NOT NULL DEFAULT '' COMMENT '推送标题',
  `content` text COMMENT '推送内容',
  `target` varchar(200) NOT NULL DEFAULT '' COMMENT '目标人群',
  `type` varchar(30) NOT NULL DEFAULT 'notice' COMMENT '推送类型 notice/urgent/bill/activity/system',
  `priority` tinyint(1) NOT NULL DEFAULT '1' COMMENT '优先级 1普通 2重要 3紧急',
  `status` tinyint(1) NOT NULL DEFAULT '1' COMMENT '状态 0停用 1启用 2已发送 3已取消',
  `sent_count` int(11) NOT NULL DEFAULT '0' COMMENT '已发送数',
  `read_count` int(11) NOT NULL DEFAULT '0' COMMENT '已读数',
  `send_time` datetime DEFAULT NULL COMMENT '发送时间',
  `admin_id` int(11) UNSIGNED NOT NULL DEFAULT '0' COMMENT '操作管理员ID',
  `remark` varchar(500) NOT NULL DEFAULT '' COMMENT '备注',
  `create_time` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `update_time` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `delete_time` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_community_id` (`community_id`),
  KEY `idx_type` (`type`),
  KEY `idx_status` (`status`),
  KEY `idx_create_time` (`create_time`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='消息推送通知';";

try {
    $pdo = new PDO("mysql:host={$host};dbname={$db};charset=utf8mb4", $user, $pass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
    ]);
    $pdo->exec($sql);
    echo "<h2>✅ ds_notification 表创建成功！</h2>";
    echo "<p>共创建 15 个字段，4 个索引。</p>";
    echo "<p style='color:red'><b>⚠️ 请立即删除此文件：_run_create_table.php</b></p>";
} catch (PDOException $e) {
    echo "<h2>❌ 创建失败</h2>";
    echo "<pre>" . $e->getMessage() . "</pre>";
}
