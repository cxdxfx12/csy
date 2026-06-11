<?php
namespace app\admin\controller;

use app\admin\BaseAdmin;
use think\facade\Db;

class AccessCard extends BaseAdmin
{
    public function lists()
    {
        [$page, $limit] = $this->getPage();
        $cid = $this->getFilteredCommunityId();
        $status = $this->request->param('status', '');

        $cntQuery = Db::name('access_card')->alias('c')->whereNull('`c`.`delete_time`');
        if ($cid === -1) $cntQuery->where('`c`.`community_id`', 'in', $this->request->boundCommunityIds);
        elseif ($cid > 0) $cntQuery->where('`c`.`community_id`', '=', intval($cid));
        if ($status !== '') $cntQuery->where('`c`.`status`', '=', $status);
        $total = $cntQuery->count();

        $listQuery = Db::name('access_card')->alias('c')
            ->leftJoin('owner o', 'o.id = c.owner_id')
            ->leftJoin('community com', 'com.id = c.community_id')
            ->field('c.*, o.realname as owner_name, com.name as community_name')
            ->whereNull('`c`.`delete_time`');
        if ($cid === -1) $listQuery->where('`c`.`community_id`', 'in', $this->request->boundCommunityIds);
        elseif ($cid > 0) $listQuery->where('`c`.`community_id`', '=', intval($cid));
        if ($status !== '') $listQuery->where('`c`.`status`', '=', $status);
        $list = $listQuery->page($page, $limit)->order('c.id', 'desc')->select();
        return $this->table($list, $total);
    }

    public function add()
    {
        $data = $this->request->post();
        unset($data['id']); // 移除 id 字段，让 MySQL 自增
        $data['create_time'] = date('Y-m-d H:i:s');
        // 自动生成唯一卡号，避免 uk_card_no 唯一索引冲突
        if (empty($data['card_no'])) {
            $data['card_no'] = 'AC_' . date('YmdHis') . '_' . substr(md5(uniqid(mt_rand(), true)), 0, 6);
        }
        Db::name('access_card')->insert($data);
        return $this->success([], '添加成功');
    }

    public function edit()
    {
        $data = $this->request->post();
        $item = Db::name('access_card')->where('id', $data['id'])->find();
        if (!$item) return $this->error('门禁卡不存在');
        // 如果 card_no 被清空，保留原值
        if (empty($data['card_no']) && !empty($item['card_no'])) {
            $data['card_no'] = $item['card_no'];
        }
        Db::name('access_card')->where('id', $data['id'])->update($data);
        return $this->success([], '修改成功');
    }

    public function delete()
    {
        $id = $this->request->post('id', 0);
        Db::name('access_card')->where('id', $id)->update(['delete_time' => date('Y-m-d H:i:s')]);
        return $this->success([], '删除成功');
    }
}
