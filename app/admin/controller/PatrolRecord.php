<?php
namespace app\admin\controller;

use app\admin\BaseAdmin;
use think\facade\Db;

class PatrolRecord extends BaseAdmin
{
    public function lists()
    {
        [$page, $limit] = $this->getPage();
        $where = [];
        $communityId = $this->request->param('community_id', 0);
        if ($communityId) $where[] = ['community_id', '=', $communityId];
        $routeId = $this->request->param('route_id', 0);
        if ($routeId) $where[] = ['route_id', '=', $routeId];
        $startDate = $this->request->param('start_date', '');
        $endDate = $this->request->param('end_date', '');
        if ($startDate) $where[] = ['check_time', '>=', $startDate . ' 00:00:00'];
        if ($endDate) $where[] = ['check_time', '<=', $endDate . ' 23:59:59'];

        $total = Db::name('patrol_record')->where($where)->count();
        $list = Db::name('patrol_record')->where($where)
            ->page($page, $limit)->order('id', 'desc')->select();
        return $this->table($list, $total);
    }
}
