<?php
namespace app\admin\controller;

use app\admin\BaseAdmin;
use think\facade\Db;

class Community extends BaseAdmin
{
    public function lists()
    {
        [$page, $limit] = $this->getPage();
        $where = [['delete_time', '=', null]];
        $keyword = $this->request->param('keyword', '');
        if ($keyword) $where[] = ['name|code|address', 'like', "%{$keyword}%"];
        $total = Db::name('community')->where($where)->count();
        $list = Db::name('community')->where($where)->page($page, $limit)->order('id', 'desc')->select();
        return $this->table($list, $total);
    }

    public function add()
    {
        $data = $this->request->post();
        $data['create_time'] = date('Y-m-d H:i:s');
        Db::name('community')->insert($data);
        return $this->success([], '添加成功');
    }

    public function edit()
    {
        $data = $this->request->post();
        Db::name('community')->where('id', $data['id'])->update($data);
        return $this->success([], '修改成功');
    }

    public function delete()
    {
        $id = $this->request->post('id', 0);
        Db::name('community')->where('id', $id)->update(['delete_time' => date('Y-m-d H:i:s')]);
        return $this->success([], '删除成功');
    }
}
