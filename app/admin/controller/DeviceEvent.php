<?php
namespace app\admin\controller;

use app\admin\BaseAdmin;
use think\facade\Db;

class DeviceEvent extends BaseAdmin
{
    public function lists()
    {
        [$page, $limit] = $this->getPage();
        $cid = $this->getFilteredCommunityId();
        $keyword = $this->request->param('keyword', '');
        $eventType = $this->request->param('event_type', '');
        $deviceId = $this->request->param('device_id', 0);

        $cntQuery = Db::name('device_event')->alias('de')
            ->leftJoin('device d', 'd.id = de.device_id')
            ->whereNull('`de`.`delete_time`');
        if ($cid === -1) $cntQuery->where('`d`.`community_id`', 'in', $this->request->boundCommunityIds);
        elseif ($cid > 0) $cntQuery->where('`d`.`community_id`', '=', intval($cid));
        if ($keyword) $cntQuery->where('`de`.`event_type`|`de`.`content`|`d`.`device_name`|`d`.`device_code`', 'like', "%{$keyword}%");
        if ($eventType !== '') $cntQuery->where('`de`.`event_type`', '=', $eventType);
        if ($deviceId) $cntQuery->where('`de`.`device_id`', '=', intval($deviceId));
        $total = $cntQuery->count();

        $listQuery = Db::name('device_event')->alias('de')
            ->leftJoin('device d', 'd.id = de.device_id')
            ->leftJoin('community c', 'c.id = d.community_id')
            ->field('de.*, d.device_name, d.device_code, d.device_type, c.name as community_name')
            ->whereNull('`de`.`delete_time`');
        if ($cid === -1) $listQuery->where('`d`.`community_id`', 'in', $this->request->boundCommunityIds);
        elseif ($cid > 0) $listQuery->where('`d`.`community_id`', '=', intval($cid));
        if ($keyword) $listQuery->where('`de`.`event_type`|`de`.`content`|`d`.`device_name`|`d`.`device_code`', 'like', "%{$keyword}%");
        if ($eventType !== '') $listQuery->where('`de`.`event_type`', '=', $eventType);
        if ($deviceId) $listQuery->where('`de`.`device_id`', '=', intval($deviceId));
        $list = $listQuery->page($page, $limit)->order('de.id', 'desc')->select();
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
