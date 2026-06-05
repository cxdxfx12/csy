<?php
namespace app\admin\controller;

use app\admin\BaseAdmin;
use think\facade\Db;

class BillDunning extends BaseAdmin
{
    public function lists()
    {
        [$page, $limit] = $this->getPage();
        $where = [['d.delete_time', 'null', '']];
        $keyword = $this->request->param('keyword', '');
        if ($keyword) {
            $where[] = ['r.room_number|b.building_name|o.realname', 'like', "%{$keyword}%"];
        }
        $cid = $this->getFilteredCommunityId();
        if ($cid === -1) $where[] = ['d.community_id', 'in', $this->request->boundCommunityIds];
        elseif ($cid > 0) $where[] = ['d.community_id', '=', $cid];
        $channel = $this->request->param('channel', '');
        if ($channel) $where[] = ['d.channel', '=', $channel];

        $total = Db::name('bill_dunning')->alias('d')
            ->leftJoin('ds_room r', 'r.id = d.room_id')
            ->leftJoin('ds_owner o', 'o.id = d.owner_id')
            ->leftJoin('ds_admin_user a', 'a.id = d.admin_id')
            ->where($where)->count();

        $list = Db::name('bill_dunning')->alias('d')
            ->field('d.*, r.room_number, r.building_name, o.realname as owner_name, o.phone as owner_phone, a.nickname as admin_name')
            ->leftJoin('ds_room r', 'r.id = d.room_id')
            ->leftJoin('ds_owner o', 'o.id = d.owner_id')
            ->leftJoin('ds_admin_user a', 'a.id = d.admin_id')
            ->where($where)->page($page, $limit)->order('d.id', 'desc')->select();

        return $this->table($list, $total);
    }
}
