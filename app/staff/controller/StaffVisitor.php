<?php
namespace app\staff\controller;

use app\staff\BaseStaff;
use think\facade\Db;

class StaffVisitor extends BaseStaff
{
    public function add()
    {
        $data = $this->request->post();
        $data['source'] = 3;
        $data['status'] = 1;
        $data['operator_id'] = $this->staffId;
        $data['create_time'] = date('Y-m-d H:i:s');
        Db::name('visitor')->insert($data);
        return $this->success([], '登记成功');
    }

    public function lists()
    {
        [$page, $limit] = $this->getPage();
        $where = [['delete_time', 'null', '']];
        $status = $this->request->param('status', '');
        if ($status !== '') $where[] = ['status', '=', $status];
        $total = Db::name('visitor')->where($where)->count();
        $list = Db::name('visitor')->where($where)->page($page, $limit)->order('id', 'desc')->select();
        return $this->success(['list' => $list, 'total' => $total]);
    }
}
