<?php
namespace app\admin\controller;

use app\admin\BaseAdmin;
use think\facade\Db;

class Device extends BaseAdmin
{
    public function listAll()
    {
        $list = Db::name('device')->where('delete_time', null)->field('id, device_name, device_code, device_type')->order('id', 'desc')->select();
        return $this->success($list);
    }

    public function lists()
    {
        [$page, $limit] = $this->getPage();
        $where = [['d.delete_time', 'null', '']];
        $keyword = $this->request->param('keyword', '');
        if ($keyword) $where[] = ['d.device_name|d.device_code|d.location|d.remark', 'like', "%{$keyword}%"];
        $communityId = $this->request->param('community_id', 0);
        if ($communityId) $where[] = ['d.community_id', '=', intval($communityId)];
        $deviceType = $this->request->param('device_type', '');
        if ($deviceType !== '') $where[] = ['d.device_type', '=', $deviceType];
        $status = $this->request->param('status', '');
        if ($status !== '') $where[] = ['d.status', '=', intval($status)];
        $total = Db::name('device')->alias('d')->where($where)->count();
        $list = Db::name('device')->alias('d')
            ->leftJoin('community c', 'c.id = d.community_id')
            ->field('d.*, c.name as community_name')
            ->where($where)->page($page, $limit)->order('d.id', 'desc')->select();
        return $this->table($list, $total);
    }

    public function add()
    {
        $data = $this->request->post();
        $data['create_time'] = date('Y-m-d H:i:s');
        $data['update_time'] = date('Y-m-d H:i:s');
        Db::name('device')->insert($data);
        return $this->success([], '添加成功');
    }

    public function edit()
    {
        $data = $this->request->post();
        $data['update_time'] = date('Y-m-d H:i:s');
        Db::name('device')->where('id', $data['id'])->update($data);
        return $this->success([], '修改成功');
    }

    public function delete()
    {
        $id = $this->request->post('id', 0);
        Db::name('device')->where('id', $id)->update(['delete_time' => date('Y-m-d H:i:s')]);
        return $this->success([], '删除成功');
    }
}
