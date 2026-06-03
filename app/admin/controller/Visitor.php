<?php
namespace app\admin\controller;

use app\admin\BaseAdmin;
use think\facade\Db;

class Visitor extends BaseAdmin
{
    public function lists()
    {
        [$page, $limit] = $this->getPage();
        $where = [['v.delete_time', '=', null]];
        $communityId = $this->request->param('community_id', 0);
        if ($communityId) $where[] = ['v.community_id', '=', $communityId];
        $status = $this->request->param('status', '');
        if ($status !== '') $where[] = ['v.status', '=', $status];
        $total = Db::name('visitor')->alias('v')->where($where)->count();
        $list = Db::name('visitor')->alias('v')
            ->leftJoin('owner o', 'o.id = v.owner_id')
            ->leftJoin('room r', 'r.id = v.room_id')
            ->field('v.*, v.visit_reason as visit_purpose, o.realname as owner_name, r.room_number, r.building_name')
            ->where($where)->page($page, $limit)->order('v.id', 'desc')->select();
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
