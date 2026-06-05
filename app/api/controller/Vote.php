<?php
namespace app\api\controller;

use app\api\BaseApi;
use think\facade\Db;

class Vote extends BaseApi
{
    // 投票状态: 1草稿 2进行中 3已结束
    public function lists()
    {
        $owner = Db::name('owner')->where('id', $this->ownerId)->find();
        $communityId = $owner['community_id'] ?? 0;
        
        $where = [
            ['v.community_id', '=', $communityId],
            ['v.status', 'in', [2, 3]],
            ['v.delete_time', 'null', ''],
        ];

        $list = Db::name('vote')->alias('v')
            ->where($where)
            ->order('v.status', 'asc')
            ->order('v.id', 'desc')
            ->select();

        foreach ($list as &$item) {
            $item['option_count'] = Db::name('vote_option')->where('vote_id', $item['id'])->count();
            $item['total_votes'] = (int)Db::name('vote_option')->where('vote_id', $item['id'])->sum('count');
            $item['has_voted'] = Db::name('vote_record')->where([
                'vote_id' => $item['id'],
                'owner_id' => $this->ownerId
            ])->count() > 0;
        }

        return $this->success($list);
    }

    public function detail()
    {
        $id = $this->request->param('id', 0);
        $owner = Db::name('owner')->where('id', $this->ownerId)->find();
        $communityId = $owner['community_id'] ?? 0;

        $where = [
            ['id', '=', $id],
            ['community_id', '=', $communityId],
            ['delete_time', 'null', ''],
        ];

        $vote = Db::name('vote')->where($where)->find();
        if (!$vote) return $this->error('投票不存在');

        $options = Db::name('vote_option')->where('vote_id', $id)
            ->order('sort', 'asc')->select();

        $total = (int)Db::name('vote_option')->where('vote_id', $id)->sum('count');
        foreach ($options as &$opt) {
            $opt['percent'] = $total > 0 ? round($opt['count'] / $total * 100, 1) : 0;
        }

        $hasVoted = Db::name('vote_record')->where([
            'vote_id' => $id,
            'owner_id' => $this->ownerId
        ])->count() > 0;

        $vote['options'] = $options;
        $vote['total_votes'] = $total;
        $vote['has_voted'] = $hasVoted;

        return $this->success($vote);
    }

    public function vote()
    {
        $voteId = $this->request->post('vote_id', 0);
        $optionIds = $this->request->post('option_ids/a', []);

        if (empty($optionIds)) return $this->error('请至少选择一个选项');

        $owner = Db::name('owner')->where('id', $this->ownerId)->find();
        $communityId = $owner['community_id'] ?? 0;

        $where = [
            ['id', '=', $voteId],
            ['community_id', '=', $communityId],
            ['delete_time', 'null', ''],
        ];

        $vote = Db::name('vote')->where($where)->find();
        if (!$vote) return $this->error('投票不存在');
        if ($vote['status'] != 2) return $this->error('投票已结束或未开始');

        // 检查是否已投票
        $exists = Db::name('vote_record')->where([
            'vote_id' => $voteId,
            'owner_id' => $this->ownerId
        ])->count();
        if ($exists) return $this->error('您已经投过票了');

        // 校验 optionIds 是否属于该投票
        $validOptions = Db::name('vote_option')->where([
            ['id', 'in', $optionIds],
            ['vote_id', '=', $voteId]
        ])->select();
        if (count($validOptions) !== count($optionIds)) return $this->error('部分选项不存在');

        // 记录投票（支持多选）
        $records = [];
        foreach ($optionIds as $optId) {
            $records[] = [
                'vote_id'    => $voteId,
                'option_id'  => $optId,
                'owner_id'   => $this->ownerId,
                'create_time' => date('Y-m-d H:i:s')
            ];
        }
        Db::name('vote_record')->insertAll($records);

        // 更新票数
        Db::name('vote_option')->where('id', 'in', $optionIds)->inc('count', 1)->update();

        return $this->success([], '投票成功');
    }
}
