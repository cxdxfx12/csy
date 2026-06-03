<?php
namespace app\admin\controller;

use app\admin\BaseAdmin;
use think\facade\Db;

class PatrolRoute extends BaseAdmin
{
    public function lists()
    {
        [$page, $limit] = $this->getPage();
        $where = [['pr.delete_time', '=', null]];
        $communityId = $this->request->param('community_id', 0);
        if ($communityId) $where[] = ['pr.community_id', '=', $communityId];
        $total = Db::name('patrol_route')->alias('pr')->where($where)->count();
        $list = Db::name('patrol_route')->alias('pr')
            ->leftJoin('community c', 'c.id = pr.community_id')
            ->field('pr.*, c.name as community_name')
            ->where($where)->page($page, $limit)->order('pr.id', 'desc')->select();
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
