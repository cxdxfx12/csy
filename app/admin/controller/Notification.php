<?php
namespace app\admin\controller;

use app\admin\BaseAdmin;
use think\facade\Db;

class Notification extends BaseAdmin
{
    public function lists()
    {
        [$page, $limit] = $this->getPage();
        $where = [['delete_time', 'null', '']];
        $keyword = $this->request->param('keyword', '');
        if ($keyword) $where[] = ['title|content', 'like', "%{$keyword}%"];
        $communityId = $this->request->param('community_id', 0);
        if ($communityId) $where[] = ['community_id', '=', $communityId];

        try {
            $total = Db::name('notification')->where($where)->count();
            $list = Db::name('notification')->where($where)->page($page, $limit)->order('id', 'desc')->select();
        } catch (\Exception $e) {
            // 表不存在则自动创建
            if (stripos($e->getMessage(), 'exist') !== false || stripos($e->getMessage(), 'not found') !== false) {
                $this->ensureTable();
                $total = 0;
                $list = [];
            } else {
                throw $e;
            }
        }

        return $this->success([
            'list' => $list,
            'total' => $total,
        ], '获取成功');
    }

    private function ensureTable()
    {
        $sql = "CREATE TABLE IF NOT EXISTS `ds_notification` (
  `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
  `community_id` int(11) UNSIGNED NOT NULL DEFAULT '0' COMMENT '小区ID',
  `title` varchar(200) NOT NULL DEFAULT '' COMMENT '推送标题',
  `content` text COMMENT '推送内容',
  `target` varchar(200) NOT NULL DEFAULT '' COMMENT '目标人群',
  `type` varchar(30) NOT NULL DEFAULT 'notice' COMMENT '推送类型',
  `priority` tinyint(1) NOT NULL DEFAULT '1' COMMENT '优先级',
  `status` tinyint(1) NOT NULL DEFAULT '1' COMMENT '状态',
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
        Db::execute($sql);
    }

    public function add()
    {
        $data = $this->request->post();
        // 只接受合法字段，防止多余字段导致SQL错误
        $allowFields = ['title', 'content', 'target', 'type', 'priority', 'status', 'community_id'];
        $insertData = array_intersect_key($data, array_flip($allowFields));
        if (empty($insertData['title'])) return $this->error('标题不能为空');
        $insertData['title'] = $insertData['title'] ?? '';
        $insertData['content'] = $insertData['content'] ?? '';
        $insertData['target'] = $insertData['target'] ?? '';
        $insertData['type'] = $insertData['type'] ?? 'notice';
        $insertData['priority'] = intval($insertData['priority'] ?? 1);
        $insertData['status'] = intval($insertData['status'] ?? 1);
        $insertData['community_id'] = intval($insertData['community_id'] ?? 0);
        $insertData['sent_count'] = 0;
        $insertData['read_count'] = 0;
        $insertData['create_time'] = date('Y-m-d H:i:s');

        try {
            Db::name('notification')->insert($insertData);
        } catch (\Exception $e) {
            if (stripos($e->getMessage(), 'exist') !== false || stripos($e->getMessage(), 'not found') !== false) {
                $this->ensureTable();
                Db::name('notification')->insert($insertData);
            } else {
                throw $e;
            }
        }

        return $this->success([], '添加成功');
    }

    public function edit()
    {
        $data = $this->request->post();
        $allowFields = ['title', 'content', 'target', 'type', 'priority', 'status', 'community_id'];
        $updateData = array_intersect_key($data, array_flip($allowFields));
        $id = intval($data['id'] ?? 0);
        if (!$id) return $this->error('参数错误');
        Db::name('notification')->where('id', $id)->update($updateData);
        return $this->success([], '修改成功');
    }

    public function delete()
    {
        $id = $this->request->post('id', 0);
        Db::name('notification')->where('id', $id)->update(['delete_time' => date('Y-m-d H:i:s')]);
        return $this->success([], '删除成功');
    }

    public function createTable()
    {
        $sql = "CREATE TABLE IF NOT EXISTS `ds_notification` (
  `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
  `community_id` int(11) UNSIGNED NOT NULL DEFAULT '0' COMMENT '小区ID',
  `title` varchar(200) NOT NULL DEFAULT '' COMMENT '推送标题',
  `content` text COMMENT '推送内容',
  `target` varchar(200) NOT NULL DEFAULT '' COMMENT '目标人群',
  `type` varchar(30) NOT NULL DEFAULT 'notice' COMMENT '推送类型',
  `priority` tinyint(1) NOT NULL DEFAULT '1' COMMENT '优先级',
  `status` tinyint(1) NOT NULL DEFAULT '1' COMMENT '状态',
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
        Db::execute($sql);
        return $this->success([], 'ds_notification 表创建成功');
    }
}
