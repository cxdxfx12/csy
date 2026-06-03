<?php
namespace app\admin\controller;

use app\admin\BaseAdmin;
use think\facade\Db;

class Notice extends BaseAdmin
{
    public function lists()
    {
        [$page, $limit] = $this->getPage();
        $where = [['n.delete_time', '=', null]];
        $keyword = $this->request->param('keyword', '');
        if ($keyword) $where[] = ['n.title|n.content', 'like', "%{$keyword}%"];
        $communityId = $this->request->param('community_id', 0);
        if ($communityId) $where[] = ['n.community_id', '=', $communityId];
        $status = $this->request->param('status', '');
        if ($status !== '') $where[] = ['n.status', '=', $status];
        $total = Db::name('notice')->alias('n')->where($where)->count();
        $list = Db::name('notice')->alias('n')
            ->field('n.*')
            ->where($where)->page($page, $limit)->order('n.top_status desc, n.id desc')->select();
        return $this->table($list, $total);
    }

    public function add()
    {
        $data = $this->request->post();
        $data['published_by'] = get_admin_info()['nickname'] ?? '';
        $data['create_time'] = date('Y-m-d H:i:s');
        Db::name('notice')->insert($data);
        return $this->success([], '添加成功');
    }

    public function edit()
    {
        $data = $this->request->post();
        Db::name('notice')->where('id', $data['id'])->update($data);
        return $this->success([], '修改成功');
    }

    public function delete()
    {
        $id = $this->request->post('id', 0);
        Db::name('notice')->where('id', $id)->update(['delete_time' => date('Y-m-d H:i:s')]);
        return $this->success([], '删除成功');
    }

    public function publish()
    {
        $id = $this->request->post('id', 0);
        $status = $this->request->post('status', 2);
        $publishTime = $status == 2 ? date('Y-m-d H:i:s') : null;
        Db::name('notice')->where('id', $id)->update([
            'status' => $status,
            'publish_time' => $publishTime,
        ]);
        return $this->success([], $status == 2 ? '发布成功' : '已撤回');
    }
}
