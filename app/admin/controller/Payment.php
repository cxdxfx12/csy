<?php
namespace app\admin\controller;

use app\admin\BaseAdmin;
use think\facade\Db;

class Payment extends BaseAdmin
{
    public function lists()
    {
        [$page, $limit] = $this->getPage();
        $where = [['p.delete_time', '=', null]];
        $keyword = $this->request->param('keyword', '');
        if ($keyword) $where[] = ['p.payment_no|p.trade_no|o.realname|o.phone|r.room_number', 'like', "%{$keyword}%"];
        $communityId = $this->request->param('community_id', 0);
        if ($communityId) $where[] = ['p.community_id', '=', $communityId];
        $payMethod = $this->request->param('pay_method', 0);
        if ($payMethod) $where[] = ['p.pay_method', '=', $payMethod];
        $startDate = $this->request->param('start_date', '');
        $endDate = $this->request->param('end_date', '');
        if ($startDate) $where[] = ['p.pay_time', '>=', $startDate . ' 00:00:00'];
        if ($endDate) $where[] = ['p.pay_time', '<=', $endDate . ' 23:59:59'];

        $total = Db::name('bill_payment')->alias('p')->where($where)->count();
        $list = Db::name('bill_payment')->alias('p')
            ->leftJoin('owner o', 'o.id = p.owner_id')
            ->leftJoin('room r', 'r.id = p.room_id')
            ->field('p.*, o.realname as owner_name, o.phone as owner_phone, r.room_number')
            ->where($where)->page($page, $limit)->order('p.id', 'desc')->select();

        return $this->table($list, $total);
    }

    public function add()
    {
        $data = $this->request->post();
        $data['payment_no'] = build_order_no('DSP');
        $data['pay_time'] = $data['pay_time'] ?? date('Y-m-d H:i:s');
        $data['operator_id'] = get_admin_id();
        $data['operator_type'] = 1;
        $data['create_time'] = date('Y-m-d H:i:s');

        Db::transaction(function () use ($data) {
            Db::name('bill_payment')->insert($data);

            // 更新账单状态
            $bill = Db::name('bill')->where('id', $data['bill_id'])->find();
            if ($bill) {
                $newPaid = $bill['paid_amount'] + $data['amount'];
                $newStatus = $newPaid >= $bill['total_amount'] ? 3 : 2;
                Db::name('bill')->where('id', $bill['id'])->update([
                    'paid_amount' => $newPaid,
                    'status'      => $newStatus,
                    'pay_date'    => $newStatus == 3 ? date('Y-m-d H:i:s') : null,
                ]);

                // 记录财务流水
                if ($newStatus == 3) {
                    Db::name('finance_flow')->insert([
                        'flow_no'       => build_order_no('DSF'),
                        'community_id'  => $data['community_id'],
                        'type'          => 1,
                        'category'      => '物业费',
                        'amount'        => $data['amount'],
                        'source_type'   => 'payment',
                        'source_id'     => $data['id'],
                        'description'   => '物业费缴费',
                        'operator_id'   => $data['operator_id'],
                        'operator_name' => get_admin_info()['nickname'] ?? '',
                        'create_time'   => date('Y-m-d H:i:s'),
                    ]);
                }
            }
        });

        return $this->success([], '缴费成功');
    }
}
