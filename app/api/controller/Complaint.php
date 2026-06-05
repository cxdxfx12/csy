<?php
namespace app\api\controller;

use app\api\BaseApi;
use think\facade\Db;

class Complaint extends BaseApi
{
    public function add()
    {
        $data = $this->request->post();
        $owner = Db::name('owner')->where('id', $this->ownerId)->find();
        $data['complaint_no'] = build_order_no('DSC');
        $data['owner_id'] = $this->ownerId;
        $data['community_id'] = $owner['community_id'] ?? 0;
        $data['complaint_name'] = $owner['realname'] ?? '';
        $data['complaint_phone'] = $data['phone'] ?? $data['contact'] ?? $owner['phone'] ?? '';
        $data['create_time'] = date('Y-m-d H:i:s');
        unset($data['contact'], $data['phone']);
        Db::name('complaint')->insert($data);
        return $this->success([], '提交成功');
    }

    public function lists()
    {
        [$page, $limit] = $this->getPage();
        $where = [['owner_id', '=', $this->ownerId], ['delete_time', 'null', '']];
        $total = Db::name('complaint')->where($where)->count();
        $list = Db::name('complaint')->where($where)->page($page, $limit)->order('id', 'desc')->select();
        return $this->success(['list' => $list, 'total' => $total]);
    }
}
