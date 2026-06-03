<?php
namespace app\admin\controller;

use app\admin\BaseAdmin;
use think\facade\Db;

class Dashboard extends BaseAdmin
{
    public function statistics()
    {
        $communityId = $this->request->param('community_id', 0);

        // 基础统计
        $communityCount = Db::name('community')->where('delete_time', null)->count();
        $buildingCount  = Db::name('building')->where('delete_time', null)->count();
        $roomCount      = Db::name('room')->where('delete_time', null)->count();
        $ownerCount     = Db::name('owner')->where('delete_time', null)->count();
        $unpaidBillCount = Db::name('bill')->where('status', 1)->where('delete_time', null)->count();
        $pendingRepairCount = Db::name('repair_order')->whereIn('status', [1, 2, 3])->where('delete_time', null)->count();
        $pendingComplaintCount = Db::name('complaint')->whereIn('status', [1, 2])->where('delete_time', null)->count();

        // 当月收入
        $monthStart = date('Y-m-01 00:00:00');
        $monthEnd   = date('Y-m-t 23:59:59');
        $monthIncome = Db::name('bill_payment')
            ->whereBetween('pay_time', [$monthStart, $monthEnd])
            ->where('delete_time', null)
            ->sum('amount');

        // 待缴费总额（status=1 未缴账单）
        $unpaidTotal = Db::name('bill')
            ->where('status', 1)
            ->where('delete_time', null)
            ->sum('amount');

        // 返回前端期望的5卡片格式
        $statCards = [
            ['icon' => '🏘️', 'label' => '管理小区', 'value' => $communityCount, 'bg' => 'linear-gradient(135deg,#ebf8ff,#bee3f8)'],
            ['icon' => '👤', 'label' => '业主总数', 'value' => $ownerCount, 'bg' => 'linear-gradient(135deg,#f0fff4,#c6f6d5)'],
            ['icon' => '💰', 'label' => '本月收费', 'value' => '¥' . number_format($monthIncome, 2), 'bg' => 'linear-gradient(135deg,#fffbeb,#fef3c7)'],
            ['icon' => '📋', 'label' => '待缴费总额', 'value' => '¥' . number_format($unpaidTotal, 2), 'bg' => 'linear-gradient(135deg,#ffebee,#ef9a9a)'],
            ['icon' => '🔧', 'label' => '待处理工单', 'value' => $pendingRepairCount, 'bg' => 'linear-gradient(135deg,#fff5f5,#fed7d7)'],
        ];

        return $this->success($statCards);
    }

    public function incomeChart()
    {
        $year = $this->request->param('year', date('Y'));
        $communityId = $this->request->param('community_id', 0);

        $months = [];
        $data = [];
        for ($i = 1; $i <= 12; $i++) {
            $months[] = $i . '月';
            $monthStr = sprintf('%s-%02d', $year, $i);
            $where = [
                ['pay_time', 'like', $monthStr . '%'],
                ['delete_time', 'null', ''],
            ];
            if ($communityId) $where[] = ['community_id', '=', $communityId];
            $data[] = Db::name('bill_payment')->where($where)->sum('amount');
        }

        return $this->success(['months' => $months, 'data' => $data]);
    }

    public function repairChart()
    {
        $now = date('Y-m-d');
        $weekAgo = date('Y-m-d', strtotime('-7 days'));
        $days = [];
        $counts = [];
        for ($i = 7; $i >= 0; $i--) {
            $date = date('Y-m-d', strtotime("-$i days"));
            $days[] = date('m-d', strtotime($date));
            $counts[] = Db::name('repair_order')
                ->whereBetween('create_time', [$date . ' 00:00:00', $date . ' 23:59:59'])
                ->where('delete_time', null)
                ->count();
        }

        return $this->success(['days' => $days, 'counts' => $counts]);
    }

    public function pieChart()
    {
        $monthStart = date('Y-m-01 00:00:00');
        $monthEnd   = date('Y-m-t 23:59:59');

        // 本月收入按收费项目分组
        $incomeBreakdown = Db::name('bill_payment')
            ->alias('p')
            ->join('ds_bill b', 'b.id = p.bill_id')
            ->whereBetween('p.pay_time', [$monthStart, $monthEnd])
            ->where('p.delete_time', null)
            ->group('b.charge_item_name')
            ->field('b.charge_item_name as name, SUM(p.amount) as value')
            ->select();

        // 待缴费总额
        $unpaidTotal = Db::name('bill')
            ->where('status', 1)
            ->where('delete_time', null)
            ->sum('amount');

        $data = [];
        foreach ($incomeBreakdown as $item) {
            $data[] = ['name' => $item['name'], 'value' => (float)$item['value']];
        }
        if ($unpaidTotal > 0) {
            $data[] = ['name' => '待缴费总额', 'value' => (float)$unpaidTotal];
        }

        return $this->success($data);
    }
}
