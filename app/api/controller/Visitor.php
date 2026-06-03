<?php
namespace app\api\controller;

use app\api\BaseApi;
use think\facade\Db;

class Visitor extends BaseApi
{
    public function add()
    {
        $data = $this->request->post();
        $owner = Db::name('owner')->where('id', $this->ownerId)->find();
        $data['owner_id'] = $this->ownerId;
        $data['community_id'] = $owner['community_id'] ?? 0;
        $data['source'] = 1;
        $data['status'] = 1;
        $data['create_time'] = date('Y-m-d H:i:s');
        Db::name('visitor')->insert($data);
        return $this->success([], '预约成功');
    }

    public function lists()
    {
        [$page, $limit] = $this->getPage();
        $where = [['owner_id', '=', $this->ownerId], ['delete_time', 'null', '']];
        $total = Db::name('visitor')->where($where)->count();
        $list = Db::name('visitor')->where($where)->page($page, $limit)->order('id', 'desc')->select();
        return $this->success(['list' => $list, 'total' => $total]);
    }
}
