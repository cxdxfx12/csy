<?php
namespace app\staff\controller;

use app\staff\BaseStaff;
use think\facade\Db;

class StaffOrder extends BaseStaff
{
    public function lists()
    {
        [$page, $limit] = $this->getPage();
        $where = [['status', 'in', [1, 2, 3]], ['delete_time', 'null', '']];
        $total = Db::name('repair_order')->where($where)->count();
        $list = Db::name('repair_order')->where($where)
            ->page($page, $limit)->order('id', 'desc')->select();
        return $this->success(['list' => $list, 'total' => $total]);
    }

    public function create()
    {
        $data = $this->request->post();
        $data['order_no'] = build_order_no('DSR');
        $data['source'] = 2;
        $data['create_time'] = date('Y-m-d H:i:s');
        Db::name('repair_order')->insert($data);
        return $this->success([], '创建成功');
    }

    public function close()
    {
        $id = $this->request->post('id', 0);
        Db::name('repair_order')->where('id', $id)->update([
            'status' => 6,
            'remark' => $this->request->post('remark', ''),
        ]);
        return $this->success([], '已关闭');
    }
}
