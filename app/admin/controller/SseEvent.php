<?php
namespace app\admin\controller;

use app\admin\BaseAdmin;
use think\facade\Db;

class SseEvent extends BaseAdmin
{
    public function lists()
    {
        [$page, $limit] = $this->getPage();
        $where = [['delete_time', 'null', '']];
        $keyword = $this->request->param('keyword', '');
        if ($keyword) $where[] = ['title|content|event_type', 'like', "%{$keyword}%"];
        $eventType = $this->request->param('event_type', '');
        if ($eventType) $where[] = ['event_type', '=', $eventType];
        $isRead = $this->request->param('is_read', '');
        if ($isRead !== '') $where[] = ['is_read', '=', intval($isRead)];
        $startDate = $this->request->param('start_date', '');
        $endDate = $this->request->param('end_date', '');
        if ($startDate) $where[] = ['create_time', '>=', $startDate . ' 00:00:00'];
        if ($endDate) $where[] = ['create_time', '<=', $endDate . ' 23:59:59'];
        $communityId = $this->request->param('community_id', 0);
        if ($communityId) $where[] = ['community_id', '=', $communityId];
        $total = Db::name('sse_event')->where($where)->count();
        $list = Db::name('sse_event')->where($where)->page($page, $limit)->order('id', 'desc')->select();
        return $this->table($list, $total);
    }

}
