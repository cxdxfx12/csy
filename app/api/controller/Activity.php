<?php
namespace app\api\controller;

use app\api\BaseApi;
use think\facade\Db;

class Activity extends BaseApi
{
    // 活动状态: 1草稿 2报名中 3进行中 4已结束 5已取消
    public function lists()
    {
        $owner = Db::name('owner')->where('id', $this->ownerId)->find();
        $communityId = $owner['community_id'] ?? 0;

        $where = [
            ['a.community_id', '=', $communityId],
            ['a.status', 'in', [2, 3]],
            ['a.delete_time', 'null', ''],
        ];

        $list = Db::name('activity')->alias('a')
            ->field('a.*')
            ->where($where)
            ->order('a.status', 'asc')
            ->order('a.id', 'desc')
            ->select();

        foreach ($list as &$item) {
            $item['signup_count'] = (int)Db::name('activity_signup')->where('activity_id', $item['id'])->count();
            // 检查当前业主的报名记录及审核状态
            $signup = Db::name('activity_signup')->where([
                'activity_id' => $item['id'],
                'owner_id' => $this->ownerId
            ])->find();
            $item['has_signed'] = $signup ? true : false;
            $item['signup_status'] = $signup ? (int)$signup['status'] : -1;
        }

        return $this->success($list);
    }

    public function detail()
    {
        $id = $this->request->param('id', 0);
        $owner = Db::name('owner')->where('id', $this->ownerId)->find();
        $communityId = $owner['community_id'] ?? 0;

        $where = [
            ['a.id', '=', $id],
            ['a.community_id', '=', $communityId],
            ['a.delete_time', 'null', ''],
        ];

        $activity = Db::name('activity')->alias('a')
            ->field('a.*')
            ->where($where)
            ->find();

        if (!$activity) return $this->error('活动不存在');
        if (!in_array($activity['status'], [2, 3])) return $this->error('活动已结束或已取消');

        $activity['signup_count'] = (int)Db::name('activity_signup')->where('activity_id', $id)->count();
        $signup = Db::name('activity_signup')->where([
            'activity_id' => $id,
            'owner_id' => $this->ownerId
        ])->find();
        $activity['has_signed'] = $signup ? true : false;
        $activity['signup_status'] = $signup ? (int)$signup['status'] : -1;

        return $this->success($activity);
    }

    // 业主报名
    public function signup()
    {
        $activityId = $this->request->post('activity_id', 0);
        $remark = $this->request->post('remark', '');

        $owner = Db::name('owner')->where('id', $this->ownerId)->find();
        $communityId = $owner['community_id'] ?? 0;

        $where = [
            ['id', '=', $activityId],
            ['community_id', '=', $communityId],
            ['delete_time', 'null', ''],
        ];

        $activity = Db::name('activity')->where($where)->find();
        if (!$activity) return $this->error('活动不存在');
        if (!in_array($activity['status'], [2, 3])) return $this->error('当前活动未开放报名');

        // 检查人数上限
        if ($activity['max_participants'] > 0) {
            $currentCount = Db::name('activity_signup')->where('activity_id', $activityId)->count();
            if ($currentCount >= $activity['max_participants']) {
                return $this->error('报名人数已满');
            }
        }

        // 检查是否已报名
        $exists = Db::name('activity_signup')->where([
            'activity_id' => $activityId,
            'owner_id' => $this->ownerId
        ])->count();
        if ($exists) return $this->error('您已报名过该活动');

        Db::name('activity_signup')->insert([
            'activity_id' => $activityId,
            'owner_id' => $this->ownerId,
            'name' => $owner['realname'] ?? $owner['name'] ?? '',
            'phone' => $owner['phone'] ?? '',
            'remark' => $remark,
            'create_time' => date('Y-m-d H:i:s')
        ]);

        // 更新参与人数
        Db::name('activity')->where('id', $activityId)->inc('current_participants', 1)->update();

        return $this->success([], '报名成功');
    }

    // 取消报名
    public function cancelSignup()
    {
        $activityId = $this->request->post('activity_id', 0);

        $signup = Db::name('activity_signup')->where([
            'activity_id' => $activityId,
            'owner_id' => $this->ownerId
        ])->find();
        if (!$signup) return $this->error('未找到报名记录');

        Db::name('activity_signup')->where('id', $signup['id'])->delete();
        Db::name('activity')->where('id', $activityId)->dec('current_participants', 1)->update();

        return $this->success([], '已取消报名');
    }

    // 查看我的报名列表
    public function mySignups()
    {
        $list = Db::name('activity_signup')->alias('s')
            ->leftJoin('activity a', 'a.id = s.activity_id')
            ->field('s.*, a.title as activity_title, a.location, a.start_time, a.end_time, a.status as activity_status')
            ->where('s.owner_id', $this->ownerId)
            ->order('s.id', 'desc')
            ->select();

        return $this->success($list);
    }
}
