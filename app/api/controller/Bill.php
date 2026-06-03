<?php
namespace app\api\controller;

use app\api\BaseApi;
use think\facade\Db;

class Bill extends BaseApi
{
    public function lists()
    {
        $ownerId = $this->ownerId;
        [$page, $limit] = $this->getPage();
        $status = $this->request->param('status', '');
        $where = [['owner_id', '=', $ownerId], ['delete_time', '=', null]];
        if ($status !== '') $where[] = ['status', '=', $status];
        $total = Db::name('bill')->where($where)->count();
        $list = Db::name('bill')->where($where)->page($page, $limit)->order('id', 'desc')->select()->toArray();
        return $this->success(['list' => $list, 'total' => $total]);
    }

    public function unpaid()
    {
        $ownerId = $this->ownerId;
        $where = [['owner_id', '=', $ownerId], ['status', 'in', [1, 2]], ['delete_time', '=', null]];
        $list = Db::name('bill')->where($where)->select()->toArray();
        $total = Db::name('bill')->where($where)->sum('total_amount - paid_amount');
        return $this->success(['list' => $list, 'total_unpaid' => $total]);
    }

    public function detail()
    {
        $id = $this->request->param('id', 0);
        $bill = Db::name('bill')->where('id', $id)->find();
        return $this->success($bill);
    }

    public function pay()
    {
        $billId = $this->request->post('bill_id', 0);
        $payMethod = $this->request->post('pay_method', 2);
        $bill = Db::name('bill')->where('id', $billId)->find();
        if (!$bill) return $this->error('账单不存在');
        if ($bill['status'] == 3) return $this->error('账单已缴清');

        $amount = $bill['total_amount'] - $bill['paid_amount'];
        if ($amount <= 0) return $this->error('账单已缴清');

        $paymentNo = build_order_no('DSP');
        $paymentData = [
            'payment_no'  => $paymentNo,
            'bill_id'     => $billId,
            'community_id'=> $bill['community_id'],
            'owner_id'    => $bill['owner_id'],
            'room_id'     => $bill['room_id'],
            'amount'      => $amount,
            'pay_method'  => $payMethod,
            'pay_time'    => date('Y-m-d H:i:s'),
            'operator_id' => $this->ownerId,
            'operator_type' => 2,
            'create_time' => date('Y-m-d H:i:s'),
        ];

        Db::transaction(function () use ($paymentData, $bill, $billId, $amount) {
            Db::name('bill_payment')->insert($paymentData);
            Db::name('bill')->where('id', $billId)->update([
                'paid_amount' => $bill['paid_amount'] + $amount,
                'status' => 3,
                'pay_date' => date('Y-m-d H:i:s'),
            ]);
        });

        return $this->success(['payment_no' => $paymentNo], '支付成功');
    }
}
