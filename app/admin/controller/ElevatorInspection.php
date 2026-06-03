<?php
namespace app\admin\controller;

use app\admin\BaseAdmin;
use think\facade\Db;

class ElevatorInspection extends BaseAdmin
{
    public function lists()
    {
        [$page, $limit] = $this->getPage();
        $where = [['ei.delete_time', 'null', '']];
        $keyword = $this->request->param('keyword', '');
        if ($keyword) $where[] = ['ei.inspector|ei.inspection_company|ei.remark|e.elevator_no', 'like', "%{$keyword}%"];
        $elevatorId = $this->request->param('elevator_id', 0);
        if ($elevatorId) $where[] = ['ei.elevator_id', '=', intval($elevatorId)];
        $communityId = $this->request->param('community_id', 0);
        if ($communityId) $where[] = ['e.community_id', '=', intval($communityId)];
        $inspectionType = $this->request->param('inspection_type', '');
        if ($inspectionType !== '') $where[] = ['ei.inspection_type', '=', intval($inspectionType)];
        $result = $this->request->param('result', '');
        if ($result !== '') $where[] = ['ei.result', '=', intval($result)];
        $total = Db::name('elevator_inspection')->alias('ei')->where($where)->count();
        $list = Db::name('elevator_inspection')->alias('ei')
            ->leftJoin('elevator e', 'e.id = ei.elevator_id')
            ->leftJoin('community c', 'c.id = e.community_id')
            ->field('ei.*, e.elevator_no, e.community_id, c.name as community_name')
            ->where($where)->page($page, $limit)->order('ei.id', 'desc')->select();
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
