<?php
namespace app\api\controller;

use app\api\BaseApi;
use think\facade\Db;

class Index extends BaseApi
{
    public function banner()
    {
        return $this->success([
            ['image' => '/admin/images/banner1.jpg', 'title' => '欢迎使用大圣物业'],
            ['image' => '/admin/images/banner2.jpg', 'title' => '智慧社区服务'],
        ]);
    }

    public function notice()
    {
        $ownerId = $this->ownerId;
        $owner = Db::name('owner')->where('id', $ownerId)->find();
        $list = Db::name('notice')
            ->where('community_id', 'in', [0, $owner['community_id']])
            ->where('status', 2)->order('top_status desc, id desc')->limit(5)->select();
        return $this->success($list);
    }

    public function myInfo()
    {
        $ownerId = $this->ownerId;
        $owner = Db::name('owner')->where('id', $ownerId)->find();
        $rooms = Db::name('owner_room')->alias('ocr')
            ->leftJoin('room r', 'r.id = ocr.room_id')
            ->where('ocr.owner_id', $ownerId)->where('ocr.delete_time', null)
            ->field('r.id, r.room_number, r.building_name, r.area')
            ->select();

        $unpaidBills = Db::name('bill')->where('owner_id', $ownerId)
            ->whereIn('status', [1, 2])->where('delete_time', null)->sum('total_amount - paid_amount');
        $pendingRepairs = Db::name('repair_order')->where('owner_id', $ownerId)
            ->whereIn('status', [1, 2, 3])->where('delete_time', null)->count();

        // 补充小区名称
        $communityName = '';
        if (!empty($owner['community_id'])) {
            $communityName = Db::name('community')->where('id', $owner['community_id'])->value('name') ?? '';
        }

        return $this->success([
            'userInfo' => $owner,
            'rooms' => $rooms,
            'unpaid_amount' => $unpaidBills,
            'pending_repairs' => $pendingRepairs,
            'community_name' => $communityName,
        ]);
    }
}
