<?php
namespace app\admin\controller;

use app\admin\BaseAdmin;
use think\facade\Db;

class ElevatorInspection extends BaseAdmin
{
    public function lists()
    {
        [$page, $limit] = $this->getPage();
        $cid = $this->getFilteredCommunityId();
        $keyword = $this->request->param('keyword', '');
        $elevatorId = $this->request->param('elevator_id', 0);
        $inspectionType = $this->request->param('inspection_type', '');
        $result = $this->request->param('result', '');

        $cntQuery = Db::name('elevator_inspection')->alias('ei')
            ->leftJoin('elevator e', 'e.id = ei.elevator_id')
            ->whereNull('`ei`.`delete_time`');
        if ($cid === -1) $cntQuery->where('`e`.`community_id`', 'in', $this->request->boundCommunityIds);
        elseif ($cid > 0) $cntQuery->where('`e`.`community_id`', '=', intval($cid));
        if ($keyword) $cntQuery->where('`ei`.`inspector`|`ei`.`inspection_company`|`ei`.`remark`|`e`.`elevator_no`', 'like', "%{$keyword}%");
        if ($elevatorId) $cntQuery->where('`ei`.`elevator_id`', '=', intval($elevatorId));
        if ($inspectionType !== '') $cntQuery->where('`ei`.`inspection_type`', '=', intval($inspectionType));
        if ($result !== '') $cntQuery->where('`ei`.`result`', '=', intval($result));
        $total = $cntQuery->count();

        $listQuery = Db::name('elevator_inspection')->alias('ei')
            ->leftJoin('elevator e', 'e.id = ei.elevator_id')
            ->leftJoin('community c', 'c.id = e.community_id')
            ->field('ei.*, e.elevator_no, e.community_id, c.name as community_name')
            ->whereNull('`ei`.`delete_time`');
        if ($cid === -1) $listQuery->where('`e`.`community_id`', 'in', $this->request->boundCommunityIds);
        elseif ($cid > 0) $listQuery->where('`e`.`community_id`', '=', intval($cid));
        if ($keyword) $listQuery->where('`ei`.`inspector`|`ei`.`inspection_company`|`ei`.`remark`|`e`.`elevator_no`', 'like', "%{$keyword}%");
        if ($elevatorId) $listQuery->where('`ei`.`elevator_id`', '=', intval($elevatorId));
        if ($inspectionType !== '') $listQuery->where('`ei`.`inspection_type`', '=', intval($inspectionType));
        if ($result !== '') $listQuery->where('`ei`.`result`', '=', intval($result));
        $list = $listQuery->page($page, $limit)->order('ei.id', 'desc')->select();
        return $this->table($list, $total);
    }

    public function add()
    {
        $data = $this->request->post();
        $data['create_time'] = date('Y-m-d H:i:s');
        Db::name('elevator_inspection')->insert($data);
        return $this->success([], '添加成功');
    }

    public function edit()
    {
        $data = $this->request->post();
        Db::name('elevator_inspection')->where('id', $data['id'])->update($data);
        return $this->success([], '修改成功');
    }

    public function delete()
    {
        $id = $this->request->post('id', 0);
        Db::name('elevator_inspection')->where('id', $id)->update(['delete_time' => date('Y-m-d H:i:s')]);
        return $this->success([], '删除成功');
    }
}
