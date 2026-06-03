<?php
namespace app\admin\controller;

use app\admin\BaseAdmin;
use think\facade\Db;

class Family extends BaseAdmin
{
    public function lists()
    {
        [$page, $limit] = $this->getPage();
        $where = [['f.delete_time', '=', null]];
        $ownerId = $this->request->param('owner_id', 0);
        if ($ownerId) $where[] = ['f.owner_id', '=', $ownerId];
        $total = Db::name('owner_family')->alias('f')->where($where)->count();
        $list = Db::name('owner_family')->alias('f')
            ->leftJoin('owner o', 'o.id = f.owner_id')
            ->leftJoin('room r', 'r.id = f.room_id')
            ->field('f.*, o.realname as owner_name, r.room_number')
            ->where($where)->page($page, $limit)->order('f.id', 'desc')->select();
        return $this->table($list, $total);
    }

    public function add()
    {
        $data = $this->request->post();
        $data['create_time'] = date('Y-m-d H:i:s');
        Db::name('owner_family')->insert($data);
        return $this->success([], '添加成功');
    }

    public function edit()
    {
        $data = $this->request->post();
        Db::name('owner_family')->where('id', $data['id'])->update($data);
        return $this->success([], '修改成功');
    }

    public function delete()
    {
        $id = $this->request->post('id', 0);
        Db::name('owner_family')->where('id', $id)->update(['delete_time' => date('Y-m-d H:i:s')]);
        return $this->success([], '删除成功');
    }
}
