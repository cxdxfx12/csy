<?php
namespace app\admin\controller;

use app\admin\BaseAdmin;
use think\facade\Db;

class ElevatorFault extends BaseAdmin
{
    public function lists()
    {
        [$page, $limit] = $this->getPage();
        $where = [['ef.delete_time', 'null', '']];
        $keyword = $this->request->param('keyword', '');
        if ($keyword) $where[] = ['ef.description|ef.fault_type|ef.repair_company|e.elevator_no', 'like', "%{$keyword}%"];
        $elevatorId = $this->request->param('elevator_id', 0);
        if ($elevatorId) $where[] = ['ef.elevator_id', '=', intval($elevatorId)];
        $communityId = $this->request->param('community_id', 0);
        if ($communityId) $where[] = ['e.community_id', '=', intval($communityId)];
        $faultType = $this->request->param('fault_type', '');
        if ($faultType !== '') $where[] = ['ef.fault_type', '=', $faultType];
        $status = $this->request->param('status', '');
        if ($status !== '') $where[] = ['ef.status', '=', intval($status)];
        $total = Db::name('elevator_fault')->alias('ef')->where($where)->count();
        $list = Db::name('elevator_fault')->alias('ef')
            ->leftJoin('elevator e', 'e.id = ef.elevator_id')
            ->leftJoin('community c', 'c.id = e.community_id')
            ->field('ef.*, e.elevator_no, e.community_id, c.name as community_name')
            ->where($where)->page($page, $limit)->order('ef.id', 'desc')->select();
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
