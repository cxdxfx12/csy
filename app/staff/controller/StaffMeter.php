<?php
namespace app\staff\controller;

use app\staff\BaseStaff;
use think\facade\Db;

class StaffMeter extends BaseStaff
{
    public function lists()
    {
        [$page, $limit] = $this->getPage();
        $where = [['m.delete_time', '=', null]];
        $communityId = $this->request->param('community_id', 0);
        if ($communityId) $where[] = ['m.community_id', '=', $communityId];
        $total = Db::name('meter_reading')->alias('m')
            ->leftJoin('room r', 'r.id = m.room_id')
            ->field('m.*, r.room_number, r.building_name')
            ->where($where)->count();
        $list = Db::name('meter_reading')->alias('m')
            ->leftJoin('room r', 'r.id = m.room_id')
            ->field('m.*, r.room_number, r.building_name')
            ->where($where)->page($page, $limit)->order('m.id', 'desc')->select()->toArray();
        return $this->success(['list' => $list, 'total' => $total]);
    }

    public function read()
    {
        $data = $this->request->post();
        $data['usage_amount'] = $data['current_reading'] - $data['previous_reading'];
        $data['operator_id'] = $this->staffId;
        $data['reading_by'] = $this->staffInfo['nickname'] ?? '';
        $data['create_time'] = date('Y-m-d H:i:s');
        Db::name('meter_reading')->insert($data);
        return $this->success([], '录入成功');
    }

    public function history()
    {
        $roomId = $this->request->param('room_id', 0);
        $type = $this->request->param('type', 1);
        $list = Db::name('meter_reading')->where('room_id', $roomId)->where('type', $type)
            ->order('id', 'desc')->limit(12)->select()->toArray();
        return $this->success($list);
    }
}
