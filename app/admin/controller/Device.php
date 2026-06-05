<?php
namespace app\admin\controller;

use app\admin\BaseAdmin;
use think\facade\Db;

class Device extends BaseAdmin
{
    public function listAll()
    {
        $cid = $this->getFilteredCommunityId();
        $query = Db::name('device')->whereNull('delete_time');
        if ($cid === -1) $query->where('community_id', 'in', $this->request->boundCommunityIds);
        elseif ($cid > 0) $query->where('community_id', '=', intval($cid));
        $list = $query->field('id, device_name, device_code, device_type')->order('id', 'desc')->select();
        return $this->success($list);
    }

    public function lists()
    {
        [$page, $limit] = $this->getPage();
        $cid = $this->getFilteredCommunityId();
        $keyword = $this->request->param('keyword', '');
        $deviceType = $this->request->param('device_type', '');
        $status = $this->request->param('status', '');

        $cntQuery = Db::name('device')->alias('d')->whereNull('`d`.`delete_time`');
        if ($cid === -1) $cntQuery->where('`d`.`community_id`', 'in', $this->request->boundCommunityIds);
        elseif ($cid > 0) $cntQuery->where('`d`.`community_id`', '=', intval($cid));
        if ($keyword) $cntQuery->where('`d`.`device_name`|`d`.`device_code`|`d`.`location`|`d`.`remark`', 'like', "%{$keyword}%");
        if ($deviceType !== '') $cntQuery->where('`d`.`device_type`', '=', $deviceType);
        if ($status !== '') $cntQuery->where('`d`.`status`', '=', intval($status));
        $total = $cntQuery->count();

        $listQuery = Db::name('device')->alias('d')
            ->leftJoin('community c', 'c.id = d.community_id')
            ->field('d.*, c.name as community_name')
            ->whereNull('`d`.`delete_time`');
        if ($cid === -1) $listQuery->where('`d`.`community_id`', 'in', $this->request->boundCommunityIds);
        elseif ($cid > 0) $listQuery->where('`d`.`community_id`', '=', intval($cid));
        if ($keyword) $listQuery->where('`d`.`device_name`|`d`.`device_code`|`d`.`location`|`d`.`remark`', 'like', "%{$keyword}%");
        if ($deviceType !== '') $listQuery->where('`d`.`device_type`', '=', $deviceType);
        if ($status !== '') $listQuery->where('`d`.`status`', '=', intval($status));
        $list = $listQuery->page($page, $limit)->order('d.id', 'desc')->select();
        return $this->table($list, $total);
    }

    public function add()
    {
        $data = $this->request->post();
        $data['create_time'] = date('Y-m-d H:i:s');
        $data['update_time'] = date('Y-m-d H:i:s');
        Db::name('device')->insert($data);
        return $this->success([], '添加成功');
    }

    public function edit()
    {
        $data = $this->request->post();
        $data['update_time'] = date('Y-m-d H:i:s');
        Db::name('device')->where('id', $data['id'])->update($data);
        return $this->success([], '修改成功');
    }

    public function delete()
    {
        $id = $this->request->post('id', 0);
        Db::name('device')->where('id', $id)->update(['delete_time' => date('Y-m-d H:i:s')]);
        return $this->success([], '删除成功');
    }
}
