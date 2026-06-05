<?php
namespace app\api\controller;

use app\api\BaseApi;
use think\facade\Db;

class Vehicle extends BaseApi
{
    public function lists()
    {
        $ownerId = $this->ownerId;
        $where = [
            ['owner_id', '=', $ownerId],
            ['delete_time', 'null', ''],
        ];
        $list = Db::name('vehicle')->where($where)->select();
        return $this->success($list);
    }

    public function add()
    {
        $data = $this->request->post();
        $owner = Db::name('owner')->where('id', $this->ownerId)->find();
        $data['owner_id'] = $this->ownerId;
        $data['community_id'] = $owner['community_id'] ?? 0;
        $data['create_time'] = date('Y-m-d H:i:s');

        // 只保留 ds_vehicle 表中存在的字段
        $allowed = ['plate_number','brand','color','owner_id','community_id',
            'vehicle_type','space_code','remark','status','create_time'];
        $data = array_intersect_key($data, array_flip($allowed));

        Db::name('vehicle')->insert($data);
        return $this->success([], '添加成功');
    }
}
