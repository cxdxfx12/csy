<?php
namespace app\admin\controller;

use app\admin\BaseAdmin;
use think\facade\Db;

class PrintController extends BaseAdmin
{
    public function receipt()
    {
        $paymentId = $this->request->param('payment_id', 0);
        $payment = Db::name('bill_payment')->alias('p')
            ->leftJoin('owner o', 'o.id = p.owner_id')
            ->leftJoin('room r', 'r.id = p.room_id')
            ->field('p.*, o.realname as owner_name, o.phone as owner_phone, r.room_number, r.building_name')
            ->where('p.id', $paymentId)->find();
        if (!$payment) return $this->error('缴费记录不存在');

        return $this->success([
            'title' => '缴费收据',
            'company_name' => '杭州喵喵至家网络有限公司',
            'payment_no' => $payment['payment_no'],
            'owner_name' => $payment['owner_name'],
            'room' => $payment['building_name'] . ' ' . $payment['room_number'],
            'amount' => $payment['amount'],
            'pay_method' => ['', '现金', '微信', '支付宝', '银行转账', 'POS刷卡', '其他'][$payment['pay_method']] ?? '',
            'pay_time' => $payment['pay_time'],
            'remark' => $payment['remark'],
        ]);
    }

    public function notice()
    {
        $communityId = $this->request->param('community_id', 0);
        $period = $this->request->param('period', date('Y-m'));

        $bills = Db::name('bill')->alias('b')
            ->leftJoin('owner o', 'o.id = b.owner_id')
            ->leftJoin('room r', 'r.id = b.room_id')
            ->field('b.*, o.realname as owner_name, o.phone as owner_phone, r.room_number, r.building_name')
            ->where('b.community_id', $communityId)
            ->where('b.bill_period', $period)
            ->where('b.status', 1)
            ->where('b.delete_time', null)
            ->select();

        $community = Db::name('community')->where('id', $communityId)->find();

        return $this->success([
            'title' => '物业费催缴通知',
            'community' => $community,
            'period' => $period,
            'bills' => $bills,
            'company_name' => '杭州喵喵至家网络有限公司',
        ]);
    }
}
