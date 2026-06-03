<?php
namespace app\admin\controller;

use app\admin\BaseAdmin;
use think\facade\Db;

class AccessCard extends BaseAdmin
{
    public function lists()
    {
        [$page, $limit] = $this->getPage();
        $where = [['c.delete_time', '=', null]];
        $communityId = $this->request->param('community_id', 0);
        if ($communityId) $where[] = ['c.community_id', '=', $communityId];
        $status = $this->request->param('status', '');
        if ($status !== '') $where[] = ['c.status', '=', $status];
        $total = Db::name('access_card')->alias('c')->where($where)->count();
        $list = Db::name('access_card')->alias('c')
            ->leftJoin('owner o', 'o.id = c.owner_id')
            ->field('c.*, o.realname as owner_name')
            ->where($where)->page($page, $limit)->order('c.id', 'desc')->select();
        return $this->table($list, $total);
    }

    public function add()
    {
        $data = $this->request->post();
        $data['create_time'] = date('Y-m-d H:i:s');
        Db::name('access_card')->insert($data);
        return $this->success([], '添加成功');
    }

    public function edit()
    {
        $data = $this->request->post();
        Db::name('access_card')->where('id', $data['id'])->update($data);
        return $this->success([], '修改成功');
    }

    public function delete()
    {
        $id = $this->request->post('id', 0);
        Db::name('access_card')->where('id', $id)->update(['delete_time' => date('Y-m-d H:i:s')]);
        return $this->success([], '删除成功');
    }
}
