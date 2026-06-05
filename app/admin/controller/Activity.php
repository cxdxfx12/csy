<?php
namespace app\admin\controller;

use app\admin\BaseAdmin;
use think\facade\Db;

class Activity extends BaseAdmin
{
    public function lists()
    {
        [$page, $limit] = $this->getPage();
        $where = [['a.delete_time', 'null', '']];
        $keyword = $this->request->param('keyword', '');
        if ($keyword) $where[] = ['a.title', 'like', "%{$keyword}%"];
        $cid = $this->getFilteredCommunityId();
        if ($cid === -1) $where[] = ['a.community_id', 'in', $this->request->boundCommunityIds];
        elseif ($cid > 0) $where[] = ['a.community_id', '=', $cid];
        $status = $this->request->param('status', 0);
        if ($status) $where[] = ['a.status', '=', $status];

        $total = Db::name('activity')->alias('a')->where($where)->count();
        $list = Db::name('activity')->alias('a')
            ->leftJoin('community c', 'c.id = a.community_id')
            ->field('a.*, c.name as community_name')
            ->where($where)
            ->page($page, $limit)->order('a.id', 'desc')->select();

        foreach ($list as &$item) {
            $item['signup_count'] = Db::name('activity_signup')->where('activity_id', $item['id'])->count();
        }

        return $this->table($list, $total);
    }

    public function add()
    {
        $data = $this->request->post();
        $data['create_time'] = date('Y-m-d H:i:s');
        $data['status'] = $data['status'] ?? 2; // 默认"报名中"，业主端立即可见
        $data['current_participants'] = 0;
        $data['max_participants'] = $data['max_participants'] ?? 0;
        $activityId = Db::name('activity')->insertGetId($data);
        return $this->success(['id' => $activityId], '添加成功');
    }

    public function edit()
    {
        $data = $this->request->post();
        Db::name('activity')->where('id', $data['id'])->update($data);
        return $this->success([], '修改成功');
    }

    public function delete()
    {
        $id = $this->request->post('id', 0);
        Db::name('activity')->where('id', $id)->update(['delete_time' => date('Y-m-d H:i:s')]);
        return $this->success([], '删除成功');
    }

    public function detail()
    {
        $id = $this->request->param('id', 0);
        $activity = Db::name('activity')->alias('a')
            ->leftJoin('community c', 'c.id = a.community_id')
            ->field('a.*, c.name as community_name')
            ->where('a.id', $id)->find();
        if (!$activity) return $this->error('活动不存在');

        $activity['signup_count'] = Db::name('activity_signup')->where('activity_id', $id)->count();
        return $this->success($activity);
    }

    public function publish()
    {
        $id = $this->request->post('id', 0);
        $activity = Db::name('activity')->where('id', $id)->find();
        if (!$activity) return $this->error('活动不存在');
        if ($activity['status'] != 1) return $this->error('只有草稿状态才能发布');
        Db::name('activity')->where('id', $id)->update(['status' => 2]);
        return $this->success([], '已发布，开始接受报名');
    }

    public function start()
    {
        $id = $this->request->post('id', 0);
        $activity = Db::name('activity')->where('id', $id)->find();
        if (!$activity) return $this->error('活动不存在');
        if (!in_array($activity['status'], [1, 2])) return $this->error('当前状态不能开始活动');
        Db::name('activity')->where('id', $id)->update(['status' => 3]);
        return $this->success([], '活动已开始');
    }

    public function complete()
    {
        $id = $this->request->post('id', 0);
        $activity = Db::name('activity')->where('id', $id)->find();
        if (!$activity) return $this->error('活动不存在');
        if (!in_array($activity['status'], [2, 3])) return $this->error('当前状态不能结束活动');
        Db::name('activity')->where('id', $id)->update(['status' => 4, 'end_time' => date('Y-m-d H:i:s')]);
        return $this->success([], '活动已结束');
    }

    public function cancel()
    {
        $id = $this->request->post('id', 0);
        $activity = Db::name('activity')->where('id', $id)->find();
        if (!$activity) return $this->error('活动不存在');
        if (in_array($activity['status'], [4, 5])) return $this->error('活动已结束或已取消');
        Db::name('activity')->where('id', $id)->update(['status' => 5]);
        return $this->success([], '活动已取消');
    }

    public function signups()
    {
        [$page, $limit] = $this->getPage();
        $activityId = $this->request->param('activity_id', 0);
        $total = Db::name('activity_signup')->alias('s')
            ->leftJoin('owner o', 'o.id = s.owner_id')
            ->where('s.activity_id', $activityId)->count();
        $list = Db::name('activity_signup')->alias('s')
            ->leftJoin('owner o', 'o.id = s.owner_id')
            ->field('s.*, o.realname as owner_name, o.phone as owner_phone')
            ->where('s.activity_id', $activityId)
            ->page($page, $limit)->order('s.id', 'desc')->select();

        return $this->table($list, $total);
    }

    public function signupList()
    {
        [$page, $limit] = $this->getPage();
        $where = [];
        $keyword = $this->request->param('keyword', '');
        if ($keyword) $where[] = ['s.name|s.phone|a.title', 'like', "%{$keyword}%"];
        $activityId = $this->request->param('activity_id', 0);
        if ($activityId) $where[] = ['s.activity_id', '=', $activityId];

        $total = Db::name('activity_signup')->alias('s')
            ->leftJoin('activity a', 'a.id = s.activity_id')
            ->where($where)->count();

        $list = Db::name('activity_signup')->alias('s')
            ->leftJoin('activity a', 'a.id = s.activity_id')
            ->field('s.*, a.title as activity_title')
            ->where($where)->page($page, $limit)->order('s.id', 'desc')->select();

        return $this->table($list, $total);
    }

    public function cancelSignup()
    {
        $id = $this->request->post('id', 0);
        $signup = Db::name('activity_signup')->where('id', $id)->find();
        if (!$signup) return $this->error('报名记录不存在');
        Db::name('activity_signup')->where('id', $id)->delete();
        Db::name('activity')->where('id', $signup['activity_id'])->dec('current_participants')->update();
        return $this->success([], '已取消报名');
    }
}
