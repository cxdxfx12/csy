<?php
namespace app\admin\controller;

use app\admin\BaseAdmin;
use think\facade\Db;

class PatrolRecord extends BaseAdmin
{
    public function lists()
    {
        [$page, $limit] = $this->getPage();
        $cid = $this->getFilteredCommunityId();
        $routeId = $this->request->param('route_id', 0);
        $startDate = $this->request->param('start_date', '');
        $endDate = $this->request->param('end_date', '');

        $cntQuery = Db::name('patrol_record');
        if ($cid === -1) $cntQuery->where('community_id', 'in', $this->request->boundCommunityIds);
        elseif ($cid > 0) $cntQuery->where('community_id', '=', intval($cid));
        if ($routeId) $cntQuery->where('route_id', '=', $routeId);
        if ($startDate) $cntQuery->where('check_time', '>=', $startDate . ' 00:00:00');
        if ($endDate) $cntQuery->where('check_time', '<=', $endDate . ' 23:59:59');
        $total = $cntQuery->count();

        $listQuery = Db::name('patrol_record')->alias('pr')
            ->leftJoin('community com', 'com.id = pr.community_id')
            ->field('pr.*, com.name as community_name');
        if ($cid === -1) $listQuery->where('`pr`.`community_id`', 'in', $this->request->boundCommunityIds);
        elseif ($cid > 0) $listQuery->where('`pr`.`community_id`', '=', intval($cid));
        if ($routeId) $listQuery->where('route_id', '=', $routeId);
        if ($startDate) $listQuery->where('check_time', '>=', $startDate . ' 00:00:00');
        if ($endDate) $listQuery->where('check_time', '<=', $endDate . ' 23:59:59');
        $list = $listQuery->page($page, $limit)->order('pr.id', 'desc')->select();
        return $this->table($list, $total);
    }
}
