<?php
namespace app\api\controller;

use app\api\BaseApi;
use think\facade\Db;

class Repair extends BaseApi
{
    public function add()
    {
        $data = $this->request->post();
        $owner = Db::name('owner')->where('id', $this->ownerId)->find();
        $data['order_no'] = build_order_no('DSR');
        $data['owner_id'] = $this->ownerId;
        $data['community_id'] = $owner['community_id'] ?? 0;
        $data['reporter'] = $owner['realname'] ?? '';
        $data['reporter_phone'] = $owner['phone'] ?? '';
        $data['create_time'] = date('Y-m-d H:i:s');
        Db::name('repair_order')->insert($data);
        return $this->success([], '报修提交成功');
    }

    public function lists()
    {
        [$page, $limit] = $this->getPage();
        $where = [['owner_id', '=', $this->ownerId], ['delete_time', '=', null]];
        $total = Db::name('repair_order')->where($where)->count();
        $list = Db::name('repair_order')->where($where)->page($page, $limit)->order('id', 'desc')->select()->toArray();
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
