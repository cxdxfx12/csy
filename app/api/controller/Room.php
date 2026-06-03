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
            ->where('ocr.owner_id', $ownerId)->where('ocr.delete_time', null)->where('r.delete_time', null)
            ->field('r.*, ocr.relation, ocr.is_primary')
            ->select()->toArray();
        return $this->success($rooms);
    }

    public function detail()
    {
        $id = $this->request->param('id', 0);
        $room = Db::name('room')->where('id', $id)->find();
        return $this->success($room);
    }
}
