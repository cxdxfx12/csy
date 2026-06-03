<?php
namespace app\admin\controller;

use app\admin\BaseAdmin;
use think\facade\Db;

class ParkingSpace extends BaseAdmin
{
    public function lists()
    {
        [$page, $limit] = $this->getPage();
        $where = [['ps.delete_time', 'null', '']];
        $communityId = $this->request->param('community_id', 0);
        if ($communityId) $where[] = ['ps.community_id', '=', $communityId];
        $status = $this->request->param('status', '');
        if ($status !== '') $where[] = ['ps.status', '=', $status];
        $keyword = $this->request->param('keyword', '');
        if ($keyword) $where[] = ['ps.space_no', 'like', "%{$keyword}%"];
        $total = Db::name('parking_space')->alias('ps')->where($where)->count();
        $list = Db::name('parking_space')->alias('ps')
            ->leftJoin('owner o', 'o.id = ps.owner_id')
            ->leftJoin('community c', 'c.id = ps.community_id')
            ->field('ps.*, ps.monthly_fee as price, o.realname as owner_name, o.phone as owner_phone, c.name as community_name')
            ->where($where)->page($page, $limit)->order('ps.id', 'desc')->select();
        return $this->table($list, $total);
    }

    public function add()
    {
        $data = $this->request->post();
        $data['create_time'] = date('Y-m-d H:i:s');
        if (isset($data['price'])) { $data['monthly_fee'] = $data['price']; unset($data['price']); }
        Db::name('parking_space')->insert($data);
        return $this->success([], '添加成功');
    }

    public function edit()
    {
        $data = $this->request->post();
        if (isset($data['price'])) { $data['monthly_fee'] = $data['price']; unset($data['price']); }
        Db::name('parking_space')->where('id', $data['id'])->update($data);
        return $this->success([], '修改成功');
    }

    public function delete()
    {
        $id = $this->request->post('id', 0);
        Db::name('parking_space')->where('id', $id)->update(['delete_time' => date('Y-m-d H:i:s')]);
        return $this->success([], '删除成功');
    }
}
