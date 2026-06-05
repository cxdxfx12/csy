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
        $data['create_time'] = date('Y-m-d H:i:s');
        Db::name('access_card')->insert($data);
        return $this->success([], '添加成功');
    }

    public function edit()
    {
        $data = $this->request->post();
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
