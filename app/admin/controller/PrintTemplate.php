<?php
namespace app\admin\controller;

use app\admin\BaseAdmin;
use think\facade\Db;

class PrintTemplate extends BaseAdmin
{
    public function lists()
    {
        [$page, $limit] = $this->getPage();
        $where = [['delete_time', 'null', '']];
        $keyword = $this->request->param('keyword', '');
        if ($keyword) $where[] = ['name|code|content', 'like', "%{$keyword}%"];
        $type = $this->request->param('type', '');
        if ($type) $where[] = ['type', '=', $type];
        $status = $this->request->param('status', '');
        if ($status !== '') $where[] = ['status', '=', intval($status)];
        $cid = $this->getFilteredCommunityId();
        if ($cid === -1) $where[] = ['community_id', 'in', $this->request->boundCommunityIds];
        elseif ($cid > 0) $where[] = ['community_id', '=', $cid];
        $total = Db::name('print_template')->where($where)->count();
        $list = Db::name('print_template')->where($where)->page($page, $limit)->order('id', 'desc')->select();
        return $this->table($list, $total);
    }

    public function add()
    {
        $data = $this->request->post();
        $data['create_time'] = date('Y-m-d H:i:s');
        Db::name('print_template')->insert($data);
        return $this->success([], '添加成功');
    }

    public function edit()
    {
        $data = $this->request->post();
        Db::name('print_template')->where('id', $data['id'])->update($data);
        return $this->success([], '修改成功');
    }

    public function delete()
    {
        $id = $this->request->post('id', 0);
        Db::name('print_template')->where('id', $id)->update(['delete_time' => date('Y-m-d H:i:s')]);
        return $this->success([], '删除成功');
    }
}
