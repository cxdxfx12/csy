<?php
namespace app\admin\controller;

use app\admin\BaseAdmin;
use think\facade\Db;

class DeviceEvent extends BaseAdmin
{
    public function lists()
    {
        [$page, $limit] = $this->getPage();
        $where = [['de.delete_time', 'null', '']];
        $keyword = $this->request->param('keyword', '');
        if ($keyword) $where[] = ['de.event_type|de.content|d.device_name|d.device_code', 'like', "%{$keyword}%"];
        $eventType = $this->request->param('event_type', '');
        if ($eventType !== '') $where[] = ['de.event_type', '=', $eventType];
        $deviceId = $this->request->param('device_id', 0);
        if ($deviceId) $where[] = ['de.device_id', '=', intval($deviceId)];
        $communityId = $this->request->param('community_id', 0);
        if ($communityId) $where[] = ['d.community_id', '=', intval($communityId)];
        $total = Db::name('device_event')->alias('de')->where($where)->count();
        $list = Db::name('device_event')->alias('de')
            ->leftJoin('device d', 'd.id = de.device_id')
            ->leftJoin('community c', 'c.id = d.community_id')
            ->field('de.*, d.device_name, d.device_code, d.device_type, c.name as community_name')
            ->where($where)->page($page, $limit)->order('de.id', 'desc')->select();
        return $this->table($list, $total);
    }

    public function add()
    {
        $data = $this->request->post();
        $data['create_time'] = date('Y-m-d H:i:s');
        Db::name('device_event')->insert($data);
        return $this->success([], '添加成功');
    }

    public function edit()
    {
        $data = $this->request->post();
        Db::name('device_event')->where('id', $data['id'])->update($data);
        return $this->success([], '修改成功');
    }

    public function delete()
    {
        $id = $this->request->post('id', 0);
        Db::name('device_event')->where('id', $id)->update(['delete_time' => date('Y-m-d H:i:s')]);
        return $this->success([], '删除成功');
    }
}
