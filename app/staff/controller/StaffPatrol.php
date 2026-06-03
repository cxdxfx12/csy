<?php
namespace app\staff\controller;

use app\staff\BaseStaff;
use think\facade\Db;

class StaffPatrol extends BaseStaff
{
    public function routes()
    {
        $communityId = $this->request->param('community_id', 0);
        $list = Db::name('patrol_route')->where('status', 1)->where('delete_time', null)
            ->when($communityId, function ($query) use ($communityId) {
                $query->where('community_id', $communityId);
            })->select()->toArray();
        return $this->success($list);
    }

    public function check()
    {
        $data = $this->request->post();
        $data['staff_id'] = $this->staffId;
        $data['staff_name'] = $this->staffInfo['nickname'] ?? '';
        $data['check_time'] = date('Y-m-d H:i:s');
        $data['create_time'] = date('Y-m-d H:i:s');
        Db::name('patrol_record')->insert($data);
        return $this->success([], '打卡成功');
    }

    public function history()
    {
        [$page, $limit] = $this->getPage();
        $where = [];
        $routeId = $this->request->param('route_id', 0);
        if ($routeId) $where[] = ['route_id', '=', $routeId];
        $total = Db::name('patrol_record')->where($where)->count();
        $list = Db::name('patrol_record')->where($where)
            ->page($page, $limit)->order('id', 'desc')->select()->toArray();
        return $this->success(['list' => $list, 'total' => $total]);
    }
}
