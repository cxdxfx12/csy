<?php
namespace app\api\controller;

use app\api\BaseApi;
use think\facade\Db;

class Badge extends BaseApi
{
    /**
     * 业主角标数量 + 最新消息
     * GET /api/badge/counts
     */
    public function counts()
    {
        $ownerId = $this->ownerId;
        $owner = $this->ownerInfo;

        if (!$owner) {
            return $this->error('用户信息异常');
        }
        $communityId = $owner['community_id'] ?? 0;

        $data = [
            'bill'       => 0,  // 待缴账单数
            'repair'     => 0,  // 处理中报修数
            'notice'     => 0,  // 近7天新公告数
            'complaint'  => 0,  // 处理中投诉
            'vote'       => 0,  // 未投的进行中投票
            'activity'   => 0,  // 可报名的活动
            'last_bill'       => null,
            'last_repair'     => null,
            'last_notice'     => null,
            'last_complaint'  => null,
            'last_vote'       => null,
            'last_activity'   => null,
        ];

        // ===== 账单：待缴 =====
        $data['bill'] = Db::name('bill')
            ->where('owner_id', $ownerId)
            ->whereIn('status', [1, 2])
            ->where('delete_time', null)
            ->count();
        if ($data['bill'] > 0) {
            $data['last_bill'] = Db::name('bill')
                ->where('owner_id', $ownerId)->whereIn('status', [1, 2])
                ->where('delete_time', null)->order('id', 'desc')->find();
        }

        // ===== 报修：处理中 =====
        $data['repair'] = Db::name('repair_order')
            ->where('owner_id', $ownerId)->whereIn('status', [1, 2, 3])
            ->where('delete_time', null)->count();
        if ($data['repair'] > 0) {
            $data['last_repair'] = Db::name('repair_order')
                ->where('owner_id', $ownerId)->whereIn('status', [1, 2, 3])
                ->where('delete_time', null)->order('id', 'desc')->find();
        }

        // ===== 公告：近7天新公告 =====
        $since = date('Y-m-d H:i:s', strtotime('-7 days'));
        $data['notice'] = Db::name('notice')
            ->where(function ($q) use ($communityId) {
                $q->where('community_id', 'in', [0, $communityId]);
            })->where('status', 2)->where('create_time', '>=', $since)
            ->whereNull('delete_time')->count();
        if ($data['notice'] > 0) {
            $data['last_notice'] = Db::name('notice')
                ->where(function ($q) use ($communityId) {
                    $q->where('community_id', 'in', [0, $communityId]);
                })->where('status', 2)->where('create_time', '>=', $since)
                ->whereNull('delete_time')->order('id', 'desc')->find();
        }

        // ===== 投诉：处理中 =====
        $data['complaint'] = Db::name('complaint')
            ->where('owner_id', $ownerId)->whereIn('status', [1, 2])
            ->whereNull('delete_time')->count();
        if ($data['complaint'] > 0) {
            $data['last_complaint'] = Db::name('complaint')
                ->where('owner_id', $ownerId)->whereIn('status', [1, 2])
                ->whereNull('delete_time')->order('id', 'desc')->find();
        }

        // ===== 投票：进行中且未投票 =====
        if ($communityId) {
            $votedIds = Db::name('vote_record')->where('owner_id', $ownerId)->column('vote_id');
            $data['vote'] = Db::name('vote')
                ->where('community_id', $communityId)->where('status', 2)
                ->whereNull('delete_time')
                ->where(function ($q) use ($votedIds) {
                    if (!empty($votedIds)) $q->where('id', 'not in', $votedIds);
                })->count();
            if ($data['vote'] > 0) {
                $data['last_vote'] = Db::name('vote')
                    ->where('community_id', $communityId)->where('status', 2)
                    ->whereNull('delete_time')
                    ->where(function ($q) use ($votedIds) {
                        if (!empty($votedIds)) $q->where('id', 'not in', $votedIds);
                    })->order('id', 'desc')->find();
            }

            // ===== 活动：报名中/进行中 =====
            $data['activity'] = Db::name('activity')
                ->where('community_id', $communityId)->whereIn('status', [2, 3])
                ->whereNull('delete_time')->count();
            if ($data['activity'] > 0) {
                $data['last_activity'] = Db::name('activity')
                    ->where('community_id', $communityId)->whereIn('status', [2, 3])
                    ->whereNull('delete_time')->order('id', 'desc')->find();
            }
        }

        return $this->success($data);
    }
}
