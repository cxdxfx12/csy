<?php
namespace app\admin\controller;

use app\admin\BaseAdmin;
use think\facade\Db;

class PushDevice extends BaseAdmin
{
    public function lists()
    {
        [$page, $limit] = $this->getPage();
        $cid = $this->getFilteredCommunityId();
        $keyword = $this->request->param('keyword', '');
        $platform = $this->request->param('platform', '');
        $status = $this->request->param('status', '');

        $cntQuery = Db::name('push_device')->whereNull('delete_time');
        if ($cid === -1) $cntQuery->where('community_id', 'in', $this->request->boundCommunityIds);
        elseif ($cid > 0) $cntQuery->where('community_id', '=', intval($cid));
        if ($keyword) $cntQuery->where('device_token|user_type|user_id', 'like', "%{$keyword}%");
        if ($platform) $cntQuery->where('platform', '=', $platform);
        if ($status !== '') $cntQuery->where('status', '=', intval($status));
        $total = $cntQuery->count();

        $listQuery = Db::name('push_device')->whereNull('delete_time');
        if ($cid === -1) $listQuery->where('community_id', 'in', $this->request->boundCommunityIds);
        elseif ($cid > 0) $listQuery->where('community_id', '=', intval($cid));
        if ($keyword) $listQuery->where('device_token|user_type|user_id', 'like', "%{$keyword}%");
        if ($platform) $listQuery->where('platform', '=', $platform);
        if ($status !== '') $listQuery->where('status', '=', intval($status));
        $list = $listQuery->page($page, $limit)->order('id', 'desc')->select();
        return $this->table($list, $total);
    }

    public function add()
    {
        $data = $this->request->post();
        $data['create_time'] = date('Y-m-d H:i:s');
        Db::name('push_device')->insert($data);
        return $this->success([], '添加成功');
    }

    public function edit()
    {
        $data = $this->request->post();
        Db::name('push_device')->where('id', $data['id'])->update($data);
        return $this->success([], '修改成功');
    }

    public function delete()
    {
        $id = $this->request->post('id', 0);
        Db::name('push_device')->where('id', $id)->update(['delete_time' => date('Y-m-d H:i:s')]);
        return $this->success([], '删除成功');
    }
}
