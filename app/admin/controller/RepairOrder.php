<?php
namespace app\admin\controller;

use app\admin\BaseAdmin;
use think\facade\Db;

class RepairOrder extends BaseAdmin
{
    public function lists()
    {
        [$page, $limit] = $this->getPage();
        $where = [['ro.delete_time', '=', null]];
        $communityId = $this->request->param('community_id', 0);
        if ($communityId) $where[] = ['ro.community_id', '=', $communityId];
        $status = $this->request->param('status', '');
        if ($status !== '') $where[] = ['ro.status', '=', $status];
        $keyword = $this->request->param('keyword', '');
        if ($keyword) $where[] = ['ro.order_no|ro.reporter|ro.reporter_phone|r.room_number', 'like', "%{$keyword}%"];

        $total = Db::name('repair_order')->alias('ro')->where($where)->count();
        $list = Db::name('repair_order')->alias('ro')
            ->leftJoin('room r', 'r.id = ro.room_id')
            ->leftJoin('repair_worker rw', 'rw.id = ro.assignee_id')
            ->field('ro.*, r.room_number, r.building_name, rw.name as worker_name, rw.phone as worker_phone')
            ->where($where)->page($page, $limit)->order('ro.id', 'desc')->select();

        $typeMap = [1=>'水',2=>'电',3=>'气',4=>'门窗',5=>'管道',6=>'家电',7=>'网络',8=>'其他'];
        foreach ($list as &$row) {
            $row['type_name'] = $typeMap[$row['type']] ?? '未知';
        }

        return $this->table($list, $total);
    }

    public function add()
    {
        $data = $this->request->post();
        $data['order_no'] = build_order_no('DSR');
        $data['create_time'] = date('Y-m-d H:i:s');
        Db::name('repair_order')->insert($data);
        return $this->success([], '提交成功');
    }

    public function assign()
    {
        $id = $this->request->post('id', 0);
        $workerId = $this->request->post('worker_id', 0);
        Db::name('repair_order')->where('id', $id)->update([
            'assignee_id' => $workerId,
            'assign_time' => date('Y-m-d H:i:s'),
            'status' => 2,
        ]);
        Db::name('repair_worker')->where('id', $workerId)->inc('order_count')->update();
        return $this->success([], '派单成功');
    }

    public function close()
    {
        $id = $this->request->post('id', 0);
        $remark = $this->request->post('remark', '');
        Db::name('repair_order')->where('id', $id)->update([
            'status' => 6,
            'remark' => $remark,
        ]);
        return $this->success([], '已关闭');
    }

    public function detail()
    {
        $id = $this->request->param('id', 0);
        $info = Db::name('repair_order')->alias('ro')
            ->leftJoin('room r', 'r.id = ro.room_id')
            ->leftJoin('repair_worker rw', 'rw.id = ro.assignee_id')
            ->leftJoin('owner o', 'o.id = ro.owner_id')
            ->field('ro.*, r.room_number, r.building_name, rw.name as worker_name, rw.phone as worker_phone, o.realname as owner_name')
            ->where('ro.id', $id)->find();
        return $this->success($info);
    }

    public function delete()
    {
        $id = $this->request->post('id', 0);
        Db::name('repair_order')->where('id', $id)->update(['delete_time' => date('Y-m-d H:i:s')]);
        return $this->success([], '删除成功');
    }
}
