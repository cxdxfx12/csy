<?php
namespace app\manager\controller;

use app\manager\BaseManager;
use think\facade\Db;

/**
 * 经理端 - 投票管理
 * 所有操作限定在当前管理的小区范围内
 */
class ManagerVote extends BaseManager
{
    /** 投票列表 */
    public function lists()
    {
        [$page, $limit] = $this->getPageParams();
        $cid = $this->getCommunityId();
        $keyword = $this->request->param('keyword', '');
        $status  = $this->request->param('status', 0);

        $query = Db::name('vote')->alias('v')
            ->whereNull('v.delete_time')
            ->where('v.community_id', $cid);
        if ($keyword) $query->where('v.title', 'like', "%{$keyword}%");
        if ($status)  $query->where('v.status', intval($status));

        $total = $query->count();
        $list  = $query->leftJoin('community c', 'c.id = v.community_id')
            ->field('v.*, c.name as community_name')
            ->page($page, $limit)->order('v.id', 'desc')->select();

        foreach ($list as &$item) {
            $item['option_count'] = Db::name('vote_option')->where('vote_id', $item['id'])->count();
            $item['total_votes']  = (int)Db::name('vote_option')->where('vote_id', $item['id'])->sum('count');
        }

        return $this->success(['list' => $list, 'total' => $total]);
    }

    /** 新建投票 */
    public function add()
    {
        $data    = $this->request->post();
        $options = $data['options'] ?? [];
        unset($data['options']);

        // 至少需要2个有效选项
        $data['type'] = $data['type'] ?? 1;
        $options = array_values(array_filter($options, function($t) { return !empty(trim($t)); }));
        if (count($options) < 2) {
            return $this->error('至少需要2个有效选项');
        }

        $data['community_id'] = $this->getCommunityId();
        $data['create_time']  = date('Y-m-d H:i:s');
        $data['status']       = $data['status'] ?? 1;

        $voteId = Db::name('vote')->insertGetId($data);

        $insertOptions = [];
        foreach ($options as $i => $title) {
            $insertOptions[] = [
                'vote_id' => $voteId,
                'title'   => trim($title),
                'sort'    => $i + 1,
                'count'   => 0,
            ];
        }
        Db::name('vote_option')->insertAll($insertOptions);

        return $this->success(['id' => $voteId], '创建成功');
    }

    /** 编辑投票 */
    public function edit()
    {
        $data    = $this->request->post();
        $id      = $data['id'] ?? 0;
        $options = $data['options'] ?? [];
        unset($data['options']);

        // 至少需要2个有效选项
        $data['type'] = $data['type'] ?? 1;
        $options = array_values(array_filter($options, function($t) { return !empty(trim($t)); }));
        if (count($options) < 2) {
            return $this->error('至少需要2个有效选项');
        }

        // 确保不能修改他人小区的投票
        $vote = Db::name('vote')->where('id', $id)->where('community_id', $this->getCommunityId())->find();
        if (!$vote) return $this->error('投票不存在');

        Db::name('vote')->where('id', $id)->update($data);

        Db::name('vote_option')->where('vote_id', $id)->delete();
        $insertOptions = [];
        foreach ($options as $i => $title) {
            $insertOptions[] = [
                'vote_id' => $id,
                'title'   => trim($title),
                'sort'    => $i + 1,
                'count'   => 0,
            ];
        }
        Db::name('vote_option')->insertAll($insertOptions);

        return $this->success([], '修改成功');
    }

    /** 删除投票 */
    public function delete()
    {
        $id = $this->request->post('id', 0);
        $vote = Db::name('vote')->where('id', $id)->where('community_id', $this->getCommunityId())->find();
        if (!$vote) return $this->error('投票不存在');
        Db::name('vote')->where('id', $id)->update(['delete_time' => date('Y-m-d H:i:s')]);
        return $this->success([], '删除成功');
    }

    /** 投票详情 */
    public function detail()
    {
        $id   = $this->request->param('id', 0);
        $vote = Db::name('vote')->alias('v')
            ->leftJoin('community c', 'c.id = v.community_id')
            ->field('v.*, c.name as community_name')
            ->where('v.id', $id)->where('v.community_id', $this->getCommunityId())
            ->find();
        if (!$vote) return $this->error('投票不存在');

        $options = Db::name('vote_option')->where('vote_id', $id)->order('sort', 'asc')->select();
        $vote['options']      = $options;
        $vote['total_votes']  = array_sum(array_column($options, 'count'));

        return $this->success($vote);
    }

    /** 发布投票（1草稿 → 2进行中） */
    public function publish()
    {
        $id   = $this->request->post('id', 0);
        $vote = Db::name('vote')->where('id', $id)->where('community_id', $this->getCommunityId())->find();
        if (!$vote) return $this->error('投票不存在');
        if ($vote['status'] != 1) return $this->error('只有草稿状态才能发布');
        Db::name('vote')->where('id', $id)->update(['status' => 2]);
        return $this->success([], '发布成功');
    }

    /** 结束投票（→3已结束） */
    public function close()
    {
        $id   = $this->request->post('id', 0);
        $vote = Db::name('vote')->where('id', $id)->where('community_id', $this->getCommunityId())->find();
        if (!$vote) return $this->error('投票不存在');
        if (!in_array($vote['status'], [1, 2])) return $this->error('当前状态不能结束投票');
        Db::name('vote')->where('id', $id)->update(['status' => 3, 'end_time' => date('Y-m-d H:i:s')]);
        return $this->success([], '投票已结束');
    }

    /** 投票结果 */
    public function result()
    {
        $id   = $this->request->param('id', 0);
        $vote = Db::name('vote')->alias('v')
            ->leftJoin('community c', 'c.id = v.community_id')
            ->field('v.*, c.name as community_name')
            ->where('v.id', $id)->where('v.community_id', $this->getCommunityId())
            ->find();
        if (!$vote) return $this->error('投票不存在');

        $options = Db::name('vote_option')->where('vote_id', $id)->order('sort', 'asc')->select();
        $total   = (int)Db::name('vote_option')->where('vote_id', $id)->sum('count');
        foreach ($options as &$opt) {
            $opt['percent'] = $total > 0 ? round($opt['count'] / $total * 100, 1) : 0;
        }
        $vote['options']     = $options;
        $vote['total_votes'] = $total;

        return $this->success($vote);
    }

    private function getPageParams()
    {
        return [intval($this->request->param('page', 1)), intval($this->request->param('limit', 15))];
    }
}
