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

        // 校验数据归属：只能查看自己的报修单
        if (!$info || (int)$info['owner_id'] !== (int)$this->ownerId) {
            return $this->error('报修单不存在');
        }

        return $this->success($info);
    }

    public function evaluate()
    {
        $id = $this->request->post('id', 0);
        $rating = $this->request->post('rating', 5);
        $comment = $this->request->post('comment', '');

        // 查询工单并校验归属和状态
        $order = Db::name('repair_order')->where('id', $id)->find();
        if (!$order || (int)$order['owner_id'] !== (int)$this->ownerId) {
            return $this->error('报修单不存在');
        }
        if ((int)$order['status'] !== 4) {
            return $this->error('当前状态不可评价，请等待维修完成');
        }

        Db::name('repair_order')->where('id', $id)->update([
            'rating' => $rating,
            'comment' => $comment,
            'status' => 5,
        ]);
        return $this->success([], '评价成功');
    }
}
