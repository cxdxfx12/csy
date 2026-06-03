<?php
namespace app\admin\controller;

use app\admin\BaseAdmin;
use think\facade\Db;

class Equipment extends BaseAdmin
{
    public function lists()
    {
        [$page, $limit] = $this->getPage();
        $where = [['e.delete_time', 'null', '']];
        $communityId = $this->request->param('community_id', 0);
        if ($communityId) $where[] = ['e.community_id', '=', $communityId];
        $category = $this->request->param('category', 0);
        if ($category) $where[] = ['e.category', '=', $category];
        $status = $this->request->param('status', '');
        if ($status !== '') $where[] = ['e.status', '=', $status];
        $keyword = $this->request->param('keyword', '');
        if ($keyword) $where[] = ['e.name|e.code|e.model', 'like', "%{$keyword}%"];

        $total = Db::name('equipment')->alias('e')->where($where)->count();
        $list = Db::name('equipment')->alias('e')
            ->leftJoin('community c', 'c.id = e.community_id')
            ->field('e.*, c.name as community_name')
            ->where($where)->page($page, $limit)->order('e.id', 'desc')->select();
        return $this->table($list, $total);
    }

    public function add()
    {
        $data = $this->request->post();
        $data['create_time'] = date('Y-m-d H:i:s');
        Db::name('equipment')->insert($data);
        return $this->success([], '添加成功');
    }

    public function edit()
    {
        $data = $this->request->post();
        Db::name('equipment')->where('id', $data['id'])->update($data);
        return $this->success([], '修改成功');
    }

    public function delete()
    {
        $id = $this->request->post('id', 0);
        Db::name('equipment')->where('id', $id)->update(['delete_time' => date('Y-m-d H:i:s')]);
        return $this->success([], '删除成功');
    }
}
