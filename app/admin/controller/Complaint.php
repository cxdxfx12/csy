<?php
namespace app\admin\controller;

use app\admin\BaseAdmin;
use think\facade\Db;

class Complaint extends BaseAdmin
{
    public function lists()
    {
        [$page, $limit] = $this->getPage();
        $where = [['c.delete_time', 'null', '']];
        $communityId = $this->request->param('community_id', 0);
        if ($communityId) {
            $where[] = ['c.community_id', '=', $communityId];
        } else {
            $filter = $this->getCommunityFilter('c.community_id');
            if (!empty($filter)) $where = array_merge($where, $filter);
        }
        $status = $this->request->param('status', '');
        if ($status !== '') $where[] = ['c.status', '=', $status];
        $type = $this->request->param('type', 0);
        if ($type) $where[] = ['c.type', '=', $type];
        $keyword = $this->request->param('keyword', '');
        if ($keyword) {
            $where[] = ['c.title|c.content|c.complaint_name', 'like', "%$keyword%"];
        }
        $total = Db::name('complaint')->alias('c')->where($where)->count();
        $list = Db::name('complaint')->alias('c')
            ->leftJoin('owner o', 'o.id = c.owner_id')
            ->leftJoin('room r', 'r.id = c.room_id')
            ->leftJoin('community com', 'com.id = c.community_id')
            ->field('c.*, o.realname as owner_name, o.phone as owner_phone, r.room_number, r.building_name, com.name as community_name')
            ->where($where)->page($page, $limit)->order('c.id', 'desc')->select();
        return $this->table($list, $total);
    }

    public function handle()
    {
        $id = $this->request->post('id', 0);
        $record = Db::name('complaint')->where('id', $id)->find();
        if ($record) $this->validateCommunityAccess($record['community_id'] ?? 0);
        $handleContent = $this->request->post('handle_content', '');
        $status = $this->request->post('status', 3);

        Db::name('complaint')->where('id', $id)->update([
            'handler_id'    => get_admin_id(),
            'handler_name'  => get_admin_info()['nickname'] ?? '',
            'handle_time'   => date('Y-m-d H:i:s'),
            'handle_content'=> $handleContent,
            'status'        => $status,
        ]);
        return $this->success([], '处理成功');
    }

    public function delete()
    {
        $id = $this->request->post('id', 0);
        $record = Db::name('complaint')->where('id', $id)->find();
        if ($record) $this->validateCommunityAccess($record['community_id'] ?? 0);
        Db::name('complaint')->where('id', $id)->update(['delete_time' => date('Y-m-d H:i:s')]);
        return $this->success([], '删除成功');
    }

    public function detail()
    {
        $id = $this->request->param('id', 0);
        $info = Db::name('complaint')->alias('c')
            ->leftJoin('owner o', 'o.id = c.owner_id')
            ->leftJoin('room r', 'r.id = c.room_id')
            ->field('c.*, o.realname as owner_name, o.phone as owner_phone, r.room_number, r.building_name')
            ->where('c.id', $id)->find();
        return $this->success($info);
    }
}
