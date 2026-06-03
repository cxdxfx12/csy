<?php
namespace app\staff\controller;

use app\staff\BaseStaff;
use think\facade\Db;

class StaffCharge extends BaseStaff
{
    public function unpaidList()
    {
        [$page, $limit] = $this->getPage();
        $communityId = $this->request->param('community_id', 0);
        $keyword = $this->request->param('keyword', '');
        $where = [['b.status', '=', 1], ['b.delete_time', 'null', '']];
        if ($communityId) $where[] = ['b.community_id', '=', $communityId];
        if ($keyword) $where[] = ['o.realname|o.phone|r.room_number', 'like', "%{$keyword}%"];
        $total = Db::name('bill')->alias('b')
            ->leftJoin('owner o', 'o.id = b.owner_id')
            ->leftJoin('room r', 'r.id = b.room_id')
            ->where($where)->count();
        $list = Db::name('bill')->alias('b')
            ->leftJoin('owner o', 'o.id = b.owner_id')
            ->leftJoin('room r', 'r.id = b.room_id')
            ->field('b.*, o.realname as owner_name, o.phone as owner_phone, r.room_number')
            ->where($where)->page($page, $limit)->order('b.id', 'desc')->select();
        return $this->success(['list' => $list, 'total' => $total]);
    }

    public function collect()
    {
        $billId = $this->request->post('bill_id', 0);
        $amount = $this->request->post('amount', 0);
        $payMethod = $this->request->post('pay_method', 1);
        $bill = Db::name('bill')->where('id', $billId)->find();
        if (!$bill) return $this->error('账单不存在');

        $paymentData = [
            'payment_no' => build_order_no('DSP'),
            'bill_id' => $billId,
            'community_id' => $bill['community_id'],
            'owner_id' => $bill['owner_id'],
            'room_id' => $bill['room_id'],
            'amount' => $amount,
            'pay_method' => $payMethod,
            'pay_time' => date('Y-m-d H:i:s'),
            'operator_id' => $this->staffId,
            'operator_type' => 2,
            'create_time' => date('Y-m-d H:i:s'),
        ];

        Db::name('bill_payment')->insert($paymentData);
        $newPaid = $bill['paid_amount'] + $amount;
        $newStatus = $newPaid >= $bill['total_amount'] ? 3 : 2;
        Db::name('bill')->where('id', $billId)->update([
            'paid_amount' => $newPaid,
            'status' => $newStatus,
            'pay_date' => $newStatus == 3 ? date('Y-m-d H:i:s') : null,
        ]);

        return $this->success([], '收款成功');
    }
}
