<?php
namespace app\manager\controller;

use app\manager\BaseManager;
use think\facade\Db;

class Dashboard extends BaseManager
{
    public function statistics()
    {
        $cid = $this->getCommunityId();

        $totalRooms = Db::name('room')->where('community_id', $cid)->where('delete_time', null)->count();
        $totalOwners = Db::name('owner')->where('community_id', $cid)->where('delete_time', null)->count();
        $totalBills = Db::name('bill')->where('community_id', $cid)->where('delete_time', null)->count();
        $pendingRepairs = Db::name('repair_order')->where('community_id', $cid)
            ->whereIn('status', [1, 2])->where('delete_time', null)->count();
        $pendingComplaints = Db::name('complaint')->where('community_id', $cid)
            ->whereIn('status', [1, 2])->where('delete_time', null)->count();

        $monthStart = date('Y-m-01 00:00:00');
        $monthEnd   = date('Y-m-t 23:59:59');
        $monthIncome = Db::name('bill_payment')->where('community_id', $cid)
            ->whereBetween('pay_time', [$monthStart, $monthEnd])
            ->where('delete_time', null)->sum('amount');

        $unpaidAmount = Db::name('bill')->where('community_id', $cid)
            ->where('status', 1)->where('delete_time', null)
            ->sum('total_amount - paid_amount');

        $chargeRate = 0;
        $totalBillAmount = Db::name('bill')->where('community_id', $cid)->where('delete_time', null)->sum('total_amount');
        if ($totalBillAmount > 0) {
            $paidAmount = Db::name('bill')->where('community_id', $cid)->where('delete_time', null)->sum('paid_amount');
            $chargeRate = round($paidAmount / $totalBillAmount * 100, 2);
        }

        return $this->success([
            'total_rooms'       => $totalRooms,
            'total_owners'      => $totalOwners,
            'total_bills'       => $totalBills,
            'pending_repairs'   => $pendingRepairs,
            'pending_complaints'=> $pendingComplaints,
            'month_income'      => $monthIncome,
            'unpaid_amount'     => $unpaidAmount,
            'charge_rate'       => $chargeRate,
        ]);
    }

    public function incomeTrend()
    {
        $cid  = $this->getCommunityId();
        $year = $this->request->param('year', date('Y'));
        $data = [];
        $months = [];
        for ($i = 1; $i <= 12; $i++) {
            $months[] = $i . '月';
            $monthStr = sprintf('%s-%02d', $year, $i);
            $data[] = Db::name('bill_payment')->where('community_id', $cid)
                ->where('pay_time', 'like', $monthStr . '%')
                ->where('delete_time', null)->sum('amount');
        }
        return $this->success(['months' => $months, 'income' => $data]);
    }

    public function repairStats()
    {
        $cid = $this->getCommunityId();
        $total = Db::name('repair_order')->where('community_id', $cid)->where('delete_time', null)->count();
        $pending = Db::name('repair_order')->where('community_id', $cid)->where('status', 1)->where('delete_time', null)->count();
        $processing = Db::name('repair_order')->where('community_id', $cid)->whereIn('status', [2, 3])->where('delete_time', null)->count();
        $finished = Db::name('repair_order')->where('community_id', $cid)->whereIn('status', [4, 5])->where('delete_time', null)->count();
        return $this->success(compact('total', 'pending', 'processing', 'finished'));
    }

    public function ownerStats()
    {
        $cid = $this->getCommunityId();
        $total = Db::name('owner')->where('community_id', $cid)->where('delete_time', null)->count();
        $newThisMonth = Db::name('owner')->where('community_id', $cid)
            ->whereBetween('create_time', [date('Y-m-01 00:00:00'), date('Y-m-t 23:59:59')])->count();
        $wxBound = Db::name('owner')->where('community_id', $cid)->where('openid', '<>', '')->where('delete_time', null)->count();
        return $this->success(compact('total', 'newThisMonth', 'wxBound'));
    }

    public function pendingTodo()
    {
        $cid = $this->getCommunityId();
        $pendingRepairs = Db::name('repair_order')->where('community_id', $cid)->where('status', 1)->where('delete_time', null)->count();
        $pendingComplaints = Db::name('complaint')->where('community_id', $cid)->whereIn('status', [1, 2])->where('delete_time', null)->count();
        $unpaidBills = Db::name('bill')->where('community_id', $cid)->where('status', 1)->where('delete_time', null)->count();
        return $this->success([
            ['title' => '待派修工单', 'count' => $pendingRepairs, 'icon' => '🔧'],
            ['title' => '待处理投诉', 'count' => $pendingComplaints, 'icon' => '📢'],
            ['title' => '未缴账单', 'count' => $unpaidBills, 'icon' => '💰'],
        ]);
    }

    public function chargeRate()
    {
        $cid = $this->getCommunityId();
        $total = Db::name('bill')->where('community_id', $cid)->where('delete_time', null)->sum('total_amount');
        $paid = Db::name('bill')->where('community_id', $cid)->where('delete_time', null)->sum('paid_amount');
        $rate = $total > 0 ? round($paid / $total * 100, 2) : 0;
        return $this->success(['rate' => $rate, 'total' => $total, 'paid' => $paid]);
    }

    // ===== 列表类 API =====

    /** 业主列表 */
    public function ownerList()
    {
        [$page, $limit] = $this->getPageParams();
        $cid = $this->getCommunityId();
        $keyword = $this->request->param('keyword', '');

        $query = Db::name('owner')->alias('o')
            ->whereNull('o.delete_time')
            ->where('o.community_id', $cid);
        if ($keyword) $query->where('o.realname|o.phone', 'like', "%{$keyword}%");

        $total = $query->count();
        $list = $query->leftJoin('owner_room ocr', 'ocr.owner_id = o.id AND ocr.delete_time IS NULL')
            ->leftJoin('room r', 'r.id = ocr.room_id')
            ->field('o.id, o.realname, o.phone, o.gender, o.type, o.status, o.create_time, GROUP_CONCAT(r.room_number) as rooms')
            ->group('o.id')->page($page, $limit)->order('o.id', 'desc')->select();

        return $this->table($list, $total);
    }

    /** 账单列表 */
    public function billList()
    {
        [$page, $limit] = $this->getPageParams();
        $cid = $this->getCommunityId();
        $status = $this->request->param('status', 0);

        $query = Db::name('bill')->alias('b')
            ->whereNull('b.delete_time')
            ->where('b.community_id', $cid);
        if ($status) $query->where('b.status', intval($status));

        $total = $query->count();
        $list = $query->leftJoin('owner o', 'o.id = b.owner_id')
            ->leftJoin('room r', 'r.id = b.room_id')
            ->field('b.*, o.realname as owner_name, r.room_number')
            ->page($page, $limit)->order('b.id', 'desc')->select();

        return $this->table($list, $total);
    }

    /** 报修列表 */
    public function repairList()
    {
        [$page, $limit] = $this->getPageParams();
        $cid = $this->getCommunityId();
        $status = $this->request->param('status', 0);

        $query = Db::name('repair_order')->alias('ro')
            ->whereNull('ro.delete_time')
            ->where('ro.community_id', $cid);
        if ($status) $query->where('ro.status', intval($status));

        $total = $query->count();
        $list = $query->leftJoin('owner o', 'o.id = ro.owner_id')
            ->leftJoin('room r', 'r.id = ro.room_id')
            ->field('ro.*, o.realname as owner_name, r.room_number')
            ->page($page, $limit)->order('ro.id', 'desc')->select();

        return $this->table($list, $total);
    }

    /** 投诉列表 */
    public function complaintList()
    {
        [$page, $limit] = $this->getPageParams();
        $cid = $this->getCommunityId();
        $status = $this->request->param('status', 0);

        $query = Db::name('complaint')->alias('c')
            ->whereNull('c.delete_time')
            ->where('c.community_id', $cid);
        if ($status) $query->where('c.status', intval($status));

        $total = $query->count();
        $list = $query->leftJoin('owner o', 'o.id = c.owner_id')
            ->field('c.*, o.realname as owner_name')
            ->page($page, $limit)->order('c.id', 'desc')->select();

        return $this->table($list, $total);
    }

    /** 当前经理管理的所有小区列表 */
    public function communityList()
    {
        $ids = $this->request->managedCommunityIds ?? [];
        if (empty($ids)) return $this->success([], '暂无管理的小区');
        $list = Db::name('community')->whereIn('id', $ids)->where('delete_time', null)
            ->field('id, name, code, address')->order('id', 'asc')->select();
        return $this->success($list);
    }

    /** 小区信息 */
    public function communityInfo()
    {
        $cid = $this->getCommunityId();
        $community = Db::name('community')->where('id', $cid)->find();
        if (!$community) return $this->error('小区不存在');
        return $this->success($community);
    }

    // ---- helpers ----

    private function getPageParams()
    {
        $page  = intval($this->request->param('page', 1));
        $limit = intval($this->request->param('limit', 15));
        return [$page, $limit];
    }
}
