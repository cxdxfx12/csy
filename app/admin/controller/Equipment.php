<?php
namespace app\admin\controller;

use app\admin\BaseAdmin;
use think\facade\Db;

class Equipment extends BaseAdmin
{
    public function lists()
    {
        [$page, $limit] = $this->getPage();
        $cid = $this->getFilteredCommunityId();
        $category = $this->request->param('category', 0);
        $status = $this->request->param('status', '');
        $keyword = $this->request->param('keyword', '');

        $cntQuery = Db::name('equipment')->alias('e')->whereNull('`e`.`delete_time`');
        if ($cid === -1) $cntQuery->where('`e`.`community_id`', 'in', $this->request->boundCommunityIds);
        elseif ($cid > 0) $cntQuery->where('`e`.`community_id`', '=', intval($cid));
        if ($category) $cntQuery->where('`e`.`category`', '=', $category);
        if ($status !== '') $cntQuery->where('`e`.`status`', '=', $status);
        if ($keyword) $cntQuery->where('`e`.`name`|`e`.`code`|`e`.`model`', 'like', "%{$keyword}%");
        $total = $cntQuery->count();

        $listQuery = Db::name('equipment')->alias('e')
            ->leftJoin('community c', 'c.id = e.community_id')
            ->field('e.*, c.name as community_name')
            ->whereNull('`e`.`delete_time`');
        if ($cid === -1) $listQuery->where('`e`.`community_id`', 'in', $this->request->boundCommunityIds);
        elseif ($cid > 0) $listQuery->where('`e`.`community_id`', '=', intval($cid));
        if ($category) $listQuery->where('`e`.`category`', '=', $category);
        if ($status !== '') $listQuery->where('`e`.`status`', '=', $status);
        if ($keyword) $listQuery->where('`e`.`name`|`e`.`code`|`e`.`model`', 'like', "%{$keyword}%");
        $list = $listQuery->page($page, $limit)->order('e.id', 'desc')->select();
        return $this->table($list, $total);
    }

    public function add()
    {
        $data = $this->request->post();
        $data['create_time'] = date('Y-m-d H:i:s');
        Db::name('equipment')->insert($data);
        return $this->success([], '添加成功');
    }

    public function edit()
    {
        $data = $this->request->post();
        Db::name('equipment')->where('id', $data['id'])->update($data);
        return $this->success([], '修改成功');
    }

    public function delete()
    {
        $id = $this->request->post('id', 0);
        Db::name('equipment')->where('id', $id)->update(['delete_time' => date('Y-m-d H:i:s')]);
        return $this->success([], '删除成功');
    }
}
