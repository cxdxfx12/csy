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

        $data = [
            'bill'    => 0,  // 待缴账单数
            'repair'  => 0,  // 处理中报修数
            'notice'  => 0,  // 近7天新公告数
            'last_bill'   => null,
            'last_repair' => null,
            'last_notice' => null,
        ];

        // ===== 账单：待缴 =====
        $data['bill'] = Db::name('bill')
            ->where('owner_id', $ownerId)
            ->whereIn('status', [1, 2])
            ->where('delete_time', null)
            ->count();

        if ($data['bill'] > 0) {
            $lastBill = Db::name('bill')
                ->where('owner_id', $ownerId)
                ->whereIn('status', [1, 2])
                ->where('delete_time', null)
                ->order('id', 'desc')->find();
            $data['last_bill'] = $lastBill;
        }

        // ===== 报修：处理中 =====
        $data['repair'] = Db::name('repair_order')
            ->where('owner_id', $ownerId)
            ->whereIn('status', [1, 2, 3])
            ->where('delete_time', null)
            ->count();

        if ($data['repair'] > 0) {
            $lastRepair = Db::name('repair_order')
                ->where('owner_id', $ownerId)
                ->whereIn('status', [1, 2, 3])
                ->where('delete_time', null)
                ->order('id', 'desc')->find();
            $data['last_repair'] = $lastRepair;
        }

        // ===== 公告：近7天新公告 =====
        $since = date('Y-m-d H:i:s', strtotime('-7 days'));
        $data['notice'] = Db::name('notice')
            ->where(function ($query) use ($owner) {
                $query->where('community_id', 'in', [0, $owner['community_id']]);
            })
            ->where('status', 2)
            ->where('create_time', '>=', $since)
            ->count();

        if ($data['notice'] > 0) {
            $lastNotice = Db::name('notice')
                ->where(function ($query) use ($owner) {
                    $query->where('community_id', 'in', [0, $owner['community_id']]);
                })
                ->where('status', 2)
                ->where('create_time', '>=', $since)
                ->order('id', 'desc')->find();
            $data['last_notice'] = $lastNotice;
        }

        return $this->success($data);
    }
}
