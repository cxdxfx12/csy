<?php
namespace app\admin\controller;

use app\admin\BaseAdmin;
use think\facade\Db;

class ElevatorFault extends BaseAdmin
{
    public function lists()
    {
        [$page, $limit] = $this->getPage();
        $cid = $this->getFilteredCommunityId();
        $keyword = $this->request->param('keyword', '');
        $elevatorId = $this->request->param('elevator_id', 0);
        $faultType = $this->request->param('fault_type', '');
        $status = $this->request->param('status', '');

        $cntQuery = Db::name('elevator_fault')->alias('ef')
            ->leftJoin('elevator e', 'e.id = ef.elevator_id')
            ->whereNull('`ef`.`delete_time`');
        if ($cid === -1) $cntQuery->where('`e`.`community_id`', 'in', $this->request->boundCommunityIds);
        elseif ($cid > 0) $cntQuery->where('`e`.`community_id`', '=', intval($cid));
        if ($keyword) $cntQuery->where('`ef`.`description`|`ef`.`fault_type`|`ef`.`repair_company`|`e`.`elevator_no`', 'like', "%{$keyword}%");
        if ($elevatorId) $cntQuery->where('`ef`.`elevator_id`', '=', intval($elevatorId));
        if ($faultType !== '') $cntQuery->where('`ef`.`fault_type`', '=', $faultType);
        if ($status !== '') $cntQuery->where('`ef`.`status`', '=', intval($status));
        $total = $cntQuery->count();

        $listQuery = Db::name('elevator_fault')->alias('ef')
            ->leftJoin('elevator e', 'e.id = ef.elevator_id')
            ->leftJoin('community c', 'c.id = e.community_id')
            ->field('ef.*, e.elevator_no, e.community_id, c.name as community_name')
            ->whereNull('`ef`.`delete_time`');
        if ($cid === -1) $listQuery->where('`e`.`community_id`', 'in', $this->request->boundCommunityIds);
        elseif ($cid > 0) $listQuery->where('`e`.`community_id`', '=', intval($cid));
        if ($keyword) $listQuery->where('`ef`.`description`|`ef`.`fault_type`|`ef`.`repair_company`|`e`.`elevator_no`', 'like', "%{$keyword}%");
        if ($elevatorId) $listQuery->where('`ef`.`elevator_id`', '=', intval($elevatorId));
        if ($faultType !== '') $listQuery->where('`ef`.`fault_type`', '=', $faultType);
        if ($status !== '') $listQuery->where('`ef`.`status`', '=', intval($status));
        $list = $listQuery->page($page, $limit)->order('ef.id', 'desc')->select();
        return $this->table($list, $total);
    }

    public function add()
    {
        $data = $this->request->post();
        $data['create_time'] = date('Y-m-d H:i:s');
        Db::name('elevator_fault')->insert($data);
        return $this->success([], '添加成功');
    }

    public function edit()
    {
        $data = $this->request->post();
        Db::name('elevator_fault')->where('id', $data['id'])->update($data);
        return $this->success([], '修改成功');
    }

    public function delete()
    {
        $id = $this->request->post('id', 0);
        Db::name('elevator_fault')->where('id', $id)->update(['delete_time' => date('Y-m-d H:i:s')]);
        return $this->success([], '删除成功');
    }
}
