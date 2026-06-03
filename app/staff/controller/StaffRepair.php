<?php
namespace app\staff\controller;

use app\staff\BaseStaff;
use think\facade\Db;

class StaffRepair extends BaseStaff
{
    public function lists()
    {
        [$page, $limit] = $this->getPage();
        $where = [['ro.delete_time', '=', null]];
        $status = $this->request->param('status', '');
        if ($status !== '') $where[] = ['ro.status', '=', $status];
        $total = Db::name('repair_order')->alias('ro')->where($where)->count();
        $list = Db::name('repair_order')->alias('ro')
            ->leftJoin('room r', 'r.id = ro.room_id')
            ->field('ro.*, r.room_number, r.building_name')
            ->where($where)->page($page, $limit)->order('ro.id', 'desc')->select()->toArray();
        return $this->success(['list' => $list, 'total' => $total]);
    }

    public function detail()
    {
        $id = $this->request->param('id', 0);
        $info = Db::name('repair_order')->alias('ro')
            ->leftJoin('room r', 'r.id = ro.room_id')
            ->field('ro.*, r.room_number, r.building_name')
            ->where('ro.id', $id)->find();
        return $this->success($info);
    }

    public function accept()
    {
        $id = $this->request->post('id', 0);
        Db::name('repair_order')->where('id', $id)->update([
            'assignee_id' => $this->staffId,
            'accept_time' => date('Y-m-d H:i:s'),
            'status' => 3,
        ]);
        return $this->success([], '接单成功');
    }

    public function finish()
    {
        $id = $this->request->post('id', 0);
        $data = $this->request->post();
        $data['status'] = 4;
        $data['finish_time'] = date('Y-m-d H:i:s');
        Db::name('repair_order')->where('id', $id)->update($data);
        return $this->success([], '已完成');
    }
}
