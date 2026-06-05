<?php
namespace app\staff\controller;

use app\staff\BaseStaff;
use think\facade\Db;

class StaffComplaint extends BaseStaff
{
    /**
     * 投诉列表 - 只显示本小区的投诉
     */
    public function lists()
    {
        [$page, $limit] = [intval($this->request->param('page', 1)), intval($this->request->param('limit', 15))];
        $status = $this->request->param('status', '');

        // 获取员工所属小区ID
        $communityId = $this->staffInfo['community_id'] ?? 0;

        $query = Db::name('complaint')->alias('c')
            ->leftJoin('owner o', 'o.id = c.owner_id')
            ->leftJoin('room r', 'r.id = c.room_id')
            ->field('c.*, o.realname as owner_name, o.phone as owner_phone, r.room_number, r.building_name');

        // 小区隔离：员工只能看自己小区的投诉
        if ($communityId > 0) {
            $query->where('c.community_id', $communityId);
        }

        $query->whereNull('c.delete_time');

        if ($status !== '') {
            $query->where('c.status', intval($status));
        }

        $total = $query->count();
        $list = $query->order('c.id', 'desc')->page($page, $limit)->select();

        return $this->success(['list' => $list, 'total' => $total]);
    }

    /**
     * 投诉详情
     */
    public function detail()
    {
        $id = $this->request->param('id', 0);
        $communityId = $this->staffInfo['community_id'] ?? 0;

        $query = Db::name('complaint')->alias('c')
            ->leftJoin('owner o', 'o.id = c.owner_id')
            ->leftJoin('room r', 'r.id = c.room_id')
            ->field('c.*, o.realname as owner_name, o.phone as owner_phone, r.room_number, r.building_name')
            ->where('c.id', $id)
            ->whereNull('c.delete_time');

        if ($communityId > 0) {
            $query->where('c.community_id', $communityId);
        }

        $info = $query->find();

        if (!$info) {
            return $this->error('投诉不存在或无权查看');
        }

        return $this->success($info);
    }

    /**
     * 处理投诉 - 所有物业人员都有权限
     */
    public function handle()
    {
        $id = $this->request->post('id', 0);
        $handleContent = $this->request->post('handle_content', '');
        $status = $this->request->post('status', 3); // 默认已处理

        $communityId = $this->staffInfo['community_id'] ?? 0;

        $query = Db::name('complaint')->where('id', $id)->whereNull('delete_time');
        if ($communityId > 0) {
            $query->where('community_id', $communityId);
        }
        $complaint = $query->find();

        if (!$complaint) {
            return $this->error('投诉不存在或无权处理');
        }

        Db::name('complaint')->where('id', $id)->update([
            'handler_id'    => $this->staffId,
            'handler_name'  => $this->staffInfo['realname'] ?? '物业人员',
            'handle_time'   => date('Y-m-d H:i:s'),
            'handle_content'=> $handleContent,
            'status'        => $status,
        ]);

        return $this->success([], '处理成功');
    }
}
