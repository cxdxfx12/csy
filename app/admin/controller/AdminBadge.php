<?php
namespace app\admin\controller;

use app\admin\BaseAdmin;
use think\facade\Db;

/**
 * 后台管理角标：未处理事项计数
 * GET /api/admin/badge/counts
 */
class AdminBadge extends BaseAdmin
{
    public function counts()
    {
        $data = [
            'bill'      => 0,  // 待缴账单
            'repair'    => 0,  // 待处理报修
            'complaint' => 0,  // 待处理投诉
            'order'     => 0,  // 进行中工单
            'vote'      => 0,  // 进行中投票
            'activity'  => 0,  // 报名中活动
            'last_bill'      => null,
            'last_repair'    => null,
            'last_complaint' => null,
            'last_order'     => null,
            'last_vote'      => null,
            'last_activity'  => null,
        ];

        // 小区过滤
        $cid = $this->getFilteredCommunityId();
        $communityWhere = [];
        if ($cid === -1 && !empty($this->request->boundCommunityIds)) {
            // 多个小区
            $communityWhere[] = ['community_id', 'in', $this->request->boundCommunityIds];
        } elseif ($cid > 0) {
            $communityWhere[] = ['community_id', '=', $cid];
        }

        // ===== 账单：待缴 =====
        $billQuery = Db::name('bill')->where('delete_time', null)->whereIn('status', [1, 2]);
        if ($communityWhere) $billQuery->where($communityWhere);
        $data['bill'] = $billQuery->count();
        if ($data['bill'] > 0) {
            $bq = Db::name('bill')->where('delete_time', null)->whereIn('status', [1, 2]);
            if ($communityWhere) $bq->where($communityWhere);
            $data['last_bill'] = $bq->order('id', 'desc')->find();
        }

        // ===== 报修：待处理（status=1待派修 or status=2待接单） =====
        $repairQuery = Db::name('repair_order')->where('delete_time', null)->whereIn('status', [1, 2]);
        if ($communityWhere) $repairQuery->where($communityWhere);
        $data['repair'] = $repairQuery->count();
        if ($data['repair'] > 0) {
            $rq = Db::name('repair_order')->where('delete_time', null)->whereIn('status', [1, 2]);
            if ($communityWhere) $rq->where($communityWhere);
            $data['last_repair'] = $rq->order('id', 'desc')->find();
        }

        // ===== 投诉：待处理 =====
        $complaintQuery = Db::name('complaint')->whereNull('delete_time')->where('status', 1);
        if ($communityWhere) $complaintQuery->where($communityWhere);
        $data['complaint'] = $complaintQuery->count();
        if ($data['complaint'] > 0) {
            $cq = Db::name('complaint')->whereNull('delete_time')->where('status', 1);
            if ($communityWhere) $cq->where($communityWhere);
            $data['last_complaint'] = $cq->order('id', 'desc')->find();
        }

        // ===== 工单：进行中 =====
        $orderQuery = Db::name('repair_order')->where('delete_time', null)->whereIn('status', [1, 2, 3]);
        if ($communityWhere) $orderQuery->where($communityWhere);
        $data['order'] = $orderQuery->count();
        if ($data['order'] > 0) {
            $oq = Db::name('repair_order')->where('delete_time', null)->whereIn('status', [1, 2, 3]);
            if ($communityWhere) $oq->where($communityWhere);
            $data['last_order'] = $oq->order('id', 'desc')->find();
        }

        // ===== 投票：进行中 =====
        $voteQuery = Db::name('vote')->whereNull('delete_time')->where('status', 2);
        if ($communityWhere) $voteQuery->where($communityWhere);
        $data['vote'] = $voteQuery->count();
        if ($data['vote'] > 0) {
            $vq = Db::name('vote')->whereNull('delete_time')->where('status', 2);
            if ($communityWhere) $vq->where($communityWhere);
            $data['last_vote'] = $vq->order('id', 'desc')->find();
        }

        // ===== 活动：报名中 =====
        $activityQuery = Db::name('activity')->whereNull('delete_time')->where('status', 2);
        if ($communityWhere) $activityQuery->where($communityWhere);
        $data['activity'] = $activityQuery->count();
        if ($data['activity'] > 0) {
            $aq = Db::name('activity')->whereNull('delete_time')->where('status', 2);
            if ($communityWhere) $aq->where($communityWhere);
            $data['last_activity'] = $aq->order('id', 'desc')->find();
        }

        return $this->success($data);
    }
}
