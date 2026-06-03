<?php
namespace app\admin\controller;

use app\admin\BaseAdmin;
use think\facade\Db;

class Message extends BaseAdmin
{
    public function lists()
    {
        [$page, $limit] = $this->getPage();
        $where = [];
        $keyword = $this->request->param('keyword', '');
        if ($keyword) $where = [['title|content', 'like', "%{$keyword}%"]];
        $messageType = $this->request->param('type', '');
        if ($messageType) $where[] = ['type', '=', $messageType];
        $receiverType = $this->request->param('receiver_type', '');
        if ($receiverType) $where[] = ['receiver_type', '=', $receiverType];

        $total = Db::name('message')->where($where)->count();
        $list = Db::name('message')->where($where)->page($page, $limit)->order('id', 'desc')->select();

        return $this->table($list, $total);
    }

    public function add()
    {
        $data = $this->request->post();
        $data['create_time'] = date('Y-m-d H:i:s');
        Db::name('message')->insert($data);
        return $this->success([], '发送成功');
    }

    public function delete()
    {
        $id = $this->request->post('id', 0);
        Db::name('message')->where('id', $id)->delete();
        return $this->success([], '删除成功');
    }
}
