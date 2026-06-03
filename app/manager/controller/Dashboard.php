<?php
namespace app\manager\controller;

use app\manager\BaseManager;
use think\facade\Db;

class Dashboard extends BaseManager
{
    public function statistics()
    {
        $totalCommunities = Db::name('community')->where('delete_time', null)->count();
        $totalRooms = Db::name('room')->where('delete_time', null)->count();
        $totalOwners = Db::name('owner')->where('delete_time', null)->count();
        $monthIncome = Db::name('bill_payment')
            ->whereBetween('pay_time', [date('Y-m-01 00:00:00'), date('Y-m-t 23:59:59')])
            ->where('delete_time', null)->sum('amount');
        $unpaidAmount = Db::name('bill')->whereIn('status', [1, 2])->where('delete_time', null)
            ->sum('total_amount - paid_amount');
        $chargeRate = 0;
        $totalBillAmount = Db::name('bill')->where('delete_time', null)->sum('total_amount');
        if ($totalBillAmount > 0) {
            $paidAmount = Db::name('bill')->where('delete_time', null)->sum('paid_amount');
            $chargeRate = round($paidAmount / $totalBillAmount * 100, 2);
        }

        return $this->success([
            'total_communities' => $totalCommunities,
            'total_rooms' => $totalRooms,
            'total_owners' => $totalOwners,
            'month_income' => $monthIncome,
            'unpaid_amount' => $unpaidAmount,
            'charge_rate' => $chargeRate,
        ]);
    }

    public function incomeTrend()
    {
        $year = $this->request->param('year', date('Y'));
        $data = [];
        $months = [];
        for ($i = 1; $i <= 12; $i++) {
            $months[] = $i . '月';
            $monthStr = sprintf('%s-%02d', $year, $i);
            $data[] = Db::name('bill_payment')
                ->where('pay_time', 'like', $monthStr . '%')
                ->where('delete_time', null)->sum('amount');
        }
        return $this->success(['months' => $months, 'income' => $data]);
    }

    public function repairStats()
    {
        $total = Db::name('repair_order')->where('delete_time', null)->count();
        $pending = Db::name('repair_order')->whereIn('status', [1, 2])->where('delete_time', null)->count();
        $processing = Db::name('repair_order')->where('status', 3)->where('delete_time', null)->count();
        $finished = Db::name('repair_order')->whereIn('status', [4, 5])->where('delete_time', null)->count();
        return $this->success(compact('total', 'pending', 'processing', 'finished'));
    }

    public function ownerStats()
    {
        $total = Db::name('owner')->where('delete_time', null)->count();
        $newThisMonth = Db::name('owner')
            ->whereBetween('create_time', [date('Y-m-01 00:00:00'), date('Y-m-t 23:59:59')])
            ->count();
        return $this->success(compact('total', 'newThisMonth'));
    }

    public function communityRank()
    {
        $list = Db::name('community')->field('id,name')->where('delete_time', null)
            ->select()->toArray();
        foreach ($list as &$item) {
            $item['room_count'] = Db::name('room')->where('community_id', $item['id'])->where('delete_time', null)->count();
            $item['owner_count'] = Db::name('owner')->where('community_id', $item['id'])->where('delete_time', null)->count();
            $item['income'] = Db::name('bill_payment')->where('community_id', $item['id'])
                ->whereBetween('pay_time', [date('Y-m-01 00:00:00'), date('Y-m-t 23:59:59')])
                ->sum('amount');
        }
        array_multisort(array_column($list, 'income'), SORT_DESC, $list);
        return $this->success($list);
    }

    public function pendingTodo()
    {
        $pendingRepairs = Db::name('repair_order')->where('status', 1)->where('delete_time', null)->count();
        $pendingComplaints = Db::name('complaint')->whereIn('status', [1, 2])->where('delete_time', null)->count();
        $unpaidBills = Db::name('bill')->where('status', 1)->where('delete_time', null)->count();
        $expiringCards = Db::name('access_card')
            ->whereBetween('expire_date', [date('Y-m-d'), date('Y-m-d', strtotime('+7 days'))])
            ->count();
        $maintainDue = Db::name('equipment')
            ->where('next_maintain_date', '<=', date('Y-m-d', strtotime('+7 days')))
            ->where('status', 1)->count();

        return $this->success([
            ['title' => '待派修工单', 'count' => $pendingRepairs, 'icon' => 'engine'],
            ['title' => '待处理投诉', 'count' => $pendingComplaints, 'icon' => 'speaker'],
            ['title' => '未缴账单', 'count' => $unpaidBills, 'icon' => 'rmb'],
            ['title' => '门禁卡即将过期', 'count' => $expiringCards, 'icon' => 'card'],
            ['title' => '即将维保设备', 'count' => $maintainDue, 'icon' => 'engine'],
        ]);
    }

    public function chargeRate()
    {
        $communities = Db::name('community')->where('delete_time', null)->field('id,name')->select()->toArray();
        $data = [];
        foreach ($communities as $c) {
            $total = Db::name('bill')->where('community_id', $c['id'])->where('delete_time', null)->sum('total_amount');
            $paid = Db::name('bill')->where('community_id', $c['id'])->where('delete_time', null)->sum('paid_amount');
            $rate = $total > 0 ? round($paid / $total * 100, 2) : 0;
            $data[] = ['community' => $c['name'], 'rate' => $rate];
        }
        return $this->success($data);
    }
}
