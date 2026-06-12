<?php
namespace app\admin\controller;

use app\admin\BaseAdmin;
use think\facade\Db;

class Vote extends BaseAdmin
{
    public function lists()
    {
        [$page, $limit] = $this->getPage();
        $where = [['v.delete_time', 'null', '']];
        $keyword = $this->request->param('keyword', '');
        if ($keyword) $where[] = ['v.title', 'like', "%{$keyword}%"];
        $cid = $this->getFilteredCommunityId();
        if ($cid === -1) $where[] = ['v.community_id', 'in', $this->request->boundCommunityIds];
        elseif ($cid > 0) $where[] = ['v.community_id', '=', $cid];
        $status = $this->request->param('status', 0);
        if ($status) $where[] = ['v.status', '=', $status];

        $total = Db::name('vote')->alias('v')->where($where)->count();
        $list = Db::name('vote')->alias('v')
            ->leftJoin('community c', 'c.id = v.community_id')
            ->field('v.*, c.name as community_name')
            ->where($where)
            ->page($page, $limit)->order('v.id', 'desc')->select();

        foreach ($list as &$item) {
            $item['option_count'] = Db::name('vote_option')->where('vote_id', $item['id'])->count();
            $item['total_votes'] = (int)Db::name('vote_option')->where('vote_id', $item['id'])->sum('count');
        }

        return $this->table($list, $total);
    }

    public function add()
    {
        $data = $this->request->post();
        $options = $data['options'] ?? [];
        unset($data['options']);

        // 至少需要2个有效选项
        $data['type'] = $data['type'] ?? 1;
        $options = array_values(array_filter($options, function($t) { return !empty(trim($t)); }));
        if (count($options) < 2) {
            return $this->error('至少需要2个有效选项');
        }

        $this->validateCommunityAccess($data['community_id'] ?? 0);
        $data['create_time'] = date('Y-m-d H:i:s');
        $data['status'] = $data['status'] ?? 1;
        $voteId = Db::name('vote')->insertGetId($data);

        $insertOptions = [];
        foreach ($options as $i => $title) {
            $insertOptions[] = [
                'vote_id' => $voteId,
                'title' => trim($title),
                'sort' => $i + 1,
                'count' => 0,
            ];
        }
        Db::name('vote_option')->insertAll($insertOptions);

        return $this->success(['id' => $voteId], '添加成功');
    }

    public function edit()
    {
        $data = $this->request->post();
        $id = $data['id'] ?? 0;
        $options = $data['options'] ?? [];
        unset($data['options']);

        // 至少需要2个有效选项
        $data['type'] = $data['type'] ?? 1;
        $options = array_values(array_filter($options, function($t) { return !empty(trim($t)); }));
        if (count($options) < 2) {
            return $this->error('至少需要2个有效选项');
        }

        $this->validateCommunityAccess($data['community_id'] ?? 0);
        Db::name('vote')->where('id', $id)->update($data);

        Db::name('vote_option')->where('vote_id', $id)->delete();
        $insertOptions = [];
        foreach ($options as $i => $title) {
            $insertOptions[] = [
                'vote_id' => $id,
                'title' => trim($title),
                'sort' => $i + 1,
                'count' => 0,
            ];
        }
        Db::name('vote_option')->insertAll($insertOptions);

        return $this->success([], '修改成功');
    }

    public function delete()
    {
        $id = $this->request->post('id', 0);
        $record = Db::name('vote')->where('id', $id)->find();
        if ($record) $this->validateCommunityAccess($record['community_id'] ?? 0);
        Db::name('vote')->where('id', $id)->update(['delete_time' => date('Y-m-d H:i:s')]);
        return $this->success([], '删除成功');
    }

    public function detail()
    {
        $id = $this->request->param('id', 0);
        $vote = Db::name('vote')->alias('v')
            ->leftJoin('community c', 'c.id = v.community_id')
            ->field('v.*, c.name as community_name')
            ->where('v.id', $id)->find();
        if (!$vote) return $this->error('投票不存在');

        $options = Db::name('vote_option')->where('vote_id', $id)->order('sort', 'asc')->select();
        $vote['options'] = $options;
        $vote['total_votes'] = array_sum(array_column($options->toArray(), 'count'));

        return $this->success($vote);
    }

    public function publish()
    {
        $id = $this->request->post('id', 0);
        $vote = Db::name('vote')->where('id', $id)->find();
        if (!$vote) return $this->error('投票不存在');
        $this->validateCommunityAccess($vote['community_id'] ?? 0);
        if ($vote['status'] != 1) return $this->error('只有草稿状态才能发布');
        Db::name('vote')->where('id', $id)->update(['status' => 2]);
        return $this->success([], '发布成功');
    }

    public function close()
    {
        $id = $this->request->post('id', 0);
        $vote = Db::name('vote')->where('id', $id)->find();
        if (!$vote) return $this->error('投票不存在');
        $this->validateCommunityAccess($vote['community_id'] ?? 0);
        if (!in_array($vote['status'], [1, 2])) return $this->error('当前状态不能结束投票');
        Db::name('vote')->where('id', $id)->update(['status' => 3, 'end_time' => date('Y-m-d H:i:s')]);
        return $this->success([], '投票已结束');
    }

    public function result()
    {
        $id = $this->request->param('id', 0);
        $vote = Db::name('vote')->alias('v')
            ->leftJoin('community c', 'c.id = v.community_id')
            ->field('v.*, c.name as community_name')
            ->where('v.id', $id)->find();
        if (!$vote) return $this->error('投票不存在');

        $options = Db::name('vote_option')->where('vote_id', $id)->order('sort', 'asc')->select();
        $total = (int)Db::name('vote_option')->where('vote_id', $id)->sum('count');
        foreach ($options as &$opt) {
            $opt['percent'] = $total > 0 ? round($opt['count'] / $total * 100, 1) : 0;
        }
        $vote['options'] = $options;
        $vote['total_votes'] = $total;

        // Recent voters
        $recentVotes = Db::name('vote_record')->alias('vr')
            ->leftJoin('owner o', 'o.id = vr.owner_id')
            ->leftJoin('vote_option vo', 'vo.id = vr.option_id')
            ->field('vr.*, o.realname as owner_name, o.phone, vo.title as option_title')
            ->where('vr.vote_id', $id)
            ->order('vr.id', 'desc')->limit(20)->select();
        $vote['recent_votes'] = $recentVotes;

        return $this->success($vote);
    }
}
