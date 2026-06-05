<?php
namespace app\admin\controller;

use app\admin\BaseAdmin;
use think\facade\Db;

class Elevator extends BaseAdmin
{
    public function listAll()
    {
        $cid = $this->getFilteredCommunityId();
        $query = Db::name('elevator')->whereNull('delete_time');
        if ($cid === -1) $query->where('community_id', 'in', $this->request->boundCommunityIds);
        elseif ($cid > 0) $query->where('community_id', '=', intval($cid));
        $list = $query->field('id, elevator_no, community_id, building_id, brand, model')->order('id', 'desc')->select();
        return $this->success($list);
    }

    public function lists()
    {
        [$page, $limit] = $this->getPage();
        $cid = $this->getFilteredCommunityId();
        $keyword = $this->request->param('keyword', '');
        $buildingId = $this->request->param('building_id', 0);
        $status = $this->request->param('status', '');

        $cntQuery = Db::name('elevator')->alias('e')->whereNull('`e`.`delete_time`');
        if ($cid === -1) $cntQuery->where('`e`.`community_id`', 'in', $this->request->boundCommunityIds);
        elseif ($cid > 0) $cntQuery->where('`e`.`community_id`', '=', intval($cid));
        if ($keyword) $cntQuery->where('`e`.`elevator_no`|`e`.`brand`|`e`.`model`|`e`.`maintain_company`|`e`.`remark`', 'like', "%{$keyword}%");
        if ($buildingId) $cntQuery->where('`e`.`building_id`', '=', intval($buildingId));
        if ($status !== '') $cntQuery->where('`e`.`status`', '=', intval($status));
        $total = $cntQuery->count();

        $listQuery = Db::name('elevator')->alias('e')
            ->leftJoin('community c', 'c.id = e.community_id')
            ->leftJoin('building b', 'b.id = e.building_id')
            ->field('e.*, c.name as community_name, b.name as building_name')
            ->whereNull('`e`.`delete_time`');
        if ($cid === -1) $listQuery->where('`e`.`community_id`', 'in', $this->request->boundCommunityIds);
        elseif ($cid > 0) $listQuery->where('`e`.`community_id`', '=', intval($cid));
        if ($keyword) $listQuery->where('`e`.`elevator_no`|`e`.`brand`|`e`.`model`|`e`.`maintain_company`|`e`.`remark`', 'like', "%{$keyword}%");
        if ($buildingId) $listQuery->where('`e`.`building_id`', '=', intval($buildingId));
        if ($status !== '') $listQuery->where('`e`.`status`', '=', intval($status));
        $list = $listQuery->page($page, $limit)->order('e.id', 'desc')->select();
        return $this->table($list, $total);
    }

    public function add()
    {
        $data = $this->request->post();
        $data['create_time'] = date('Y-m-d H:i:s');
        $data['update_time'] = date('Y-m-d H:i:s');
        Db::name('elevator')->insert($data);
        return $this->success([], '添加成功');
    }

    public function edit()
    {
        $data = $this->request->post();
        $data['update_time'] = date('Y-m-d H:i:s');
        Db::name('elevator')->where('id', $data['id'])->update($data);
        return $this->success([], '修改成功');
    }

    public function delete()
    {
        $id = $this->request->post('id', 0);
        Db::name('elevator')->where('id', $id)->update(['delete_time' => date('Y-m-d H:i:s')]);
        return $this->success([], '删除成功');
    }
}
