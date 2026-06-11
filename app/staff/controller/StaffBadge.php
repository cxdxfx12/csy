<?php
namespace app\staff\controller;

use app\staff\BaseStaff;
use think\facade\Db;

/**
 * 角标数量 + 最新消息
 */
class StaffBadge extends BaseStaff
{
    /**
     * 获取当前员工社区 ID
     */
    protected function getCommunityId()
    {
        $info = $this->staffInfo;
        // 维修工
        if (!empty($info['is_worker'])) {
            return $info['community_id'] ?? 0;
        }
        // 普通物业员工
        return $info['community_id'] ?? 0;
    }

    /**
     * 获取维修工 ID（仅维修工）
     */
    protected function getWorkerId()
    {
        $info = $this->staffInfo;
        if (!empty($info['is_worker'])) {
            return $info['id'];
        }
        return 0;
    }

    /**
     * 返回所有角标数量和最新消息
     * GET /api/staff/badge/counts
     */
    public function counts()
    {
        $communityId = $this->getCommunityId();
        $workerId = $this->getWorkerId();

        $data = [
            'repair'    => 0,  // 报修-待接单
            'charge'    => 0,  // 收费-未缴账单
            'order'     => 0,  // 工单-进行中
            'complaint' => 0,  // 投诉-待处理
            'vote'      => 0,  // 进行中投票
            'activity'  => 0,  // 进行中活动
            'last_repair' => null,
            'last_bill'   => null,
            'last_order'  => null,
            'last_complaint' => null,
            'last_vote'   => null,
            'last_activity' => null,
        ];

        if (!$communityId) {
            return $this->success($data);
        }

        // ===== 报修：已派单待接单 =====
        $repairWhere = [
            ['ro.delete_time', 'null', ''],
            ['ro.community_id', '=', $communityId],
            ['ro.status', '=', 2],
        ];
        if ($workerId) {
            $repairWhere[] = ['ro.assignee_id', '=', $workerId];
        }
        $data['repair'] = Db::name('repair_order')->alias('ro')->where($repairWhere)->count();

        // 最新报修单（用于弹窗）
        if ($data['repair'] > 0) {
            $lastRepair = Db::name('repair_order')->alias('ro')
                ->leftJoin('room r', 'r.id = ro.room_id')
                ->field('ro.id, ro.title, ro.room_id, r.room_number, r.building_name')
                ->where($repairWhere)
                ->order('ro.id', 'desc')->find();
            $data['last_repair'] = $lastRepair;
        }

        // ===== 收费：未缴账单 =====
        $data['charge'] = Db::name('bill')
            ->where('community_id', $communityId)
            ->where('status', 1) // 待缴费
            ->where('delete_time', null)
            ->count();

        // 最新账单
        if ($data['charge'] > 0) {
            $lastBill = Db::name('bill')->alias('b')
                ->leftJoin('owner o', 'o.id = b.owner_id')
                ->leftJoin('room r', 'r.id = b.room_id')
                ->field('b.id, b.total_amount, o.realname as owner_name, r.room_number')
                ->where('b.community_id', $communityId)
                ->where('b.status', 1)
                ->where('b.delete_time', null)
                ->order('b.id', 'desc')->find();
            $data['last_bill'] = $lastBill;
        }

        // ===== 工单：进行中的工单 =====
        $data['order'] = Db::name('repair_order')
            ->where('community_id', $communityId)
            ->where('delete_time', null)
            ->whereIn('status', [1, 2, 3])
            ->count();

        // ===== 投诉：待处理 =====
        $data['complaint'] = Db::name('complaint')
            ->where('community_id', $communityId)
            ->where('status', 1)
            ->whereNull('delete_time')
            ->count();
        if ($data['complaint'] > 0) {
            $data['last_complaint'] = Db::name('complaint')
                ->where('community_id', $communityId)->where('status', 1)
                ->whereNull('delete_time')->order('id', 'desc')->find();
        }

        // ===== 投票：进行中 =====
        $data['vote'] = Db::name('vote')
            ->where('community_id', $communityId)->where('status', 2)
            ->whereNull('delete_time')->count();
        if ($data['vote'] > 0) {
            $data['last_vote'] = Db::name('vote')
                ->where('community_id', $communityId)->where('status', 2)
                ->whereNull('delete_time')->order('id', 'desc')->find();
        }

        // ===== 活动：进行中/报名中 =====
        $data['activity'] = Db::name('activity')
            ->where('community_id', $communityId)->whereIn('status', [2, 3])
            ->whereNull('delete_time')->count();
        if ($data['activity'] > 0) {
            $data['last_activity'] = Db::name('activity')
                ->where('community_id', $communityId)->whereIn('status', [2, 3])
                ->whereNull('delete_time')->order('id', 'desc')->find();
        }

        // ===== 工单最新（供弹窗用） =====
        if ($data['order'] > 0) {
            $data['last_order'] = Db::name('repair_order')
                ->where('community_id', $communityId)->whereIn('status', [1, 2, 3])
                ->where('delete_time', null)->order('id', 'desc')->find();
        }

        return $this->success($data);
    }
}
