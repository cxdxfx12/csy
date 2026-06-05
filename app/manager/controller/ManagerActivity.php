<?php
namespace app\manager\controller;

use app\manager\BaseManager;
use think\facade\Db;

/**
 * 经理端 - 社区活动管理 + 报名审核
 * 所有操作限定在当前管理的小区范围内
 * 活动状态: 1=草稿 2=报名中 3=进行中 4=已结束 5=已取消
 * 报名审核状态: 0=待审核 1=已通过 2=已拒绝
 */
class ManagerActivity extends BaseManager
{
    /** 活动列表 */
    public function lists()
    {
        [$page, $limit] = $this->getPageParams();
        $cid     = $this->getCommunityId();
        $keyword = $this->request->param('keyword', '');
        $status  = $this->request->param('status', 0);

        $query = Db::name('activity')->alias('a')
            ->whereNull('a.delete_time')
            ->where('a.community_id', $cid);
        if ($keyword) $query->where('a.title', 'like', "%{$keyword}%");
        if ($status)  $query->where('a.status', intval($status));

        $total = $query->count();
        $list  = $query->leftJoin('community c', 'c.id = a.community_id')
            ->field('a.*, c.name as community_name')
            ->page($page, $limit)->order('a.id', 'desc')->select();

        foreach ($list as &$item) {
            $item['signup_count']       = Db::name('activity_signup')->where('activity_id', $item['id'])->count();
            $item['pending_signup']     = Db::name('activity_signup')->where('activity_id', $item['id'])->where('status', 0)->count();
            $item['approved_signup']    = Db::name('activity_signup')->where('activity_id', $item['id'])->where('status', 1)->count();
        }

        return $this->success(['list' => $list, 'total' => $total]);
    }

    /** 新建活动（直接发布为"报名中"状态） */
    public function add()
    {
        $data = $this->request->post();
        $data['community_id']         = $this->getCommunityId();
        $data['create_time']          = date('Y-m-d H:i:s');
        $data['status']               = $data['status'] ?? 2; // 默认报名中
        $data['current_participants'] = 0;
        $data['max_participants']     = $data['max_participants'] ?? 0;

        $activityId = Db::name('activity')->insertGetId($data);
        return $this->success(['id' => $activityId], '发布成功');
    }

    /** 编辑活动 */
    public function edit()
    {
        $data = $this->request->post();
        $id   = $data['id'] ?? 0;
        $activity = Db::name('activity')->where('id', $id)->where('community_id', $this->getCommunityId())->find();
        if (!$activity) return $this->error('活动不存在');
        Db::name('activity')->where('id', $id)->update($data);
        return $this->success([], '修改成功');
    }

    /** 删除活动 */
    public function delete()
    {
        $id = $this->request->post('id', 0);
        $activity = Db::name('activity')->where('id', $id)->where('community_id', $this->getCommunityId())->find();
        if (!$activity) return $this->error('活动不存在');
        Db::name('activity')->where('id', $id)->update(['delete_time' => date('Y-m-d H:i:s')]);
        return $this->success([], '删除成功');
    }

    /** 活动详情 */
    public function detail()
    {
        $id = $this->request->param('id', 0);
        $activity = Db::name('activity')->alias('a')
            ->leftJoin('community c', 'c.id = a.community_id')
            ->field('a.*, c.name as community_name')
            ->where('a.id', $id)->where('a.community_id', $this->getCommunityId())
            ->find();
        if (!$activity) return $this->error('活动不存在');

        $activity['signup_count']    = Db::name('activity_signup')->where('activity_id', $id)->count();
        $activity['pending_signup']  = Db::name('activity_signup')->where('activity_id', $id)->where('status', 0)->count();
        $activity['approved_signup'] = Db::name('activity_signup')->where('activity_id', $id)->where('status', 1)->count();

        return $this->success($activity);
    }

    /** 发布（1→2 报名中） */
    public function publish()
    {
        $id = $this->request->post('id', 0);
        $activity = Db::name('activity')->where('id', $id)->where('community_id', $this->getCommunityId())->find();
        if (!$activity) return $this->error('活动不存在');
        if ($activity['status'] != 1) return $this->error('只有草稿状态才能发布');
        Db::name('activity')->where('id', $id)->update(['status' => 2]);
        return $this->success([], '已发布，开始接受报名');
    }

    /** 开始（1或2→3 进行中） */
    public function start()
    {
        $id = $this->request->post('id', 0);
        $activity = Db::name('activity')->where('id', $id)->where('community_id', $this->getCommunityId())->find();
        if (!$activity) return $this->error('活动不存在');
        if (!in_array($activity['status'], [1, 2])) return $this->error('当前状态无法开始');
        Db::name('activity')->where('id', $id)->update(['status' => 3]);
        return $this->success([], '活动已开始');
    }

    /** 结束（2或3→4 已结束） */
    public function complete()
    {
        $id = $this->request->post('id', 0);
        $activity = Db::name('activity')->where('id', $id)->where('community_id', $this->getCommunityId())->find();
        if (!$activity) return $this->error('活动不存在');
        if (!in_array($activity['status'], [2, 3])) return $this->error('当前状态无法结束');
        Db::name('activity')->where('id', $id)->update(['status' => 4, 'end_time' => date('Y-m-d H:i:s')]);
        return $this->success([], '活动已结束');
    }

    /** 取消（非4/5→5 已取消） */
    public function cancel()
    {
        $id = $this->request->post('id', 0);
        $activity = Db::name('activity')->where('id', $id)->where('community_id', $this->getCommunityId())->find();
        if (!$activity) return $this->error('活动不存在');
        if (in_array($activity['status'], [4, 5])) return $this->error('活动已结束或已取消');
        Db::name('activity')->where('id', $id)->update(['status' => 5]);
        return $this->success([], '活动已取消');
    }

    /** 报名列表 */
    public function signups()
    {
        [$page, $limit] = $this->getPageParams();
        $activityId = $this->request->param('activity_id', 0);
        $status     = $this->request->param('status', -1); // -1=全部 0=待审核 1=已通过 2=已拒绝

        // 验证活动属于当前经理管理的小区，防止越权查看
        $activity = Db::name('activity')->where('id', $activityId)
            ->where('community_id', $this->getCommunityId())->find();
        if (!$activity) return $this->error('活动不存在或无权操作');

        $query = Db::name('activity_signup')->alias('s')
            ->leftJoin('owner o', 'o.id = s.owner_id')
            ->where('s.activity_id', $activityId);
        if ($status >= 0) $query->where('s.status', intval($status));

        $total = $query->count();
        $list  = $query->field('s.*, o.realname as owner_name, o.phone as owner_phone')
            ->page($page, $limit)->order('s.id', 'desc')->select();

        return $this->success(['list' => $list, 'total' => $total]);
    }

    /** 审核通过报名 */
    public function approveSignup()
    {
        $id = $this->request->post('id', 0);
        $signup = Db::name('activity_signup')->where('id', $id)->find();
        if (!$signup) return $this->error('报名记录不存在');

        // 验证活动属于当前经理管理的小区
        $activity = Db::name('activity')->where('id', $signup['activity_id'])
            ->where('community_id', $this->getCommunityId())->find();
        if (!$activity) return $this->error('无权操作');

        Db::name('activity_signup')->where('id', $id)->update(['status' => 1]);
        return $this->success([], '审核通过');
    }

    /** 拒绝报名 */
    public function rejectSignup()
    {
        $id = $this->request->post('id', 0);
        $signup = Db::name('activity_signup')->where('id', $id)->find();
        if (!$signup) return $this->error('报名记录不存在');

        $activity = Db::name('activity')->where('id', $signup['activity_id'])
            ->where('community_id', $this->getCommunityId())->find();
        if (!$activity) return $this->error('无权操作');

        Db::name('activity_signup')->where('id', $id)->update(['status' => 2]);
        return $this->success([], '已拒绝');
    }

    /** 取消报名 */
    public function cancelSignup()
    {
        $id = $this->request->post('id', 0);
        $signup = Db::name('activity_signup')->where('id', $id)->find();
        if (!$signup) return $this->error('报名记录不存在');

        $activity = Db::name('activity')->where('id', $signup['activity_id'])
            ->where('community_id', $this->getCommunityId())->find();
        if (!$activity) return $this->error('无权操作');

        Db::name('activity_signup')->where('id', $id)->delete();
        Db::name('activity')->where('id', $signup['activity_id'])->dec('current_participants')->update();
        return $this->success([], '已取消报名');
    }

    /** 确保 activity_signup 表有 status 字段（运行时自动添加） */
    public function ensureSignupStatusColumn()
    {
        try {
            $pdo = new \PDO('mysql:host='.env('database.hostname','127.0.0.1').';dbname='.env('database.database','dasheng').';charset=utf8mb4', env('database.username','root'), env('database.password',''));
            $pdo->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);
            // 检查 status 字段是否存在
            $stmt = $pdo->query("SHOW COLUMNS FROM ds_activity_signup LIKE 'status'");
            if (!$stmt->fetch()) {
                // 字段不存在则添加
                $pdo->exec("ALTER TABLE ds_activity_signup ADD COLUMN status TINYINT(1) NOT NULL DEFAULT 0 COMMENT '审核状态:0待审核 1已通过 2已拒绝' AFTER remark");
            }
        } catch (\Exception $e) {
            return $this->success([], 'init_ok');
        }
        return $this->success([], 'OK');
    }

    private function getPageParams()
    {
        return [intval($this->request->param('page', 1)), intval($this->request->param('limit', 15))];
    }
}
