<?php
namespace app\api\controller;

use app\api\BaseApi;
use think\facade\Db;

class Room extends BaseApi
{
    public function lists()
    {
        $ownerId = $this->ownerId;
        $rooms = Db::name('owner_room')->alias('ocr')
            ->leftJoin('room r', 'r.id = ocr.room_id')
            ->leftJoin('community c', 'c.id = r.community_id')
            ->where('ocr.owner_id', $ownerId)->where('ocr.delete_time', null)->where('r.delete_time', null)
            ->field('r.*, c.name as community_name, r.room_number as room_no, r.check_in_date as move_in_date, ocr.relation, ocr.is_primary')
            ->select();
        return $this->success($rooms);
    }

    public function detail()
    {
        $id = $this->request->param('id', 0);
        $room = Db::name('room')->alias('r')
            ->leftJoin('community c', 'c.id = r.community_id')
            ->where('r.id', $id)
            ->field('r.*, c.name as community_name, r.room_number as room_no, r.check_in_date as move_in_date')
            ->find();
        return $this->success($room);
    }
}
