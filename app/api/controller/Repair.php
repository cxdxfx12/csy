<?php
namespace app\api\controller;

use app\api\BaseApi;
use think\facade\Db;

class Repair extends BaseApi
{
    public function add()
    {
        $raw = $this->request->post();
        $owner = Db::name('owner')->where('id', $this->ownerId)->find();
        // 只保留数据库存在的字段
        $data = [
            'order_no'    => build_order_no('DSR'),
            'title'       => $raw['title'] ?? '',
            'content'     => $raw['content'] ?? '',
            'owner_id'    => $this->ownerId,
            'community_id'=> $owner['community_id'] ?? 0,
            'reporter'    => $owner['realname'] ?? '',
            'reporter_phone' => $owner['phone'] ?? '',
            'source'      => 1,
            'status'      => 1,
            'create_time' => date('Y-m-d H:i:s'),
        ];
        // 处理房号 → room_id
        if (!empty($raw['room_no'])) {
            $room = Db::name('room')->where('room_number', $raw['room_no'])->where('community_id', $owner['community_id'] ?? 0)->find();
            if ($room) $data['room_id'] = $room['id'];
        }
        Db::name('repair_order')->insert($data);
        return $this->success([], '报修提交成功');
    }

    public function lists()
    {
        [$page, $limit] = $this->getPage();
        $where = [['owner_id', '=', $this->ownerId], ['delete_time', 'null', '']];
        $total = Db::name('repair_order')->where($where)->count();
        $list = Db::name('repair_order')->where($where)->page($page, $limit)->order('id', 'desc')->select();
        return $this->success(['list' => $list, 'total' => $total]);
    }

    public function detail()
    {
        $id = $this->request->param('id', 0);
        $info = Db::name('repair_order')->alias('ro')
            ->leftJoin('repair_worker rw', 'rw.id = ro.assignee_id')
            ->field('ro.*, rw.name as worker_name, rw.phone as worker_phone')
            ->where('ro.id', $id)->find();
        return $this->success($info);
    }

    public function evaluate()
    {
        $id = $this->request->post('id', 0);
        $rating = $this->request->post('rating', 5);
        $comment = $this->request->post('comment', '');
        Db::name('repair_order')->where('id', $id)->update([
            'rating' => $rating,
            'comment' => $comment,
            'status' => 5,
        ]);
        return $this->success([], '评价成功');
    }
}
