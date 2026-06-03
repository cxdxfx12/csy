<?php
namespace app\api\controller;

use app\api\BaseApi;
use think\facade\Db;

class Vehicle extends BaseApi
{
    public function lists()
    {
        $ownerId = $this->ownerId;
        $list = Db::name('vehicle')->where('owner_id', $ownerId)->where('delete_time', null)->select()->toArray();
        return $this->success($list);
    }

    public function add()
    {
        $data = $this->request->post();
        $owner = Db::name('owner')->where('id', $this->ownerId)->find();
        $data['owner_id'] = $this->ownerId;
        $data['community_id'] = $owner['community_id'] ?? 0;
        $data['create_time'] = date('Y-m-d H:i:s');
        Db::name('vehicle')->insert($data);
        return $this->success([], '添加成功');
    }
}
