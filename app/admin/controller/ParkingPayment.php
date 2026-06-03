<?php
namespace app\admin\controller;

use app\admin\BaseAdmin;
use think\facade\Db;

class ParkingPayment extends BaseAdmin
{
    public function lists()
    {
        [$page, $limit] = $this->getPage();
        $where = [['pp.delete_time', 'null', '']];
        $keyword = $this->request->param('keyword', '');
        if ($keyword) $where[] = ['pp.plate_no|pp.trade_no|pp.remark', 'like', "%{$keyword}%"];
        $communityId = $this->request->param('community_id', 0);
        if ($communityId) $where[] = ['pp.community_id', '=', $communityId];
        $paymentType = $this->request->param('payment_type', '');
        if ($paymentType !== '') $where[] = ['pp.payment_type', '=', intval($paymentType)];
        $payMethod = $this->request->param('pay_method', '');
        if ($payMethod !== '') $where[] = ['pp.pay_method', '=', intval($payMethod)];
        $total = Db::name('parking_payment')->alias('pp')->where($where)->count();
        $list = Db::name('parking_payment')->alias('pp')
            ->leftJoin('community com', 'com.id = pp.community_id')
            ->leftJoin('vehicle v', 'v.id = pp.vehicle_id')
            ->field('pp.*, com.name as community_name, v.plate_number as vehicle_plate')
            ->where($where)->page($page, $limit)->order('pp.id', 'desc')->select();
        return $this->table($list, $total);
    }

    public function add()
    {
        $data = $this->request->post();
        $data['create_time'] = date('Y-m-d H:i:s');
        Db::name('parking_payment')->insert($data);
        return $this->success([], '添加成功');
    }

    public function edit()
    {
        $data = $this->request->post();
        Db::name('parking_payment')->where('id', $data['id'])->update($data);
        return $this->success([], '修改成功');
    }

    public function delete()
    {
        $id = $this->request->post('id', 0);
        Db::name('parking_payment')->where('id', $id)->update(['delete_time' => date('Y-m-d H:i:s')]);
        return $this->success([], '删除成功');
    }
}
