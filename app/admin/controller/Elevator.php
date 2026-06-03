<?php
namespace app\admin\controller;

use app\admin\BaseAdmin;
use think\facade\Db;

class Elevator extends BaseAdmin
{
    public function listAll()
    {
        $list = Db::name('elevator')->where('delete_time', null)->field('id, elevator_no, community_id, building_id, brand, model')->order('id', 'desc')->select();
        return $this->success($list);
    }

    public function lists()
    {
        [$page, $limit] = $this->getPage();
        $where = [['e.delete_time', 'null', '']];
        $keyword = $this->request->param('keyword', '');
        if ($keyword) $where[] = ['e.elevator_no|e.brand|e.model|e.maintain_company|e.remark', 'like', "%{$keyword}%"];
        $communityId = $this->request->param('community_id', 0);
        if ($communityId) $where[] = ['e.community_id', '=', intval($communityId)];
        $buildingId = $this->request->param('building_id', 0);
        if ($buildingId) $where[] = ['e.building_id', '=', intval($buildingId)];
        $status = $this->request->param('status', '');
        if ($status !== '') $where[] = ['e.status', '=', intval($status)];
        $total = Db::name('elevator')->alias('e')->where($where)->count();
        $list = Db::name('elevator')->alias('e')
            ->leftJoin('community c', 'c.id = e.community_id')
            ->leftJoin('building b', 'b.id = e.building_id')
            ->field('e.*, c.name as community_name, b.name as building_name')
            ->where($where)->page($page, $limit)->order('e.id', 'desc')->select();
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
