<?php
namespace app\admin\controller;

use app\admin\BaseAdmin;
use think\facade\Db;

class PatrolRoute extends BaseAdmin
{
    public function lists()
    {
        [$page, $limit] = $this->getPage();
        $cid = $this->getFilteredCommunityId();

        $cntQuery = Db::name('patrol_route')->whereNull('delete_time');
        if ($cid === -1) $cntQuery->where('community_id', 'in', $this->request->boundCommunityIds);
        elseif ($cid > 0) $cntQuery->where('community_id', '=', intval($cid));
        $total = $cntQuery->count();

        $listQuery = Db::name('patrol_route')->alias('pr')
            ->leftJoin('community c', 'c.id = pr.community_id')
            ->field('pr.*, c.name as community_name')
            ->whereNull('`pr`.`delete_time`');
        if ($cid === -1) $listQuery->where('`pr`.`community_id`', 'in', $this->request->boundCommunityIds);
        elseif ($cid > 0) $listQuery->where('`pr`.`community_id`', '=', intval($cid));
        $list = $listQuery->page($page, $limit)->order('pr.id', 'desc')->select();
        return $this->table($list, $total);
    }

    public function add()
    {
        $data = $this->request->post();
        if (isset($data['points']) && is_array($data['points'])) {
            $data['points'] = json_encode($data['points'], JSON_UNESCAPED_UNICODE);
            $data['total_points'] = count($data['points']);
        }
        $data['create_time'] = date('Y-m-d H:i:s');
        Db::name('patrol_route')->insert($data);
        return $this->success([], '添加成功');
    }

    public function edit()
    {
        $data = $this->request->post();
        if (isset($data['points']) && is_array($data['points'])) {
            $data['points'] = json_encode($data['points'], JSON_UNESCAPED_UNICODE);
            $data['total_points'] = count($data['points']);
        }
        Db::name('patrol_route')->where('id', $data['id'])->update($data);
        return $this->success([], '修改成功');
    }

    public function delete()
    {
        $id = $this->request->post('id', 0);
        Db::name('patrol_route')->where('id', $id)->update(['delete_time' => date('Y-m-d H:i:s')]);
        return $this->success([], '删除成功');
    }
}
