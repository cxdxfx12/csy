<?php
namespace app\admin\controller;

use app\admin\BaseAdmin;
use think\facade\Db;

class Dashboard extends BaseAdmin
{
    public function statistics()
    {
        $communityId = $this->request->param('community_id', 0);
        $roleId = $this->adminInfo['role_id'] ?? 0;
        $boundIds = $this->request->boundCommunityIds ?? [];

        // 小区过滤条件
        $hasFilter = ($roleId != 1 && !empty($boundIds));
        $inIds = $hasFilter ? $boundIds : [];

        // 基础统计 —— 用显式条件链，避免闭包 whereIn 在 count() 中失效
        $cQ = Db::name('community')->where('delete_time', null);
        if ($hasFilter) $cQ->whereIn('id', $inIds);
        $communityCount = $cQ->count();

        $bQ = Db::name('building')->where('delete_time', null);
        if ($hasFilter) $bQ->whereIn('community_id', $inIds);
        $buildingCount = $bQ->count();

        $rQ = Db::name('room')->where('delete_time', null);
        if ($hasFilter) $rQ->whereIn('community_id', $inIds);
        $roomCount = $rQ->count();

        $oQ = Db::name('owner')->where('delete_time', null);
        if ($hasFilter) $oQ->whereIn('community_id', $inIds);
        $ownerCount = $oQ->count();

        $uBQ = Db::name('bill')->where('status', 1)->where('delete_time', null);
        if ($hasFilter) $uBQ->whereIn('community_id', $inIds);
        $unpaidBillCount = $uBQ->count();

        $pRQ = Db::name('repair_order')->whereIn('status', [1, 2, 3])->where('delete_time', null);
        if ($hasFilter) $pRQ->whereIn('community_id', $inIds);
        $pendingRepairCount = $pRQ->count();

        $pCQ = Db::name('complaint')->whereIn('status', [1, 2])->where('delete_time', null);
        if ($hasFilter) $pCQ->whereIn('community_id', $inIds);
        $pendingComplaintCount = $pCQ->count();

        // 当月收入
        $monthStart = date('Y-m-01 00:00:00');
        $monthEnd   = date('Y-m-t 23:59:59');
        $mIQ = Db::name('bill_payment')
            ->whereBetween('pay_time', [$monthStart, $monthEnd])
            ->where('delete_time', null);
        if ($hasFilter) $mIQ->whereIn('community_id', $inIds);
        $monthIncome = $mIQ->sum('amount');

        // 待缴费总额
        $uTQ = Db::name('bill')
            ->where('status', 1)
            ->where('delete_time', null);
        if ($hasFilter) $uTQ->whereIn('community_id', $inIds);
        $unpaidTotal = $uTQ->sum('amount');

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
        $roleId = $this->adminInfo['role_id'] ?? 0;
        $boundIds = $this->request->boundCommunityIds ?? [];
        $hasFilter = ($roleId != 1 && !empty($boundIds));

        $months = [];
        $data = [];
        for ($i = 1; $i <= 12; $i++) {
            $months[] = $i . '月';
            $monthStr = sprintf('%s-%02d', $year, $i);
            $where = [
                ['pay_time', 'like', $monthStr . '%'],
                ['delete_time', 'null', ''],
            ];
            if ($communityId) {
                $where[] = ['community_id', '=', $communityId];
            } elseif ($hasFilter) {
                $where[] = ['community_id', 'in', $boundIds];
            }
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

    /**
     * 数字大屏全部数据（单次请求）
     * 权限：超管/系统管理员看全部小区，其余角色看自己绑定的小区数据
     */
    public function bigscreen()
    {
        $adminInfo = $this->adminInfo;
        $roleId = $adminInfo['role_id'] ?? 0;

        $roleInfo = Db::name('role')->where('id', $roleId)->find();
        $roleCode = $roleInfo['code'] ?? '';

        // 超管(role_id=1) 和 系统管理员(code=admin) 看全部小区
        $isFullAccess = ($roleId == 1 || $roleCode == 'admin');

        // 获取社区过滤范围
        $communityIds = [];
        $communityName = '全部小区';

        if (!$isFullAccess) {
            // 非超管/管理员：读取绑定的社区
            $communityIds = $this->request->boundCommunityIds ?? [];
            if (empty($communityIds)) {
                $communityIds = array_filter(array_map('intval', explode(',', $adminInfo['community_ids'] ?? '')));
            }
            if (empty($communityIds)) {
                return $this->error('无权限访问数据大屏（未绑定小区）', 403);
            }
            $names = Db::name('community')->whereIn('id', $communityIds)->where('delete_time', null)->column('name');
            $communityName = implode('、', $names) ?: '我的小区';
        }

        // 社区过滤辅助函数：对 Query 添加 community_id 过滤
        $applyFilter = function ($query, $tableAlias = '') use ($communityIds) {
            if (empty($communityIds)) return;
            $field = ($tableAlias ? $tableAlias . '.' : '') . 'community_id';
            $query->whereIn($field, $communityIds);
        };

        // ---- 基础资产 ----
        $communityQ = Db::name('community')->where('delete_time', null);
        if (!empty($communityIds)) $communityQ->whereIn('id', $communityIds);
        $communityCount = $communityQ->count();

        $buildingQ = Db::name('building')->where('delete_time', null);
        $applyFilter($buildingQ);
        $buildingCount = $buildingQ->count();

        $roomQ = Db::name('room')->where('delete_time', null);
        $applyFilter($roomQ);
        $roomCount = $roomQ->count();

        $ownerQ = Db::name('owner')->where('delete_time', null);
        $applyFilter($ownerQ);
        $ownerCount = $ownerQ->count();

        $staffQ = Db::name('staff')->where('delete_time', null)->where('status', 1);
        $applyFilter($staffQ);
        $staffCount = $staffQ->count();

        $vehicleQ = Db::name('vehicle')->where('delete_time', null);
        $applyFilter($vehicleQ);
        $vehicleCount = $vehicleQ->count();

        // 房间利用率
        $occQ = Db::name('owner_room')->alias('ocr')
            ->join('ds_room r', 'r.id = ocr.room_id')
            ->where('ocr.delete_time', null)->where('r.delete_time', null)
            ->group('ocr.room_id');
        $applyFilter($occQ, 'r');
        $occupiedRoomCount = $occQ->count();

        // ---- 财务数据 ----
        $monthStart = date('Y-m-01 00:00:00');
        $monthEnd   = date('Y-m-t 23:59:59');
        $yearStart  = date('Y-01-01 00:00:00');

        $miQ = Db::name('bill_payment')
            ->whereBetween('pay_time', [$monthStart, $monthEnd])
            ->where('delete_time', null);
        $applyFilter($miQ);
        $monthIncome = $miQ->sum('amount');

        $yiQ = Db::name('bill_payment')
            ->where('pay_time', '>=', $yearStart)
            ->where('delete_time', null);
        $applyFilter($yiQ);
        $yearIncome = $yiQ->sum('amount');

        $utQ = Db::name('bill')->where('status', 1)->where('delete_time', null);
        $applyFilter($utQ);
        $unpaidTotal = $utQ->sum('amount');

        $tbQ = Db::name('bill')->where('delete_time', null)->where('create_time', '>=', $yearStart);
        $applyFilter($tbQ);
        $totalBillAmount = $tbQ->sum('amount');

        $collectionRate = $totalBillAmount > 0
            ? round(($yearIncome / $totalBillAmount) * 100, 1)
            : 0;

        // ---- 运营数据 ----
        $prQ = Db::name('repair_order')->whereIn('status', [1, 2, 3])->where('delete_time', null);
        $applyFilter($prQ);
        $pendingRepairCount = $prQ->count();

        $drQ = Db::name('repair_order')->where('status', 4)->where('delete_time', null);
        $applyFilter($drQ);
        $doneRepairCount = $drQ->count();

        $pcQ = Db::name('complaint')->whereIn('status', [1, 2])->where('delete_time', null);
        $applyFilter($pcQ);
        $pendingComplaintCount = $pcQ->count();

        $today = date('Y-m-d');
        $tvQ = Db::name('visitor')
            ->whereBetween('create_time', [$today . ' 00:00:00', $today . ' 23:59:59'])
            ->where('delete_time', null);
        $applyFilter($tvQ);
        $todayVisitorCount = $tvQ->count();

        $trQ = Db::name('repair_order')
            ->whereBetween('create_time', [$today . ' 00:00:00', $today . ' 23:59:59'])
            ->where('delete_time', null);
        $applyFilter($trQ);
        $todayRepairCount = $trQ->count();

        // ---- 年度收入趋势（12个月） ----
        $year = date('Y');
        $incomeMonths = [];
        $incomeData = [];
        for ($i = 1; $i <= 12; $i++) {
            $incomeMonths[] = $i . '月';
            $monthStr = sprintf('%s-%02d', $year, $i);
            $itQ = Db::name('bill_payment')
                ->where('pay_time', 'like', $monthStr . '%')
                ->where('delete_time', null);
            $applyFilter($itQ);
            $incomeData[] = (float)$itQ->sum('amount');
        }

        // ---- 近30日报修趋势 ----
        $repairDays = [];
        $repairData = [];
        for ($i = 29; $i >= 0; $i--) {
            $date = date('Y-m-d', strtotime("-$i days"));
            $repairDays[] = date('m/d', strtotime($date));
            $rtQ = Db::name('repair_order')
                ->whereBetween('create_time', [$date . ' 00:00:00', $date . ' 23:59:59'])
                ->where('delete_time', null);
            $applyFilter($rtQ);
            $repairData[] = $rtQ->count();
        }

        // ---- 收费项目分布（饼图） ----
        $cdQ = Db::name('bill_payment')
            ->alias('p')
            ->join('ds_bill b', 'b.id = p.bill_id')
            ->whereBetween('p.pay_time', [$monthStart, $monthEnd])
            ->where('p.delete_time', null)
            ->group('b.charge_item_name')
            ->field('b.charge_item_name as name, SUM(p.amount) as value');
        $applyFilter($cdQ, 'p');
        $chargeDistribution = $cdQ->select();

        $pieData = [];
        foreach ($chargeDistribution as $item) {
            $pieData[] = ['name' => $item['name'], 'value' => (float)$item['value']];
        }

        // ---- 报修状态分布 ----
        $repairStatusData = [];
        $statusMap = [1 => '待派修', 2 => '处理中', 3 => '待验收', 4 => '已完成'];
        foreach ($statusMap as $s => $label) {
            $rsQ = Db::name('repair_order')->where('status', $s)->where('delete_time', null);
            $applyFilter($rsQ);
            $cnt = $rsQ->count();
            if ($cnt > 0) $repairStatusData[] = ['name' => $label, 'value' => $cnt];
        }

        // ---- 最新缴费记录（5条） ----
        $lpQ = Db::name('bill_payment')->alias('p')
            ->join('ds_bill b', 'b.id = p.bill_id')
            ->join('ds_owner o', 'o.id = b.owner_id')
            ->where('p.delete_time', null)
            ->order('p.pay_time', 'desc')
            ->limit(5)
            ->field('o.realname as owner_name, b.charge_item_name, p.amount, p.pay_time');
        $applyFilter($lpQ, 'p');
        $latestPayments = $lpQ->select();

        // ---- 最新报修（5条） ----
        $lrQ = Db::name('repair_order')->alias('r')
            ->join('ds_owner o', 'o.id = r.owner_id')
            ->where('r.delete_time', null)
            ->order('r.create_time', 'desc')
            ->limit(5)
            ->field('o.realname as owner_name, r.community_id, r.type, r.status, r.create_time');
        $applyFilter($lrQ, 'r');
        $latestRepairs = $lrQ->select();

        $repairTypeMap = [1 => '水', 2 => '电', 3 => '气', 4 => '门窗', 5 => '管道', 6 => '家电', 7 => '网络', 8 => '其他'];
        foreach ($latestRepairs as &$item) {
            $item['repair_type'] = $repairTypeMap[$item['type'] ?? 0] ?? '未知';
            unset($item['type']);
        }

        // ---- 最新投诉（5条） ----
        $lcQ = Db::name('complaint')->alias('c')
            ->join('ds_owner o', 'o.id = c.owner_id')
            ->where('c.delete_time', null)
            ->order('c.create_time', 'desc')
            ->limit(5)
            ->field('o.realname as owner_name, c.community_id, c.type, c.status, c.create_time');
        $applyFilter($lcQ, 'c');
        $latestComplaints = $lcQ->select();

        $complaintTypeMap = [1 => '投诉', 2 => '建议', 3 => '表扬', 4 => '咨询'];
        foreach ($latestComplaints as &$item) {
            $item['complaint_type'] = $complaintTypeMap[$item['type'] ?? 0] ?? '未知';
            unset($item['type']);
        }

        return $this->success([
            'title'            => $communityName,
            'stats' => [
                'community_count'   => $communityCount,
                'building_count'    => $buildingCount,
                'room_count'        => $roomCount,
                'occupied_count'    => $occupiedRoomCount,
                'owner_count'       => $ownerCount,
                'staff_count'       => $staffCount,
                'vehicle_count'     => $vehicleCount,
            ],
            'finance' => [
                'month_income'     => round($monthIncome, 2),
                'year_income'      => round($yearIncome, 2),
                'unpaid_total'     => round($unpaidTotal, 2),
                'collection_rate'  => $collectionRate,
            ],
            'operation' => [
                'pending_repairs'    => $pendingRepairCount,
                'done_repairs'       => $doneRepairCount,
                'pending_complaints' => $pendingComplaintCount,
                'today_visitors'     => $todayVisitorCount,
                'today_repairs'      => $todayRepairCount,
            ],
            'income_trend' => [
                'months' => $incomeMonths,
                'data'   => $incomeData,
            ],
            'repair_trend' => [
                'days' => $repairDays,
                'data' => $repairData,
            ],
            'charge_pie'   => $pieData,
            'repair_status_pie' => $repairStatusData,
            'latest_payments'   => $latestPayments,
            'latest_repairs'    => $latestRepairs,
            'latest_complaints' => $latestComplaints,
        ]);
    }
}
