<?php
namespace app\admin\controller;

use app\admin\BaseAdmin;
use think\facade\Db;

class Visitor extends BaseAdmin
{
    public function lists()
    {
        [$page, $limit] = $this->getPage();
        $cid = $this->getFilteredCommunityId();
        $status = $this->request->param('status', '');

        $cntQuery = Db::name('visitor')->alias('v')->whereNull('`v`.`delete_time`');
        if ($cid === -1) $cntQuery->where('`v`.`community_id`', 'in', $this->request->boundCommunityIds);
        elseif ($cid > 0) $cntQuery->where('`v`.`community_id`', '=', intval($cid));
        if ($status !== '') $cntQuery->where('`v`.`status`', '=', $status);
        $total = $cntQuery->count();

        $listQuery = Db::name('visitor')->alias('v')
            ->leftJoin('owner o', 'o.id = v.owner_id')
            ->leftJoin('room r', 'r.id = v.room_id')
            ->leftJoin('community com', 'com.id = v.community_id')
            ->field('v.*, v.visit_reason as visit_purpose, o.realname as owner_name, r.room_number, r.building_name, com.name as community_name')
            ->whereNull('`v`.`delete_time`');
        if ($cid === -1) $listQuery->where('`v`.`community_id`', 'in', $this->request->boundCommunityIds);
        elseif ($cid > 0) $listQuery->where('`v`.`community_id`', '=', intval($cid));
        if ($status !== '') $listQuery->where('`v`.`status`', '=', $status);
        $list = $listQuery->order('v.id', 'desc')->page($page, $limit)->select();
        return $this->table($list, $total);
    }

    public function add()
    {
        $data = $this->request->post();
        $data['create_time'] = date('Y-m-d H:i:s');
        if (isset($data['visit_purpose'])) { $data['visit_reason'] = $data['visit_purpose']; unset($data['visit_purpose']); }
        if (isset($data['visitor_count'])) { $data['visitors'] = $data['visitor_count']; unset($data['visitor_count']); }
        Db::name('visitor')->insert($data);
        return $this->success([], '登记成功');
    }

    public function edit()
    {
        $data = $this->request->post();
        if (isset($data['visit_purpose'])) { $data['visit_reason'] = $data['visit_purpose']; unset($data['visit_purpose']); }
        if (isset($data['visitor_count'])) { $data['visitors'] = $data['visitor_count']; unset($data['visitor_count']); }
        Db::name('visitor')->where('id', $data['id'])->update($data);
        return $this->success([], '修改成功');
    }

    public function delete()
    {
        $id = $this->request->post('id', 0);
        Db::name('visitor')->where('id', $id)->update(['delete_time' => date('Y-m-d H:i:s')]);
        return $this->success([], '删除成功');
    }

    public function leave()
    {
        $id = $this->request->post('id', 0);
        Db::name('visitor')->where('id', $id)->update([
            'leave_time' => date('Y-m-d H:i:s'),
            'status' => 3,
        ]);
        return $this->success([], '已标记离开');
    }
}
