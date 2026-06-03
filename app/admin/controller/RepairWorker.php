<?php
namespace app\admin\controller;

use app\admin\BaseAdmin;
use think\facade\Db;

class RepairWorker extends BaseAdmin
{
    public function lists()
    {
        [$page, $limit] = $this->getPage();
        $where = [['rw.delete_time', 'null', '']];
        $communityId = $this->request->param('community_id', 0);
        if ($communityId) $where[] = ['rw.community_id', '=', $communityId];
        $keyword = $this->request->param('keyword', '');
        if ($keyword) $where[] = ['rw.name|rw.phone', 'like', "%{$keyword}%"];
        $total = Db::name('repair_worker')->alias('rw')->where($where)->count();
        $list = Db::name('repair_worker')->alias('rw')
            ->leftJoin('community com', 'com.id = rw.community_id')
            ->field('rw.*, com.name as community_name')
            ->where($where)->page($page, $limit)->order('rw.id', 'desc')->select();
        foreach ($list as &$row) {
            $row['specialty'] = $row['type'] ?? '';
        }
        return $this->table($list, $total);
    }

    public function add()
    {
        $data = $this->request->post();
        if (isset($data['specialty'])) {
            $data['type'] = $data['specialty'];
            unset($data['specialty']);
        }
        $data['create_time'] = date('Y-m-d H:i:s');
        Db::name('repair_worker')->insert($data);
        return $this->success([], '添加成功');
    }

    public function edit()
    {
        $data = $this->request->post();
        if (isset($data['specialty'])) {
            $data['type'] = $data['specialty'];
            unset($data['specialty']);
        }
        Db::name('repair_worker')->where('id', $data['id'])->update($data);
        return $this->success([], '修改成功');
    }

    public function delete()
    {
        $id = $this->request->post('id', 0);
        Db::name('repair_worker')->where('id', $id)->update(['delete_time' => date('Y-m-d H:i:s')]);
        return $this->success([], '删除成功');
    }
}
